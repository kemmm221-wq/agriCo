"""
lighting_normalizer.py

Stage 2 — Lighting normalization.

Corrects for dark/bright capture conditions so the same soil scanned in low
light or bright light produces the same color result. Only the L (lightness)
channel in LAB color space is adjusted — A and B (which carry hue and chroma
information) are left completely untouched, so the actual soil color is never
distorted.

Public API:
    normalize_lighting(image: PIL.Image.Image) -> PIL.Image.Image

The returned image is always RGB mode, ready for Stage 3 (color_detector).
"""

import os
import sys
import cv2
import numpy as np
from PIL import Image


# Target mean L* value (D65, 0–100 scale). Soil is typically mid-toned;
# aiming for 50 gives good results across loamy browns and dark clays.
_TARGET_L = 50.0

# Clipping guard: set to 0.0 so Stage 2 always centers L* on 50.0 for every scan.
_L_TOLERANCE = 0.0

# Floor guard: very dark soils (L* < this value) must NOT be brightened.
# Examples: near-black clay (#211F1B, L*≈8), DarkMuted soils.
# Normalization would lift their apparent color into a completely wrong Munsell chip.
_DARK_FLOOR_L = 20.0

_GRADIENT_GRID = 4              # NxN grid — used only for diagnostic logging now
_FLATFIELD_SIGMA_FRAC = 1 / 6   # Gaussian sigma as a fraction of the larger image dimension
_FLATFIELD_MAX_GAIN = 2.2   # widened from 1.6 — allow stronger correction
_FLATFIELD_MIN_GAIN = 0.45  # widened from 0.625
_RESIDUAL_GRADIENT_THRESHOLD = 2.5  # grid_std after flat-fielding above this -> route to CLAHE

# Tracks the actual engine used (including fallback details) for accurate logging
LAST_STAGE2_MODE: str = "classical"



def _srgb_to_linear(c_arr: np.ndarray) -> np.ndarray:
    """Vectorized sRGB -> linear light (per IEC 61966-2-1)."""
    c = c_arr / 255.0
    return np.where(c <= 0.04045, c / 12.92, ((c + 0.055) / 1.055) ** 2.4)


def _linear_to_srgb(c_arr: np.ndarray) -> np.ndarray:
    """Vectorized linear light -> sRGB."""
    return np.where(
        c_arr <= 0.0031308,
        c_arr * 12.92,
        1.055 * (c_arr ** (1.0 / 2.4)) - 0.055,
    )


def _rgb_to_lab(rgb_arr: np.ndarray) -> np.ndarray:
    """
    Converts a (H, W, 3) float32 array in [0, 255] to CIE L*a*b* (D65).
    Returns (H, W, 3) float32 with L in [0, 100], a and b roughly [-128, 127].
    """
    r = _srgb_to_linear(rgb_arr[:, :, 0])
    g = _srgb_to_linear(rgb_arr[:, :, 1])
    b = _srgb_to_linear(rgb_arr[:, :, 2])

    # sRGB -> XYZ (D65 matrix)
    x = r * 0.4124 + g * 0.3576 + b * 0.1805
    y = r * 0.2126 + g * 0.7152 + b * 0.0722
    z = r * 0.0193 + g * 0.1192 + b * 0.9505

    # Normalize by D65 white point
    x /= 0.95047
    # y /= 1.00000  (no-op)
    z /= 1.08883

    def f(t: np.ndarray) -> np.ndarray:
        return np.where(t > 0.008856, t ** (1.0 / 3.0), 7.787 * t + 16.0 / 116.0)

    fx, fy, fz = f(x), f(y), f(z)
    L = 116.0 * fy - 16.0
    a = 500.0 * (fx - fy)
    b_ = 200.0 * (fy - fz)
    return np.stack([L, a, b_], axis=-1).astype(np.float32)


def _lab_to_rgb(lab_arr: np.ndarray) -> np.ndarray:
    """
    Converts (H, W, 3) float32 L*a*b* back to (H, W, 3) uint8 RGB.
    """
    L, a, b_ = lab_arr[:, :, 0], lab_arr[:, :, 1], lab_arr[:, :, 2]

    fy = (L + 16.0) / 116.0
    fx = a / 500.0 + fy
    fz = fy - b_ / 200.0

    def inv_f(t: np.ndarray) -> np.ndarray:
        return np.where(t > 0.20690, t ** 3.0, (t - 16.0 / 116.0) / 7.787)

    x = inv_f(fx) * 0.95047
    y = inv_f(fy)  # * 1.00000
    z = inv_f(fz) * 1.08883

    # XYZ -> linear RGB (sRGB matrix, D65)
    r_lin = x *  3.2406 + y * -1.5372 + z * -0.4986
    g_lin = x * -0.9689 + y *  1.8758 + z *  0.0415
    b_lin = x *  0.0557 + y * -0.2040 + z *  1.0570

    r_lin = np.clip(r_lin, 0.0, 1.0)
    g_lin = np.clip(g_lin, 0.0, 1.0)
    b_lin = np.clip(b_lin, 0.0, 1.0)

    r = _linear_to_srgb(r_lin)
    g = _linear_to_srgb(g_lin)
    b = _linear_to_srgb(b_lin)

    rgb = np.stack([r, g, b], axis=-1)
    rgb = np.clip(rgb * 255.0, 0, 255).astype(np.uint8)
    return rgb


def _apply_clahe_on_l(L_channel: np.ndarray, clip_limit: float = 2.0, grid: int = 8) -> np.ndarray:
    """
    Contrast Limited Adaptive Histogram Equalization (CLAHE) on the L channel.
    Used for unevenly lit images (shadow on one side, glare on the other).
    Scales to uint8 [0, 255] for OpenCV, or uses skimage with kernel_size=None.
    """
    try:
        import cv2
        L_u8 = np.clip(L_channel * 2.55, 0, 255).astype(np.uint8)
        clahe = cv2.createCLAHE(clipLimit=clip_limit, tileGridSize=(grid, grid))
        L_eq = clahe.apply(L_u8)
        return (L_eq.astype(np.float32) / 2.55)
    except ImportError:
        try:
            from skimage.exposure import equalize_adapthist  # type: ignore
            L_norm = np.clip(L_channel / 100.0, 0.0, 1.0)
            L_eq = equalize_adapthist(L_norm, kernel_size=None, clip_limit=0.02)
            return (L_eq * 100.0).astype(np.float32)
        except ImportError:
            L_min, L_max = L_channel.min(), L_channel.max()
            if L_max - L_min < 1.0:
                return L_channel
            return ((L_channel - L_min) / (L_max - L_min) * 100.0).astype(np.float32)


def _grid_cell_std(L: np.ndarray, grid: int = _GRADIENT_GRID) -> float:
    """Std of per-cell mean L* across an NxN grid — diagnostic only, no
    longer gates whether flat-fielding runs. Kept so real-scan values keep
    accumulating in the logs for future calibration of the gain clamp."""
    h, w = L.shape
    cell_h, cell_w = h // grid, w // grid
    means = []
    for i in range(grid):
        y0 = i * cell_h
        y1 = (i + 1) * cell_h if i < grid - 1 else h
        for j in range(grid):
            x0 = j * cell_w
            x1 = (j + 1) * cell_w if j < grid - 1 else w
            means.append(float(np.mean(L[y0:y1, x0:x1])))
    return float(np.std(means))


def _flat_field_correct(L: np.ndarray) -> np.ndarray:
    """Estimate the slow-varying illumination field via a large-kernel blur
    and divide it out, re-centering on the field's own mean so overall
    brightness is preserved. Runs unconditionally — on an already-uniform
    image the estimated illum field is nearly flat, so gain stays close to
    1.0 everywhere and this is close to a no-op by construction."""
    h, w = L.shape
    sigma = max(h, w) * _FLATFIELD_SIGMA_FRAC
    illum = cv2.GaussianBlur(L, (0, 0), sigmaX=sigma)
    illum = np.clip(illum, 1e-3, None)
    gain = np.mean(illum) / illum
    gain = np.clip(gain, _FLATFIELD_MIN_GAIN, _FLATFIELD_MAX_GAIN)
    return np.clip(L * gain, 0.0, 100.0)


def normalize_lighting(image: Image.Image) -> Image.Image:
    """
    Stage 2 — Lighting normalization.

    1. Dark-floor bail-out (unchanged, runs first, on raw L*).
    2. Flat-field correction — ALWAYS runs (no gate). Removes broad spatial
       gradients via Gaussian-blur illumination estimation; near-no-op on
       already-uniform images because the gain clamp keeps corrections
       small when the illumination field is already flat.
    3. CLAHE or gamma correction on the flattened L* to hit the target
       mean, branch driven by post-flatten variance.

    A* and B* are never modified.
    """
    img_rgb = image.convert("RGB")
    arr = np.asarray(img_rgb, dtype=np.float32)
    lab = _rgb_to_lab(arr)
    L = lab[:, :, 0]

    global LAST_STAGE2_MODE
    if "fallback" not in LAST_STAGE2_MODE:
        LAST_STAGE2_MODE = "classical"

    mean_L = float(np.mean(L))

    # Dark floor bail-out — must run before any flat-field logic, on the
    # raw unmodified image.
    if mean_L < _DARK_FLOOR_L:
        sys.stderr.write(
            f"[Stage2] mean_L={mean_L:.2f} below dark floor "
            f"({_DARK_FLOOR_L}) — skipping normalization\n"
        )
        sys.stderr.flush()
        return img_rgb

    grid_std = _grid_cell_std(L)  # diagnostic only, no longer a gate
    L = _flat_field_correct(L)

    mean_L2 = float(np.mean(L))
    std_L2 = float(np.std(L))
    grid_std_post = _grid_cell_std(L)   # NEW: residual gradient check

    sys.stderr.write(
        f"[DEBUG Stage 2] lighting_normalizer: engine={LAST_STAGE2_MODE}, "
        f"grid_std_before={grid_std:.2f}, grid_std_after={grid_std_post:.2f}, "
        f"mean_L_before={mean_L:.2f}, mean_L_after={mean_L2:.2f}, "
        f"branch={'CLAHE' if (std_L2 > 30.0 or grid_std_post > _RESIDUAL_GRADIENT_THRESHOLD) else 'gamma'}\n"
    )
    sys.stderr.flush()

    if abs(mean_L2 - _TARGET_L) <= _L_TOLERANCE:
        return img_rgb

    preserve_mean_l = os.environ.get("STAGE2_PRESERVE_MEAN_L", "1").strip().lower() in ("1", "true", "yes")
    if preserve_mean_l:
        # Flat-fielding has already eliminated spatial illumination gradients across the soil
        # while preserving authentic soil lightness (mean L*). Bypassing forced L*=50 gamma shift
        # prevents dark soils (e.g. 5YR 2.5/1) from having their Value artificially inflated from 2.5/3 to 5/6.
        lab[:, :, 0] = L
        rgb_out = _lab_to_rgb(lab)
        return Image.fromarray(rgb_out, mode="RGB")

    if std_L2 > 30.0 or grid_std_post > _RESIDUAL_GRADIENT_THRESHOLD:
        lab[:, :, 0] = _apply_clahe_on_l(L)
    else:
        L_norm = np.clip(L / 100.0, 1e-6, 1.0)
        if mean_L2 < _TARGET_L:
            gamma = np.log(_TARGET_L / 100.0) / np.log(mean_L2 / 100.0 + 1e-9)
            gamma = float(np.clip(gamma, 0.3, 1.0))
        else:
            gamma = np.log(_TARGET_L / 100.0) / np.log(mean_L2 / 100.0 + 1e-9)
            gamma = float(np.clip(gamma, 1.0, 3.0))
        L_corrected = (L_norm ** gamma) * 100.0
        lab[:, :, 0] = L_corrected.astype(np.float32)

    rgb_out = _lab_to_rgb(lab)
    return Image.fromarray(rgb_out, mode="RGB")



def normalize_exposure(image: Image.Image, target_y: float = 0.184) -> Image.Image:
    """
    Stage 2 — Linear-light exposure normalization.
    Converts sRGB to linear light, scales channels uniformly to match
    target middle-gray luminance (Y ≈ 0.184, corresponding to L* = 50.0),
    and converts back to sRGB. Preserves exact RGB chromaticity ratios.
    """
    img_rgb = image.convert("RGB")
    arr = np.asarray(img_rgb, dtype=np.float32)
    lin = _srgb_to_linear(arr)
    # CIE 1931 linear luminance Y
    y = 0.2126 * lin[:, :, 0] + 0.7152 * lin[:, :, 1] + 0.0722 * lin[:, :, 2]
    mean_y = float(np.mean(y))
    if mean_y < 1e-5:
        return img_rgb
    scale = target_y / mean_y
    lin_scaled = np.clip(lin * scale, 0.0, 1.0)
    srgb = _linear_to_srgb(lin_scaled)
    rgb_out = np.clip(srgb * 255.0, 0, 255).astype(np.uint8)
    return Image.fromarray(rgb_out, mode="RGB")


def flatten_lighting(
    image: Image.Image,
    blur_radius: float = 60.0,
    target_L: float = _TARGET_L,
    dark_floor_L: float = _DARK_FLOOR_L,
    gamma_trim: bool = True,
) -> Image.Image:
    """
    Stage 2 — Local illumination flattening (blur-and-divide).

    Corrects spatially uneven lighting (corner shadows, one-sided glare,
    vignetting) that global gamma cannot fix because the mean L* is fine
    overall even though parts of the image are too dark / too bright.

    Algorithm:
      1. Convert to LAB; isolate L* channel.
      2. Gaussian-blur L* heavily → this is the estimated illumination
         envelope (slow, smooth lighting bias across the frame).
      3. Divide pixel-wise L* by the blurred envelope and rescale so the
         mean L* maps to target_L — this removes the spatial bias.
      4. Optionally apply a single mild gamma pass to bring the global
         mean to exactly target_L (gamma_trim=True by default).
      5. A/B channels are never modified.

    Parameters:
        image       : PIL Image (any mode; converted to RGB internally).
        blur_radius : Gaussian sigma in pixels. Larger = coarser illumination
                      model. 60 px is a good default for 200–400 px crops.
        target_L    : Desired mean L* after correction (default 50.0).
        dark_floor_L: Images with mean L* below this are returned unchanged
                      (same guard as normalize_lighting).
        gamma_trim  : If True, apply a final gamma pass to hit target_L exactly.

    Returns:
        PIL Image in RGB mode with spatially uniform lighting.
    """
    from scipy.ndimage import gaussian_filter  # type: ignore

    global LAST_STAGE2_MODE
    LAST_STAGE2_MODE = "flatten"

    img_rgb = image.convert("RGB")
    arr = np.asarray(img_rgb, dtype=np.float32)

    lab = _rgb_to_lab(arr)
    L = lab[:, :, 0].copy()  # shape (H, W), values 0–100

    mean_L = float(np.mean(L))
    sys.stderr.write(
        f"[Stage2/flatten] mean_L={mean_L:.2f} blur_radius={blur_radius:.1f}\n"
    )
    sys.stderr.flush()

    # Very dark soil — never brighten
    if mean_L < dark_floor_L:
        sys.stderr.write("[Stage2/flatten] dark floor hit — returning raw image\n")
        sys.stderr.flush()
        return img_rgb

    # ── Step 1: estimate illumination envelope via Gaussian blur ──────────────
    # Large sigma captures only the slow lighting gradient; fine texture is
    # preserved because we're only modelling the coarse illumination bias.
    # A small epsilon floor prevents division-by-zero in deep-shadow corners.
    envelope = gaussian_filter(L, sigma=blur_radius).astype(np.float32)
    envelope = np.clip(envelope, 1.0, 100.0)   # floor at 1 L* unit

    # ── Step 2: divide out the illumination bias ──────────────────────────────
    # After division L_flat has a spatially flat illumination profile.
    # We rescale so the mean maps to target_L.
    L_flat = L / envelope                       # remove spatial gradient
    current_mean = float(np.mean(L_flat))
    if current_mean < 1e-6:
        return img_rgb                           # degenerate image guard
    scale = target_L / current_mean             # map mean → target_L
    L_corrected = np.clip(L_flat * scale, 0.0, 100.0).astype(np.float32)

    # ── Step 3 (optional): light gamma trim to nail target_L exactly ──────────
    if gamma_trim:
        post_mean = float(np.mean(L_corrected))
        if abs(post_mean - target_L) > 1.0 and post_mean > 1e-6:
            L_norm = np.clip(L_corrected / 100.0, 1e-6, 1.0)
            gamma = np.log(target_L / 100.0) / np.log(post_mean / 100.0 + 1e-9)
            gamma = float(np.clip(gamma, 0.3, 3.0))
            L_corrected = np.clip(
                (L_norm ** gamma) * 100.0, 0.0, 100.0
            ).astype(np.float32)
            sys.stderr.write(
                f"[Stage2/flatten] gamma_trim applied: gamma={gamma:.3f}\n"
            )
            sys.stderr.flush()

    lab[:, :, 0] = L_corrected
    rgb_out = _lab_to_rgb(lab)
    return Image.fromarray(rgb_out, mode="RGB")


def normalize_lighting_with_promptnorm(
    image: Image.Image,
    service_url: str = None,
    timeout_s: float = 10.0,
) -> Image.Image:
    """
    Stage 2 — ML-sidecar enhanced lighting normalization.
    Sends PNG to the sidecar, with automatic classical fallback and explicit stderr logs.
    """
    import io
    import os
    import sys
    global LAST_STAGE2_MODE

    if not service_url:
        service_url = os.environ.get("PROMPTNORM_SIDECAR_URL", "http://127.0.0.1:8500/normalize")
    if not service_url.endswith("/normalize"):
        service_url = service_url.rstrip("/") + "/normalize"

    try:
        import requests as _requests
    except ImportError:
        sys.stderr.write("[Stage2] 'requests' not installed; used classical fallback\n")
        sys.stderr.flush()
        LAST_STAGE2_MODE = "classical (promptnorm fallback)"
        return normalize_lighting(image)

    img_rgb = image.convert("RGB")

    # Encode as lossless PNG to avoid JPEG re-compression chroma artifacts
    buf = io.BytesIO()
    img_rgb.save(buf, format="PNG")
    buf.seek(0)

    try:
        resp = _requests.post(
            service_url,
            files={"file": ("image.png", buf, "image/png")},
            timeout=timeout_s,
        )
        resp.raise_for_status()

        result_img = Image.open(io.BytesIO(resp.content)).convert("RGB")
        sys.stderr.write(f"[Stage2] Successfully used sidecar ({service_url})\n")
        sys.stderr.flush()
        LAST_STAGE2_MODE = "promptnorm"
        return result_img

    except Exception as exc:
        sys.stderr.write(f"[Stage2] Sidecar unavailable at {service_url} ({exc}); used classical fallback\n")
        sys.stderr.flush()
        LAST_STAGE2_MODE = "classical (promptnorm fallback)"
        return normalize_lighting(image)


def _call_lighting_sidecar(
    image: "Image.Image",
    service_url: str,
    timeout_s: float,
    label: str,
) -> "Image.Image":
    """
    Shared helper: POST an image to a lighting-normalization sidecar and return
    the normalized PIL Image. Falls back to normalize_lighting() on any error.
    """
    import io
    global LAST_STAGE2_MODE

    try:
        import requests as _requests
    except ImportError:
        sys.stderr.write(f"[Stage2/{label}] 'requests' not installed; used classical fallback\n")
        sys.stderr.flush()
        LAST_STAGE2_MODE = f"classical ({label.lower()} fallback)"
        return normalize_lighting(image)

    img_rgb = image.convert("RGB")
    buf = io.BytesIO()
    img_rgb.save(buf, format="PNG")   # lossless PNG — avoids extra chroma compression artifacts
    buf.seek(0)

    try:
        resp = _requests.post(
            service_url,
            files={"file": ("image.png", buf, "image/png")},
            timeout=timeout_s,
        )
        resp.raise_for_status()
        result = Image.open(io.BytesIO(resp.content)).convert("RGB")
        sys.stderr.write(f"[Stage2] used sidecar ({label} @ {service_url})\n")
        sys.stderr.flush()
        LAST_STAGE2_MODE = label.lower()
        return result
    except Exception as exc:
        sys.stderr.write(
            f"[Stage2] sidecar unavailable ({label} @ {service_url}: {exc}); used classical fallback\n"
        )
        sys.stderr.flush()
        LAST_STAGE2_MODE = f"classical ({label.lower()} fallback)"
        return normalize_lighting(image)



def normalize_lighting_with_dinolight(
    image: Image.Image,
    service_url: str = "http://127.0.0.1:8501/normalize/dinolight",
    timeout_s: float = 30.0,
) -> Image.Image:
    """
    Stage 2 — DINOLight sidecar normalization.

    Sends the image as PNG to the DINOLight FastAPI sidecar and returns the
    normalized result. Falls back to normalize_lighting() (classical gamma/CLAHE)
    automatically if the sidecar is not running or returns an error.

    The sidecar is expected to be running lighting_service.py on port 8501.
    Start it with:  python python/lighting_service.py

    Parameters:
        image      : PIL Image (any mode; converted to RGB internally).
        service_url: Sidecar endpoint. Override to test different ports/hosts.
        timeout_s  : Request timeout. DINOLight inference is slower than gamma;
                     30 s is conservative but safe for CPU-only setups.
    """
    return _call_lighting_sidecar(image, service_url, timeout_s, "DINOLight")


def normalize_lighting_with_omnilight(
    image: Image.Image,
    service_url: str = "http://127.0.0.1:8501/normalize/omnilight",
    timeout_s: float = 30.0,
) -> Image.Image:
    """
    Stage 2 — OmniLight sidecar normalization.

    Same call contract as normalize_lighting_with_dinolight() but routed to the
    /normalize/omnilight endpoint. Falls back to normalize_lighting() on any error.

    Parameters:
        image      : PIL Image (any mode; converted to RGB internally).
        service_url: Sidecar endpoint.
        timeout_s  : Request timeout.
    """
    return _call_lighting_sidecar(image, service_url, timeout_s, "OmniLight")


def generate_stage2_comparison(image: Image.Image) -> tuple:
    """
    Stage 2 — 4-Image Lighting Normalization Comparison.

    Creates a 2x2 grid image (410x410) with 10px dark separator:
      - Panel 1 (Top-Left): Low-light / Underexposed input (brightness 0.5x)
      - Panel 2 (Top-Right): Low-light normalized (L* centered on 50.0)
      - Panel 3 (Bottom-Left): Harsh-sun / Overexposed input (brightness 1.6x)
      - Panel 4 (Bottom-Right): Harsh-sun normalized (L* centered on 50.0)

    Returns:
      (grid_image: PIL.Image.Image, explanation_dict: dict)
    """
    from PIL import ImageEnhance

    img_rgb = image.convert("RGB")
    thumb = img_rgb.resize((200, 200), Image.Resampling.BILINEAR)

    dark = ImageEnhance.Brightness(thumb).enhance(0.5)
    dark_norm = normalize_lighting(dark)

    bright = ImageEnhance.Brightness(thumb).enhance(1.6)
    bright_norm = normalize_lighting(bright)

    # Calculate mean L* for each quadrant
    dark_arr = np.asarray(dark, dtype=np.float32)
    dark_norm_arr = np.asarray(dark_norm, dtype=np.float32)
    bright_arr = np.asarray(bright, dtype=np.float32)
    bright_norm_arr = np.asarray(bright_norm, dtype=np.float32)

    dark_L = float(np.mean(_rgb_to_lab(dark_arr)[:, :, 0]))
    dark_norm_L = float(np.mean(_rgb_to_lab(dark_norm_arr)[:, :, 0]))
    bright_L = float(np.mean(_rgb_to_lab(bright_arr)[:, :, 0]))
    bright_norm_L = float(np.mean(_rgb_to_lab(bright_norm_arr)[:, :, 0]))

    # Compose 410x410 2x2 grid with 10px dark separator
    grid = Image.new("RGB", (410, 410), (30, 30, 30))
    grid.paste(dark, (0, 0))
    grid.paste(dark_norm, (210, 0))
    grid.paste(bright, (0, 210))
    grid.paste(bright_norm, (210, 210))

    explanation = {
        "image_1_top_left": {
            "title": "Low-Light / Underexposed Input",
            "description": f"Simulated low-light capture condition (brightness reduced by 50%). Shows dark illumination with mean L* = {dark_L:.1f}.",
            "mean_L": round(dark_L, 2),
            "condition": "underexposed"
        },
        "image_2_top_right": {
            "title": "Low-Light Normalized (L* 50% Center)",
            "description": f"The low-light image corrected by Stage 2 Lighting Normalizer. L* lightness channel is centered to 50.0 (mean L* = {dark_norm_L:.1f}) while preserving authentic soil hue and chroma.",
            "mean_L": round(dark_norm_L, 2),
            "condition": "normalized"
        },
        "image_3_bottom_left": {
            "title": "Harsh-Sun / Overexposed Input",
            "description": f"Simulated harsh-sun capture condition (brightness increased by 160%). Shows washed-out glare with mean L* = {bright_L:.1f}.",
            "mean_L": round(bright_L, 2),
            "condition": "overexposed"
        },
        "image_4_bottom_right": {
            "title": "Harsh-Sun Normalized (L* 50% Center)",
            "description": f"The harsh-sun image corrected by Stage 2 Lighting Normalizer. Excess lightness is pulled back to the 50.0 baseline (mean L* = {bright_norm_L:.1f}), eliminating glare distortion.",
            "mean_L": round(bright_norm_L, 2),
            "condition": "normalized"
        }
    }

    import io, base64
    buf = io.BytesIO()
    grid.save(buf, format="JPEG", quality=90)
    explanation["image_data"] = "data:image/jpeg;base64," + base64.b64encode(buf.getvalue()).decode("ascii")

    return grid, explanation


