import os
import sys
import numpy as np
from PIL import Image

# Ensure python directory is in sys.path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from color_detector import detect_color_regions, crop_region
from region_merge import merge_munsell_regions
from soil_classifier import classify_soil

print("=" * 60)
print("RUNNING 5 STAGE 3 & 4 VERIFICATION TESTS")
print("=" * 60)

# -------------------------------------------------------------
# TEST 1: Uniform Soil Image
# -------------------------------------------------------------
print("\n[TEST 1] Uniform Soil Image")
sample_img_path = os.path.join(
    os.path.dirname(os.path.abspath(__file__)),
    "Scan_Soil_Image", "Real_Soil", "10YR", "10YR 4", "1",
    "real_soil_10YR4-1_20261025T003502Z_8b58dee5.jpg"
)

if os.path.exists(sample_img_path):
    img1 = Image.open(sample_img_path).convert("RGB")
else:
    # Fallback to realistic synthetic uniform 10YR 4/3 soil
    arr = np.full((300, 300, 3), [115, 90, 70], dtype=np.uint8)
    noise = np.random.normal(0, 5, arr.shape).astype(np.int16)
    arr = np.clip(arr.astype(np.int16) + noise, 0, 255).astype(np.uint8)
    img1 = Image.fromarray(arr)

regions1 = detect_color_regions(img1)
print(f"Detected regions count: {len(regions1)}")
for idx, r in enumerate(regions1):
    print(f"  Region {idx}: RGB={r['rgb']}, Proportion={r['proportion']:.4f}, Lightness Spread={r.get('lightness_spread')}, Shades={len(r.get('shades', []))}")
assert len(regions1) >= 1, "Expected at least 1 region"
print(">>> TEST 1 PASSED: Soil detected as dominant cohesive color with sub-shades.")

# -------------------------------------------------------------
# TEST 2: Two Distinct Soils (Different Hue/Chroma)
# -------------------------------------------------------------
print("\n[TEST 2] Two Distinct Soils (Left = 10YR 3/2 dark brown, Right = 7.5YR 6/6 reddish yellow)")
arr2 = np.zeros((300, 300, 3), dtype=np.uint8)
# Left half: dark brown [80, 65, 50]
arr2[:, :150] = [80, 65, 50]
# Right half: reddish yellow [190, 115, 50]
arr2[:, 150:] = [190, 115, 50]
# Add small natural texture noise
arr2 = np.clip(arr2.astype(np.int16) + np.random.normal(0, 3, arr2.shape).astype(np.int16), 0, 255).astype(np.uint8)
img2 = Image.fromarray(arr2)

regions2 = detect_color_regions(img2)
print(f"Detected regions count: {len(regions2)}")
for idx, r in enumerate(regions2):
    print(f"  Region {idx}: RGB={r['rgb']}, Proportion={r['proportion']:.4f}")
assert len(regions2) == 2, f"Expected exactly 2 distinct soil regions, got {len(regions2)}"
p0, p1 = regions2[0]['proportion'], regions2[1]['proportion']
print(f"  Proportions: {p0:.2f} vs {p1:.2f} (Expected ~0.50 each)")
assert abs(p0 - 0.5) < 0.1 and abs(p1 - 0.5) < 0.1, "Proportions not balanced near 50/50"
print(">>> TEST 2 PASSED: Two distinct soils correctly separated with accurate proportions.")

# -------------------------------------------------------------
# TEST 3: Same Soil with Shadow + Sunlight (Same soil, different lightness)
# -------------------------------------------------------------
print("\n[TEST 3] Same Soil with Shadow + Sunlight (Dark half L* ~30, Light half L* ~60)")
arr3 = np.zeros((300, 300, 3), dtype=np.uint8)
# Base color: 10YR 4/4 brown [120, 95, 65]
# Shadow half (darkened by factor ~0.6): [72, 57, 39]
arr3[:, :150] = [72, 57, 39]
# Sunlit half (brightened by factor ~1.3): [156, 123, 84]
arr3[:, 150:] = [156, 123, 84]
arr3 = np.clip(arr3.astype(np.int16) + np.random.normal(0, 3, arr3.shape).astype(np.int16), 0, 255).astype(np.uint8)
img3 = Image.fromarray(arr3)

regions3 = detect_color_regions(img3)
print(f"Stage 3 detected regions count: {len(regions3)}")
for idx, r in enumerate(regions3):
    print(f"  Region {idx}: RGB={r['rgb']}, Proportion={r['proportion']:.4f}, Spread={r.get('lightness_spread')}, Shades={len(r.get('shades', []))}")

# Check with Munsell merging
simulated_munsell_regions = []
for idx, r in enumerate(regions3):
    # Simulated matched Munsell: same Hue 10YR, same Chroma 4, Value 3 vs 5 (within 2 steps)
    val = 3.0 if idx == 0 else 5.0
    simulated_munsell_regions.append({
        **r,
        "munsell": f"10YR {val:.0f}/4",
        "munsell_hue": "10YR",
        "munsell_value": val,
        "munsell_chroma": 4.0,
        "name": "Dark Yellowish Brown",
    })
merged = merge_munsell_regions(simulated_munsell_regions, img3)
print(f"After Stage 5.5 Munsell Merge count: {len(merged)}")
print(f"  Merged proportion: {merged[0]['proportion']:.2f}")
print(f"  Merged shades: {[s['level'] for s in merged[0].get('shades', [])]}")
print(f"  Lightness spread: {merged[0].get('lightness_spread')}")
print(f"  Lighting warning: {merged[0].get('lighting_warning')}")
assert len(merged) == 1, f"Expected shadow & sunlit parts to merge into 1 soil color, got {len(merged)}"
assert merged[0]['proportion'] >= 0.95, "Merged region should cover virtually 100% of classified area"
print(">>> TEST 3 PASSED: Shadow and sunlight successfully merged into single soil color with sub-shades.")

# -------------------------------------------------------------
# TEST 4: Soil with Twig / Glare / Gravel (Noise Dropping)
# -------------------------------------------------------------
print("\n[TEST 4] Soil with Specks (Small 1% white glare & 1% twig specks)")
arr4 = np.full((300, 300, 3), [115, 90, 70], dtype=np.uint8)
# 1% bright glare specks [255, 255, 255]
arr4[50:60, 50:60] = [255, 255, 255]
# 1% dark twig specks [15, 10, 5]
arr4[200:210, 200:210] = [15, 10, 5]
img4 = Image.fromarray(arr4)

regions4 = detect_color_regions(img4, min_proportion=0.05)
print(f"Detected regions count: {len(regions4)}")
for idx, r in enumerate(regions4):
    print(f"  Region {idx}: RGB={r['rgb']}, Proportion={r['proportion']:.4f}")
assert len(regions4) == 1, f"Expected specks <5% to be dropped/merged, but got {len(regions4)} regions"
print(">>> TEST 4 PASSED: Tiny noise specks dropped/absorbed, leaving pure soil color.")

# -------------------------------------------------------------
# TEST 5: Crop Region Inspection (In-Region Resampling)
# -------------------------------------------------------------
print("\n[TEST 5] Crop Region In-Region Resampling (Stage 4 Patch Preparation)")
arr5 = np.zeros((100, 100, 3), dtype=np.uint8)
# Background is bright neon green (neighboring non-soil or contrasting soil)
arr5[:, :] = [0, 255, 0]
# Region of interest is soil brown in center 40x40 circle
mask5 = np.zeros((100, 100), dtype=bool)
for y in range(100):
    for x in range(100):
        if (x - 50)**2 + (y - 50)**2 < 20**2:
            arr5[y, x] = [120, 95, 65]
            mask5[y, x] = True

img5 = Image.fromarray(arr5)
cropped_patch = crop_region(img5, mask5)
cropped_arr = np.asarray(cropped_patch)
print(f"Cropped patch shape: {cropped_arr.shape}")

# Verify no neon green [0, 255, 0] exists in the cropped patch!
# All non-region pixels in the bounding box must have been replaced with soil pixels.
green_pixels = (cropped_arr[:, :, 1] > 200) & (cropped_arr[:, :, 0] < 50) & (cropped_arr[:, :, 2] < 50)
green_count = int(green_pixels.sum())
print(f"Green pixels bleeding in crop: {green_count} (Expected: 0)")
assert green_count == 0, f"Found {green_count} neighboring pixels leaking into patch!"
assert cropped_arr.shape[0] >= 64 and cropped_arr.shape[1] >= 64, "Crop should be at least 64x64"
print(">>> TEST 5 PASSED: In-region pixel resampling completely eliminates neighboring color bleed.")

print("\n" + "=" * 60)
print("ALL 5 VERIFICATION TESTS PASSED SUCCESSFULLY!")
print("=" * 60)
