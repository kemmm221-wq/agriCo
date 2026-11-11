<?php

namespace App\Services;

use App\Models\LocationMapping;
use App\Models\MunsellColor;
use App\Models\Soil;
use App\Models\SoilScan;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class SoilMLAnalysisService
{
    public function __construct(
        protected SoilScanService        $fallbackService,
        protected SoilValidationService  $validator
    ) {}

    /**
     * Analyze soil image via ML Model (Option B) with automatic fallback to Option A (SoilScanService).
     *
     * Stage 1's ML gate (soil_gate.py, trained on Fake_Soil/ + Real_Soil/) is the sole
     * soil/not-soil gatekeeper. Tier A–D (SoilValidationService) has been removed from
     * this path — the classifier alone decides acceptance or rejection, and the controller
     * enforces the soft-reject (422) when label !== 'real_soil'.
     *
     * SoilValidationService is retained in the constructor and file but is no longer called
     * here. Remove it entirely once Stage 1 is confirmed reliable end-to-end.
     *
     * @param mixed $imageInput UploadedFile, file path, or base64 string
     * @return array Analysis metrics
     */
    public function analyzeImage(mixed $imageInput, ?string $scanId = null): array
    {
        // Tier A–D removed — Stage 1's ML gate (soil_gate.py) is now the sole gatekeeper.
        // $this->validator->validate($imageInput);

        $mlEndpoint = config('services.soil_ml.endpoint', env('SOIL_ML_API_URL'));

        if ($mlEndpoint && is_string($mlEndpoint) && filter_var($mlEndpoint, FILTER_VALIDATE_URL)) {
            try {
                $mlResult = $this->callMlInferenceApi($mlEndpoint, $imageInput);
                if ($mlResult && isset($mlResult['success']) && $mlResult['success'] === true) {
                    $mlResult['engine'] = 'Option B (TFLite / Machine Learning Model)';
                    return $mlResult;
                }
            } catch (\Throwable $e) {
                Log::warning('Soil ML Inference API unavailable or failed. Falling back to Python Microservice / Option A. Error: ' . $e->getMessage());
            }
        }

        $scanId = $scanId ?: (string) \Illuminate\Support\Str::uuid();

        // Try local Python Microservice Pipeline (non-blocking; result read from scan_result_{id}.json)
        $this->callPythonMicroservice($imageInput, $scanId);

        // Return a sentinel so the controller knows polling is needed
        return ['success' => true, 'async' => true, 'scan_id' => $scanId];
    }

    /**
     * Resolve the scan status from Python result, falling back to dominant region or 'unclassified'.
     */
    public function resolveStatus(array $pythonResult): string
    {
        $rawStatus = $pythonResult['status'] ?? ($pythonResult['regions'][0]['status'] ?? null);
        return (!empty($rawStatus) && is_string($rawStatus)) ? $rawStatus : 'unclassified';
    }

    /**
     * Build the enriched analysis payload from Python result and base fallback analysis.
     */
    public function buildAnalysis(array $pythonResult, array $baseAnalysis = []): array
    {
        $analysis = $baseAnalysis;

        if (isset($pythonResult['label'])) {
            $analysis['label'] = $pythonResult['label'];
            $analysis['classification'] = $pythonResult['label'];
        }
        $deltaE = $pythonResult['munsell']['color_delta_e'] ?? ($pythonResult['color_delta_e'] ?? ($pythonResult['delta_e'] ?? null));
        if ($deltaE === null && !empty($pythonResult['regions'][0]['color_delta_e'])) {
            $deltaE = $pythonResult['regions'][0]['color_delta_e'];
        }
        if ($deltaE !== null && is_numeric($deltaE) && (float)$deltaE >= 0) {
            $nearScore = round(max(65.0, min(90.0, 90.0 - ((float)$deltaE * 1.25))), 1);
            $analysis['confidence'] = $nearScore;
            $analysis['confidence_formatted'] = $nearScore . '%';
            $analysis['near_match'] = $nearScore . '%';
        } elseif (isset($pythonResult['confidence']) && (float)$pythonResult['confidence'] <= 1.0) {
            $nearScore = 84.0;
            $analysis['confidence'] = $nearScore;
            $analysis['confidence_formatted'] = $nearScore . '%';
            $analysis['near_match'] = $nearScore . '%';
        } elseif (isset($pythonResult['confidence'])) {
            $analysis['confidence'] = round($pythonResult['confidence'], 1);
            $analysis['confidence_formatted'] = $analysis['confidence'] . '%';
        }
        if (isset($pythonResult['munsell']['notation'])) {
            $analysis['munsell_code'] = $pythonResult['munsell']['notation'];
            $analysis['munsell_details'] = $pythonResult['munsell'];
        }
        if (isset($pythonResult['image_id'])) {
            $analysis['dataset_image_id'] = $pythonResult['image_id'];
        }
        if (isset($pythonResult['stored_path'])) {
            $analysis['stored_dataset_path'] = $pythonResult['stored_path'];
        }
        if (isset($pythonResult['stage2_comparison'])) {
            $analysis['stage2_comparison'] = $pythonResult['stage2_comparison'];
        }
        if (isset($pythonResult['stage2_normalized_path'])) {
            $analysis['stage2_normalized_path'] = $pythonResult['stage2_normalized_path'];
        }
        if (isset($pythonResult['stage2_normalized_color'])) {
            $analysis['stage2_normalized_color'] = $pythonResult['stage2_normalized_color'];
        }
        if (isset($pythonResult['stage2_mode'])) {
            $analysis['stage2_mode'] = $pythonResult['stage2_mode'];
            $analysis['stage2_engine'] = $pythonResult['stage2_mode'];
        }
        $analysis['status'] = $this->resolveStatus($pythonResult);

        // ── regions[] — new multi-color result set (Stages 3–6) ──────────────
        // If the Python script emits a non-empty regions array, use it directly.
        // Backward compat: if absent (old script version), synthesize a single
        // region from the flat munsell key so the API shape is always consistent.
        if (!empty($pythonResult['regions']) && is_array($pythonResult['regions'])) {
            $analysis['regions'] = array_map(function ($r) use ($analysis) {
                $munsell = $r['munsell'] ?? ($r['notation'] ?? '');
                $hex = null;
                if (!empty($r['rgb']) && is_array($r['rgb']) && count($r['rgb']) >= 3) {
                    $hex = sprintf('#%02x%02x%02x', $r['rgb'][0], $r['rgb'][1], $r['rgb'][2]);
                }
                if (!$hex && $munsell) {
                    $mc = \App\Models\MunsellColor::where('full_code', $munsell)->first();
                    $hex = $mc?->hex_code;
                }
                $r['hex'] = $hex ?: ($analysis['hex'] ?? '#8F694E');
                $proportion = (float)($r['proportion'] ?? 1.0);
                $r['percent'] = round($proportion * 100, 1);

                $cleanMunsell = trim($munsell);
                $mc = !empty($cleanMunsell) ? \App\Models\MunsellColor::whereRaw('UPPER(TRIM(full_code)) = ?', [strtoupper($cleanMunsell)])->first() : null;
                $soil = !empty($cleanMunsell) ? \App\Models\Soil::whereRaw('UPPER(TRIM(full_code)) = ?', [strtoupper($cleanMunsell)])->first() : null;

                $colorName = $mc?->color_name ?? $soil?->effective_soil_name ?? ($r['label'] ?? 'Determined Soil');
                $r['munsell_code'] = $cleanMunsell;
                $r['code'] = $cleanMunsell;
                $r['full_code'] = $cleanMunsell;
                $r['color_name'] = $colorName;
                $r['name'] = $colorName;
                $r['organic_matter'] = $soil?->effective_organic_matter ?? ($mc?->organic_matter ?? 'Moderate (2.2% – 3.7%)');
                $r['soil_type'] = $soil?->effective_soil_type ?? ($mc?->soil_type ?? 'Agricultural Loam');
                $r['description'] = (!empty($mc?->description) ? trim($mc->description) : null)
                    ?? (!empty($soil?->effective_description) ? trim($soil->effective_description) : null)
                    ?? "{$cleanMunsell} - {$colorName}";

                $rDeltaE = isset($r['color_delta_e']) && is_numeric($r['color_delta_e']) ? (float)$r['color_delta_e'] : 3.0;
                $regNearScore = round(max(65.0, min(90.0, 90.0 - ($rDeltaE * 1.25))), 1);
                $r['confidence'] = $regNearScore;
                $r['confidence_formatted'] = $regNearScore . '%';
                $r['near_match'] = $regNearScore . '%';

                return $r;
            }, $pythonResult['regions']);
        } elseif (isset($pythonResult['munsell']['notation'])) {
            // Older script — wrap flat munsell result as a single legacy region
            $legacyDeltaE = isset($pythonResult['munsell']['color_delta_e']) && is_numeric($pythonResult['munsell']['color_delta_e']) ? (float)$pythonResult['munsell']['color_delta_e'] : 3.0;
            $legacyNearScore = round(max(65.0, min(90.0, 90.0 - ($legacyDeltaE * 1.25))), 1);
            $analysis['regions'] = [[
                'munsell'                 => $pythonResult['munsell']['notation'],
                'label'                   => $pythonResult['label'] ?? 'unknown',
                'proportion'              => 1.0,
                'percent'                 => 100.0,
                'hex'                     => $analysis['hex'] ?? '#8F694E',
                'region_thumbnail'        => '',
                'verification_confidence' => null,
                'status'                  => 'legacy',
                'color_delta_e'           => $pythonResult['munsell']['color_delta_e'] ?? null,
                'gate_confidence'         => $pythonResult['confidence'] ?? null,
                'confidence'              => $legacyNearScore,
                'confidence_formatted'    => $legacyNearScore . '%',
                'near_match'              => $legacyNearScore . '%',
            ]];
        } else {
            $analysis['regions'] = [];
        }

        $hasLightingWarning = !empty($pythonResult['lighting_warning']);
        if (!$hasLightingWarning && !empty($analysis['regions'])) {
            foreach ($analysis['regions'] as $reg) {
                if (!empty($reg['lighting_warning']) || (isset($reg['lightness_spread']) && (float)$reg['lightness_spread'] >= 35.0)) {
                    $hasLightingWarning = true;
                    break;
                }
            }
        }
        if ($hasLightingWarning) {
            $analysis['lighting_warning'] = true;
        }

        return $analysis;
    }

    /**
     * Call external TFLite / Python ML Inference REST API.
     */
    protected function callMlInferenceApi(string $endpoint, mixed $imageInput): ?array
    {
        $response = Http::timeout(4)
            ->attach('image', is_string($imageInput) ? $imageInput : file_get_contents($imageInput->getRealPath()), 'soil_sample.jpg')
            ->post($endpoint);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Bridge call to python/colorimetry_service.py microservice script.
     *
     * Runs Python non-blocking (fire-and-forget). Python writes progress to
     * /tmp/scan_progress_{scanId}.json and the final result to
     * /tmp/scan_result_{scanId}.json. The controller reads those files via
     * the scanProgress() endpoint instead of waiting for stdout.
     */
    public function callPythonMicroservice(mixed $imageInput, ?string $scanId = null): void
    {
        $scriptPath = base_path('python/colorimetry_service.py');
        if (!file_exists($scriptPath)) {
            return;
        }

        $tempFile = null;
        try {
            $inputArg = '';
            if (is_string($imageInput)) {
                if (file_exists($imageInput)) {
                    $inputArg = $imageInput;
                } elseif (preg_match('/^data:image\/(\w+);base64,/', $imageInput, $m)) {
                    $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                    $data = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageInput));
                    $tempFile = tempnam(sys_get_temp_dir(), 'soil_scan_') . '.' . $ext;
                    file_put_contents($tempFile, $data);
                    $inputArg = $tempFile;
                }
            } elseif ($imageInput instanceof \Illuminate\Http\UploadedFile) {
                $ext = $imageInput->getClientOriginalExtension() ?: 'jpg';
                $safeScanId = $scanId ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $scanId) : uniqid('scan_');
                $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'soil_upload_' . $safeScanId . '.' . $ext;
                @copy($imageInput->getRealPath(), $tempFile);
                $inputArg = $tempFile;
            }

            if (empty($inputArg) || !file_exists($inputArg)) {
                return;
            }

            $args = [escapeshellarg($inputArg)];
            if ($scanId) {
                $args[] = '--scan-id';
                $args[] = escapeshellarg($scanId);
            }

            $runner = new PythonProcessRunner();
            $runner->runDetached($scriptPath, $args, $scanId);

        } catch (\Throwable $e) {
            Log::info('Python colorimetry bridge detached-launch skipped: ' . $e->getMessage());
        }
        // Note: $tempFile cannot be deleted here since Python is still running.
        // The OS will eventually reclaim it; for long-running servers consider
        // a scheduled cleanup of /tmp/soil_scan_*.{jpg,png} older than 1 hour.
    }

    /**
     * Persist a Python scan result into Soil + SoilScan tables and return the
     * enriched response array.
     *
     * @param array $pythonResult Raw result dictionary from Python / JSON file
     * @param string $scanId Scan UUID
     * @param string|null $userId Authenticated User ID (passed from caller)
     * @param string $platform 'website' or 'mobile'
     * @param array $locationData Optional geolocation metadata ['latitude', 'longitude', 'barangay', 'location_name']
     * @return array Enriched analysis array or soft-rejection payload
     * @throws AuthenticationException
     */
    public function persistScanResult(
        array $pythonResult,
        string $scanId,
        ?string $userId = null,
        string $platform = 'website',
        array $locationData = []
    ): array {
        // Enforce authentication rule: mobile must never silently fall back to admin
        if ($platform === 'mobile') {
            if (empty($userId) || !User::where('user_id', $userId)->exists()) {
                throw new AuthenticationException('Unauthenticated: A valid user account is required to record mobile scans.');
            }
        } else {
            // For web, fallback to ADM-001 if unauthenticated/missing
            if (empty($userId) || !User::where('user_id', $userId)->exists()) {
                $userId = User::first()?->user_id ?? 'ADM-001';
            }
        }

        // Soft-reject contract: non-soil samples are rejected cleanly
        if (isset($pythonResult['label']) && $pythonResult['label'] !== 'real_soil') {
            return [
                'rejected' => true,
                'success'  => false,
                'error'    => 'NON_SOIL_DETECTED',
                'message'  => 'No valid soil sample detected. Please align the viewfinder over a clear, well-lit soil sample and try again.',
            ];
        }

        // Build the enriched analysis array
        $engine = 'Python Microservice (6-Stage Classification & Munsell Engine)';
        $baseAnalysis = [];
        if (isset($pythonResult['rgb']) && is_array($pythonResult['rgb'])) {
            $rawRgb = $pythonResult['rgb'];
            $normalizedRgb = [
                'r' => (int)($rawRgb['r'] ?? ($rawRgb[0] ?? 0)),
                'g' => (int)($rawRgb['g'] ?? ($rawRgb[1] ?? 0)),
                'b' => (int)($rawRgb['b'] ?? ($rawRgb[2] ?? 0)),
            ];
            $baseAnalysis = $this->fallbackService->analyzeImageFromRgb($normalizedRgb, $engine);
        }
        $analysis = $this->buildAnalysis($pythonResult, $baseAnalysis);

        // Strip appended codes from soil name
        $pureSoilName = trim(preg_replace('/\s*\([A-Za-z0-9\/\.\s]+\)$/i', '', $analysis['soil_name'] ?? ''));
        if (empty($pureSoilName) && !empty($analysis['color_name'])) {
            $pureSoilName = trim(preg_replace('/\s*\([A-Za-z0-9\/\.\s]+\)$/i', '', $analysis['color_name']));
        }
        $analysis['soil_name'] = $pureSoilName ?: 'Scanned Soil';

        $munsellCode = $analysis['munsell_code'] ?? ($analysis['munsell']['notation'] ?? null);
        if ($munsellCode) {
            $cleanCode = strtoupper(trim($munsellCode));
            $mc = \App\Models\MunsellColor::whereRaw('UPPER(TRIM(full_code)) = ?', [$cleanCode])->first();
            $existingSoil = \App\Models\Soil::whereRaw('UPPER(TRIM(full_code)) = ?', [$cleanCode])->first();

            $analysis['full_code'] = $munsellCode;
            $analysis['code'] = $munsellCode;
            if ($mc?->color_name) {
                $analysis['color_name'] = $mc->color_name;
            }
            if (!empty($mc?->hex_code)) {
                $analysis['hex'] = $mc->hex_code;
            }
            if ($existingSoil) {
                $analysis['soil_name'] = (!empty($mc?->color_name) ? trim($mc->color_name) : null) ?? $existingSoil->effective_soil_name;
                $analysis['organic_matter'] = $existingSoil->effective_organic_matter;
                $analysis['soil_type'] = $existingSoil->effective_soil_type;
                $analysis['description'] = (!empty($mc?->description) ? trim($mc->description) : null)
                    ?? $existingSoil->effective_description;
            } elseif ($mc) {
                $analysis['organic_matter'] = $analysis['organic_matter'] ?? ($mc->organic_matter ?? 'Moderate (2.2% – 3.7%)');
                $analysis['soil_type'] = $analysis['soil_type'] ?? ($mc->soil_type ?? 'Agricultural Loam');
                $analysis['description'] = (!empty($mc->description) ? trim($mc->description) : "{$munsellCode} - {$mc->color_name}");
            }
        }

        $regions     = $analysis['regions'] ?? [];
        $isMultiLayer = count($regions) > 1;
        $createdScans = [];
        $soilScan     = null;
        $soil         = null;

        // Resolve or verify public web-accessible path for this scan
        $publicScansDir = storage_path('app/public/scans');
        if (!is_dir($publicScansDir)) {
            @mkdir($publicScansDir, 0755, true);
        }

        $scanJpgName  = "scans/scan_{$scanId}.jpg";
        $fullPublicJpg= storage_path('app/public/' . $scanJpgName);
        $rawJpgName   = "scans/scan_{$scanId}_raw.jpg";
        $fullRawJpg   = storage_path('app/public/' . $rawJpgName);
        $normJpgName  = "scans/scan_{$scanId}_normalized.jpg";
        $fullNormJpg  = storage_path('app/public/' . $normJpgName);
        $baJpgName    = "scans/scan_{$scanId}_before_after.jpg";
        $fullBaJpg    = storage_path('app/public/' . $baJpgName);
        $thumbJpgName = "scans/scan_{$scanId}_thumb.jpg";
        $fullThumbJpg = storage_path('app/public/' . $thumbJpgName);

        // Check for candidate Stage 2 normalized image path
        $candidateNormPath = $pythonResult['stage2_normalized_path']
            ?? $analysis['stage2_normalized_path']
            ?? null;
        if (!file_exists($fullNormJpg) && !empty($candidateNormPath) && file_exists($candidateNormPath)) {
            @copy($candidateNormPath, $fullNormJpg);
        }

        // Check for candidate Before & After comparison image path
        $candidateBaPath = $pythonResult['before_after_image_path']
            ?? $analysis['before_after_image_path']
            ?? null;
        if (!file_exists($fullBaJpg) && !empty($candidateBaPath) && file_exists($candidateBaPath)) {
            @copy($candidateBaPath, $fullBaJpg);
        }

        $rawSource = file_exists($fullRawJpg) ? $fullRawJpg : (file_exists($fullPublicJpg) ? $fullPublicJpg : null);
        $normSource = file_exists($fullNormJpg) ? $fullNormJpg : (file_exists($fullPublicJpg) ? $fullPublicJpg : null);

        // 1. Synthesize Before & After comparison image via GD if missing
        if (!file_exists($fullBaJpg) && $rawSource && $normSource && extension_loaded('gd')) {
            try {
                $imRaw = @imagecreatefromstring((string) file_get_contents($rawSource));
                $imNorm = @imagecreatefromstring((string) file_get_contents($normSource));
                if ($imRaw && $imNorm) {
                    $rw = imagesx($imRaw);
                    $rh = imagesy($imRaw);
                    $maxW = 1000;
                    if ($rw > $maxW) {
                        $scale = $maxW / (float)$rw;
                        $nw = $maxW;
                        $nh = (int)($rh * $scale);
                        $rRes = imagecreatetruecolor($nw, $nh);
                        imagecopyresampled($rRes, $imRaw, 0, 0, 0, 0, $nw, $nh, $rw, $rh);
                        imagedestroy($imRaw);
                        $imRaw = $rRes;
                        $rw = $nw;
                        $rh = $nh;
                    }
                    $nRes = imagecreatetruecolor($rw, $rh);
                    imagecopyresampled($nRes, $imNorm, 0, 0, 0, 0, $rw, $rh, imagesx($imNorm), imagesy($imNorm));
                    imagedestroy($imNorm);
                    $imNorm = $nRes;

                    $barH = max(28, (int)($rh * 0.08));
                    $canvas = imagecreatetruecolor($rw * 2, $rh + $barH);
                    $bg = imagecolorallocate($canvas, 18, 30, 24);
                    imagefilledrectangle($canvas, 0, 0, $rw * 2, $rh + $barH, $bg);
                    imagecopy($canvas, $imRaw, 0, $barH, 0, 0, $rw, $rh);
                    imagecopy($canvas, $imNorm, $rw, $barH, 0, 0, $rw, $rh);

                    // Divider line
                    $divColor = imagecolorallocatealpha($canvas, 255, 255, 255, 64);
                    imageline($canvas, $rw, 0, $rw, $rh + $barH, $divColor);

                    $colorBefore = imagecolorallocate($canvas, 251, 191, 36);
                    $colorAfter = imagecolorallocate($canvas, 142, 242, 178);
                    imagestring($canvas, 4, 10, (int)($barH * 0.2), "BEFORE", $colorBefore);
                    $munsellCode = $analysis['munsell_code'] ?? ($analysis['munsell'] ?? '');
                    imagestring($canvas, 4, $rw + 10, (int)($barH * 0.2), "AFTER (NORMALIZED) " . $munsellCode, $colorAfter);

                    // Draw green ROI square on BEFORE (left half)
                    $targetBox = $regions[0]['bounding_box'] ?? $regions[0]['box'] ?? null;
                    if (!$targetBox) {
                        $targetBox = ['x' => 0.25, 'y' => 0.25, 'w' => 0.5, 'h' => 0.5];
                    }
                    $bx = isset($targetBox['w']) ? (int)($targetBox['x'] * $rw) : (int)($targetBox['x'] ?? (0.25 * $rw));
                    $by = isset($targetBox['h']) ? (int)($barH + ($targetBox['y'] * $rh)) : (int)($barH + ($targetBox['y'] ?? (0.25 * $rh)));
                    $bw = isset($targetBox['w']) ? (int)($targetBox['w'] * $rw) : (int)($targetBox['width'] ?? ($rw * 0.5));
                    $bh = isset($targetBox['h']) ? (int)($targetBox['h'] * $rh) : (int)($targetBox['height'] ?? ($rh * 0.5));
                    $bx = max(0, min($rw - 1, $bx));
                    $by = max($barH, min($rh + $barH - 1, $by));
                    $bw = max(10, min($rw - $bx, $bw));
                    $bh = max(10, min($rh + $barH - $by, $bh));

                    $greenColor = imagecolorallocate($canvas, 74, 222, 128);
                    $darkColor = imagecolorallocate($canvas, 0, 0, 0);
                    imagesetthickness($canvas, 4);
                    imagerectangle($canvas, $bx - 1, $by - 1, $bx + $bw + 1, $by + $bh + 1, $darkColor);
                    imagesetthickness($canvas, 3);
                    imagerectangle($canvas, $bx, $by, $bx + $bw, $by + $bh, $greenColor);

                    @imagejpeg($canvas, $fullBaJpg, 90);
                    imagedestroy($canvas);
                    imagedestroy($imRaw);
                    imagedestroy($imNorm);
                }
            } catch (\Throwable $e_gd) {
                Log::warning('[SoilMLAnalysisService] GD before/after stitch failed: ' . $e_gd->getMessage());
            }
        }

        // 2. Synthesize Thumbnail with green sample square via GD if missing
        if (!file_exists($fullThumbJpg) && ($rawSource || $normSource) && extension_loaded('gd')) {
            try {
                $baseImgSrc = $rawSource ?: $normSource;
                $imThumb = @imagecreatefromstring((string) file_get_contents($baseImgSrc));
                if ($imThumb) {
                    $tw = imagesx($imThumb);
                    $th = imagesy($imThumb);
                    $maxDim = 360;
                    $scale = min($maxDim / $tw, $maxDim / $th, 1.0);
                    $targetTw = (int)($tw * $scale);
                    $targetTh = (int)($th * $scale);
                    $thumbCanvas = imagecreatetruecolor($targetTw, $targetTh);
                    imagecopyresampled($thumbCanvas, $imThumb, 0, 0, 0, 0, $targetTw, $targetTh, $tw, $th);

                    // Draw green ROI marker box
                    $targetBox = $regions[0]['bounding_box'] ?? $regions[0]['box'] ?? null;
                    if (!$targetBox) {
                        $targetBox = ['x' => 0.25, 'y' => 0.25, 'w' => 0.5, 'h' => 0.5];
                    }
                    $bx = isset($targetBox['w']) ? (int)($targetBox['x'] * $targetTw) : (int)($targetBox['x'] ?? (0.25 * $targetTw));
                    $by = isset($targetBox['h']) ? (int)($targetBox['y'] * $targetTh) : (int)($targetBox['y'] ?? (0.25 * $targetTh));
                    $bw = isset($targetBox['w']) ? (int)($targetBox['w'] * $targetTw) : (int)($targetBox['width'] ?? ($targetTw * 0.5));
                    $bh = isset($targetBox['h']) ? (int)($targetBox['h'] * $targetTh) : (int)($targetBox['height'] ?? ($targetTh * 0.5));
                    $bx = max(0, min($targetTw - 1, $bx));
                    $by = max(0, min($targetTh - 1, $by));
                    $bw = max(10, min($targetTw - $bx, $bw));
                    $bh = max(10, min($targetTh - $by, $bh));

                    $greenColor = imagecolorallocate($thumbCanvas, 74, 222, 128);
                    $darkColor = imagecolorallocate($thumbCanvas, 0, 0, 0);
                    imagesetthickness($thumbCanvas, 4);
                    imagerectangle($thumbCanvas, $bx - 1, $by - 1, $bx + $bw + 1, $by + $bh + 1, $darkColor);
                    imagesetthickness($thumbCanvas, 3);
                    imagerectangle($thumbCanvas, $bx, $by, $bx + $bw, $by + $bh, $greenColor);

                    @imagejpeg($thumbCanvas, $fullThumbJpg, 85);
                    imagedestroy($thumbCanvas);
                    imagedestroy($imThumb);
                }
            } catch (\Throwable $e_thumb) {
                Log::warning('[SoilMLAnalysisService] GD thumbnail creation failed: ' . $e_thumb->getMessage());
            }
        }

        $publicScanRelativePath = 'storage/' . $normJpgName;
        $publicBaRelativePath   = file_exists($fullBaJpg) ? ('storage/' . $baJpgName) : null;
        $publicThumbRelativePath= file_exists($fullThumbJpg) ? ('storage/' . $thumbJpgName) : null;

        // 3. Upload all generated assets to Cloudflare R2 / Persistent Storage
        $cloudStorage = app(\App\Services\CloudStorageService::class);
        try {
            if (file_exists($fullNormJpg)) {
                $uploadedNorm = $cloudStorage->uploadFile($fullNormJpg, $normJpgName);
                if ($uploadedNorm) {
                    $publicScanRelativePath = $uploadedNorm;
                }
            } elseif (file_exists($fullPublicJpg)) {
                $uploadedPub = $cloudStorage->uploadFile($fullPublicJpg, $scanJpgName);
                if ($uploadedPub) {
                    $publicScanRelativePath = $uploadedPub;
                }
            }

            if (file_exists($fullBaJpg)) {
                $uploadedBa = $cloudStorage->uploadFile($fullBaJpg, $baJpgName);
                if ($uploadedBa) {
                    $publicBaRelativePath = $uploadedBa;
                }
            }

            if (file_exists($fullThumbJpg)) {
                $uploadedThumb = $cloudStorage->uploadFile($fullThumbJpg, $thumbJpgName);
                if ($uploadedThumb) {
                    $publicThumbRelativePath = $uploadedThumb;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[SoilMLAnalysisService] Cloud storage scan upload failed: ' . $e->getMessage());
        }

        $saveRegionImage = function ($thumbBase64, $idx) use ($scanId, $publicScanRelativePath, $cloudStorage) {
            if (empty($thumbBase64)) {
                return $publicScanRelativePath;
            }
            if (str_starts_with($thumbBase64, 'storage/')) {
                return $thumbBase64;
            }
            if (str_starts_with($thumbBase64, 'http')) {
                if (!str_contains($thumbBase64, 'supabase.co') && !str_contains($thumbBase64, 'agrico-scans')) {
                    return $thumbBase64;
                }
            }
            try {
                $cleaned = preg_replace('/^data:image\/\w+;base64,/', '', $thumbBase64);
                $rawBin = base64_decode($cleaned, true);
                if ($rawBin !== false && strlen($rawBin) > 10) {
                    $ext = str_starts_with($cleaned, 'iVBOR') ? 'png' : 'jpg';
                    $rFileName = "scans/region_{$scanId}_{$idx}.{$ext}";

                    // Save locally for immediate cache
                    $destPath = storage_path('app/public/' . $rFileName);
                    $destDir = dirname($destPath);
                    if (!is_dir($destDir)) {
                        @mkdir($destDir, 0755, true);
                    }
                    @file_put_contents($destPath, $rawBin);

                    // Persist to Cloud Storage
                    if ($cloudStorage) {
                        $uploadedUrl = $cloudStorage->upload($rFileName, $rawBin, $ext === 'png' ? 'image/png' : 'image/jpeg');
                        if ($uploadedUrl) {
                            return $uploadedUrl;
                        }
                    }

                    return 'storage/' . $rFileName;
                }
            } catch (\Throwable $e) {
                Log::warning('[SoilMLAnalysisService] Save region image failed: ' . $e->getMessage());
            }
            return $publicScanRelativePath;
        };

        if ($isMultiLayer) {
            $domR    = $regions[0];
            $domCode = $domR['munsell'] ?? ($domR['munsell_code'] ?? ($analysis['munsell_code'] ?? '10YR 5/3'));
            $domMColor = MunsellColor::where('full_code', $domCode)->first();
            $domName = $domMColor?->color_name ?? ($analysis['soil_name'] ?? 'Dominant Soil Layer');
            $domHue  = $domMColor?->hue ?? ($analysis['hue'] ?? (explode(' ', $domCode)[0] ?? '10YR'));
            $domColorId = $domMColor?->munsell_color_id ?? ($analysis['color_id'] ?? null);
            $domDesc = (!empty($domMColor?->description) ? trim($domMColor->description) : null) ?? ($analysis['description'] ?? "{$domCode} - {$domName}");
            $domPct  = $domR['percent'] ?? (!empty($domR['proportion']) ? round($domR['proportion'] * 100, 1) : null);
            $domDeltaE = isset($domR['color_delta_e']) && is_numeric($domR['color_delta_e']) ? (float)$domR['color_delta_e'] : (isset($analysis['color_delta_e']) ? (float)$analysis['color_delta_e'] : (isset($analysis['delta_e']) ? (float)$analysis['delta_e'] : 3.0));
            $domConf = round(max(88.5, min(99.6, 99.4 - ($domDeltaE * 0.45))), 1) . '%';
            $soil = Soil::firstOrCreate(
                ['full_code' => $domCode],
                ['soil_name' => $domName, 'hue' => $domHue, 'full_code' => $domCode,
                 'description' => $domDesc, 'status' => 'Active', 'scan_count' => 0,
                 'color_id' => $domColorId, 'admin_id' => $userId]
            );
            if (!empty($domMColor?->description) && $soil->getRawOriginal('description') !== trim($domMColor->description)) {
                $soil->update(['description' => trim($domMColor->description), 'color_id' => $domColorId ?: $soil->color_id]);
            }
            $soil->increment('scan_count');
            $analysis['description']    = $soil->effective_description;
            $analysis['soil_name']      = $soil->effective_soil_name;
            $analysis['organic_matter'] = $soil->effective_organic_matter;
            $analysis['soil_type']      = $soil->effective_soil_type;
            $domImgPath = $saveRegionImage($domR['region_thumbnail'] ?? null, 0);
            $dominantScanImage = $publicScanRelativePath ?: ($domImgPath ?: null);

            // Create ONLY ONE SoilScan record for the entire scan session!
            $soilScan = SoilScan::create([
                'user_id' => $userId, 'soil_id' => $soil->soil_id, 'platform' => $platform,
                'scan_note' => "Determined Soil: {$soil->effective_soil_name} ({$domCode})" . ($domPct ? " - Soil Percentage: {$domPct}%" : "") . " - Confidence: {$domConf}",
                'regions_data' => $regions, 'scan_date' => now(),
                'scan_image' => $dominantScanImage,
                'before_after_image' => $publicBaRelativePath,
                'thumbnail_image' => $publicThumbRelativePath ?: $domImgPath,
            ]);

            // Dominant layer entry (index 0)
            $createdScans = [[
                'scan_id' => $soilScan->soil_scan_id,
                'soil_id' => $soil->soil_id,
                'scan_seq' => SoilScan::where('soil_id', $soil->soil_id)->count(),
                'name' => $soil->effective_soil_name, 'color_name' => $soil->effective_soil_name,
                'code' => $domCode, 'hue' => $soil->hue ?? $domHue,
                'hex' => $soil->effective_hex_color ?? ($domR['hex'] ?? '#8F694E'),
                'desc' => $soil->effective_description, 'organic' => $soil->effective_organic_matter,
                'drainage' => $soil->effective_soil_type, 'soil_type' => $soil->effective_soil_type,
                'confidence' => $domConf, 'percent' => $domPct, 'regions' => $regions,
                'image' => $dominantScanImage ? asset($dominantScanImage) : null,
                'scan_image' => $dominantScanImage ? asset($dominantScanImage) : null,
            ]];

            // Process secondary layers for soil catalog without creating extra SoilScan rows
            for ($i = 1; $i < count($regions); $i++) {
                $r = $regions[$i];
                $mCode = $r['munsell'] ?? ($r['munsell_code'] ?? ($r['notation'] ?? null));
                if (!$mCode) continue;
                $mColor = MunsellColor::where('full_code', $mCode)->first();
                $layerName = $mColor?->color_name ?? ($r['label'] ?? 'Soil Layer');
                $layerHue  = $mColor?->hue ?? (explode(' ', $mCode)[0] ?? '10YR');
                $layerColorId = $mColor?->munsell_color_id ?? null;
                $layerDesc = (!empty($mColor?->description) ? trim($mColor->description) : null) ?? ($r['description'] ?? "{$mCode} - {$layerName}");
                $pctVal  = $r['percent'] ?? (!empty($r['proportion']) ? round($r['proportion'] * 100, 1) : null);
                $rDeltaE = isset($r['color_delta_e']) && is_numeric($r['color_delta_e']) ? (float)$r['color_delta_e'] : (isset($analysis['color_delta_e']) ? (float)$analysis['color_delta_e'] : (isset($analysis['delta_e']) ? (float)$analysis['delta_e'] : 3.0));
                $confVal = round(max(88.5, min(99.6, 99.4 - ($rDeltaE * 0.45))), 1) . '%';
                $layerSoil = Soil::firstOrCreate(
                    ['full_code' => $mCode],
                    ['soil_name' => $layerName, 'hue' => $layerHue, 'full_code' => $mCode,
                     'description' => $layerDesc, 'status' => 'Active', 'scan_count' => 0,
                     'color_id' => $layerColorId, 'admin_id' => $userId]
                );
                if (!empty($mColor?->description) && $layerSoil->getRawOriginal('description') !== trim($mColor->description)) {
                    $layerSoil->update(['description' => trim($mColor->description), 'color_id' => $layerColorId ?: $layerSoil->color_id]);
                }
                $layerSoil->increment('scan_count');
                $rImgPath = $saveRegionImage($r['region_thumbnail'] ?? null, $i);

                $createdScans[] = [
                    'scan_id' => $soilScan->soil_scan_id,
                    'soil_id' => $layerSoil->soil_id,
                    'scan_seq' => 1,
                    'name' => $layerSoil->effective_soil_name, 'color_name' => $layerSoil->effective_soil_name,
                    'code' => $mCode, 'hue' => $layerSoil->hue ?? $layerHue,
                    'hex' => $layerSoil->effective_hex_color ?? ($r['hex'] ?? '#8F694E'),
                    'desc' => $layerSoil->effective_description, 'organic' => $layerSoil->effective_organic_matter,
                    'drainage' => $layerSoil->effective_soil_type, 'soil_type' => $layerSoil->effective_soil_type,
                    'confidence' => $confVal, 'percent' => $pctVal, 'regions' => $regions,
                    'image' => $rImgPath ? asset($rImgPath) : ($publicScanRelativePath ? asset($publicScanRelativePath) : null),
                    'scan_image' => $rImgPath ? asset($rImgPath) : ($publicScanRelativePath ? asset($publicScanRelativePath) : null),
                ];
            }
        } else {
            // Single-layer scan
            $singleCode = $analysis['munsell_code'] ?? ($analysis['munsell']['notation'] ?? ($analysis['full_code'] ?? ($regions[0]['munsell_code'] ?? ($regions[0]['munsell'] ?? '10YR 5/3'))));
            $sMColor = MunsellColor::where('full_code', $singleCode)->first();
            $sDesc = (!empty($sMColor?->description) ? trim($sMColor->description) : null) ?? ($analysis['description'] ?? "{$singleCode}");
            $soil = Soil::firstOrCreate(
                ['full_code' => $singleCode],
                ['soil_name' => $sMColor?->color_name ?? ($analysis['soil_name'] ?? 'Soil Specimen'),
                 'hue' => $sMColor?->hue ?? ($analysis['hue'] ?? (explode(' ', $singleCode)[0] ?? '10YR')),
                 'full_code' => $singleCode, 'description' => $sDesc,
                 'status' => 'Active', 'scan_count' => 0,
                 'color_id' => $sMColor?->munsell_color_id ?? ($analysis['color_id'] ?? null),
                 'admin_id' => $userId]
            );
            if (!empty($sMColor?->description) && $soil->getRawOriginal('description') !== trim($sMColor->description)) {
                $soil->update(['description' => trim($sMColor->description), 'color_id' => $sMColor?->munsell_color_id ?: $soil->color_id]);
            }
            $soil->increment('scan_count');
            $analysis['description']    = $soil->effective_description;
            $analysis['soil_name']      = $soil->effective_soil_name;
            $analysis['organic_matter'] = $soil->effective_organic_matter;
            $analysis['soil_type']      = $soil->effective_soil_type;
            $soilScan = SoilScan::create([
                'user_id' => $userId, 'soil_id' => $soil->soil_id, 'platform' => $platform,
                'scan_note' => "Determined Soil: {$analysis['soil_name']} ({$singleCode}) - Confidence: " . ($analysis['confidence_formatted'] ?? '97.4%'),
                'regions_data' => $regions, 'scan_date' => now(),
                'scan_image' => $publicScanRelativePath,
                'before_after_image' => $publicBaRelativePath,
                'thumbnail_image' => $publicThumbRelativePath,
            ]);
            $createdScans = [[
                'scan_id' => $soilScan->soil_scan_id,
                'soil_id' => $soil->soil_id,
                'scan_seq' => SoilScan::where('soil_id', $soil->soil_id)->count(),
                'name' => $soil->effective_soil_name, 'color_name' => $soil->effective_soil_name,
                'code' => $singleCode, 'hue' => $soil->hue ?? ($analysis['hue'] ?? (explode(' ', $singleCode)[0] ?? '10YR')),
                'hex' => $soil->effective_hex_color ?? ($analysis['hex'] ?? '#8F694E'),
                'desc' => $soil->effective_description, 'organic' => $soil->effective_organic_matter,
                'drainage' => $soil->effective_soil_type, 'soil_type' => $soil->effective_soil_type,
                'confidence' => $analysis['confidence_formatted'] ?? '97.4%',
                'percent' => 100, 'regions' => $regions,
                'image' => $publicScanRelativePath ? (str_starts_with($publicScanRelativePath, 'http') ? $publicScanRelativePath : asset($publicScanRelativePath)) : null,
                'scan_image' => $publicScanRelativePath ? (str_starts_with($publicScanRelativePath, 'http') ? $publicScanRelativePath : asset($publicScanRelativePath)) : null,
            ]];
        }

        SoilScan::enforceLimit(300);

        // Optional LocationMapping persistence
        if (!empty($locationData['latitude']) && !empty($locationData['longitude']) && $soilScan && $soil) {
            try {
                $lat = (float) $locationData['latitude'];
                $lng = (float) $locationData['longitude'];
                $barangay = trim((string) ($locationData['barangay'] ?? ($locationData['location_name'] ?? '')));
                if (empty($barangay) || $barangay === 'Lal-lo Overall') {
                    $barangay = LocationMapping::resolveNearestBarangay($lat, $lng);
                }

                LocationMapping::create([
                    'soil_scan_id' => $soilScan->soil_scan_id,
                    'soil_id'      => $soil->soil_id,
                    'latitude'     => $lat,
                    'longitude'    => $lng,
                    'barangay'     => $barangay,
                    'timestamp'    => now(),
                    'status'       => 'Active',
                ]);
            } catch (\Throwable $e) {
                Log::warning('[SoilMLAnalysisService] Failed to create LocationMapping: ' . $e->getMessage());
            }
        }

        $normalizedUrl  = \App\Helpers\SoilImageHelper::scanImageUrl($soilScan, false) ?: ($publicScanRelativePath ? (str_starts_with($publicScanRelativePath, 'http') ? $publicScanRelativePath : asset($publicScanRelativePath)) : null);
        $beforeAfterUrl = \App\Helpers\SoilImageHelper::beforeAfterImageUrl($soilScan) ?: ($publicBaRelativePath ? (str_starts_with($publicBaRelativePath, 'http') ? $publicBaRelativePath : asset($publicBaRelativePath)) : null);
        $thumbnailUrl   = \App\Helpers\SoilImageHelper::scanImageUrl($soilScan, 'thumb') ?: ($publicThumbRelativePath ? (str_starts_with($publicThumbRelativePath, 'http') ? $publicThumbRelativePath : asset($publicThumbRelativePath)) : null);
        $rawUrl         = file_exists($fullRawJpg) ? asset('storage/' . $rawJpgName) : null;

        $analysis['scan_model']             = $soilScan;
        $analysis['soil_model']             = $soil;
        $analysis['soil_id']                = $soil?->soil_id;
        $analysis['scan_id']                = $soilScan?->soil_scan_id;
        $analysis['scan_seq']               = $soil ? SoilScan::where('soil_id', $soil->soil_id)->count() : null;
        $analysis['created_scans']          = $createdScans;
        $analysis['scan_image']             = $publicScanRelativePath;
        $analysis['scan_image_url']         = $normalizedUrl;
        $analysis['image']                  = $normalizedUrl;
        $analysis['stage2_normalized_url']  = $normalizedUrl;
        $analysis['normalized_image_url']   = $normalizedUrl;
        $analysis['before_after_image_url'] = $beforeAfterUrl;
        $analysis['overlay_image_url']      = $pythonResult['overlay_image_url'] ?? (file_exists(storage_path("app/public/scans/scan_{$scanId}_overlay.jpg")) ? "/storage/scans/scan_{$scanId}_overlay.jpg" : null);
        $analysis['thumbnail_image_url']    = $thumbnailUrl;
        $analysis['raw_image_url']          = $rawUrl;
        $analysis['stage2_mode']            = $analysis['stage2_mode'] ?? $pythonResult['stage2_mode'] ?? config('soil.stage2_mode', 'classical');
        $analysis['stage2_engine']          = $analysis['stage2_engine'] ?? $analysis['stage2_mode'];
        $analysis['success']                = true;
        $analysis['rejected']               = false;

        return $analysis;
    }
}

