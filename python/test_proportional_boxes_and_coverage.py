import sys
import os
import glob
from PIL import Image
import numpy as np

# Ensure python directory is on sys.path
sys.path.insert(0, os.path.dirname(__file__))

from color_detector import detect_color_regions, locate_color_box
from munsell_lookup import MunsellTable
from soil_classifier import classify_soil


def test_proportional_boxes_and_coverage():
    print("=====================================================================")
    print("  VERIFICATION TEST: Proportional Boxes, 100% Coverage, All Colors   ")
    print("=====================================================================")

    real_soil_dir = os.path.join(os.path.dirname(__file__), "..", "Scan_Soil_Image", "Real_Soil")
    candidates = (
        glob.glob(os.path.join(real_soil_dir, "**", "*.jpg"), recursive=True)
        + glob.glob(os.path.join(real_soil_dir, "**", "*.png"), recursive=True)
    )
    img_candidates = [f for f in candidates if os.path.isfile(f)]

    if not img_candidates:
        print("[WARN] No real soil images found in Scan_Soil_Image/Real_Soil, using synthetic test.")
        # Create a 400x300 synthetic test image with two distinct zones: 80% brown, 20% sandy
        test_arr = np.zeros((300, 400, 3), dtype=np.uint8)
        test_arr[:, :] = [77, 54, 51]       # 10R 2.5/2 brown
        test_arr[50:110, 200:320] = [236, 193, 129]  # 10YR 8/6 sandy light
        img_path = "synthetic_soil_test.jpg"
        img = Image.fromarray(test_arr)
    else:
        # Use first real soil image
        img_path = img_candidates[0]
        img = Image.open(img_path).convert("RGB")

    print(f"\n1. Testing Image: {os.path.basename(img_path)} ({img.size[0]}x{img.size[1]})")

    # Step 1: Multi-color detection
    regions = detect_color_regions(img)
    print(f"   Detected {len(regions)} distinct color regions (No minimum percentage drop).")

    total_coverage = sum(r["proportion"] for r in regions)
    print(f"   Total Coverage Sum: {total_coverage * 100:.2f}% (Expected: 100.0%)")
    assert abs(total_coverage - 1.0) < 0.005, f"Coverage {total_coverage:.4f} != 1.0"

    print("\n   Table of Detected Colors & Proportional Bounding Boxes:")
    print("   ------------------------------------------------------------------------------------------------------")
    print("   Idx | Hex Color | Proportion (p) | Box [x, y, w, h]             | Box Area Frac | |AreaFrac - p| < 0.02")
    print("   ------------------------------------------------------------------------------------------------------")

    for idx, r in enumerate(regions):
        p = r["proportion"]
        b = r["box"]
        x, y, w, h = b["x"], b["y"], b["w"], b["h"]
        area_frac = round(w * h, 4)
        diff = abs(area_frac - p)
        hex_c = f"#{r['rgb'][0]:02x}{r['rgb'][1]:02x}{r['rgb'][2]:02x}"

        # Assert bounds clamp
        assert 0.0 <= x <= 1.0, f"x={x} out of bounds"
        assert 0.0 <= y <= 1.0, f"y={y} out of bounds"
        assert x + w <= 1.001, f"x+w={x+w} overflows image"
        assert y + h <= 1.001, f"y+h={y+h} overflows image"

        # Assert proportional box area formula |box_w * box_h - p| < 0.02
        assert diff < 0.02, f"Region {idx}: |area_frac ({area_frac}) - p ({p})| = {diff:.4f} >= 0.02"

        print(f"   {idx:3d} | {hex_c:9s} | {p*100:12.1f}% | [{x:0.4f}, {y:0.4f}, {w:0.4f}, {h:0.4f}] | {area_frac*100:11.1f}% | PASS (diff={diff:.4f})")

    print("   ------------------------------------------------------------------------------------------------------")

    # Step 2: Full End-to-End Classification test
    print("\n2. Testing End-to-End Pipeline (classify_soil):")
    munsell_csv = os.path.join(os.path.dirname(__file__), "munsell_reference_full.csv")
    scan_root = os.path.join(os.path.dirname(__file__), "..", "Scan_Soil_Image")
    munsell_table = MunsellTable(munsell_csv, scan_root=scan_root)
    result = classify_soil(img_path, munsell_table=munsell_table)

    print(f"   Predicted Label: {result.label}")
    print(f"   Dominant Munsell Header: {result.munsell_notation}")
    print(f"   Total Final Regions: {len(result.regions)}")

    if result.regions:
        dom_r = result.regions[0]
        print(f"   Dominant Region (0): {dom_r.get('munsell')} ({dom_r.get('proportion')*100:.1f}%)")
        assert result.munsell_notation == dom_r.get("munsell"), (
            f"Header notation {result.munsell_notation} must match dominant region {dom_r.get('munsell')}"
        )
        print("   Dominant header color matches highest percentage region: PASS")

        for idx, r in enumerate(result.regions):
            p = r["proportion"]
            b = r["box"]
            area_frac = b["w"] * b["h"]
            diff = abs(area_frac - p)
            assert diff < 0.02, f"Final region {idx}: diff {diff:.4f} >= 0.02"
            assert b["x"] + b["w"] <= 1.001
            assert b["y"] + b["h"] <= 1.001

    print("\n=====================================================================")
    print("  ALL VERIFICATION CHECKS PASSED: Area ~ Percentage & Coverage 100%   ")
    print("=====================================================================\n")


if __name__ == "__main__":
    test_proportional_boxes_and_coverage()
