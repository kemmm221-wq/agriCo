"""
region_checker.py

Stage 4 — Per-region zoom + real-vs-fake re-check.

For each color region detected in Stage 3, crops the region out of the
normalized image and re-validates it as genuine soil using two independent
checks:

  A. Soil gate re-check: runs predict_is_soil() from soil_gate.py on the
     cropped region sub-image (catches regions that are actually debris,
     rocks, packaging, or non-soil material that slipped through the
     whole-frame gate).

  B. Fake_Soil record similarity: computes mean structural similarity (SSIM)
     between the cropped region and up to 10 stored Fake_Soil images.
     A close match to stored fake-soil records (SSIM >= FAKE_SOIL_SSIM_THRESHOLD)
     is treated as an additional signal to reject the region, catching cases
     that resemble previously confirmed fake soil.

If only one significant color region was found in Stage 3, the whole
normalized image is used directly (no sub-crop needed).

Public API:
    filter_real_regions(
        normalized_image: PIL.Image.Image,
        regions: list[dict],
        fake_soil_dir: str,
    ) -> list[dict]

Each surviving dict gains two new keys:
    "gate_confidence"  : float  -- soil gate confidence for this region
    "is_real_region"   : True   -- (fake regions are simply excluded)
"""

import os
import sys
import random
from typing import List, Tuple

import numpy as np
from PIL import Image

# Threshold above which an SSIM score against a Fake_Soil image causes rejection
FAKE_SOIL_SSIM_THRESHOLD = 0.80

# Max number of Fake_Soil reference images to compare against per region
MAX_FAKE_SOIL_REFS = 10

# Resize resolution for SSIM comparison (keep it small for speed)
SSIM_COMPARE_SIZE = (64, 64)

IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png", ".bmp", ".tiff", ".webp"}


def _load_fake_soil_refs(fake_soil_dir: str) -> List[np.ndarray]:
    """
    Loads up to MAX_FAKE_SOIL_REFS grayscale thumbnails from the Fake_Soil directory.
    Returns list of (H, W) float32 arrays normalized to [0, 1].
    """
    if not os.path.isdir(fake_soil_dir):
        return []

    paths = [
        os.path.join(fake_soil_dir, f)
        for f in os.listdir(fake_soil_dir)
        if os.path.splitext(f)[1].lower() in IMAGE_EXTENSIONS
    ]

    if len(paths) > MAX_FAKE_SOIL_REFS:
        paths = random.sample(paths, MAX_FAKE_SOIL_REFS)

    refs = []
    for p in paths:
        try:
            img = Image.open(p).convert("L").resize(SSIM_COMPARE_SIZE, Image.Resampling.BILINEAR)
            refs.append(np.asarray(img, dtype=np.float32) / 255.0)
        except Exception:
            pass
    return refs


def _ssim_score(a: np.ndarray, b: np.ndarray) -> float:
    """
    Computes Structural Similarity Index (SSIM) between two (H, W) float32
    arrays in [0, 1] using skimage if available, else a simple correlation.
    """
    try:
        from skimage.metrics import structural_similarity as ssim  # type: ignore
        score = float(ssim(a, b, data_range=1.0))
        return max(0.0, score)
    except ImportError:
        # Fallback: Pearson correlation as a rough proxy
        a_flat = a.flatten()
        b_flat = b.flatten()
        if a_flat.std() < 1e-6 or b_flat.std() < 1e-6:
            return 0.0
        corr = float(np.corrcoef(a_flat, b_flat)[0, 1])
        return max(0.0, corr)


def _max_ssim_vs_fake(region_thumb: np.ndarray, fake_refs: List[np.ndarray]) -> float:
    """
    Returns the highest SSIM score between `region_thumb` and any fake-soil reference.
    Returns 0.0 if no references are available.
    """
    if not fake_refs:
        return 0.0
    scores = [_ssim_score(region_thumb, ref) for ref in fake_refs]
    return max(scores)


def _region_to_thumbnail(region_image: Image.Image) -> np.ndarray:
    """Converts a region PIL Image to a grayscale (H, W) float32 thumbnail."""
    thumb = region_image.convert("L").resize(SSIM_COMPARE_SIZE, Image.Resampling.BILINEAR)
    return np.asarray(thumb, dtype=np.float32) / 255.0


def filter_real_regions(
    image: Image.Image,
    regions: List[dict],
    fake_soil_dir: str,
    stage1_passed: bool = True,
) -> Tuple[List[dict], List[dict]]:
    """
    Stage 4 — Per-region zoom + real-vs-fake re-check.

    Never deletes pixels. Hard-rejects only if BOTH:
        soil_p < 0.30 AND (Fake_SSIM >= 0.80 OR tiny/white glare)
    Otherwise marks region as low_confidence with verified=False, keeping its
    pixels intact in the final segmentation. Hard-rejected regions are returned
    separately in `rejected_regions` (assigned neutral gray).

    Returns:
        (real_regions, rejected_regions)
    """
    # Lazy import to avoid circular dependency
    from soil_gate import predict_is_soil  # noqa: E402
    from color_detector import crop_region  # noqa: E402

    fake_refs = _load_fake_soil_refs(fake_soil_dir)

    real_regions = []
    rejected_regions = []

    # If only one region, check the whole image directly
    if len(regions) == 1:
        region = dict(regions[0])
        region["region_image"] = image.convert("RGB")
        is_soil, gate_conf = predict_is_soil(image)
        soil_p = gate_conf if is_soil else (1.0 - gate_conf)
        region_thumb = _region_to_thumbnail(region["region_image"])
        max_fake_ssim = _max_ssim_vs_fake(region_thumb, fake_refs)

        region["gate_confidence"] = round(soil_p, 3)
        region["gate_raw_conf"] = round(gate_conf, 3)
        region["fake_ssim"] = round(max_fake_ssim, 3)

        if soil_p < 0.30 and max_fake_ssim >= FAKE_SOIL_SSIM_THRESHOLD:
            region["is_real_region"] = False
            region["verified"] = False
            region["low_confidence"] = True
            region["rejected"] = True
            region["status"] = "rejected"
            region["overlay_color"] = (150, 150, 150)
            region["overlay_hex"] = "#969696"
            return [], [region]

        passed = is_soil and (max_fake_ssim < FAKE_SOIL_SSIM_THRESHOLD)
        if not passed and stage1_passed and max_fake_ssim < FAKE_SOIL_SSIM_THRESHOLD:
            passed = True
        region["is_real_region"] = True
        region["verified"] = bool(passed)
        region["low_confidence"] = bool(not passed)
        region["rejected"] = False
        region["status"] = "verified" if passed else "unverified"
        return [region], []

    # Multiple regions: crop and check each independently
    for region in regions:
        region = dict(region)
        pixel_mask = region["pixel_mask"]
        cropped = crop_region(image, pixel_mask)
        region["region_image"] = cropped

        # A. Soil gate on cropped region
        is_soil, gate_conf = predict_is_soil(cropped)
        soil_p = gate_conf if is_soil else (1.0 - gate_conf)

        # B. Fake_Soil similarity check
        region_thumb = _region_to_thumbnail(cropped)
        max_fake_ssim = _max_ssim_vs_fake(region_thumb, fake_refs)

        # C. Glare or tiny check
        cropped_arr = np.asarray(cropped.convert("RGB"), dtype=np.float32)
        mean_luma = float(np.mean(0.299 * cropped_arr[:, :, 0] + 0.587 * cropped_arr[:, :, 1] + 0.114 * cropped_arr[:, :, 2]))
        is_glare_or_tiny = (mean_luma >= 240.0) or (region.get("proportion", 0.0) < 0.005 and not is_soil)

        # Hard-reject ONLY if BOTH: soil_p < 0.30 AND (Fake_SSIM >= 0.80 OR tiny/white glare)
        is_hard_reject = (soil_p < 0.30) and (max_fake_ssim >= FAKE_SOIL_SSIM_THRESHOLD or is_glare_or_tiny)

        region["gate_confidence"] = round(soil_p, 3)
        region["gate_raw_conf"] = round(gate_conf, 3)
        region["fake_ssim"] = round(max_fake_ssim, 3)

        if is_hard_reject:
            sys.stderr.write(
                f"[DEBUG Stage 4] Region {region.get('color_index')} HARD-REJECTED: "
                f"soil_p={soil_p:.3f} < 0.30, Fake_SSIM={max_fake_ssim:.3f}, glare_or_tiny={is_glare_or_tiny}\n"
            )
            sys.stderr.flush()
            region["is_real_region"] = False
            region["verified"] = False
            region["low_confidence"] = True
            region["rejected"] = True
            region["status"] = "rejected"
            region["overlay_color"] = (150, 150, 150)
            region["overlay_hex"] = "#969696"
            rejected_regions.append(region)
        else:
            # Region is KEPT — never delete pixels
            passed = is_soil and (max_fake_ssim < FAKE_SOIL_SSIM_THRESHOLD)
            prop = float(region.get("proportion", 0.0))
            if not passed and prop > 0.50 and stage1_passed and max_fake_ssim < FAKE_SOIL_SSIM_THRESHOLD:
                passed = True  # Inherit Stage 1 if dominant region
            region["is_real_region"] = True
            region["verified"] = bool(passed)
            region["low_confidence"] = bool(not passed)
            region["rejected"] = False
            region["status"] = "verified" if passed else "unverified"
            sys.stderr.write(
                f"[DEBUG Stage 4] Region {region.get('color_index')} KEPT ({region['status']}): "
                f"soil_p={soil_p:.3f}, Fake_SSIM={max_fake_ssim:.3f}, verified={region['verified']}\n"
            )
            sys.stderr.flush()
            sys.stderr.write(
                f"[DEBUG Stage 4] Region {region.get('color_index')} KEPT ({region['status']}): "
                f"gate_prob={gate_conf:.3f}, Fake_SSIM={max_fake_ssim:.3f}, verified={region['verified']}\n"
            )
            sys.stderr.flush()
            real_regions.append(region)

    sys.stderr.write(
        f"[DEBUG Stage 4] region_checker summary: {len(real_regions)} kept "
        f"({sum(1 for r in real_regions if r['verified'])} verified, "
        f"{sum(1 for r in real_regions if not r['verified'])} unverified), "
        f"{len(rejected_regions)} hard-rejected\n"
    )
    sys.stderr.flush()
    return real_regions, rejected_regions
