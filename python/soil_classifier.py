"""
soil_classifier.py

Orchestrates the full 6-stage soil scan pipeline:

  Stage 1 — Soil Gate + Fake_Soil record similarity check
             (soil_gate.py → predict_is_soil)
  Stage 2 — Lighting normalization
             (lighting_normalizer.py → normalize_lighting)
  Stage 3 — Multi-color detection
             (color_detector.py → detect_color_regions)
  Stage 4 — Per-region zoom + real-vs-fake re-check
             (region_checker.py → filter_real_regions)
  Stage 5 — Per-region Munsell matching
             (munsell_region_matcher.py → match_regions)
  Stage 6 — ML cross-check against Real_Soil records
             (real_soil_verifier.py → verify_regions)

The returned ClassificationResult preserves all top-level fields for backward
compatibility (label, confidence, munsell_notation, rgb, texture_score) derived
from the dominant (highest-proportion) verified region. The full per-region
result set is exposed in the new `regions` field.

Note: 'color_only' is preserved in schemas for historical record compatibility,
      but all non-real-soil scans are categorized as 'fake_soil'.
"""

import os
import sys
import time
import traceback
from dataclasses import dataclass, field
from typing import List, Optional, Tuple

import numpy as np

from munsell_lookup import MunsellTable, dominant_rgb
from soil_gate import predict_is_soil


@dataclass
class ClassificationResult:
    label: str              # "real_soil" | "fake_soil" (historical: "color_only")
    confidence: float       # 0.0 - 1.0
    munsell_hue: str
    munsell_value: float
    munsell_chroma: float
    munsell_notation: str   # e.g. "10R 4/3"
    color_delta_e: float    # distance to nearest known soil chip
    texture_score: float = 0.0
    rgb: Tuple[int, int, int] = (0, 0, 0)
    regions: List[dict] = field(default_factory=list)   # per-region results (Stages 3–6)
    status: str = "needs_review"        # from dominant region's Stage 6 status
    normalized_image: Optional[object] = None  # Stage 2 lighting-normalized image (PIL Image)
    stage2_mode: str = "classical"       # Stage 2 normalization mode used (e.g. promptnorm, classical)
    color_map_png: Optional[str] = None  # Stage 3 multi-color segmentation overlay PNG (base64)
    rejected_regions: List[dict] = field(default_factory=list) # Regions rejected by Stage 4 patch gate


def _texture_score(image_path: str) -> float:
    """
    Returns a 0.0-1.0 texture score. ~0 = perfectly flat/uniform color,
    higher = more visual texture/variance.
    """
    from PIL import Image
    import numpy as np

    img = Image.open(image_path).convert("L")
    arr = np.asarray(img, dtype="float32")
    std = arr.std()
    score = min(std / 40.0, 1.0)
    return float(score)


def classify_soil(
    image_path: str,
    munsell_table: MunsellTable,
    texture_threshold: float = 0.08,
    soil_gamut_delta_e: float = 14.0,
    progress_fn=None,
    # ── Stage 2 mode ─────────────────────────────────────────────────────────
    # Preferred: pass stage2_mode explicitly or set STAGE2_MODE in the env.
    # Values: 'classical' | 'promptnorm' | 'dinolight' | 'omnilight' | 'flatten'
    # Deprecated flags kept for backward compatibility during the transition.
    stage2_mode: Optional[str] = None,
    use_promptnorm: bool = False,   # deprecated — use stage2_mode='promptnorm'
    use_flattennorm: bool = False,  # deprecated — use stage2_mode='flatten'
) -> ClassificationResult:
    """
    Full 6-stage pipeline orchestrator.

    progress_fn(stage, label, status, extra): called at start/end of each stage.
      Pass None to disable. status values: 'running', 'done', 'rejected'.

    Stage 1: Soil gate — validates the image is genuine soil.
             Short-circuits to fake_soil immediately on rejection.

    Stages 2–6 run only if Stage 1 passes.

    Stage 2: Lighting normalization (LAB L-channel correction).
             Mode is resolved (highest priority first):
               stage2_mode arg  → explicit caller override
               STAGE2_MODE env  → deployment config (.env / PHP config)
               use_promptnorm   → deprecated boolean flag (maps to 'promptnorm')
               use_flattennorm  → deprecated boolean flag (maps to 'flatten')
               default          → 'classical' (gamma / CLAHE)
    Stage 3: Multi-color palette detection (ColorThief + pixel assignment).
    Stage 4: Per-region zoom re-check (soil gate + Fake_Soil SSIM).
    Stage 5: Per-region Munsell matching (dataset-driven centroid).
    Stage 6: ML cross-check against Real_Soil reference images (SSIM).

    Top-level label / confidence / munsell_* fields are derived from the
    dominant (highest-proportion) verified region for backward compatibility.
    """
    from PIL import Image
    from color_detector import DEFAULT_ROI_FRACTION, _apply_region_crop
    from munsell_lookup import dominant_rgb_from_image

    if isinstance(image_path, (str, os.PathLike)):
        original_img = Image.open(image_path).convert("RGB")
    else:
        original_img = image_path.convert("RGB")
    roi_img = _apply_region_crop(original_img, DEFAULT_ROI_FRACTION)

    # Hardware check
    try:
        import torch
        cuda_avail = torch.cuda.is_available()
        device_name = torch.cuda.get_device_name(0) if cuda_avail else "CPU (torch installed)"
        sys.stderr.write(f"[HARDWARE] torch.cuda.is_available={cuda_avail}, device={device_name}\n")
    except ImportError:
        sys.stderr.write("[HARDWARE] torch not imported / pipeline running on lightweight scikit-learn/numpy/PIL stack\n")

    pipeline_start = time.perf_counter()

    try:
        rgb = dominant_rgb_from_image(roi_img)
    except Exception:
        rgb = dominant_rgb(image_path)
    texture = _texture_score(image_path)

    # ── Stage 1: Soil Gate ──────────────────────────────────────────────────────
    t_stage1 = time.perf_counter()
    _prog = progress_fn  # local alias for brevity
    if _prog: _prog(1, "Soil Gate", "running", None)
    # Check both the full provided image and the center ROI.
    # When the user provides a viewfinder crop, original_img contains the chosen
    # context (which may have fissures/cracks across the center), while for raw
    # full-frame uploads roi_img avoids non-soil background edges.
    is_soil_full, conf_full = predict_is_soil(original_img)
    is_soil_roi, conf_roi = predict_is_soil(roi_img)

    if is_soil_full:
        is_soil, gate_conf = True, conf_full
        roi_used = "full_image"
    elif is_soil_roi:
        is_soil, gate_conf = True, conf_roi
        roi_used = "center_roi"
    else:
        is_soil, gate_conf = False, max(conf_full, conf_roi)
        roi_used = "none"

    stage1_elapsed = time.perf_counter() - t_stage1
    sys.stderr.write(f"[DEBUG Stage 1] soil_gate: is_soil={is_soil}, probability={gate_conf:.3f}, ROI_used={roi_used} (full_prob={conf_full:.3f}, roi_prob={conf_roi:.3f})\n")
    sys.stderr.write(f"[TIMING] Stage 1 (Soil Gate): {stage1_elapsed:.3f}s (is_soil={is_soil}, conf={gate_conf:.3f}, full={is_soil_full}:{conf_full:.3f}, roi={is_soil_roi}:{conf_roi:.3f})\n")
    sys.stderr.flush()

    # Stage 1 live dataset comparison (precomputed cache; never reads raw dataset images).
    # STAGE1_LIVE_MODE: off | shadow (log only, default) | veto (may reject)
    _live_mode = os.environ.get("STAGE1_LIVE_MODE", "shadow").lower()
    live = None
    if _live_mode != "off":
        t_live = time.perf_counter()
        try:
            from soil_gate import compare_to_reference
            live = compare_to_reference(original_img)
        except Exception as exc:
            sys.stderr.write(f"[Stage1] live comparison error (ignored): {exc}\n")
        sys.stderr.write(f"[TIMING] Stage 1 live-compare: {time.perf_counter() - t_live:.4f}s mode={_live_mode} live={live}\n")
        _veto_margin = float(os.environ.get("STAGE1_VETO_MARGIN", "0.10"))
        _veto_max_gate = float(os.environ.get("STAGE1_VETO_MAX_GATE_CONF", "0.85"))
        if (_live_mode == "veto" and is_soil and live
                and live["margin"] <= -_veto_margin and gate_conf < _veto_max_gate):
            is_soil, gate_conf = False, round(live["fake_sim"], 3)

    if not is_soil:
        if _prog: _prog(1, "Soil Gate", "rejected", {"reason": "non_soil_detected"})
        return ClassificationResult(
            label="fake_soil",
            confidence=round(gate_conf, 3),
            munsell_hue="NA",
            munsell_value=-1,
            munsell_chroma=-1,
            munsell_notation="NA",
            color_delta_e=-1.0,
            texture_score=round(texture, 3),
            rgb=rgb,
            regions=[],
        )
    if _prog: _prog(1, "Soil Gate", "done", None)

    # ── Stage 2: Lighting Normalization ────────────────────────────────────────
    t_stage2 = time.perf_counter()
    if _prog: _prog(2, "Lighting Normalizer", "running", None)

    # Resolve stage2_mode (priority: arg > env > deprecated booleans > default)
    _mode = (
        stage2_mode
        or os.environ.get("STAGE2_MODE")
        or ("promptnorm"  if use_promptnorm  else None)
        or ("flatten"     if use_flattennorm else None)
        or "classical"
    ).lower()

    try:
        if _mode == "dinolight":
            from lighting_normalizer import normalize_lighting_with_dinolight  # noqa: E402
            normalized_img = normalize_lighting_with_dinolight(original_img)
        elif _mode == "omnilight":
            from lighting_normalizer import normalize_lighting_with_omnilight  # noqa: E402
            normalized_img = normalize_lighting_with_omnilight(original_img)
        elif _mode == "promptnorm":
            from lighting_normalizer import normalize_lighting_with_promptnorm  # noqa: E402
            normalized_img = normalize_lighting_with_promptnorm(original_img)
        elif _mode == "flatten":
            from lighting_normalizer import flatten_lighting  # noqa: E402
            normalized_img = flatten_lighting(original_img)
        else:  # 'classical' or anything unrecognised
            from lighting_normalizer import normalize_lighting  # noqa: E402
            normalized_img = normalize_lighting(original_img)
    except Exception as exc:
        sys.stderr.write(f"[Stage2] Lighting normalization failed, using raw image: {exc}\n")
        normalized_img = original_img

    stage2_elapsed = time.perf_counter() - t_stage2
    try:
        import lighting_normalizer
        _actual_mode = getattr(lighting_normalizer, "LAST_STAGE2_MODE", _mode)
    except Exception:
        _actual_mode = _mode

    if _actual_mode == _mode:
        sys.stderr.write(f"[TIMING] Stage 2 mode={_actual_mode} elapsed={stage2_elapsed:.3f}s\n")
    else:
        sys.stderr.write(f"[TIMING] Stage 2 mode={_actual_mode} (requested={_mode}) elapsed={stage2_elapsed:.3f}s\n")

    if _prog: _prog(2, "Lighting Normalizer", "done", None)

    time.sleep(0.08)

    # Strictly recalculate dominant RGB from Stage 2 normalized image (never use raw image color)
    from munsell_lookup import dominant_rgb_from_image
    try:
        rgb = dominant_rgb_from_image(normalized_img)
    except Exception:
        pass

    # ── Stage 3: Multi-color Detection ─────────────────────────────────────────
    t_stage3 = time.perf_counter()
    if _prog: _prog(3, "Multi-Color Detection", "running", None)
    try:
        from color_detector import detect_color_regions  # noqa: E402
        color_regions = detect_color_regions(normalized_img)
    except Exception as exc:
        sys.stderr.write(f"[Stage3] Color detection failed, falling back to single region: {exc}\n")
        color_regions = []

    if not color_regions:
        from munsell_lookup import dominant_rgb_from_image
        try:
            norm_rgb = dominant_rgb_from_image(normalized_img)
        except Exception:
            norm_rgb = rgb
        color_regions = [{
            "rgb": norm_rgb,
            "proportion": 1.0,
            "pixel_mask": None,
            "color_index": 0,
        }]
    stage3_elapsed = time.perf_counter() - t_stage3
    proportions_summary = ", ".join([f"R{idx}: {r.get('proportion', 0):.1%}" for idx, r in enumerate(color_regions)])
    sys.stderr.write(f"[DEBUG Stage 3] color_detector: palette_regions={len(color_regions)}, proportions=[{proportions_summary}]\n")
    sys.stderr.write(f"[TIMING] Stage 3 (Color Detection): {stage3_elapsed:.3f}s (regions={len(color_regions)})\n")
    sys.stderr.flush()
    if _prog: _prog(3, "Multi-Color Detection", "done", {"region_count": len(color_regions)})

    # ── Stage 4: Per-region real-vs-fake re-check ─────────────────────────────
    t_stage4 = time.perf_counter()
    scan_root = munsell_table.scan_root or os.path.join(os.getcwd(), "Scan_Soil_Image")
    fake_soil_dir = os.path.join(scan_root, "Fake_Soil")
    if _prog: _prog(4, "Per-Region Verification", "running", None)
    stage4_rejected = []
    try:
        from region_checker import filter_real_regions  # noqa: E402
        res = filter_real_regions(original_img, color_regions, fake_soil_dir)
        if isinstance(res, tuple) and len(res) == 2:
            real_regions, stage4_rejected = res
        else:
            real_regions = res
            stage4_rejected = [r for r in color_regions if not r.get("is_real_region", False)]
    except Exception as exc:
        sys.stderr.write(f"[Stage4] Region filter failed, using all color regions: {exc}\n")
        for r in color_regions:
            r.setdefault("gate_confidence", gate_conf)
            r.setdefault("is_real_region", True)
            r.setdefault("verified", True)
            r.setdefault("region_image", original_img)
        real_regions = color_regions
        stage4_rejected = []
    stage4_elapsed = time.perf_counter() - t_stage4
    sys.stderr.write(f"[TIMING] Stage 4 (Per-Region Verification): {stage4_elapsed:.3f}s (real_regions={len(real_regions)}, rejected={len(stage4_rejected)})\n")

    if not real_regions:
        if _prog: _prog(4, "Per-Region Verification", "rejected", {"reason": "all_regions_rejected"})
        from munsell_lookup import dominant_rgb_from_image
        try:
            norm_rgb = dominant_rgb_from_image(normalized_img)
        except Exception:
            norm_rgb = rgb
        chip, delta_e = munsell_table.nearest(norm_rgb)
        return ClassificationResult(
            label="fake_soil",
            confidence=round(gate_conf, 3),
            munsell_hue="NA",
            munsell_value=-1,
            munsell_chroma=-1,
            munsell_notation="NA",
            color_delta_e=round(delta_e, 3),
            texture_score=round(texture, 3),
            rgb=norm_rgb,
            regions=[],
        )
    if _prog:
        bboxes = [r.get("bounding_box") for r in real_regions if r.get("bounding_box")]
        _prog(4, "Per-Region Verification", "done", {"bounding_boxes": bboxes})
    time.sleep(0.08)

    # ── Stage 5: Per-region Munsell matching ─────────────────────────────────────
    t_stage5 = time.perf_counter()
    if _prog: _prog(5, "Munsell Spectral Match", "running", None)
    try:
        from munsell_region_matcher import match_regions  # noqa: E402
        matched_regions = match_regions(real_regions, munsell_table)
    except Exception as exc:
        sys.stderr.write(f"[Stage5] Munsell matching failed: {exc}\n")
        matched_regions = []
    stage5_elapsed = time.perf_counter() - t_stage5
    matches_summary = ", ".join([f"R{idx}: {r.get('munsell')} ({r.get('label')})" for idx, r in enumerate(matched_regions)])
    sys.stderr.write(f"[DEBUG Stage 5] munsell_region_matcher: matched_count={len(matched_regions)}, matches=[{matches_summary}]\n")
    sys.stderr.write(f"[TIMING] Stage 5 (Munsell Match): {stage5_elapsed:.3f}s (matched={len(matched_regions)})\n")
    sys.stderr.flush()
    if _prog: _prog(5, "Munsell Spectral Match", "done", None)
    time.sleep(0.08)

    # ── Stage 5.5: Munsell Shade Merging ─────────────────────────────────────────
    t_merge = time.perf_counter()
    combined_overlay_b64 = None
    try:
        from region_merge import merge_munsell_regions  # noqa: E402
        from color_detector import render_color_map, extract_blobs, OVERLAY_PALETTE, OVERLAY_COLOR_NAMES  # noqa: E402
        pre_merge_count = len(matched_regions)
        matched_regions = merge_munsell_regions(matched_regions, normalized_img)
        merge_elapsed = time.perf_counter() - t_merge
        sys.stderr.write(
            f"[TIMING] Stage 5.5 (Munsell Merge): {merge_elapsed:.3f}s "
            f"(regions: {pre_merge_count} -> {len(matched_regions)})\n"
        )

        # Rebuild full-size int16 label map from final merged pixel_masks
        H_norm, W_norm = normalized_img.size[1], normalized_img.size[0]
        final_group = np.zeros((H_norm, W_norm), dtype=np.int16)
        n_final = len(matched_regions)
        for gi, r in enumerate(matched_regions):
            mask = r.get("pixel_mask")
            if mask is not None:
                final_group[mask] = gi

        # Draw rejected regions as index n_final (drawn in gray #969696)
        for rej in stage4_rejected:
            r_mask = rej.get("pixel_mask")
            if r_mask is not None:
                final_group[r_mask] = n_final

        combined_overlay_b64, per_color_masks_b64 = render_color_map(final_group, n_final, alpha=0.5)

        # Overwrite each region's mask_png, blobs, overlay_hex, overlay_color, overlay_color_name
        for gi, r in enumerate(matched_regions):
            pal_rgb = OVERLAY_PALETTE[gi % len(OVERLAY_PALETTE)]
            r["overlay_color"] = pal_rgb
            r["overlay_hex"] = f"#{pal_rgb[0]:02x}{pal_rgb[1]:02x}{pal_rgb[2]:02x}"
            r["overlay_color_name"] = OVERLAY_COLOR_NAMES[gi % len(OVERLAY_COLOR_NAMES)]
            if gi < len(per_color_masks_b64):
                r["mask_png"] = per_color_masks_b64[gi]
            if r.get("pixel_mask") is not None:
                r["blobs"] = extract_blobs(r["pixel_mask"])

        for rej in stage4_rejected:
            rej["overlay_color"] = (150, 150, 150)
            rej["overlay_hex"] = "#969696"
            rej["overlay_color_name"] = "Gray"
    except Exception as exc:
        sys.stderr.write(f"[Stage 5.5] Munsell merge error, keeping matched regions: {exc}\n{traceback.format_exc()}\n")
    sys.stderr.flush()

    # ── Stage 6: ML cross-check ────────────────────────────────────────────────
    t_stage6 = time.perf_counter()
    if _prog: _prog(6, "Agronomic Profile & Reference Centroid", "running", None)
    try:
        from real_soil_verifier import verify_regions  # noqa: E402
        final_regions = verify_regions(matched_regions, scan_root)
    except Exception as exc:
        sys.stderr.write(f"[Stage6] Verification failed, skipping cross-check: {exc}\n")
        for r in matched_regions:
            r.setdefault("verification_confidence", 0.5)
            r.setdefault("status", "needs_review")
        final_regions = matched_regions
    stage6_elapsed = time.perf_counter() - t_stage6
    total_elapsed = time.perf_counter() - pipeline_start
    verif_summary = ", ".join([f"R{idx}: conf={r.get('verification_confidence', 0):.2f}, status={r.get('status')}" for idx, r in enumerate(final_regions)])
    sys.stderr.write(f"[DEBUG Stage 6] real_soil_verifier: verified_count={len(final_regions)}, results=[{verif_summary}]\n")
    sys.stderr.write(f"[TIMING] Stage 6 (ML Cross-Check): {stage6_elapsed:.3f}s | TOTAL: {total_elapsed:.3f}s\n")
    sys.stderr.flush()

    # ── Build top-level summary from dominant region ───────────────────────
    dominant = final_regions[0] if final_regions else None

    if dominant:
        dom_hue = dominant.get("hue", "NA")
        dom_value = float(dominant.get("value", -1))
        dom_chroma = float(dominant.get("chroma", -1))
        dom_notation = dominant.get("munsell", "NA")
        dom_delta_e = float(dominant.get("color_delta_e", -1.0))
        dom_status = dominant.get("status", "needs_review")
        verification_conf = float(dominant.get("verification_confidence", 0.5))

        # Color confidence combines gate, texture, and color gamut proximity
        color_conf = max(0.0, 1.0 - (dom_delta_e / soil_gamut_delta_e)) if dom_delta_e >= 0 else 0.5
        confidence = round(
            min(1.0, (
                0.35 * gate_conf
                + 0.20 * min(texture / 0.08, 1.0)
                + 0.25 * color_conf
                + 0.20 * verification_conf
            )), 3
        )
        has_soil_texture = texture >= texture_threshold
        in_soil_gamut = dom_delta_e <= soil_gamut_delta_e if dom_delta_e >= 0 else True
        # NOTE: label stays real_soil vs fake_soil based on texture/gamut only,
        # same as before — a low Stage 6 score does NOT flip this to fake_soil.
        # It lowers confidence and surfaces dom_status="needs_review" instead,
        # so a genuine soil sample that just lacks strong reference coverage
        # isn't wrongly rejected outright.
        top_label = "real_soil" if (has_soil_texture and in_soil_gamut) else "fake_soil"
    else:
        dom_hue, dom_value, dom_chroma, dom_notation, dom_delta_e = "NA", -1, -1, "NA", -1.0
        dom_status = "needs_review"
        confidence = round(gate_conf, 3)
        top_label = "fake_soil"

    final_rgb = dominant.get("rgb", rgb) if dominant else rgb
    color_map_b64 = combined_overlay_b64 if combined_overlay_b64 else (color_regions[0].get("color_map_png") if color_regions else None)
    rejected_regions = stage4_rejected if stage4_rejected is not None else [r for r in color_regions if not r.get("is_real_region", False)]

    result = ClassificationResult(
        label=top_label,
        confidence=confidence,
        munsell_hue=dom_hue if top_label == "real_soil" else "NA",
        munsell_value=dom_value if top_label == "real_soil" else -1,
        munsell_chroma=dom_chroma if top_label == "real_soil" else -1,
        munsell_notation=dom_notation if top_label == "real_soil" else "NA",
        color_delta_e=round(dom_delta_e, 3),
        texture_score=round(texture, 3),
        rgb=final_rgb,
        regions=final_regions,
        status=dom_status,
        normalized_image=normalized_img,
        stage2_mode=_actual_mode,
        color_map_png=color_map_b64,
        rejected_regions=rejected_regions,
    )
    if _prog:
        _prog(6, "Agronomic Profile & Reference Centroid", "running", {
            "matched_reference": dom_notation if top_label == "real_soil" else "NA",
        })

    return result
