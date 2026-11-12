"""
color_detector.py

Stage 3 — Multi-color detection with shade merging.

Takes a lighting-normalized PIL Image and extracts the significant distinct
soil color regions present in it, along with per-region pixel masks, proportions,
and sub-shade distributions.

Key Improvements over ColorThief:
  1. Operates in CIE Lab space with lightness-relaxed CIEDE2000 (kL=3.0) so shadow
     and highlighted variants of the same soil cluster together instead of appearing
     as separate fake colors.
  2. Over-segments with MiniBatchKMeans on a fast downscaled working copy, then
     merges small/shadow patches into their dominant color anchor.
  3. Drops pure noise speckles (<0.5%) before merging, and rolls low-share (<5%)
     shades into nearest parent colors.
  4. Computes true representative color using trimmed median (skipping 10% shadow/glare extremes).
  5. Provides per-region shade breakdown ("dark", "medium", "light", "uniform") and lightness_spread.

Public API:
    detect_color_regions(
        image         : PIL.Image.Image,
        max_colors    : int   = 6,
        min_proportion: float = 0.05,
        roi_fraction  : float = DEFAULT_ROI_FRACTION,
    ) -> list[dict]
"""

import base64
import io
import math
import os
from typing import List, Optional, Tuple

import cv2
import numpy as np
from PIL import Image
from skimage.color import rgb2lab, deltaE_ciede2000
from sklearn.cluster import MiniBatchKMeans

# ── Environment & Tunable Settings ─────────────────────────────────────────────
DEFAULT_ROI_FRACTION = float(os.environ.get("SCAN_ROI_FRACTION", "1.0"))

WORK_SIZE = int(os.environ.get("STAGE3_WORK_SIZE", "320"))     # longest side used for clustering
OVERSEG_K = int(os.environ.get("STAGE3_OVERSEG_K", "12"))      # intentionally more clusters than needed (12)
MAX_COLORS = int(os.environ.get("STAGE3_MAX_COLORS", "12"))    # max distinct color anchors kept (12)
MIN_SPECK = float(os.environ.get("STAGE3_MIN_SPECK", "0.001")) # ignore clusters < 0.1% (pure noise) before initial merge
MERGE_DE = float(os.environ.get("STAGE3_MERGE_DE", "2.0"))      # ΔE2000 to merge near-identical clusters (< 2.0)
MAX_L_GAP = float(os.environ.get("STAGE3_MAX_L_GAP", "28.0"))  # max L* difference allowed inside one color (28.0)
FINAL_MIN = float(os.environ.get("STAGE3_FINAL_MIN", "0.0"))   # 0.0 = do not hide/merge minor colors by a min proportion
TRIM_PCT = int(os.environ.get("STAGE3_TRIM_PCT", "10"))        # ignore darkest/lightest 10% when computing true color

# ── High-Contrast Bright Overlay Colors (Stands out sharply on earthy brown soil) ─
OVERLAY_PALETTE = [
    (255, 64, 64),    # 0: Bright Red
    (40, 120, 255),   # 1: Bright Blue
    (255, 214, 0),    # 2: Bright Yellow
    (0, 200, 120),    # 3: Emerald Green
    (190, 80, 255),   # 4: Purple / Violet
    (255, 140, 0),    # 5: Bright Orange
    (0, 225, 225),    # 6: Cyan / Aqua
    (255, 105, 180),  # 7: Hot Pink
    (138, 43, 226),   # 8: Indigo
    (50, 205, 50),    # 9: Lime Green
    (255, 20, 147),   # 10: Deep Pink
    (30, 144, 255),   # 11: Dodger Blue
]
OVERLAY_COLOR_NAMES = [
    "Red", "Blue", "Yellow", "Green", "Purple", "Orange",
    "Cyan", "Pink", "Indigo", "Lime", "Rose", "Sky"
]
OVERLAY_MAX_SIDE = 640
SMOOTH_KSIZE = int(os.environ.get("STAGE3_SMOOTH_KSIZE", "5"))  # majority filter kernel size (default 5)


def _png_b64(arr: np.ndarray, mode: str) -> str:
    buf = io.BytesIO()
    Image.fromarray(arr, mode=mode).save(buf, format="PNG", optimize=True)
    return base64.b64encode(buf.getvalue()).decode("ascii")


def smooth_label_map(label_map: np.ndarray, n_labels: int, ksize: int = 5) -> np.ndarray:
    """Majority (mode) filter applied at working resolution before upscaling."""
    if ksize <= 1 or n_labels <= 1:
        return label_map
    votes = np.empty((n_labels,) + label_map.shape, np.float32)
    for g in range(n_labels):
        votes[g] = cv2.blur((label_map == g).astype(np.float32), (ksize, ksize))
    return votes.argmax(axis=0).astype(np.int16)


def extract_blobs(mask: np.ndarray, min_area_frac: float = 0.003, max_blobs: int = 10) -> list:
    """ALL meaningful areas of one color, in normalized coordinates. NO size clamping."""
    H, W = mask.shape
    m = cv2.morphologyEx(mask.astype(np.uint8) * 255, cv2.MORPH_CLOSE,
                         cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (5, 5)))
    n, _, stats, _ = cv2.connectedComponentsWithStats(m)
    blobs = []
    for i in range(1, n):
        x, y, w, h, area = [int(v) for v in stats[i]]
        if area < min_area_frac * H * W:
            continue
        blobs.append({"norm_x": round(x / W, 4), "norm_y": round(y / H, 4),
                      "norm_w": round(w / W, 4), "norm_h": round(h / H, 4),
                      "area_share": round(area / (H * W), 4)})
    blobs.sort(key=lambda b: b["area_share"], reverse=True)
    return blobs[:max_blobs]


def render_color_map(full_group: np.ndarray, n_colors: int, alpha: float = 0.5):
    """
    full_group: (H, W) int16 label map for the WHOLE image (0..n_colors-1, or n_colors for rejected).
    Returns (combined_overlay_png_b64, [per_color_mask_png_b64 ...]) at <=640px.
    Per-color mask_png is an RGBA PNG: overlay color where mask is true (alpha 200), transparent elsewhere.
    """
    H, W = full_group.shape
    s = min(1.0, OVERLAY_MAX_SIDE / max(H, W))
    small = cv2.resize(full_group, (max(1, int(W * s)), max(1, int(H * s))),
                       interpolation=cv2.INTER_NEAREST)
    rgba = np.zeros(small.shape + (4,), dtype=np.uint8)
    masks = []
    for gi in range(n_colors):
        m = small == gi
        r, g, b = OVERLAY_PALETTE[gi % len(OVERLAY_PALETTE)]
        rgba[m] = (r, g, b, int(255 * alpha))

        # Per-color mask as RGBA with alpha 200 where true, 0 elsewhere
        color_rgba = np.zeros(small.shape + (4,), dtype=np.uint8)
        color_rgba[m] = (r, g, b, 200)
        masks.append(_png_b64(color_rgba, "RGBA"))

    # If rejected regions exist (index n_colors), draw in neutral gray #969696
    rej_m = small == n_colors
    if np.any(rej_m):
        rgba[rej_m] = (150, 150, 150, int(255 * alpha))

    return _png_b64(rgba, "RGBA"), masks


def detect_border_padding(arr: np.ndarray, max_check: int = 12) -> Tuple[int, int, int, int]:
    """
    Detects if borders (left, top, right, bottom) contain repeated or padded pixels
    introduced by Stage 2 mirror/replicate padding, convolution borders, or camera letterboxing.
    Returns (pad_left, pad_top, pad_right, pad_bottom).
    """
    H, W = arr.shape[:2]
    pad_left = 0
    for c in range(min(max_check, W // 4)):
        diff_next = float(np.mean(np.abs(arr[:, c].astype(float) - arr[:, c + 1].astype(float))))
        col_std = float(np.std(arr[:, c].astype(float)))
        # Exact duplicate column from padding or completely constant border strip
        if np.all(arr[:, c] == arr[:, c + 1]) or diff_next == 0.0 or col_std == 0.0:
            pad_left = c + 1
        else:
            break

    pad_right = 0
    for c in range(min(max_check, W // 4)):
        diff_prev = float(np.mean(np.abs(arr[:, W - 1 - c].astype(float) - arr[:, W - 2 - c].astype(float))))
        col_std = float(np.std(arr[:, W - 1 - c].astype(float)))
        if np.all(arr[:, W - 1 - c] == arr[:, W - 2 - c]) or diff_prev == 0.0 or col_std == 0.0:
            pad_right = c + 1
        else:
            break

    pad_top = 0
    for r in range(min(max_check, H // 4)):
        diff_next = float(np.mean(np.abs(arr[r, :].astype(float) - arr[r + 1, :].astype(float))))
        row_std = float(np.std(arr[r, :].astype(float)))
        if np.all(arr[r, :] == arr[r + 1, :]) or diff_next == 0.0 or row_std == 0.0:
            pad_top = r + 1
        else:
            break

    pad_bottom = 0
    for r in range(min(max_check, H // 4)):
        diff_prev = float(np.mean(np.abs(arr[H - 1 - r, :].astype(float) - arr[H - 2 - r, :].astype(float))))
        row_std = float(np.std(arr[H - 1 - r, :].astype(float)))
        if np.all(arr[H - 1 - r, :] == arr[H - 2 - r, :]) or diff_prev == 0.0 or row_std == 0.0:
            pad_bottom = r + 1
        else:
            break

    return pad_left, pad_top, pad_right, pad_bottom


def _apply_region_crop(image: Image.Image, roi_fraction: float) -> Image.Image:
    """
    Crops the image to a centered sub-rectangle with side length
    `roi_fraction` × original size.

    Parameters:
        image        : PIL Image (RGB).
        roi_fraction : Fraction of each dimension to keep (0 < roi_fraction <= 1.0).

    Returns:
        Cropped PIL Image (RGB).
    """
    if roi_fraction >= 1.0:
        return image

    w, h = image.size
    margin_x = (w * (1.0 - roi_fraction)) / 2.0
    margin_y = (h * (1.0 - roi_fraction)) / 2.0

    left   = int(round(margin_x))
    top    = int(round(margin_y))
    right  = int(round(w - margin_x))
    bottom = int(round(h - margin_y))

    return image.crop((left, top, right, bottom))


def _shade_distance(lab1, lab2) -> float:
    """ΔE2000 with lightness down-weighted (kL=3): dark/light of one soil stay close."""
    return float(deltaE_ciede2000(lab1[None, :], lab2[None, :], kL=3.0)[0])


def _merge_shades(centers, counts) -> dict:
    """Biggest cluster first; each smaller cluster joins the bigger color it matches."""
    total = counts.sum()
    groups = {}
    for c in np.argsort(-counts):
        if counts[c] / total < MIN_SPECK:
            continue
        best, best_d = None, 1e9
        for a in groups:
            if abs(centers[c][0] - centers[a][0]) > MAX_L_GAP:
                continue
            d = _shade_distance(centers[c], centers[a])
            if d < best_d:
                best, best_d = a, d
        if best is not None and best_d <= MERGE_DE:
            groups[best].append(int(c))
        else:
            groups[int(c)] = [int(c)]
    return groups


def locate_color_box(
    img_rgb: np.ndarray,
    color_rgb: tuple,
    max_de: float = 12.0,
    pixel_mask: Optional[np.ndarray] = None,
    proportion: Optional[float] = None,
) -> dict:
    """
    Computes a normalized bounding box strictly proportional to the color's percentage:
      box_w = img_w * sqrt(p)
      box_h = img_h * sqrt(p)
    Centered on the densest area (centroid of largest connected component of pixel mask).
    Shifted within [0.005, 0.995] bounds so it never overflows and is never shrunk.
    """
    H, W = img_rgb.shape[:2]
    total_pixels = float(H * W) if (H > 0 and W > 0) else 1.0

    # 1. Determine percentage p
    if proportion is not None:
        p = float(proportion)
    elif pixel_mask is not None and np.any(pixel_mask):
        p = float(np.sum(pixel_mask > 0)) / total_pixels
    else:
        p = 0.05
    p = max(0.0001, min(1.0, p))

    # 2. Box dimensions proportional to sqrt(p), keeping aspect ratio of the image
    min_b = 0.005
    max_b = 0.995
    max_span = max_b - min_b  # 0.99
    norm_w = min(max_span, math.sqrt(p))
    norm_h = min(max_span, math.sqrt(p))

    # 3. Find center coordinates: densest area (largest connected component centroid)
    cx, cy = float(W) / 2.0, float(H) / 2.0
    try:
        mask_to_analyze = None
        if pixel_mask is not None and np.any(pixel_mask):
            mask_to_analyze = (pixel_mask > 0).astype(np.uint8) * 255
        elif color_rgb is not None:
            lab = rgb2lab(img_rgb.astype(np.float64) / 255.0)
            ref = rgb2lab(np.array([[color_rgb]], dtype=np.float64) / 255.0)[0, 0]
            de = deltaE_ciede2000(lab, ref[None, None, :])
            mask_to_analyze = (de < max_de).astype(np.uint8) * 255

        if mask_to_analyze is not None and np.any(mask_to_analyze):
            num_labels, labels, stats, centroids = cv2.connectedComponentsWithStats(mask_to_analyze)
            if num_labels > 1:
                # label 0 is background; find largest component among labels 1..num_labels-1
                areas = stats[1:, cv2.CC_STAT_AREA]
                largest_idx = 1 + int(np.argmax(areas))
                cx, cy = float(centroids[largest_idx][0]), float(centroids[largest_idx][1])
            else:
                ys, xs = np.where(mask_to_analyze > 0)
                if len(xs) > 0 and len(ys) > 0:
                    cx, cy = float(np.mean(xs)), float(np.mean(ys))
    except Exception:
        pass

    norm_cx = cx / float(W)
    norm_cy = cy / float(H)

    # 4. Center box on (norm_cx, norm_cy)
    norm_x = norm_cx - (norm_w / 2.0)
    norm_y = norm_cy - (norm_h / 2.0)

    # 5. Shift inside bounds [min_b, max_b] without shrinking
    if norm_x < min_b:
        norm_x = min_b
    elif norm_x + norm_w > max_b:
        norm_x = max_b - norm_w

    if norm_y < min_b:
        norm_y = min_b
    elif norm_y + norm_h > max_b:
        norm_y = max_b - norm_h

    bx = int(round(norm_x * W))
    by = int(round(norm_y * H))
    bw = int(round(norm_w * W))
    bh = int(round(norm_h * H))

    return {
        "x": int(bx),
        "y": int(by),
        "width": int(bw),
        "height": int(bh),
        "norm_x": round(float(norm_x), 4),
        "norm_y": round(float(norm_y), 4),
        "norm_w": round(float(norm_w), 4),
        "norm_h": round(float(norm_h), 4),
    }


def detect_color_regions(
    image: Image.Image,
    max_colors: int = MAX_COLORS,
    min_proportion: float = FINAL_MIN,
    roi_fraction: float = DEFAULT_ROI_FRACTION,
) -> List[dict]:
    # Check SCAN_ROI_FRACTION env override
    roi_fraction_env = os.environ.get("SCAN_ROI_FRACTION")
    if roi_fraction_env is not None:
        try:
            roi_fraction = float(roi_fraction_env)
        except ValueError:
            pass

    if roi_fraction < 1.0:
        import sys
        sys.stderr.write(
            f"[Stage 3] SCAN_ROI_FRACTION={roi_fraction:.2f} (< 1.0): analyzing center "
            f"{roi_fraction * 100:.0f}% only. (Ensure SCAN_ROI_FRACTION=1.0 for full image)\n"
        )
        sys.stderr.flush()

    img_rgb = image.convert("RGB")
    roi_img = _apply_region_crop(img_rgb, roi_fraction)
    Wc, Hc = roi_img.size

    # Check for Stage 2 border padding / mirror artifacts on the edges
    roi_raw_arr = np.asarray(roi_img, dtype=np.uint8)
    pad_l, pad_t, pad_r, pad_b = detect_border_padding(roi_raw_arr)
    if pad_l > 0 or pad_t > 0 or pad_r > 0 or pad_b > 0:
        import sys
        sys.stderr.write(
            f"[Stage 3] Trimmed border padding/mirror (L={pad_l}, T={pad_t}, R={pad_r}, B={pad_b}) "
            f"to prevent counting fake pixels in percentages\n"
        )
        sys.stderr.flush()
        crop_box = (pad_l, pad_t, max(pad_l + 1, Wc - pad_r), max(pad_t + 1, Hc - pad_b))
        roi_img = roi_img.crop(crop_box)
        Wc, Hc = roi_img.size
    else:
        pad_l, pad_t = 0, 0

    # 1. Small working copy in Lab
    small = roi_img.copy()
    small.thumbnail((WORK_SIZE, WORK_SIZE), Image.Resampling.BILINEAR)
    small_arr = np.asarray(small, dtype=np.float32)
    sh, sw = small_arr.shape[:2]
    lab = rgb2lab(small_arr / 255.0).reshape(-1, 3)

    # 2. Over-segment on purpose (OVERSEG_K = 12)
    k = min(OVERSEG_K, len(lab))
    km = MiniBatchKMeans(n_clusters=k, n_init=3, random_state=0, batch_size=2048)
    labels = km.fit_predict(lab)
    centers = km.cluster_centers_
    counts = np.bincount(labels, minlength=k).astype(np.float64)
    total = counts.sum()

    # 3. Merge shades/small parts into the bigger colors they match
    groups = _merge_shades(centers, counts)
    share = lambda a: sum(counts[c] for c in groups[a]) / total

    # 4. Small colors: merge only if min_proportion > 0.0 (default 0.0 retains all detected colors)
    if min_proportion > 0.0:
        changed = True
        while changed and len(groups) > 1:
            changed = False
            for a in sorted(list(groups), key=share):
                if share(a) < min_proportion:
                    others = [o for o in groups if o != a]
                    if others:
                        near = min(others, key=lambda o: _shade_distance(centers[a], centers[o]))
                        groups[near].extend(groups[a])
                        del groups[a]
                        changed = True
                        break

    # Cap to max_colors (12) by merging smallest remaining into nearest anchor
    while len(groups) > max_colors:
        smallest_a = min(groups, key=share)
        remaining = [o for o in groups if o != smallest_a]
        near = min(remaining, key=lambda o: _shade_distance(centers[smallest_a], centers[o]))
        groups[near].extend(groups[smallest_a])
        del groups[smallest_a]

    anchors = sorted(groups, key=share, reverse=True)

    # 5. Map EVERY K-means cluster (including any tiny specks) to its nearest anchor
    anchor_labs = [centers[a] for a in anchors]
    cluster_to_group = np.zeros(k, dtype=np.int16)
    for gi, a in enumerate(anchors):
        for c in groups[a]:
            cluster_to_group[c] = gi
    member = {c for a in anchors for c in groups[a]}
    for c in range(k):
        if c not in member:  # tiny/speck/leftover cluster
            d = [_shade_distance(centers[c], al) for al in anchor_labs]
            cluster_to_group[c] = int(np.argmin(d))

    small_group = cluster_to_group[labels].reshape(sh, sw)   # no -1 anywhere
    # Apply majority (mode) filter at working resolution BEFORE upscaling to ROI size
    small_group = smooth_label_map(small_group, len(anchors), ksize=SMOOTH_KSIZE)
    roi_group = cv2.resize(small_group, (Wc, Hc), interpolation=cv2.INTER_NEAREST)

    full_arr = np.asarray(img_rgb, dtype=np.uint8)
    H, W = full_arr.shape[:2]
    roi_x0 = int(round(W * (1.0 - roi_fraction) / 2.0)) + pad_l
    roi_y0 = int(round(H * (1.0 - roi_fraction) / 2.0)) + pad_t
    lab2d = lab.reshape(sh, sw, 3)
    labels2d = labels.reshape(sh, sw)

    full_group = np.zeros((H, W), dtype=np.int16)
    full_group[roi_y0: roi_y0 + Hc, roi_x0: roi_x0 + Wc] = roi_group

    # If border padding was trimmed, fill borders with edge labels so full image is 100% labeled
    if pad_l > 0:
        full_group[:, :pad_l] = full_group[:, pad_l:pad_l+1]
    if pad_r > 0:
        full_group[:, W - pad_r:] = full_group[:, W - pad_r - 1: W - pad_r]
    if pad_t > 0:
        full_group[:pad_t, :] = full_group[pad_t:pad_t+1, :]
    if pad_b > 0:
        full_group[H - pad_b:, :] = full_group[H - pad_b - 1: H - pad_b, :]

    total_full_pixels = H * W
    combined_overlay_b64, per_color_masks_b64 = render_color_map(full_group, len(anchors), alpha=0.5)

    regions = []
    for gi, a in enumerate(anchors):
        full_mask = full_group == gi
        count_gi = int(full_mask.sum())
        proportion = count_gi / float(total_full_pixels)

        # True color = median of mid-tone pixels (skip shadow/glare extremes)
        sm = small_group == gi
        px, Lpx = small_arr[sm], lab2d[sm][:, 0]
        lo, hi = np.percentile(Lpx, [TRIM_PCT, 100 - TRIM_PCT])
        mid = px[(Lpx >= lo) & (Lpx <= hi)]
        if len(mid) == 0:
            mid = px
        rgb = tuple(int(round(v)) for v in np.median(mid, axis=0))

        # Shades inside this color: collapse to at most 3 entries (dark, medium, light) by L* terciles
        total_sm = len(Lpx)
        shades = []
        if total_sm > 0:
            q33, q66 = np.percentile(Lpx, [33.33, 66.67])
            dark_m = Lpx <= q33
            light_m = Lpx > q66
            med_m = (~dark_m) & (~light_m)
            if dark_m.any():
                shades.append({
                    "level": "dark",
                    "rgb": tuple(int(round(v)) for v in px[dark_m].mean(axis=0)),
                    "proportion": round(float(dark_m.sum() / total_sm), 4),
                })
            if med_m.any():
                shades.append({
                    "level": "medium",
                    "rgb": tuple(int(round(v)) for v in px[med_m].mean(axis=0)),
                    "proportion": round(float(med_m.sum() / total_sm), 4),
                })
            if light_m.any():
                shades.append({
                    "level": "light",
                    "rgb": tuple(int(round(v)) for v in px[light_m].mean(axis=0)),
                    "proportion": round(float(light_m.sum() / total_sm), 4),
                })
            if len(shades) == 1:
                shades[0]["level"] = "uniform"

        ordered = sorted(groups[a], key=lambda c: centers[c][0])
        lightness_spread = round(float(Lpx.max() - Lpx.min()), 1) if total_sm > 0 else 0.0

        box_data = locate_color_box(full_arr, rgb, pixel_mask=full_mask, proportion=proportion)

        r, g, b = OVERLAY_PALETTE[gi % len(OVERLAY_PALETTE)]
        overlay_hex = f"#{r:02x}{g:02x}{b:02x}"
        overlay_color_name = OVERLAY_COLOR_NAMES[gi % len(OVERLAY_COLOR_NAMES)]
        blobs = extract_blobs(full_mask)
        mask_png_b64 = per_color_masks_b64[gi] if gi < len(per_color_masks_b64) else ""

        regions.append({
            "rgb": rgb,
            "proportion": round(float(proportion), 4),
            "shades": shades,
            "lightness_spread": lightness_spread,
            "pixel_mask": full_mask,
            "color_index": gi,
            "bounding_box": box_data,
            "box": {"x": box_data["norm_x"], "y": box_data["norm_y"],
                    "w": box_data["norm_w"], "h": box_data["norm_h"]},
            "overlay_color": (r, g, b),
            "overlay_hex": overlay_hex,
            "overlay_color_name": overlay_color_name,
            "blobs": blobs,
            "mask_png": mask_png_b64,
        })

    regions.sort(key=lambda r: r["proportion"], reverse=True)
    if regions:
        regions[0]["color_map_png"] = combined_overlay_b64

    # Assert 100% coverage
    coverage_sum = sum(r["proportion"] for r in regions)
    assert abs(coverage_sum - 1.0) < 0.005, f"Coverage sum {coverage_sum:.4f} != 1.0"
    import sys
    sys.stderr.write(f"[Stage 3] coverage = 100.0% ({len(regions)} colors)\n")
    sys.stderr.flush()

    return regions


def create_segmentation_overlay(
    image: Image.Image,
    regions: List[dict],
    alpha: float = 0.42,
) -> Image.Image:
    """
    Creates a visual segmentation overlay using bright contrasting colors
    (Red, Blue, Yellow, etc.) blended over the soil image so distinct layers,
    pebbles, and clumps stand out clearly against brown soil, while the
    companion legend displays the true soil color next to each swatch.
    """
    base = np.asarray(image.convert("RGB"), dtype=np.uint8).copy()
    overlay = base.copy()

    for gi, r in enumerate(regions):
        mask = r.get("pixel_mask")
        if mask is None or not np.any(mask):
            continue
        c = r.get("overlay_color")
        if not c:
            c = OVERLAY_PALETTE[gi % len(OVERLAY_PALETTE)]
        overlay[mask] = c

    blended = cv2.addWeighted(base, 1.0 - alpha, overlay, alpha, 0)
    return Image.fromarray(blended, mode="RGB")


def crop_region(image: Image.Image, pixel_mask: np.ndarray) -> Image.Image:
    """
    Crops the bounding box of a region for Stage 4 verification.
    Pixels inside the box but NOT in the region (other colors) are replaced with
    pixels randomly sampled from the region itself, so the soil gate and SSIM
    check only see this color's texture and are not confused by neighboring colors.
    """
    arr = np.asarray(image.convert("RGB"), dtype=np.uint8)
    if pixel_mask.shape != arr.shape[:2]:
        pixel_mask = cv2.resize(pixel_mask.astype(np.uint8),
                                (arr.shape[1], arr.shape[0]),
                                interpolation=cv2.INTER_NEAREST).astype(bool)
    rows = np.any(pixel_mask, axis=1)
    cols = np.any(pixel_mask, axis=0)
    if not rows.any() or not cols.any():
        return image.convert("RGB")
    r_min = int(np.argmax(rows))
    r_max = int(len(rows) - 1 - np.argmax(rows[::-1]))
    c_min = int(np.argmax(cols))
    c_max = int(len(cols) - 1 - np.argmax(cols[::-1]))

    patch = arr[r_min:r_max + 1, c_min:c_max + 1].copy()
    m = pixel_mask[r_min:r_max + 1, c_min:c_max + 1]
    outside = ~m
    if outside.any() and m.any():
        region_px = patch[m]
        rng = np.random.default_rng(0)  # fixed seed = repeatable results
        patch[outside] = region_px[rng.integers(0, len(region_px), size=int(outside.sum()))]

    out = Image.fromarray(patch, mode="RGB")
    # Tiny crops can break the gate model; upscale to a minimum size
    if min(out.size) < 64:
        scale = 64 / min(out.size)
        out = out.resize((max(64, int(out.width * scale)), max(64, int(out.height * scale))),
                         Image.Resampling.LANCZOS)
    return out
