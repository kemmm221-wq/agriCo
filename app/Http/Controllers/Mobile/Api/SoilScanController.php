<?php

namespace App\Http\Controllers\Mobile\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SoilScanResource;
use App\Models\LocationMapping;
use App\Models\MunsellColor;
use App\Models\Soil;
use App\Models\SoilScan;
use App\Models\User;
use App\Services\SoilMLAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SoilScanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        if ($user && method_exists($user, 'isApproved') && !$user->isApproved()) {
            return response()->json([
                'success' => false,
                'error'   => 'ACCOUNT_PENDING_APPROVAL',
                'message' => 'Your account is awaiting admin approval. Scanning access will be enabled once approved.',
            ], 403);
        }

        $targetUserId = $request->input('user_id') ?: ($user?->user_id ?? $user?->id ?? null);

        $query = SoilScan::with(['soil.munsellColor', 'location', 'user']);

        // Scope to account scans unless explicit 'all=true' is requested
        if ($targetUserId && !$request->boolean('all')) {
            $targetUser = User::where('user_id', $targetUserId)->first();
            if ($targetUser) {
                $relatedUserIds = User::where('email', $targetUser->email)
                    ->orWhere(function ($q) use ($targetUser) {
                        if ($targetUser->name && strlen($targetUser->name) > 3) {
                            $q->where('name', $targetUser->name);
                        }
                    })
                    ->pluck('user_id')
                    ->filter()
                    ->toArray();
                if (!in_array($targetUser->user_id, $relatedUserIds)) {
                    $relatedUserIds[] = $targetUser->user_id;
                }
                $query->whereIn('user_id', $relatedUserIds);
            } else {
                $query->where('user_id', $targetUserId);
            }
        }

        $perPage = min((int)$request->input('per_page', 100), 200);

        $scans = $query->latest('scan_date')
            ->paginate($perPage);

        $scans->getCollection()->transform(function ($scan) use ($request) {
            return (new \App\Http\Resources\SoilScanResource($scan))->toArray($request);
        });

        return response()->json([
            'success' => true,
            'status'  => 'success',
            'data'    => $scans
        ]);
    }

    public function store(Request $request, SoilMLAnalysisService $mlService)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        if ($user && method_exists($user, 'isApproved') && !$user->isApproved()) {
            return response()->json([
                'success' => false,
                'error'   => 'ACCOUNT_PENDING_APPROVAL',
                'message' => 'Your account is awaiting admin approval. Scanning access will be enabled once approved.',
            ], 403);
        }

        $userId = $user?->user_id ?? $user?->id ?? null;
        if (!$userId || !User::where('user_id', $userId)->exists()) {
            $fallbackUser = User::first();
            $userId = $fallbackUser?->user_id ?? 'ADM-001';
        }

        // 1. Direct Scan Specimen Submission (Auto-saved from Mobile Scan Viewfinder)
        if ($request->filled('munsell_code') || $request->filled('soil_id') || $request->filled('soil_name')) {
            $munsellCode = $request->input('munsell_code', '10YR 4/1');
            $soilName = $request->input('soil_name', 'Determined Soil');
            $hue = $request->input('hue') ?: explode(' ', $munsellCode)[0];
            $desc = $request->input('description') ?: "{$munsellCode} - {$soilName}";
            $confidence = $request->input('confidence', '95%');
            $scanNote = $request->input('scan_note') ?: "Determined Soil: {$soilName} ({$munsellCode})" . ($confidence ? " - Confidence: {$confidence}" : '');

            $disk = env('FILESYSTEM_DISK', 'public');
            $savedImagePath = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $savedPath = $request->file('image')->store('scans', $disk);
                $savedImagePath = $disk === 's3' ? Storage::disk('s3')->url($savedPath) : $savedPath;
            } elseif ($request->hasFile('scan_image') && $request->file('scan_image')->isValid()) {
                $savedPath = $request->file('scan_image')->store('scans', $disk);
                $savedImagePath = $disk === 's3' ? Storage::disk('s3')->url($savedPath) : $savedPath;
            } elseif ($request->filled('scan_image') && str_starts_with($request->input('scan_image'), 'data:image')) {
                $imgData = $request->input('scan_image');
                if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $m)) {
                    $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                    $data = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imgData));
                    $filename = 'scans/scan_' . time() . '_' . uniqid() . '.' . $ext;
                    Storage::disk($disk)->put($filename, $data, 'public');
                    $supabaseUrl = app(\App\Services\SupabaseStorageService::class)->upload($filename, $data, $ext === 'png' ? 'image/png' : 'image/jpeg');
                    $savedImagePath = $supabaseUrl ?: ($disk === 's3' ? Storage::disk('s3')->url($filename) : $filename);
                }
            } elseif ($request->filled('scan_image_url')) {
                $savedImagePath = $request->input('scan_image_url');
            }

            $munsellColor = MunsellColor::where('full_code', $munsellCode)->first();

            $soil = null;
            if ($request->filled('soil_id')) {
                $soil = Soil::find($request->input('soil_id'));
            }
            if (!$soil) {
                $soil = Soil::firstOrCreate(
                    ['full_code' => $munsellCode],
                    [
                        'soil_name'   => $soilName,
                        'hue'         => $hue,
                        'full_code'   => $munsellCode,
                        'description' => $desc,
                        'status'      => 'Active',
                        'scan_count'  => 0,
                        'color_id'    => $munsellColor?->color_id,
                        'admin_id'    => $userId,
                    ]
                );
            }

            $soil->increment('scan_count');

            $soilScan = SoilScan::create([
                'user_id'    => $userId,
                'soil_id'    => $soil->soil_id,
                'platform'   => 'mobile',
                'scan_note'  => $scanNote,
                'scan_date'  => now(),
                'scan_image' => $savedImagePath ? (str_starts_with($savedImagePath, 'http') || str_starts_with($savedImagePath, '/') ? $savedImagePath : Storage::url($savedImagePath)) : ($soil->munsellColor?->color_image ?? null),
            ]);

            SoilScan::enforceLimit(300);

            // Connect geo-coordinates to LocationMapping if passed from Mobile
            if ($request->filled('latitude') && $request->filled('longitude')) {
                try {
                    $lat = (float) $request->input('latitude');
                    $lng = (float) $request->input('longitude');
                    $barangay = trim((string) $request->input('location_name', ''));
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
                    report($e);
                }
            }

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'Soil scan recorded and saved to admin history successfully.',
                'data'    => [
                    'scan_id'      => $soilScan->soil_scan_id,
                    'soil_id'      => $soil->soil_id,
                    'munsell_code' => $munsellCode,
                    'soil_name'    => $soilName,
                    'scan_note'    => $scanNote,
                    'scan_date'    => $soilScan->scan_date->toIso8601String(),
                    'platform'     => 'mobile',
                    'user_id'      => $userId,
                ]
            ], 201);
        }

        // 2. Server-Side ML Image Analysis Pipeline (Uploaded Photos)
        $imageInput = null;

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imageInput = $request->file('image');
        } elseif ($request->hasFile('scan_image') && $request->file('scan_image')->isValid()) {
            $imageInput = $request->file('scan_image');
        } elseif ($request->filled('scan_image')) {
            $imageInput = $request->input('scan_image');
        } elseif ($request->filled('image_base64')) {
            $imageInput = $request->input('image_base64');
        }

        if (!$imageInput) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload or capture a clear soil sample photo.'
            ], 422);
        }

        try {
            $analysis = $mlService->analyzeImage($imageInput);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to analyze soil image. Please verify sample image quality.'
            ], 422);
        }

        $savedImagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $savedImagePath = $request->file('image')->store('scans', 'public');
        } elseif ($request->hasFile('scan_image') && $request->file('scan_image')->isValid()) {
            $savedImagePath = $request->file('scan_image')->store('scans', 'public');
        } elseif (is_string($imageInput) && preg_match('/^data:image\/(\w+);base64,/', $imageInput, $m)) {
            $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
            $data = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageInput));
            $filename = 'scans/scan_' . time() . '_' . uniqid() . '.' . $ext;
            Storage::disk('public')->put($filename, $data);
            $savedImagePath = $filename;
        }

        $authUser = Auth::guard('sanctum')->user() ?? Auth::guard('web')->user() ?? Auth::user() ?? Auth::guard('admin')->user();
        $userId = $authUser?->user_id ?? $authUser?->id ?? null;
        if (!$userId || !User::where('user_id', $userId)->exists()) {
            $fallbackUser = User::first();
            $userId = $fallbackUser?->user_id ?? 'ADM-001';
        }

        $soil = Soil::firstOrCreate(
            ['full_code' => $analysis['munsell_code']],
            [
                'soil_name'   => $analysis['soil_name'],
                'hue'         => $analysis['hue'],
                'full_code'   => $analysis['munsell_code'],
                'description' => $analysis['description'],
                'status'      => 'Active',
                'scan_count'  => 0,
                'color_id'    => $analysis['color_id'],
                'admin_id'    => $userId,
            ]
        );

        $soil->increment('scan_count');

        $soilScan = SoilScan::create([
            'user_id'    => $userId,
            'soil_id'    => $soil->soil_id,
            'platform'   => 'mobile',
            'scan_note'  => "Determined Soil: {$analysis['soil_name']} ({$analysis['munsell_code']})",
            'scan_date'  => now(),
            'scan_image' => $savedImagePath ? Storage::url($savedImagePath) : null,
        ]);

        SoilScan::enforceLimit(300);

        $analysis['scan_id'] = $soilScan->soil_scan_id;
        $analysis['scan_image_url'] = $savedImagePath ? Storage::url($savedImagePath) : null;

        return response()->json([
            'success' => true,
            'message' => 'Soil scan analyzed and saved successfully.',
            'data'    => $analysis
        ]);
    }

    /**
     * Asynchronously initiate soil analysis for mobile.
     * Pre-saves the image, writes sidecar metadata (including GPS coordinates),
     * fires off the background Python pipeline, and returns 202 Accepted.
     */
    public function analyzeAsync(Request $request, SoilMLAnalysisService $mlService)
    {
        $user = $request->user();
        $userId = $user?->user_id ?? $user?->id ?? $request->input('user_id');
        if (!$userId || !User::where('user_id', $userId)->exists()) {
            if ($user) {
                $userId = $user->user_id ?? $user->id;
            } elseif ($request->filled('user_id') && User::where('user_id', $request->input('user_id'))->exists()) {
                $userId = $request->input('user_id');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user.',
                ], 401);
            }
        }

        $targetUser = $user ?: User::where('user_id', $userId)->first();
        if ($targetUser && method_exists($targetUser, 'isApproved') && !$targetUser->isApproved()) {
            return response()->json([
                'success' => false,
                'error'   => 'ACCOUNT_PENDING_APPROVAL',
                'message' => 'Your account is awaiting admin approval. Scanning access will be enabled once approved.',
            ], 403);
        }

        $imageInput = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imageInput = $request->file('image');
        } elseif ($request->hasFile('scan_image') && $request->file('scan_image')->isValid()) {
            $imageInput = $request->file('scan_image');
        } elseif ($request->filled('scan_image')) {
            $imageInput = $request->input('scan_image');
        } elseif ($request->filled('image_base64')) {
            $imageInput = $request->input('image_base64');
        }

        if (!$imageInput) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload or capture a clear soil sample photo.'
            ], 422);
        }

        $scanId = $request->input('scan_id');
        if (!$scanId || !preg_match('/^[a-zA-Z0-9\-]{8,64}$/', $scanId)) {
            $scanId = (string) Str::uuid();
        }

        // Pre-save public scan photo
        try {
            $publicScansDir = storage_path('app/public/scans');
            if (!is_dir($publicScansDir)) {
                @mkdir($publicScansDir, 0755, true);
            }
            $targetPublicJpg = storage_path('app/public/scans/scan_' . $scanId . '.jpg');
            $targetRawJpg    = storage_path('app/public/scans/scan_' . $scanId . '_raw.jpg');

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                @copy($request->file('image')->getRealPath(), $targetPublicJpg);
                @copy($request->file('image')->getRealPath(), $targetRawJpg);
            } elseif ($request->hasFile('scan_image') && $request->file('scan_image')->isValid()) {
                @copy($request->file('scan_image')->getRealPath(), $targetPublicJpg);
                @copy($request->file('scan_image')->getRealPath(), $targetRawJpg);
            } elseif (is_string($imageInput)) {
                if (preg_match('/^data:image\/(\w+);base64,/', $imageInput)) {
                    $rawBin = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageInput));
                    if ($rawBin) {
                        @file_put_contents($targetPublicJpg, $rawBin);
                        @file_put_contents($targetRawJpg, $rawBin);
                    }
                } elseif (file_exists($imageInput)) {
                    @copy($imageInput, $targetPublicJpg);
                    @copy($imageInput, $targetRawJpg);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[SoilScanController] Could not pre-save public scan photo: ' . $e->getMessage());
        }

        // Save sidecar metadata (GPS coordinates, user, barangay)
        $sidecar = [
            'user_id'       => $userId,
            'platform'      => 'mobile',
            'latitude'      => $request->input('latitude'),
            'longitude'     => $request->input('longitude'),
            'location_name' => $request->input('location_name'),
            'barangay'      => $request->input('barangay') ?? $request->input('location_name'),
        ];
        $metaFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "scan_meta_{$scanId}.json";
        @file_put_contents($metaFile, json_encode($sidecar));

        try {
            $analysisInput = (isset($targetPublicJpg) && file_exists($targetPublicJpg) && filesize($targetPublicJpg) > 0)
                ? $targetPublicJpg
                : $imageInput;
            $mlService->analyzeImage($analysisInput, $scanId);
        } catch (\Throwable $e) {
            @unlink($metaFile);
            return response()->json([
                'success' => false,
                'error'   => 'SCAN_PIPELINE_ERROR',
                'message' => 'Soil Scan Error: Unable to start scan pipeline. Please try again.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'async'   => true,
            'scan_id' => $scanId,
            'message' => 'Scan analysis initiated. Poll progress via /scan/progress/' . $scanId,
        ], 202);
    }

    /**
     * Poll progress of a running mobile scan pipeline.
     */
    public function progress(Request $request, string $scanId, SoilMLAnalysisService $mlService)
    {
        if (!preg_match('/^[a-zA-Z0-9\-]{8,64}$/', $scanId)) {
            return response()->json(['error' => 'invalid_id'], 400);
        }

        $user = $request->user();
        $userId = $user?->user_id ?? $user?->id;
        if (!$userId || !User::where('user_id', $userId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated user.',
            ], 401);
        }

        // Return cached payload immediately if already finalized
        $cachedPayload = \Illuminate\Support\Facades\Cache::get("scan_result_payload_{$scanId}");
        if ($cachedPayload) {
            return response()->json($cachedPayload);
        }

        $tmpDir       = sys_get_temp_dir();
        $progressFile = $tmpDir . DIRECTORY_SEPARATOR . "scan_progress_{$scanId}.json";
        $resultFile   = $tmpDir . DIRECTORY_SEPARATOR . "scan_result_{$scanId}.json";
        $metaFile     = $tmpDir . DIRECTORY_SEPARATOR . "scan_meta_{$scanId}.json";

        if (!file_exists($progressFile)) {
            $scanIdSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $scanId);
            $logFile = $tmpDir . DIRECTORY_SEPARATOR . "scan_python_{$scanIdSafe}.log";
            if (file_exists($logFile) && filesize($logFile) > 0) {
                $rawContent = file_get_contents($logFile);
                $errContent = is_string($rawContent) ? trim($rawContent) : '';
                if (!empty($errContent) && (
                    str_contains($errContent, 'Traceback') ||
                    str_contains($errContent, '"success": false') ||
                    str_contains($errContent, 'Image not found') ||
                    str_contains($errContent, 'Error:')
                )) {
                    return response()->json([
                        'status'  => 'error',
                        'stage'   => 0,
                        'cursor'  => 1,
                        'events'  => [['stage' => 0, 'status' => 'error', 'error' => $errContent]],
                        'message' => 'Analysis process failed: ' . $errContent,
                    ]);
                }
            }
            return response()->json([
                'status' => 'pending',
                'stage'  => 0,
                'cursor' => 0,
                'events' => [],
            ]);
        }

        $history = json_decode(file_get_contents($progressFile), true) ?? [];
        if (!is_array($history)) $history = [];

        $since = (int) $request->query('since', 0);
        $newEvents = array_slice($history, $since);
        $latest = end($history) ?: ['stage' => 0, 'status' => 'pending'];

        $payload = [
            'status' => $latest['status'] ?? 'pending',
            'stage'  => $latest['stage'] ?? 0,
            'cursor' => count($history),
            'events' => $newEvents,
        ];

        // Pipeline fatal error
        if (($latest['stage'] ?? 0) === 0 && ($latest['status'] ?? '') === 'error') {
            @unlink($progressFile);
            @unlink($resultFile);
            @unlink($metaFile);
            $payload['status'] = 'error';
            $payload['message'] = $latest['error'] ?? 'Scan analysis pipeline failed.';
            return response()->json($payload, 422);
        }

        // Stage 1 Soil Gate Rejection
        if (($latest['status'] ?? '') === 'rejected') {
            @unlink($progressFile);
            @unlink($resultFile);
            @unlink($metaFile);
            $payload['status']   = 'rejected';
            $payload['rejected'] = true;
            $payload['stage']    = $latest['stage'] ?? 1;
            $payload['message']  = $latest['message'] ?? 'No valid soil sample detected. Please point camera directly at genuine soil.';
            \Illuminate\Support\Facades\Cache::put("scan_result_payload_{$scanId}", $payload, 180);
            return response()->json($payload);
        }

        if (($latest['stage'] ?? 0) === 6 && ($latest['status'] ?? '') === 'done') {
            $lockKey = "scan_finalizing_{$scanId}";
            $acquired = \Illuminate\Support\Facades\Cache::add($lockKey, true, 30);

            if ($acquired && file_exists($resultFile)) {
                try {
                    $rawResult = json_decode(file_get_contents($resultFile), true);
                    if ($rawResult && ($rawResult['success'] ?? false)) {
                        $locationData = [];
                        if (file_exists($metaFile)) {
                            $meta = json_decode(file_get_contents($metaFile), true);
                            if (is_array($meta)) {
                                $locationData = $meta;
                            }
                        }

                        $scanUserId = (!empty($locationData['user_id']) && User::where('user_id', $locationData['user_id'])->exists())
                            ? $locationData['user_id']
                            : $userId;

                        $fullResult = $mlService->persistScanResult(
                            $rawResult,
                            $scanId,
                            $scanUserId,
                            'mobile',
                            $locationData
                        );

                        if (!empty($fullResult['rejected'])) {
                            $payload['status']   = 'rejected';
                            $payload['rejected'] = true;
                            $payload['message']  = $fullResult['message'] ?? 'No valid soil sample detected.';
                        } else {
                            $payload['status'] = 'done';
                            $payload['data']   = $fullResult;
                            if (!empty($fullResult['scan_model'])) {
                                $payload['scan'] = (new SoilScanResource($fullResult['scan_model']))->toArray($request);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('[SoilScanController] Error finalizing mobile scan: ' . $e->getMessage());
                } finally {
                    @unlink($progressFile);
                    @unlink($resultFile);
                    @unlink($metaFile);
                    \Illuminate\Support\Facades\Cache::put("scan_result_payload_{$scanId}", $payload, 180);
                    \Illuminate\Support\Facades\Cache::forget($lockKey);
                }
            } elseif (\Illuminate\Support\Facades\Cache::has($lockKey)) {
                $payload['status'] = 'finalizing';
            }
        }

        return response()->json($payload);
    }
}

