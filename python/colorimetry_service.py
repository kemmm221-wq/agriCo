#!/usr/bin/env python3
"""
colorimetry_service.py

Drop-in replacement/extension for your existing colorimetry_service.py.

CONTRACT (unchanged from your existing script, so PHP's
SoilMLAnalysisService::callPythonMicroservice() does not need to change
how it invokes this script):

    Input:  image path as sys.argv[1]  (called via shell_exec from PHP)
    Output: a single JSON object printed to stdout

What's new: this script now also
    1. classifies the image as real_soil or fake_soil (catch-all for non-soil)
    2. normalizes lighting before color analysis (Stage 2)
    3. detects multiple soil color regions in the image (Stage 3)
    4. re-validates each region for real soil vs. fake (Stage 4)
    5. matches each region to the Munsell soil-color chart (Stage 5)
    6. cross-checks each region against stored Real_Soil reference images (Stage 6)
    7. saves the image into the correct Scan_Soil_Image/... folder
    8. writes a .json metadata sidecar
    9. indexes the scan into a SQLite database for training/reindex use

JSON output schema (this is what PHP receives from shell_exec, and what
you should pass straight through to React Native as the API response
body — plain JSON keys work fine with fetch()/Axios in RN, no special
casing is required):

{
  "success": true,
  "label": "real_soil" | "fake_soil",
  "confidence": 0.87,
  "munsell": {
    "hue": "10R",
    "value": 4,
    "chroma": 3,
    "notation": "10R 4/3",
    "color_delta_e": 3.21
  },
  "regions": [
    {
      "munsell": "10YR 4/3",
      "label": "olive brown",
      "proportion": 0.62,
      "region_thumbnail": "<base64 PNG>",
      "verification_confidence": 0.91,
      "status": "verified"
    }
  ],
  "rgb": { "r": 120, "g": 90, "b": 60 },
  "texture_score": 0.43,
  "image_id": "9f3a1c02b1e04e2b8f8a...",
  "stored_path": "/abs/path/Scan_Soil_Image/Real_Soil/10R/10R 4/3/real_soil_10R4-3_....jpg",
  "source": "user_scan"
}

On error, prints:
{ "success": false, "error": "<message>" }
and exits with a non-zero status code, so PHP's shell_exec error handling
(check exit code / empty stdout) keeps working the same way it does today.

Environment variables (override defaults without touching code):
    SCAN_SOIL_ROOT        - path to Scan_Soil_Image/ (default: ./Scan_Soil_Image)
    MUNSELL_REFERENCE_CSV - path to the Munsell reference table CSV
                             (default: ./munsell_reference_full.csv —
                             replace with your full official table)
    SCAN_SOURCE           - "user_scan" (default) or "seed_dataset",
                             tag stored with each scan for training splits
"""

import json
import os
import sys
import tempfile
import time

# Allow running this script from any working directory.
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))


def _env(name: str, default: str) -> str:
    return os.environ.get(name, default)


def emit_progress(scan_id: str | None, stage: int, label: str, status: str, extra: dict | None = None) -> None:
    if not scan_id:
        return
    event = {"stage": stage, "label": label, "status": status, "ts": time.time()}
    if extra:
        event.update(extra)

    tmp_dir = tempfile.gettempdir()
    progress_path = os.path.join(tmp_dir, f"scan_progress_{scan_id}.json")

    history = []
    try:
        if os.path.exists(progress_path):
            with open(progress_path, "r") as f:
                history = json.load(f)
                if not isinstance(history, list):
                    history = []
    except (OSError, json.JSONDecodeError):
        history = []

    history.append(event)

    tmp_path = progress_path + ".tmp"
    try:
        with open(tmp_path, "w") as f:
            json.dump(history, f)
        os.replace(tmp_path, progress_path)
    except OSError:
        pass


def resolve_status(record, result) -> str:
    """
    Safely resolves status from record (dict or object) and in-memory result.
    Falls back to 'unclassified' if status is missing or null.
    """
    rec_status = record.get("status") if isinstance(record, dict) else getattr(record, "status", None)
    return str(rec_status or getattr(result, "status", None) or "unclassified")


def build_output(record, result, api_regions: list) -> dict:
    """
    Builds the final API output dictionary from record, result, and api_regions.
    Handles record being a dict, custom object, or minimal structure safely.
    """
    final_status = resolve_status(record, result)

    def _get(source, key, default=None):
        if source is None:
            return default
        if isinstance(source, dict):
            return source.get(key, default)
        return getattr(source, key, default)

    predicted_label = _get(record, "predicted_label", getattr(result, "label", "fake_soil"))
    confidence = _get(record, "confidence", getattr(result, "confidence", 0.0))
    image_id = _get(record, "image_id", "")
    stored_path = _get(record, "file_path", "")
    source_name = _get(record, "source", "")

    munsell_hue = _get(record, "munsell_hue", None)
    munsell_value = _get(record, "munsell_value", None)
    munsell_chroma = _get(record, "munsell_chroma", None)
    munsell_notation = _get(record, "munsell_notation", None)
    color_delta_e = _get(record, "color_delta_e", None)

    rgb = getattr(result, "rgb", (0, 0, 0))
    texture_score = getattr(result, "texture_score", 0.0)

    out = {
        "success": True,
        "label": predicted_label,
        "confidence": confidence,
        "status": final_status,
        "rgb": {
            "r": rgb[0],
            "g": rgb[1],
            "b": rgb[2]
        },
        "munsell": {
            "hue": munsell_hue,
            "value": munsell_value,
            "chroma": munsell_chroma,
            "notation": munsell_notation,
            "color_delta_e": color_delta_e,
        } if predicted_label == "real_soil" else None,
        "regions": api_regions,
        "texture_score": texture_score,
    }
    if _get(record, "stage2_normalized_path"):
        out["stage2_normalized_path"] = _get(record, "stage2_normalized_path")
    if _get(record, "stage2_normalized_color"):
        out["stage2_normalized_color"] = _get(record, "stage2_normalized_color")
    if _get(record, "stage2_comparison"):
        out["stage2_comparison"] = _get(record, "stage2_comparison")

    stage2_mode = getattr(result, "stage2_mode", None) or _get(record, "stage2_mode", "classical")
    out["stage2_mode"] = stage2_mode
    out["stage2_engine"] = stage2_mode

    out.update({
        "image_id": image_id,
        "stored_path": stored_path,
        "source": source_name,
    })
    return out


def main() -> int:
    import argparse
    parser = argparse.ArgumentParser(description="AgriCo Soil Colorimetry Service")
    parser.add_argument("image_path", help="Path to soil image file")
    parser.add_argument("--scan-id", dest="scan_id", default=None,
                        help="UUID for progress/result tracking (optional)")
    parser.add_argument(
        "--use-promptnorm", dest="use_promptnorm", action="store_true",
        help="Use PromptNorm ML sidecar for Stage 2 lighting normalization "
             "(falls back to CLAHE/gamma normalizer if sidecar is unreachable)",
    )
    args = parser.parse_args()

    image_path = args.image_path
    scan_id = args.scan_id  # None when not provided
    use_promptnorm = args.use_promptnorm

    if not os.path.isfile(image_path):
        print(json.dumps({"success": False, "error": f"Image not found: {image_path}"}))
        return 1

    default_scan_root = os.path.join(os.path.dirname(os.path.abspath(__file__)), "Scan_Soil_Image")
    scan_root = _env("SCAN_SOIL_ROOT", default_scan_root)
    default_csv = os.path.join(os.path.dirname(os.path.abspath(__file__)), "munsell_reference_full.csv")
    if not os.path.isfile(default_csv):
        default_csv = os.path.join(os.path.dirname(os.path.abspath(__file__)), "munsell_reference_sample.csv")
    munsell_csv = _env("MUNSELL_REFERENCE_CSV", default_csv)
    source = _env("SCAN_SOURCE", "user_scan")

    try:
        from munsell_lookup import MunsellTable          # noqa: E402
        from soil_classifier import classify_soil        # noqa: E402
        from storage import SoilScanStore                # noqa: E402

        munsell_table = MunsellTable(munsell_csv, scan_root=scan_root)

        # Build a progress callback that writes scan_progress_{id}.json after each stage
        def _progress(stage, label, status, extra=None):
            emit_progress(scan_id, stage, label, status, extra)

        result = classify_soil(
            image_path,
            munsell_table,
            progress_fn=_progress,
            use_promptnorm=use_promptnorm,
        )

        # Stage 1 Soil Gate Rejection Short-Circuit
        if getattr(result, "label", "fake_soil") != "real_soil":
            reject_msg = "No valid soil sample detected. Please align the camera directly over genuine soil."
            emit_progress(
                scan_id,
                1,
                "Soil Gate",
                "rejected",
                {"reason": "non_soil_detected", "message": reject_msg}
            )
            output = {
                "success": True,
                "label": "fake_soil",
                "rejected": True,
                "status": "rejected",
                "message": reject_msg,
                "confidence": getattr(result, "confidence", 0.0),
                "texture_score": getattr(result, "texture_score", 0.0),
                "rgb": {"r": 0, "g": 0, "b": 0},
                "regions": [],
            }
            if scan_id:
                tmp_dir = tempfile.gettempdir()
                result_path = os.path.join(tmp_dir, f"scan_result_{scan_id}.json")
                tmp_path = result_path + ".tmp"
                try:
                    with open(tmp_path, "w") as f:
                        json.dump(output, f)
                    os.replace(tmp_path, result_path)
                except OSError:
                    pass
            print(json.dumps(output))
            return 0

        store = SoilScanStore(root_dir=scan_root)
        record = store.save_scan(image_path, result, source=source)

        # ── Build regions for API response ───────────────────────────────
        # Strip pixel_mask (not JSON serializable) and region_image (PIL object)
        # from each region before serialising.
        api_regions = []
        has_lighting_warning = False
        for r in result.regions:
            rgb = r.get("rgb", (0, 0, 0))
            hex_color = "#{:02X}{:02X}{:02X}".format(rgb[0], rgb[1], rgb[2]) if isinstance(rgb, (tuple, list)) and len(rgb) >= 3 else "#808080"
            lightness_spread = float(r.get("lightness_spread", 0.0))
            reg_lighting_warning = bool(lightness_spread >= 35.0 or r.get("lighting_warning", False))
            if reg_lighting_warning:
                has_lighting_warning = True

            api_regions.append({
                "munsell": r.get("munsell", "NA"),
                "label": r.get("label", ""),
                "proportion": r.get("proportion", 0),
                "region_thumbnail": r.get("region_thumbnail", ""),
                "verification_confidence": r.get("verification_confidence", 0),
                "status": r.get("status", "needs_review"),
                "color_delta_e": r.get("color_delta_e", -1),
                "gate_confidence": r.get("gate_confidence", 1.0),
                "hex": hex_color,
                "bounding_box": r.get("bounding_box"),
                "box": r.get("box"),
                "shades": r.get("shades", []),
                "lightness_spread": lightness_spread,
                "merged_from": r.get("merged_from", []),
                "lighting_warning": reg_lighting_warning,
                "overlay_color": r.get("overlay_color"),
                "overlay_hex": r.get("overlay_hex"),
                "overlay_color_name": r.get("overlay_color_name"),
            })

        output = build_output(record, result, api_regions)
        if has_lighting_warning:
            output["lighting_warning"] = True

        # Directly save Stage 2 normalized image into Laravel public scans storage if scan_id provided
        norm_img = getattr(result, "normalized_image", None)
        if scan_id and norm_img is not None:
            try:
                laravel_scans_dir = os.path.join(
                    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                    "storage", "app", "public", "scans"
                )
                os.makedirs(laravel_scans_dir, exist_ok=True)
                norm_dest_file = os.path.join(laravel_scans_dir, f"scan_{scan_id}_normalized.jpg")
                primary_dest_file = os.path.join(laravel_scans_dir, f"scan_{scan_id}.jpg")
                norm_img.save(norm_dest_file, quality=95)
                norm_img.save(primary_dest_file, quality=95)
                output["stage2_normalized_path"] = norm_dest_file
                output["stage2_normalized_url"] = f"/storage/scans/scan_{scan_id}_normalized.jpg"
                output["normalized_image_url"] = f"/storage/scans/scan_{scan_id}_normalized.jpg"
                sys.stderr.write(f"[INFO] Saved stage 2 normalized scan image to {norm_dest_file}\n")

                # Generate Before & After comparison image like test_promptnorm_soil.py
                try:
                    from PIL import Image, ImageDraw, ImageFont
                    raw_pil = Image.open(image_path).convert("RGB")
                    rw, rh = raw_pil.size
                    max_w = 1200
                    if rw > max_w:
                        scale = max_w / float(rw)
                        rw = int(rw * scale)
                        rh = int(rh * scale)
                        raw_pil = raw_pil.resize((rw, rh), Image.LANCZOS)

                    norm_resized = norm_img.convert("RGB").resize((rw, rh), Image.LANCZOS)
                    bar_h = max(30, int(rh * 0.08))
                    canvas = Image.new("RGB", (rw * 2, rh + bar_h), (18, 30, 24))
                    canvas.paste(raw_pil, (0, bar_h))
                    canvas.paste(norm_resized, (rw, bar_h))

                    draw = ImageDraw.Draw(canvas)
                    font_size = max(13, int(bar_h * 0.45))
                    font = None
                    for font_candidate in [
                        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
                        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
                        "/usr/share/fonts/truetype/freefont/FreeSansBold.ttf",
                        "DejaVuSans.ttf",
                        "arial.ttf",
                    ]:
                        try:
                            font = ImageFont.truetype(font_candidate, font_size)
                            break
                        except Exception:
                            continue
                    if font is None:
                        try:
                            font = ImageFont.load_default(size=font_size)
                        except Exception:
                            font = ImageFont.load_default()

                    draw.text((12, int(bar_h * 0.22)), "BEFORE", fill=(255, 200, 100), font=font)
                    after_label = f"AFTER (NORMALIZED) -- {result.munsell_notation or ''}".strip()
                    draw.text((rw + 12, int(bar_h * 0.22)), after_label, fill=(142, 242, 178), font=font)

                    # Green ROI box is shown via the client-side HTML overlay only.
                    # No box is baked into the server image — this prevents the box
                    # from ever appearing on the AFTER (right) side of the comparison.

                    ba_dest_file = os.path.join(laravel_scans_dir, f"scan_{scan_id}_before_after.jpg")
                    canvas.save(ba_dest_file, quality=92)
                    output["before_after_image_path"] = ba_dest_file
                    output["before_after_image_url"] = f"/storage/scans/scan_{scan_id}_before_after.jpg"
                    sys.stderr.write(f"[INFO] Saved before/after comparison image to {ba_dest_file}\n")

                    # Generate bright color segmentation overlay (smoothed pebbles/clumps)
                    try:
                        from color_detector import create_segmentation_overlay
                        overlay_img = create_segmentation_overlay(norm_img, result.regions, alpha=0.42)
                        overlay_dest_file = os.path.join(laravel_scans_dir, f"scan_{scan_id}_overlay.jpg")
                        overlay_img.save(overlay_dest_file, quality=92)
                        output["overlay_image_path"] = overlay_dest_file
                        output["overlay_image_url"] = f"/storage/scans/scan_{scan_id}_overlay.jpg"
                        sys.stderr.write(f"[INFO] Saved segmentation overlay image to {overlay_dest_file}\n")
                    except Exception as e_ov:
                        sys.stderr.write(f"[WARN] Could not generate segmentation overlay image: {e_ov}\n")
                except Exception as e_ba:
                    sys.stderr.write(f"[WARN] Could not generate before/after comparison image: {e_ba}\n")
            except Exception as e_save:
                sys.stderr.write(f"[WARN] Failed to save normalized scan image to Laravel storage: {e_save}\n")

        sys.stderr.write(
            f"[INFO] Classified {image_path} -> Label: {result.label}, "
            f"Munsell: {result.munsell_notation}, Texture: {result.texture_score}, "
            f"DeltaE: {result.color_delta_e}, Regions: {len(api_regions)}\n"
        )

        # Write final result file so the PHP poll endpoint can deliver it
        if scan_id:
            tmp_dir = tempfile.gettempdir()
            result_path = os.path.join(tmp_dir, f"scan_result_{scan_id}.json")
            tmp_path = result_path + ".tmp"
            try:
                with open(tmp_path, "w") as f:
                    json.dump(output, f)
                os.replace(tmp_path, result_path)
            except OSError:
                pass
            emit_progress(
                scan_id,
                6,
                "Agronomic Profile & Reference Centroid",
                "done",
                {"matched_reference": getattr(result, "munsell_notation", "NA")},
            )

        print(json.dumps(output))
        return 0

    except Exception as exc:  # noqa: BLE001 - surface any failure as clean JSON
        emit_progress(scan_id, 0, "Pipeline Error", "error", {"error": str(exc)})
        sys.stderr.write(f"[ERROR] colorimetry_service failed: {exc}\n")
        print(json.dumps({"success": False, "error": str(exc)}))
        return 1


if __name__ == "__main__":
    sys.exit(main())
