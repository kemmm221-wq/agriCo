import os
import sys
import base64
import numpy as np
from PIL import Image

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from color_detector import detect_color_regions, extract_blobs, render_color_map, smooth_label_map, SMOOTH_KSIZE
from region_merge import merge_munsell_regions

print("=" * 70)
print(f"RUNNING QUALITY & OVERLAY TESTS (SMOOTH_KSIZE = {SMOOTH_KSIZE})")
print("=" * 70)

out_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test_outputs")
os.makedirs(out_dir, exist_ok=True)

# ---------------------------------------------------------------------------
# Test (a): Speckled reddish-brown soil with rocks, pebbles and clumps
# ---------------------------------------------------------------------------
print("\n[TEST A] Speckled Soil (Reddish-brown matrix with gray pebbles & dark organic clumps)")
rng = np.random.default_rng(42)
# Base matrix: reddish-brown [150, 85, 55] (approx 65% area)
arr_a = np.full((320, 320, 3), [150, 85, 55], dtype=np.uint8)
# Add texture noise
noise = rng.normal(0, 8, arr_a.shape).astype(np.int16)
arr_a = np.clip(arr_a.astype(np.int16) + noise, 0, 255).astype(np.uint8)

# Scattered gray pebbles [180, 175, 170] (approx 20% scattered)
for _ in range(35):
    px, py = rng.integers(10, 310), rng.integers(10, 310)
    rad = rng.integers(4, 12)
    y, x = np.ogrid[:320, :320]
    mask = (x - px)**2 + (y - py)**2 <= rad**2
    arr_a[mask] = [180, 175, 170]

# Scattered dark clumps [70, 45, 30] (approx 15% scattered)
for _ in range(25):
    px, py = rng.integers(10, 310), rng.integers(10, 310)
    rad = rng.integers(5, 14)
    y, x = np.ogrid[:320, :320]
    mask = (x - px)**2 + (y - py)**2 <= rad**2
    arr_a[mask] = [70, 45, 30]

img_a = Image.fromarray(arr_a)
regions_a = detect_color_regions(img_a)

total_pct_a = sum(r["proportion"] for r in regions_a)
print(f"  Regions detected: {len(regions_a)}")
for idx, r in enumerate(regions_a):
    print(f"    Region {idx}: RGB={r['rgb']}, Proportion={r['proportion']:.3f}, Hex={r.get('overlay_hex')}, Blobs={len(r.get('blobs', []))}")
print(f"  Sum of proportions: {total_pct_a:.4f} (Expected ~1.00)")
assert abs(total_pct_a - 1.0) < 0.05, f"Percentages do not sum to ~100%: {total_pct_a}"
assert len(regions_a) >= 2, "Expected multiple regions for speckled soil"
assert "color_map_png" in regions_a[0], "Missing color_map_png in dominant region"

# Save overlay
overlay_bytes_a = base64.b64decode(regions_a[0]["color_map_png"])
with open(os.path.join(out_dir, "test_overlay_a.png"), "wb") as f:
    f.write(overlay_bytes_a)
print("  Saved overlay to: test_outputs/test_overlay_a.png")

# ---------------------------------------------------------------------------
# Test (b): Uniform soil
# ---------------------------------------------------------------------------
print("\n[TEST B] Uniform Soil Sample")
arr_b = np.full((320, 320, 3), [115, 90, 70], dtype=np.uint8)
arr_b = np.clip(arr_b.astype(np.int16) + rng.normal(0, 4, arr_b.shape).astype(np.int16), 0, 255).astype(np.uint8)
img_b = Image.fromarray(arr_b)
regions_b = detect_color_regions(img_b)

total_pct_b = sum(r["proportion"] for r in regions_b)
print(f"  Regions detected: {len(regions_b)}")
for idx, r in enumerate(regions_b):
    print(f"    Region {idx}: RGB={r['rgb']}, Proportion={r['proportion']:.3f}, Hex={r.get('overlay_hex')}")
print(f"  Sum of proportions: {total_pct_b:.4f} (Expected 1.00)")
assert abs(total_pct_b - 1.0) < 0.05, f"Percentages do not sum to ~100%: {total_pct_b}"
assert len(regions_b) == 1, f"Expected 1 uniform soil region, got {len(regions_b)}"

overlay_bytes_b = base64.b64decode(regions_b[0]["color_map_png"])
with open(os.path.join(out_dir, "test_overlay_b.png"), "wb") as f:
    f.write(overlay_bytes_b)
print("  Saved overlay to: test_outputs/test_overlay_b.png")

# ---------------------------------------------------------------------------
# Test (c): Two different soils (50/50 split)
# ---------------------------------------------------------------------------
print("\n[TEST C] Two Distinct Soils (Left = 10YR 3/2 dark brown, Right = 7.5YR 6/6 reddish yellow)")
arr_c = np.zeros((320, 320, 3), dtype=np.uint8)
arr_c[:, :160] = [80, 65, 50]
arr_c[:, 160:] = [190, 115, 50]
arr_c = np.clip(arr_c.astype(np.int16) + rng.normal(0, 4, arr_c.shape).astype(np.int16), 0, 255).astype(np.uint8)
img_c = Image.fromarray(arr_c)
regions_c = detect_color_regions(img_c)

total_pct_c = sum(r["proportion"] for r in regions_c)
print(f"  Regions detected: {len(regions_c)}")
for idx, r in enumerate(regions_c):
    print(f"    Region {idx}: RGB={r['rgb']}, Proportion={r['proportion']:.3f}, Hex={r.get('overlay_hex')}, Blobs={len(r.get('blobs', []))}")
print(f"  Sum of proportions: {total_pct_c:.4f} (Expected 1.00)")
assert abs(total_pct_c - 1.0) < 0.05, f"Percentages do not sum to ~100%: {total_pct_c}"
assert len(regions_c) == 2, f"Expected exactly 2 distinct soil regions, got {len(regions_c)}"

overlay_bytes_c = base64.b64decode(regions_c[0]["color_map_png"])
with open(os.path.join(out_dir, "test_overlay_c.png"), "wb") as f:
    f.write(overlay_bytes_c)
print("  Saved overlay to: test_outputs/test_overlay_c.png")

# ---------------------------------------------------------------------------
# Test (d): Strong shadow on the same soil (L* ~ 30 shadow vs L* ~ 60 sunlight)
# ---------------------------------------------------------------------------
print("\n[TEST D] Strong Shadow on Same Soil")
arr_d = np.zeros((320, 320, 3), dtype=np.uint8)
# Shadow half: [72, 57, 39]
arr_d[:, :160] = [72, 57, 39]
# Sunlight half: [156, 123, 84]
arr_d[:, 160:] = [156, 123, 84]
arr_d = np.clip(arr_d.astype(np.int16) + rng.normal(0, 4, arr_d.shape).astype(np.int16), 0, 255).astype(np.uint8)
img_d = Image.fromarray(arr_d)
regions_d = detect_color_regions(img_d)

# Stage 5.5 Munsell merge: same hue/chroma (10YR /4), value 3 vs 5
simulated_munsell_d = []
for idx, r in enumerate(regions_d):
    val = 3.0 if idx == 0 else 5.0
    simulated_munsell_d.append({
        **r,
        "munsell": f"10YR {val:.0f}/4",
        "munsell_hue": "10YR",
        "munsell_value": val,
        "munsell_chroma": 4.0,
        "name": "Dark Yellowish Brown",
    })
merged_d = merge_munsell_regions(simulated_munsell_d, img_d)

total_pct_d = sum(r["proportion"] for r in merged_d)
print(f"  After Munsell merge count: {len(merged_d)}")
print(f"  Proportion: {merged_d[0]['proportion']:.3f}, Lightness spread: {merged_d[0].get('lightness_spread')}")
print(f"  Sum of proportions: {total_pct_d:.4f} (Expected 1.00)")
assert abs(total_pct_d - 1.0) < 0.05, f"Percentages do not sum to ~100%: {total_pct_d}"
assert len(merged_d) == 1, "Expected shadow and sunlight of same soil to merge into 1"

overlay_bytes_d = base64.b64decode(regions_d[0]["color_map_png"])
with open(os.path.join(out_dir, "test_overlay_d.png"), "wb") as f:
    f.write(overlay_bytes_d)
print("  Saved overlay to: test_outputs/test_overlay_d.png")

print("\n" + "=" * 70)
print("ALL 4 QUALITY & OVERLAY TESTS PASSED SUCCESSFULLY!")
print("=" * 70)
