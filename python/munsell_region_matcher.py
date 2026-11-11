"""
munsell_region_matcher.py

Stage 5 — Per-region Munsell matching.

For each confirmed real-soil region from Stage 4, extracts the mean RGB of
the region's pixels and matches it to the nearest Munsell notation using
the existing dataset-driven MunsellTable.

Each region is also encoded as a base64 PNG thumbnail for inclusion in the
final API response (so the frontend can display which part of the image
corresponds to each Munsell notation).

Public API:
    match_regions(
        regions: list[dict],
        munsell_table: MunsellTable,
    ) -> list[dict]

Each output dict has:
    {
        "rgb"                    : (R, G, B)
        "proportion"             : float
        "color_index"            : int
        "gate_confidence"        : float
        "hue"                    : str          e.g. "10YR"
        "value"                  : float        e.g. 4.0
        "chroma"                 : float        e.g. 3.0
        "munsell"                : str          e.g. "10YR 4/3"
        "label"                  : str          e.g. "olive brown"  (from CSV if available, else notation)
        "color_delta_e"          : float
        "region_thumbnail"       : str          base64-encoded PNG thumbnail
    }
"""

import base64
import io
from typing import List

from PIL import Image

# Thumbnail dimensions embedded in API response
THUMBNAIL_SIZE = (96, 96)

# Munsell hue → approximate descriptive label mapping.
# Used as a fallback when the CSV does not include a label column.
_HUE_LABEL_HINTS = {
    "10R": "red",
    "2.5YR": "reddish brown",
    "5YR": "yellowish red",
    "7.5YR": "brown",
    "10YR": "yellowish brown",
    "2.5Y": "olive yellow",
    "5Y": "olive",
    "10Y": "greenish olive",
    "5G": "grayish green",
    "10G": "green",
    "5B": "blue",
    "10B": "bluish gray",
    "5P": "purple",
    "10P": "reddish purple",
    "N": "neutral gray",
}


def _munsell_label(hue: str, value: float, chroma: float) -> str:
    """
    Returns a human-readable color description for a Munsell chip.
    Derives tone prefix (very dark / dark / medium / light) from value,
    combined with a hue hint.
    """
    if value <= 2.5:
        tone = "very dark "
    elif value <= 3.5:
        tone = "dark "
    elif value <= 5.0:
        tone = ""
    elif value <= 6.5:
        tone = "light "
    else:
        tone = "very light "

    if chroma <= 1:
        sat = "gray"
    elif chroma <= 2:
        sat = "grayish "
    else:
        sat = ""

    base = _HUE_LABEL_HINTS.get(hue, hue.lower())
    if sat == "gray":
        return f"{tone}gray"
    return f"{tone}{sat}{base}".strip()


def _image_to_b64_thumbnail(region_image: Image.Image) -> str:
    """
    Resizes the region image to THUMBNAIL_SIZE and encodes it as a base64 PNG string.
    """
    thumb = region_image.convert("RGB").resize(THUMBNAIL_SIZE, Image.Resampling.LANCZOS)
    buf = io.BytesIO()
    thumb.save(buf, format="PNG")
    return base64.b64encode(buf.getvalue()).decode("ascii")


def match_regions(
    regions: List[dict],
    munsell_table,
) -> List[dict]:
    """
    Stage 5 — Per-region Munsell matching.

    Parameters:
        regions        : Confirmed real-soil region dicts from Stage 4.
        munsell_table  : MunsellTable instance (from munsell_lookup.py).

    Returns:
        Same list augmented with Munsell fields and region_thumbnail per region.
    """
    matched = []
    for region in regions:
        rgb = region["rgb"]
        chip, delta_e = munsell_table.nearest(rgb)

        if chip is None:
            # Should not happen if the CSV is non-empty, but guard anyway
            continue

        region_image: Image.Image = region.get("region_image") or Image.new("RGB", THUMBNAIL_SIZE, rgb)
        thumbnail_b64 = _image_to_b64_thumbnail(region_image)

        matched_dict = dict(region)
        matched_dict.update({
            # Carry forward from Stage 3/4
            "rgb": rgb,
            "proportion": region["proportion"],
            "color_index": region["color_index"],
            "gate_confidence": region.get("gate_confidence", 1.0),
            "bounding_box": region.get("bounding_box"),
            "box": region.get("box"),
            "shades": region.get("shades", []),
            "lightness_spread": region.get("lightness_spread", 0.0),
            "merged_from": region.get("merged_from", [region.get("color_index")]),
            "pixel_mask": region.get("pixel_mask"),
            "region_image": region_image,
            # Stage 5: Munsell result
            "hue": chip.hue,
            "value": chip.value,
            "chroma": chip.chroma,
            "munsell": chip.notation,
            "label": _munsell_label(chip.hue, chip.value, chip.chroma),
            "color_delta_e": round(float(delta_e), 3),
            "region_thumbnail": thumbnail_b64,
        })
        matched.append(matched_dict)

    return matched
