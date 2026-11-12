"""
region_merge.py

Stage 5.5 — Munsell Color Shade Merging.

After Stage 4 has certified real soil regions and Stage 5 has matched each
region to its nearest Munsell standard, this step merges regions that land on
the SAME Munsell Hue and near Chroma, differing only in Value (brightness/shadow).

The larger (higher proportion) region acts as the anchor, retaining its
Munsell notation and representative RGB while absorbing the smaller region's
surface area, pixel mask, and shade sub-distribution.
"""

import re
import sys
from typing import List, Optional
import numpy as np
from color_detector import locate_color_box, extract_blobs

# ── Tunable Settings ───────────────────────────────────────────────────────────
import os
MUNSELL_MAX_VALUE_GAP = float(os.environ.get("STAGE5_MUNSELL_MAX_VALUE_GAP", "2.0"))    # Value steps apart allowed (shadow vs light)
MUNSELL_MAX_CHROMA_GAP = float(os.environ.get("STAGE5_MUNSELL_MAX_CHROMA_GAP", "1.0"))   # Chroma steps apart allowed (set 0 for strict)
MUNSELL_MAX_HUE_GAP = float(os.environ.get("STAGE5_MUNSELL_MAX_HUE_GAP", "2.5"))         # Hue units apart allowed (10R to 2.5YR = 2.5)

_HUE_FAMILY_OFFSET = {
    "R": 0.0,
    "YR": 10.0,
    "Y": 20.0,
    "GY": 30.0,
    "G": 40.0,
    "BG": 50.0,
    "B": 60.0,
    "PB": 70.0,
    "P": 80.0,
    "RP": 90.0,
}


def munsell_hue_position(hue_num: float, hue_family: str) -> Optional[float]:
    """Converts (hue_num, hue_family) to a continuous 0-100 coordinate on Munsell circle."""
    if hue_family not in _HUE_FAMILY_OFFSET:
        return None
    return (_HUE_FAMILY_OFFSET[hue_family] + hue_num) % 100.0


def hue_distance(p, q) -> float:
    """Returns angular hue distance in Munsell units (e.g. 10R vs 2.5YR is 2.5)."""
    if not p or not q:
        return 999.0
    pos_p = munsell_hue_position(p[0], p[1])
    pos_q = munsell_hue_position(q[0], q[1])
    if pos_p is None or pos_q is None:
        return 999.0
    diff = abs(pos_p - pos_q)
    return min(diff, 100.0 - diff)

_MUNSELL_RE = re.compile(
    r"^\s*(\d+(?:\.\d+)?)\s*([A-Z]{1,2})\s+(\d+(?:\.\d+)?)\s*/\s*(\d+(?:\.\d+)?)\s*$"
)


def _munsell_code(region: dict) -> Optional[str]:
    """Extracts Munsell notation string from region dictionary."""
    m = region.get("munsell") or region.get("munsell_code") or region.get("notation") or region.get("code")
    if isinstance(m, dict):
        m = m.get("code") or m.get("notation")
    return str(m).strip() if m else None


def parse_munsell(code: Optional[str]):
    """
    Parses '10YR 4/3' -> (hue_number: float, hue_family: str, value: float, chroma: float).
    Returns None if unparseable or neutral.
    """
    if not code:
        return None
    m = _MUNSELL_RE.match(str(code).upper())
    if not m:
        return None
    return float(m.group(1)), m.group(2), float(m.group(3)), float(m.group(4))


def _luma(rgb) -> float:
    return 0.299 * rgb[0] + 0.587 * rgb[1] + 0.114 * rgb[2]


def _relabel_shades(shades: list) -> list:
    shades = sorted(shades, key=lambda s: _luma(s["rgb"]))
    n = len(shades)
    if n <= 1:
        if n == 1:
            shades[0]["level"] = "uniform"
        return shades
    if n == 2:
        shades[0]["level"] = "dark"
        shades[1]["level"] = "light"
        return shades
    if n == 3:
        shades[0]["level"] = "dark"
        shades[1]["level"] = "medium"
        shades[2]["level"] = "light"
        return shades
    # If more than 3 shades after merging, group into 3 terciles (dark, medium, light)
    t1 = n // 3
    t2 = (2 * n) // 3
    groups = [shades[:t1], shades[t1:t2], shades[t2:]]
    levels = ["dark", "medium", "light"]
    collapsed = []
    for g, level in zip(groups, levels):
        if not g:
            continue
        tot_prop = sum(float(s.get("proportion", 0.0)) for s in g)
        denom = max(tot_prop, 1e-6)
        avg_r = int(round(sum(s["rgb"][0] * s.get("proportion", 1.0) for s in g) / denom))
        avg_g = int(round(sum(s["rgb"][1] * s.get("proportion", 1.0) for s in g) / denom))
        avg_b = int(round(sum(s["rgb"][2] * s.get("proportion", 1.0) for s in g) / denom))
        collapsed.append({
            "rgb": (avg_r, avg_g, avg_b),
            "level": level,
            "proportion": round(tot_prop, 4),
        })
    return collapsed


def merge_munsell_regions(regions: List[dict], image) -> List[dict]:
    """
    Merge regions that match the same Munsell Hue (and nearly the same Chroma),
    differing only in Value. The bigger region is the anchor and keeps its Munsell
    code and rgb. `image` is the normalized PIL image used for the bounding box.
    """
    if not regions or len(regions) <= 1:
        return regions

    img_arr = np.asarray(image.convert("RGB"), dtype=np.uint8)
    merged: List[dict] = []
    merge_events = []

    # Process largest proportion first so big colors become anchors
    for r in sorted(regions, key=lambda x: x.get("proportion", 0.0), reverse=True):
        p = parse_munsell(_munsell_code(r))
        target = None
        if p:
            for m in merged:
                q = m["_parsed"]
                if (q and hue_distance(p, q) <= MUNSELL_MAX_HUE_GAP
                        and abs(p[2] - q[2]) <= MUNSELL_MAX_VALUE_GAP
                        and abs(p[3] - q[3]) <= MUNSELL_MAX_CHROMA_GAP):
                    target = m
                    break

        if target is None:
            r = dict(r)
            r["_parsed"] = p
            r["merged_from"] = [r.get("color_index")] if r.get("color_index") is not None else []
            merged.append(r)
            continue

        # Merge r into target
        old_p = float(target.get("proportion", 0.0))
        add_p = float(r.get("proportion", 0.0))
        new_p = old_p + add_p
        if new_p <= 0:
            new_p = 1e-4

        shades = []
        for s in target.get("shades", []):
            s = dict(s)
            s["proportion"] = round(float(s.get("proportion", 1.0)) * old_p / new_p, 4)
            shades.append(s)
        for s in r.get("shades", []):
            s = dict(s)
            s["proportion"] = round(float(s.get("proportion", 1.0)) * add_p / new_p, 4)
            shades.append(s)

        target["proportion"] = round(new_p, 4)

        # Merge pixel masks safely
        t_mask = target.get("pixel_mask")
        r_mask = r.get("pixel_mask")
        if t_mask is not None and r_mask is not None:
            target["pixel_mask"] = t_mask | r_mask
        elif r_mask is not None:
            target["pixel_mask"] = r_mask

        target["shades"] = _relabel_shades(shades)
        target["lightness_spread"] = max(float(target.get("lightness_spread", 0.0)),
                                         float(r.get("lightness_spread", 0.0)))

        c_idx = r.get("color_index")
        if c_idx is not None and c_idx not in target.get("merged_from", []):
            target["merged_from"].append(c_idx)

        # Recompute representative bounding box, blobs, and mask_png on the merged soil surface
        if target.get("pixel_mask") is not None:
            box = locate_color_box(img_arr, target["rgb"], pixel_mask=target["pixel_mask"])
            target["bounding_box"] = box
            target["box"] = {"x": box["norm_x"], "y": box["norm_y"],
                             "w": box["norm_w"], "h": box["norm_h"]}
            target["blobs"] = extract_blobs(target["pixel_mask"])
            try:
                from color_detector import _png_b64
                target["mask_png"] = _png_b64((target["pixel_mask"].astype(np.uint8) * 255), "L")
            except Exception:
                pass

        merge_events.append((r.get("color_index"), target.get("color_index"), _munsell_code(r), _munsell_code(target)))

    for m in merged:
        m.pop("_parsed", None)

    merged.sort(key=lambda x: x["proportion"], reverse=True)

    if merge_events:
        for ev in merge_events:
            sys.stderr.write(
                f"[MunsellMerge] Merged region {ev[0]} ({ev[2]}) -> anchor {ev[1]} ({ev[3]})\n"
            )
        sys.stderr.write(
            f"[MunsellMerge] Regions reduced from {len(regions)} to {len(merged)}.\n"
        )
        sys.stderr.flush()

    return merged

