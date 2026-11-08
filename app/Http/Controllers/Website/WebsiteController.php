<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\FAQ;
use App\Models\LocationMapping;
use App\Models\MunsellColor;
use App\Models\Soil;
use App\Models\SoilScan;
use App\Models\User;
use App\Models\UserRequest;
use App\Services\SoilMLAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class WebsiteController extends Controller
{
    /**
     * Analyze uploaded or captured soil image dynamically via ML/CIELAB pipeline.
     */
    public function analyzeScan(Request $request, SoilMLAnalysisService $mlService)
    {
        $authUser = Auth::guard('web')->user() ?? Auth::user() ?? Auth::guard('admin')->user();
        if (!$authUser && session()->has('user_id')) {
            $authUser = User::where('user_id', session('user_id'))->first();
        }
        if (!$authUser && session()->has('admin_id')) {
            $authUser = User::where('user_id', session('admin_id'))->first();
        }

        if (!$authUser) {
            $authUser = User::first();
        }

        if ($authUser && method_exists($authUser, 'isApproved') && !$authUser->isApproved()) {
            return response()->json([
                'success' => false,
                'error'   => 'ACCOUNT_PENDING_APPROVAL',
                'message' => 'Account Pending Approval: Your account is awaiting admin approval. Scanning and Map access will be enabled once approved.',
            ], 403);
        }

        $imageInput = null;

        if ($request->hasFile('scan_image') && $request->file('scan_image')->isValid()) {
            $imageInput = $request->file('scan_image');
        } elseif ($request->filled('scan_image')) {
            $imageInput = $request->input('scan_image');
        } elseif ($request->filled('image_base64')) {
            $imageInput = $request->input('image_base64');
        }

        if (!$imageInput) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload or capture a clear soil sample photo to scan.'
            ], 422);
        }

        // Accept the client-generated scan UUID or generate one; validate to prevent path traversal
        $scanId = $request->input('scan_id', '');
        if (empty($scanId)) {
            $scanId = (string) \Illuminate\Support\Str::uuid();
        } elseif (!preg_match('/^[a-zA-Z0-9\-]{8,64}$/', $scanId)) {
            return response()->json(['success' => false, 'message' => 'Invalid scan_id.'], 422);
        }

        // Persist initial scan image directly to public storage so it has a guaranteed web-accessible path
        try {
            $publicScansDir = storage_path('app/public/scans');
            if (!is_dir($publicScansDir)) {
                @mkdir($publicScansDir, 0755, true);
            }
            $targetPublicJpg = storage_path('app/public/scans/scan_' . $scanId . '.jpg');
            $targetRawJpg    = storage_path('app/public/scans/scan_' . $scanId . '_raw.jpg');
            if ($request->hasFile('scan_image') && $request->file('scan_image')->isValid()) {
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

            try {
                if (file_exists($targetPublicJpg)) {
                    $supabaseStorage = app(\App\Services\SupabaseStorageService::class);
                    $supabaseStorage->uploadFile($targetPublicJpg, 'scans/scan_' . $scanId . '.jpg');
                }
            } catch (\Throwable $e_supa) {
                \Illuminate\Support\Facades\Log::warning('[WebsiteController] Supabase scan upload failed: ' . $e_supa->getMessage());
            }
        } catch (\Throwable $e) {
            Log::warning('[WebsiteController] Could not pre-save public scan photo: ' . $e->getMessage());
        }
        try {
            // Pass pre-saved public JPG if available to guarantee file persistence for detached Python
            $analysisInput = (isset($targetPublicJpg) && file_exists($targetPublicJpg) && filesize($targetPublicJpg) > 0)
                ? $targetPublicJpg
                : $imageInput;
            $mlService->analyzeImage($analysisInput, $scanId);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'NON_SOIL_DETECTED',
                'message' => 'Soil Scan Error: Unable to start scan pipeline. Please try again.',
            ], 422);
        }

        // Return immediately (HTTP 202 Accepted) — frontend polls /scan/progress/{scanId}
        return response()->json([
            'success' => true,
            'async'   => true,
            'scan_id' => $scanId,
        ], 202);
    }

    /**
     * Poll progress of a running Python scan pipeline.
     *
     * Reads /tmp/scan_progress_{scanId}.json written by colorimetry_service.py.
     * When Stage 6 is done, also reads /tmp/scan_result_{scanId}.json,
     * runs the full DB-write logic, attaches the result, then cleans up both files.
     */
    public function scanProgress(Request $request, string $scanId, SoilMLAnalysisService $mlService)
    {
        // Sanitise to prevent path traversal
        if (!preg_match('/^[a-zA-Z0-9\-]{8,64}$/', $scanId)) {
            return response()->json(['error' => 'invalid_id'], 400);
        }

        $tmpDir       = sys_get_temp_dir();
        // Return cached payload immediately if already finalized
        $cachedPayload = \Illuminate\Support\Facades\Cache::get("scan_result_payload_{$scanId}");
        if ($cachedPayload) {
            return response()->json($cachedPayload);
        }

        $progressFile = $tmpDir . DIRECTORY_SEPARATOR . "scan_progress_{$scanId}.json";
        $resultFile   = $tmpDir . DIRECTORY_SEPARATOR . "scan_result_{$scanId}.json";

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
                        'events' => [['stage' => 0, 'status' => 'error', 'error' => $errContent]],
                        'stage'  => 0,
                        'status' => 'error',
                        'cursor' => 1,
                    ]);
                }
            }
            $debug = [];
            if (config('app.debug')) {
                $debug = [
                    'tmpDir'     => $tmpDir,
                    'logFile'    => $logFile,
                    'logExists'  => file_exists($logFile),
                    'logSize'    => file_exists($logFile) ? filesize($logFile) : 0,
                    'logContent' => file_exists($logFile) ? substr((string) file_get_contents($logFile), 0, 500) : null,
                    'pyBinary'   => \App\Services\PythonProcessRunner::getPythonBinary(),
                ];
            }
            return response()->json(['events' => [], 'stage' => 0, 'status' => 'pending', 'cursor' => 0, 'debug' => $debug]);
        }

        $history = json_decode(file_get_contents($progressFile), true) ?? [];
        if (!is_array($history)) $history = [];

        $since = (int) $request->query('since', 0);
        $newEvents = array_slice($history, $since);

        $latest = end($history) ?: ['stage' => 0, 'status' => 'pending'];
        $payload = [
            'events' => $newEvents,
            'cursor' => count($history),
            'stage'  => $latest['stage'] ?? 0,
            'status' => $latest['status'] ?? 'pending',
        ];

        if (($latest['status'] ?? '') === 'rejected') {
            @unlink($progressFile);
            @unlink($resultFile);
            $payload['status']   = 'rejected';
            $payload['rejected'] = true;
            $payload['stage']    = $latest['stage'] ?? 1;
            $payload['message']  = $latest['message'] ?? 'No valid soil sample detected. Please align the viewfinder over a clear, well-lit soil sample and try again.';
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
                        // Resolve authenticated user for web
                        $authUser = Auth::guard('web')->user() ?? Auth::user() ?? Auth::guard('admin')->user();
                        if (!$authUser && session()->has('user_id')) {
                            $authUser = User::where('user_id', session('user_id'))->first();
                        }
                        if (!$authUser && session()->has('admin_id')) {
                            $authUser = User::where('user_id', session('admin_id'))->first();
                        }
                        if (!$authUser) {
                            $authUser = User::first();
                        }
                        $userId = $authUser?->user_id ?? $authUser?->id ?? null;
                        if (!$userId || !User::where('user_id', $userId)->exists()) {
                            $userId = User::first()?->user_id ?? 'ADM-001';
                        }

                        $fullResult = $mlService->persistScanResult($rawResult, $scanId, $userId, 'website');
                        $payload['result'] = $fullResult;
                        $payload['stage'] = 6;
                        $payload['status'] = 'done';
                        if (!empty($fullResult['rejected'])) {
                            $payload['status'] = 'rejected';
                            $payload['rejected'] = true;
                        }
                    } else {
                        $payload['status'] = 'error';
                        $payload['error'] = $rawResult['error'] ?? 'Unknown scan analysis failure';
                        $payload['events'][] = ['stage' => 0, 'status' => 'error', 'error' => $payload['error']];
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('[WebsiteController] Error finalizing scan: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                    $payload['status'] = 'error';
                    $payload['error'] = 'Error finalizing scan result: ' . $e->getMessage();
                    $payload['events'][] = ['stage' => 0, 'status' => 'error', 'error' => $e->getMessage()];
                } finally {
                    @unlink($progressFile);
                    @unlink($resultFile);
                    if (!empty($payload['result'])) {
                        \Illuminate\Support\Facades\Cache::put("scan_result_payload_{$scanId}", $payload, 180);
                    }
                    \Illuminate\Support\Facades\Cache::forget($lockKey);
                }
            } elseif (\Illuminate\Support\Facades\Cache::has($lockKey)) {
                // Background worker is currently writing to DB; tell frontend to keep polling
                $payload['stage'] = 6;
                $payload['status'] = 'finalizing';
            }
        }

        $scanIdSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $scanId);
        $pyLogFile = $tmpDir . DIRECTORY_SEPARATOR . "scan_python_{$scanIdSafe}.log";
        if (file_exists($pyLogFile) && ($request->boolean('debug') || config('app.debug') || in_array($payload['status'] ?? '', ['done', 'error', 'rejected']))) {
            $payload['debug_log'] = (string) file_get_contents($pyLogFile);
        }

        return response()->json($payload);
    }

    /**
     * Public Home page.
     */
    public function home()
    {
        $totalScans    = SoilScan::count();
        $totalColors   = MunsellColor::where(function ($q) {
            $q->where('status', 'Active')->orWhereNull('status');
        })->count();
        $totalUsers    = User::whereNotIn('role', ['admin', 'superadmin'])
            ->where(function ($q) {
                $q->where('status', 'Active')->orWhereNull('status');
            })->count();
        // Pinned Soil Locations: count actual user-submitted / approved pinned locations or pin location requests
        $pinnedLocationsCount = LocationMapping::whereNotNull('latitude')->whereNotNull('longitude')->count();
        $pinRequestsCount     = UserRequest::where(function ($q) {
            $q->where('type', 'Soil Coordinates')
              ->orWhere('type', 'Pin Soil Location')
              ->orWhere('type', 'LIKE', '%Pin%')
              ->orWhere('type', 'LIKE', '%Coordinates%');
        })->count();
        $totalSoils = max($pinnedLocationsCount, $pinRequestsCount);

        // Recent soil scans for the dashboard carousel (latest 6, all users)
        $recentScans = SoilScan::with(['soil.munsellColor', 'location'])
            ->latest('scan_date')
            ->limit(6)
            ->get();

        // Pending admin approval count (soils not yet Active)
        $pendingCount = Soil::where('status', '!=', 'Active')
            ->orWhereNull('status')
            ->count();

        // Offline queue is managed by the Android app; always 0 on web
        $offlineQueueCount = 0;

        return view('website.home', compact(
            'totalScans',
            'totalColors',
            'totalUsers',
            'totalSoils',
            'recentScans',
            'pendingCount',
            'offlineQueueCount'
        ));
    }


    /**
     * Scan History page (authenticated and approved users see their records; unapproved users and guests are guarded).
     */
    public function scan()
    {
        $user = Auth::guard('web')->user() ?? Auth::user() ?? Auth::guard('admin')->user();
        if (!$user && session()->has('user_id')) {
            $user = User::where('user_id', session('user_id'))->first();
        }
        if (!$user && session()->has('admin_id')) {
            $user = User::where('user_id', session('admin_id'))->first();
        }

        $isLoggedIn = (bool) $user;
        $isApproved = $isLoggedIn && (method_exists($user, 'isApproved') ? $user->isApproved() : true);
        $isPending  = $isLoggedIn && !$isApproved;

        $userId = $user?->user_id ?? $user?->id ?? null;

        $perPage = (int) request('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        }

        if ($isLoggedIn && $isApproved && $userId) {
            $scans = SoilScan::with(['soil.munsellColor', 'location', 'user'])
                ->where('user_id', $userId)
                ->orderBy('soil_scan_id', 'desc')
                ->paginate($perPage)
                ->appends(request()->query())
                ->fragment('scanHistory');
        } else {
            // Unauthenticated guest or pending unapproved user: return empty paginator for view
            $scans = SoilScan::whereRaw('1 = 0')->paginate($perPage)->fragment('scanHistory');
        }

        $allMunsellColors = MunsellColor::all();

        return response()
            ->view('website.scan', compact('scans', 'allMunsellColors', 'isLoggedIn', 'isApproved', 'isPending'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Store analyzed soil scan record into database.
     */
    public function storeScan(\Illuminate\Http\Request $request)
    {
        $authUser = Auth::guard('web')->user() ?? Auth::user() ?? Auth::guard('admin')->user();
        if (!$authUser && session()->has('user_id')) {
            $authUser = User::where('user_id', session('user_id'))->first();
        }
        if (!$authUser && session()->has('admin_id')) {
            $authUser = User::where('user_id', session('admin_id'))->first();
        }

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'error'   => 'AUTH_REQUIRED',
                'message' => 'Please log in to perform a scan and view scan history.',
            ], 401);
        }

        if (method_exists($authUser, 'isApproved') && !$authUser->isApproved()) {
            return response()->json([
                'success' => false,
                'error'   => 'ACCOUNT_PENDING_APPROVAL',
                'message' => 'Account Pending Approval: Your account is awaiting admin approval. Scanning and Map access will be enabled once approved.',
            ], 403);
        }

        $request->validate([
            'soil_name' => 'required|string|max:255',
            'full_code' => 'required|string|max:100',
            'hue'       => 'nullable|string|max:50',
            'scan_note' => 'nullable|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $userId = $authUser?->user_id ?? $authUser?->id ?? null;
        if (!$userId || !User::where('user_id', $userId)->exists()) {
            $fallbackUser = User::first();
            $userId = $fallbackUser?->user_id ?? 'ADM-001';
        }

        // Find matching MunsellColor record
        $munsellColor = MunsellColor::where('full_code', $request->full_code)
            ->orWhere('hue', $request->hue)
            ->first();

        // Find or create Soil record
        $soil = Soil::firstOrCreate(
            ['full_code' => $request->full_code],
            [
                'soil_name'   => $request->soil_name,
                'hue'         => $request->hue ?? '10YR',
                'full_code'   => $request->full_code,
                'description' => $request->scan_note ?? 'Sample processed using AgriCo Munsell Determination Algorithm.',
                'status'      => 'Active',
                'scan_count'  => 0,
                'color_id'    => $munsellColor?->munsell_color_id,
                'admin_id'    => $userId,
            ]
        );

        $soil->hue = $request->hue ?? $soil->hue ?? '10YR';
        $soil->full_code = $request->full_code ?? $soil->full_code;
        if ($munsellColor && !$soil->color_id) {
            $soil->color_id = $munsellColor->munsell_color_id;
        }
        $soil->increment('scan_count');
        $soil->save();

        // Create SoilScan record
        $soilScan = SoilScan::create([
            'user_id'    => $userId,
            'soil_id'    => $soil->soil_id,
            'scan_note'  => $request->scan_note ?? "Determined Soil: {$request->soil_name} ({$request->full_code})",
            'scan_date'  => now(),
            'scan_image' => $request->scan_image ?? null,
        ]);

        if ($request->filled('latitude') && $request->filled('longitude')) {
            LocationMapping::create([
                'latitude'     => (float) $request->latitude,
                'longitude'    => (float) $request->longitude,
                'barangay'     => $request->barangay ?? $request->location_name ?? 'Scanned Field Location',
                'timestamp'    => now(),
                'status'       => 'Active',
                'soil_scan_id' => $soilScan->soil_scan_id,
                'soil_id'      => $soil->soil_id,
            ]);
        }

        // Enforce max 300 history records (oldest automatically deleted)
        SoilScan::enforceLimit(300);

        $scanSeq = SoilScan::where('soil_id', $soil->soil_id)->count();

        return response()->json([
            'success'  => true,
            'message'  => 'Soil scan recorded successfully!',
            'scan_id'  => $soilScan->soil_scan_id,
            'scan_seq' => $scanSeq,
        ]);
    }

    /**
     * Store newly pinned soil sample location from Website Scanner for Admin Approval or Direct Active.
     */
    public function pinLocation(Request $request)
    {
        // Tolerant normalization for soil_confidence and soil_percentage
        if ($request->has('soil_confidence')) {
            $rawConf = $request->input('soil_confidence');
            if ($rawConf !== null && $rawConf !== '') {
                $cleanConf = is_string($rawConf) ? trim(str_replace(['%', ' '], '', $rawConf)) : $rawConf;
                if (is_numeric($cleanConf)) {
                    $cVal = (float)$cleanConf;
                    if ($cVal > 0 && $cVal <= 1.0) {
                        $cVal = $cVal * 100;
                    }
                    $request->merge(['soil_confidence' => round($cVal, 1)]);
                }
            } else {
                $request->merge(['soil_confidence' => null]);
            }
        }

        if ($request->has('soil_percentage')) {
            $rawPct = $request->input('soil_percentage');
            if ($rawPct !== null && $rawPct !== '') {
                $cleanPct = is_string($rawPct) ? trim($rawPct) : (string)$rawPct;
                if (!str_ends_with($cleanPct, '%') && is_numeric($cleanPct)) {
                    $cleanPct .= '%';
                }
                $request->merge(['soil_percentage' => $cleanPct]);
            }
        }

        try {
            $validated = $request->validate([
                'latitude'           => ['required', 'numeric', 'between:-90,90'],
                'longitude'          => ['required', 'numeric', 'between:-180,180'],
                'barangay'           => ['required', 'string', 'max:255'],
                'soil_id'            => ['nullable'],
                'soil_scan_id'       => ['nullable'],
                'scan_note'          => ['nullable', 'string', 'max:1000'],
                'coverage_area'      => ['nullable', 'string', 'max:100'],
                'pin_all'            => ['nullable', 'boolean'],
                'pin_mode'           => ['nullable', 'string', 'max:50'],
                'pinned_soil_index'  => ['nullable', 'integer'],
                'soil_percentage'    => ['nullable', 'string', 'max:50'],
                'soil_confidence'    => ['nullable', 'numeric', 'between:0,100'],
                'soil_layer_meta'    => ['nullable'],
                'regions'            => ['nullable', 'array'],
            ]);

            // Strict Lal-lo Boundary Enforcement (One Source of Truth)
            $lat = (float) $validated['latitude'];
            $lng = (float) $validated['longitude'];

            if (!\App\Services\LalloBoundaryService::isInsideLallo($lat, $lng)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected location is outside the official Lal-lo, Cagayan municipal coverage area (35 Barangays).'
                ], 422);
            }

            // Auto-align barangay if submitted as generic or out-of-date
            if (empty($validated['barangay']) || in_array($validated['barangay'], ['Lal-lo Overall', 'Outside Lal-lo', 'Outside Lal-lo Coverage'])) {
                $detectedBrgy = \App\Services\LalloBoundaryService::findBarangayAt($lat, $lng);
                if ($detectedBrgy) {
                    $validated['barangay'] = $detectedBrgy;
                }
            }

            $authUser = Auth::guard('web')->user() ?? Auth::user() ?? Auth::guard('admin')->user();
            if (!$authUser && session()->has('user_id')) {
                $authUser = User::where('user_id', session('user_id'))->first();
            }
            if (!$authUser && session()->has('admin_id')) {
                $authUser = User::where('user_id', session('admin_id'))->first();
            }
            $userId = $authUser?->user_id ?? $authUser?->id ?? null;
            $validUser = ($userId ? User::where('user_id', $userId)->first() : null) ?? User::first();
            $finalUserId = $validUser?->user_id ?? 'ADM-001';
            $isAdmin = $authUser && in_array($authUser->role, ['admin', 'superadmin']);

            // Validate scan ownership if soil_scan_id is provided
            if (!empty($validated['soil_scan_id'])) {
                $scanCheck = SoilScan::find($validated['soil_scan_id']);
                if ($scanCheck && !$isAdmin && $scanCheck->user_id && $userId && $scanCheck->user_id !== $userId && $scanCheck->user_id !== $finalUserId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized: This soil scan does not belong to your account.'
                    ], 403);
                }
            }

            // Determine or associate Soil record
            $soilId = $validated['soil_id'] ?? null;
            if ((!$soilId || !Soil::where('soil_id', $soilId)->exists()) && !empty($validated['soil_scan_id'])) {
                $scan = SoilScan::find($validated['soil_scan_id']);
                $soilId = $scan?->soil_id;
            }
            if (!$soilId || !Soil::where('soil_id', $soilId)->exists()) {
                $soil = Soil::first();
                $soilId = $soil?->soil_id ?? 1;
            }

            // Determine or create SoilScan record
            $soilScanId = $validated['soil_scan_id'] ?? null;
            if (!$soilScanId || !SoilScan::where('soil_scan_id', $soilScanId)->exists()) {
                // Try finding the user's latest valid scan with an image
                $recentScan = SoilScan::where('user_id', $finalUserId)
                    ->whereNotNull('scan_image')
                    ->latest('soil_scan_id')
                    ->first();

                if ($recentScan) {
                    $soilScanId = $recentScan->soil_scan_id;
                    if (!$soilId) {
                        $soilId = $recentScan->soil_id;
                    }
                } else {
                    $newScan = SoilScan::create([
                        'user_id'            => $finalUserId,
                        'soil_id'            => $soilId,
                        'platform'           => 'web',
                        'scan_note'          => $validated['scan_note'] ?? 'Website soil location sample pin',
                        'scan_date'          => now(),
                        'scan_image'         => $request->input('scan_image') ?? $request->input('scan_image_url'),
                        'thumbnail_image'    => $request->input('thumbnail_image') ?? $request->input('thumbnail_image_url'),
                        'before_after_image' => $request->input('before_after_image') ?? $request->input('before_after_image_url'),
                    ]);
                    $soilScanId = $newScan->soil_scan_id;
                }
            } elseif (!empty($validated['scan_note'])) {
                SoilScan::where('soil_scan_id', $soilScanId)->update([
                    'scan_note' => $validated['scan_note'],
                ]);
            }

            $directPublish = $isAdmin && $request->boolean('direct_publish');
            $isPinAll = $request->boolean('pin_all') || ($request->input('pin_mode') === 'all');
            $regions = $request->input('regions');
            $createdLocations = [];

            if ($isPinAll) {
                // If regions not provided, fallback to scan's regions_data
                if (empty($regions) && $soilScanId) {
                    $scanObj = SoilScan::find($soilScanId);
                    $regions = $scanObj?->regions ?? (is_array($scanObj?->regions_data) ? $scanObj->regions_data : json_decode($scanObj?->regions_data ?? '[]', true));
                }

                if (!is_array($regions) || count($regions) === 0) {
                    $regions = [['soil_id' => $soilId, 'pinned_index' => 0, 'percentage' => '100%']];
                }

                $alreadyPinnedIndexes = LocationMapping::where('soil_scan_id', $soilScanId)
                    ->whereNotNull('pinned_soil_index')
                    ->pluck('pinned_soil_index')
                    ->map(fn($v) => (int)$v)
                    ->toArray();

                $alreadyPinnedSoilIds = LocationMapping::where('soil_scan_id', $soilScanId)
                    ->pluck('soil_id')
                    ->map(fn($v) => (int)$v)
                    ->toArray();

                \Illuminate\Support\Facades\DB::transaction(function () use (
                    $regions, $alreadyPinnedIndexes, $alreadyPinnedSoilIds, $soilScanId, $soilId,
                    $validated, $directPublish, &$createdLocations
                ) {
                    foreach ($regions as $idx => $r) {
                        $pIdx = isset($r['pinned_index']) ? (int)$r['pinned_index'] : (isset($r['index']) ? (int)$r['index'] : $idx);
                        if (in_array($pIdx, $alreadyPinnedIndexes, true)) {
                            continue; // Skip already pinned
                        }

                        $subSoilId = $r['soil_id'] ?? $r['id'] ?? null;
                        if (!$subSoilId && !empty($r['munsell'])) {
                            $subSoilId = Soil::where('full_code', $r['munsell'])->first()?->soil_id;
                            if (!$subSoilId) {
                                $mColor = \App\Models\MunsellColor::where('full_code', $r['munsell'])->first();
                                if ($mColor) {
                                    $subSoilId = Soil::where('color_id', $mColor->munsell_color_id)->first()?->soil_id;
                                }
                            }
                        }
                        if (!$subSoilId || !Soil::where('soil_id', $subSoilId)->exists()) {
                            $subSoilId = $soilId;
                        }

                        if (in_array($subSoilId, $alreadyPinnedSoilIds, true) && in_array($pIdx, $alreadyPinnedIndexes, true)) {
                            continue;
                        }

                        $pctStr = isset($r['percent']) ? ($r['percent'] . '%') : ($r['percentage'] ?? null);
                        if ($pctStr && is_numeric($pctStr)) $pctStr .= '%';

                        $confNum = null;
                        if (isset($r['confidence']) && $r['confidence'] !== null && $r['confidence'] !== '') {
                            $cClean = is_string($r['confidence']) ? trim(str_replace(['%', ' '], '', $r['confidence'])) : $r['confidence'];
                            if (is_numeric($cClean)) {
                                $confNum = round((float)$cClean, 1);
                            }
                        }

                        // Same exact coordinate grouped as cluster
                        $loc = LocationMapping::create([
                            'soil_scan_id'      => $soilScanId,
                            'soil_id'           => $subSoilId,
                            'latitude'          => $validated['latitude'],
                            'longitude'         => $validated['longitude'],
                            'barangay'          => $validated['barangay'],
                            'timestamp'         => now(),
                            'status'            => $directPublish ? 'Active' : 'Pending',
                            'pinned_soil_index' => $pIdx,
                            'soil_percentage'   => $pctStr,
                            'soil_confidence'   => $confNum,
                            'soil_layer_meta'   => $r,
                        ]);
                        $createdLocations[] = $loc;
                    }
                });

                if (empty($createdLocations)) {
                    return response()->json([
                        'success'        => false,
                        'message'        => 'All detected soil layers for this scan are already pinned.',
                        'already_pinned' => true
                    ], 422);
                }

                $location = $createdLocations[0];
            } else {
                // Single pin mode: exactly ONE soil
                $targetIndex = isset($validated['pinned_soil_index']) ? (int)$validated['pinned_soil_index'] : 0;

                // Check duplicate by either layer index or soil ID on the same scan
                $alreadyExists = LocationMapping::where('soil_scan_id', $soilScanId)
                    ->where(function ($q) use ($targetIndex, $soilId) {
                        $q->where('pinned_soil_index', $targetIndex)
                          ->orWhere('soil_id', $soilId);
                    })
                    ->exists();

                if ($alreadyExists) {
                    return response()->json([
                        'success'        => false,
                        'message'        => 'This soil specimen has already been pinned to the map.',
                        'already_pinned' => true
                    ], 422);
                }

                $meta = $validated['soil_layer_meta'] ?? null;
                if (!$meta && !empty($validated['regions']) && is_array($validated['regions'])) {
                    $meta = $validated['regions'][$targetIndex] ?? ($validated['regions'][0] ?? null);
                }

                $pct = $validated['soil_percentage'] ?? ($meta['percent'] ?? ($meta['percentage'] ?? '100%'));
                if (is_numeric($pct)) $pct = $pct . '%';
                
                $conf = $validated['soil_confidence'] ?? null;
                if ($conf === null && !empty($meta['confidence'])) {
                    $cClean = is_string($meta['confidence']) ? trim(str_replace(['%', ' '], '', $meta['confidence'])) : $meta['confidence'];
                    if (is_numeric($cClean)) {
                        $conf = round((float)$cClean, 1);
                    }
                }

                $location = LocationMapping::create([
                    'soil_scan_id'      => $soilScanId,
                    'soil_id'           => $soilId,
                    'latitude'          => $validated['latitude'],
                    'longitude'         => $validated['longitude'],
                    'barangay'          => $validated['barangay'],
                    'timestamp'         => now(),
                    'status'            => $directPublish ? 'Active' : 'Pending',
                    'pinned_soil_index' => $targetIndex,
                    'soil_percentage'   => $pct,
                    'soil_confidence'   => $conf,
                    'soil_layer_meta'   => $meta,
                ]);
                $createdLocations[] = $location;
            }

            // Create ONE combined UserRequest ticket for Admin Approval in Manage Request (unless direct published)
            if (!$directPublish) {
                try {
                    $user = $authUser ?? User::where('user_id', $finalUserId)->first();
                    $userName = $user?->name ?? $user?->full_name ?? ($isAdmin ? 'AgriCo Admin' : 'Website User');
                    $userEmail = $user?->email ?? ($isAdmin ? 'agricosite77@gmail.com' : 'user@agrico.gov.ph');
                    $coverageText = !empty($validated['coverage_area']) ? " [Covered Area: {$validated['coverage_area']}]" : '';
                    
                    $pinCount = count($createdLocations);
                    if ($pinCount > 1) {
                        $notesText = "Pin All: {$pinCount} Soil Sample Locations pinned to Barangay: {$validated['barangay']}{$coverageText}. Coords: ({$validated['latitude']}, {$validated['longitude']}). Notes: " . ($validated['scan_note'] ?? 'Batch pinned soils.');
                    } else {
                        $notesText = "Soil Sample Location Pin #{$location->location_mapping_id} - Barangay: {$validated['barangay']}{$coverageText}. Coords: ({$validated['latitude']}, {$validated['longitude']}). Notes: " . ($validated['scan_note'] ?? 'No extra notes');
                    }
                    $requestCode = 'REQ-PIN-' . strtoupper(bin2hex(random_bytes(3)));

                    UserRequest::create([
                        'request_code' => $requestCode,
                        'user_name'    => $userName,
                        'user_email'   => $userEmail,
                        'type'         => 'Soil Coordinates',
                        'description'  => $notesText,
                        'status'       => 'Pending',
                        'submitted_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            // Trigger ONE combined notification across Website, Mobile Application, and Email
            try {
                $targetUser = $authUser ?? User::where('user_id', $finalUserId)->first();
                \App\Services\NotificationService::notifyPinSubmitted($location, $targetUser, 'website');
            } catch (\Throwable $e) {
                report($e);
            }

            $pinCount = count($createdLocations);
            $msg = $directPublish
                ? ($pinCount > 1 ? "{$pinCount} soil coordinates pinned and published directly to Soil Map!" : "Coordinates pinned and published directly to Soil Map!")
                : ($pinCount > 1 ? "{$pinCount} soil layers in Brgy. {$validated['barangay']} submitted to Admin for approval!" : "Your soil coordinates in Brgy. {$validated['barangay']} have been submitted to Admin for approval!");

            $pinnedIndices = array_values(array_filter(array_map(fn($l) => $l->pinned_soil_index, $createdLocations), fn($v) => $v !== null));

            return response()->json([
                'success'           => true,
                'message'           => $msg,
                'location'          => $location,
                'created_locations' => $createdLocations,
                'pinned_count'      => $pinCount,
                'pinned_indices'    => $pinnedIndices,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'The given data was invalid.',
                'errors'  => $e->validator->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[WebsiteController@pinLocation] ' . $e->getMessage(), [
                'exception' => $e,
                'request'   => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to submit pin location. Please try again.',
            ], 500);
        }
    }

    /**
     * Interactive Map page — open and accessible to the public.
     */
    public function map()
    {
        $user = Auth::guard('web')->user() ?? Auth::user() ?? Auth::guard('admin')->user();
        if (!$user && session()->has('user_id')) {
            $user = User::where('user_id', session('user_id'))->first();
        }
        if (!$user && session()->has('admin_id')) {
            $user = User::where('user_id', session('admin_id'))->first();
        }

        $isLoggedIn = (bool) $user;
        $isApproved = $isLoggedIn && (method_exists($user, 'isApproved') ? $user->isApproved() : true);
        $isPending  = $isLoggedIn && !$isApproved;

        $mappings = LocationMapping::with(['soil.munsellColor', 'soilScan.user'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($q) {
                $q->where('status', 'Active')
                  ->orWhereNull('status')
                  ->orWhere('status', '');
            })
            ->whereDoesntHave('soilScan', function ($sq) {
                $sq->where('verification_status', 'Rejected');
            })
            ->orderBy('location_mapping_id', 'asc')
            ->get();

        $mappings->each(function ($m) {
            $covStr = '';
            if ($m->soilScan?->scan_note && preg_match('/\[Covered Area:\s*([^\]]+)\]/i', $m->soilScan->scan_note, $cm)) {
                $covStr = trim($cm[1]);
            }
            $m->setAttribute('coverage', $covStr ?: '500 m²');

            // Exact Barangay Name (never 'Lal-lo Overall')
            if (empty($m->barangay) || strtolower($m->barangay) === 'lal-lo overall' || strtolower($m->barangay) === 'all barangays') {
                $m->barangay = LocationMapping::resolveNearestBarangay((float) $m->latitude, (float) $m->longitude);
            }

            // Munsell Soil Type / General Group
            $fullCode = $m->soil?->full_code ?? $m->soil?->munsellColor?->full_code ?? '';
            $munsellSoilType = $m->soil?->effective_soil_type ?: ($m->soil?->munsellColor?->soil_type ?? null);
            if (!$munsellSoilType && $fullCode) {
                $lookup = \App\Services\MunsellLookupService::lookup($fullCode);
                $munsellSoilType = $lookup['soil_type'] ?? null;
            }
            if (!$munsellSoilType && !empty($m->soil?->soil_type)) {
                $munsellSoilType = $m->soil->soil_type;
            }
            $m->setAttribute('munsell_soil_type', $munsellSoilType ?: 'Leached grayish brown');
        });

        return view('website.map', compact('mappings', 'isLoggedIn', 'isApproved', 'isPending'));
    }

    /**
     * Munsell Color Chart — all active colors.
     */
    public function munsell()
    {
        $colors = MunsellColor::where(function ($q) {
            $q->where('status', 'Active')->orWhereNull('status');
        })->orderBy('hue')->orderBy('value')->orderBy('chroma')->get();

        $hues = $colors->pluck('hue')->unique()->sort()->values();

        return view('website.munsell', compact('colors', 'hues'));
    }

    /**
     * FAQ page — all FAQ entries with category tabs and search.
     */
    public function faq()
    {
        $categories = config('faq.categories', []);
        $configItems = config('faq.items', []);

        // Check if database has custom FAQ records
        $dbFaqs = FAQ::where(function ($q) {
            $q->where('status', 'Active')->orWhereNull('status');
        })->orderBy('faq_id')->get();

        if ($dbFaqs->isNotEmpty()) {
            $configMap = collect($configItems)->keyBy('question');
            $faqs = $dbFaqs->map(function ($faq) use ($categories, $configMap) {
                $catSlug = \Illuminate\Support\Str::slug($faq->category ?: 'getting-started');
                $matchedConfig = $configMap->get($faq->question);
                $anchorId = $matchedConfig['id'] ?? (\Illuminate\Support\Str::slug($faq->question) ?: ('faq-' . $faq->faq_id));

                return [
                    'id'            => $anchorId,
                    'category'      => $catSlug,
                    'category_name' => $categories[$catSlug] ?? ($faq->category ?: 'General'),
                    'question'      => $faq->question,
                    'answer'        => $faq->answer,
                    'keywords'      => strtolower($faq->question . ' ' . strip_tags($faq->answer)),
                ];
            });
        } else {
            $faqs = collect($configItems)->map(function ($item) use ($categories) {
                return [
                    'id'            => $item['id'],
                    'category'      => $item['category'],
                    'category_name' => $categories[$item['category']] ?? 'General',
                    'question'      => $item['question'],
                    'answer'        => $item['answer'],
                    'keywords'      => strtolower($item['question'] . ' ' . strip_tags($item['answer']) . ' ' . ($item['keywords'] ?? '')),
                ];
            });
        }

        return view('website.faq', compact('faqs', 'categories'));
    }

    /**
     * Download the latest active mobile application APK (auth & approval required, with maintenance guard).
     */
    public function downloadApp()
    {
        // 1. Guard against System Maintenance / Updating Mode
        if (\App\Models\Application::isMaintenanceMode()) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'SYSTEM_MAINTENANCE',
                    'message' => 'AgriCo is currently undergoing system updates and scheduled maintenance. Mobile application downloads are temporarily paused.',
                ], 503);
            }

            return redirect()->route('website.home')->with('maintenance_download_blocked', true);
        }

        $authUser = Auth::guard('web')->user() ?? Auth::user() ?? Auth::guard('admin')->user();
        if (!$authUser && session()->has('user_id')) {
            $authUser = User::where('user_id', session('user_id'))->first();
        }
        if (!$authUser && session()->has('admin_id')) {
            $authUser = User::where('user_id', session('admin_id'))->first();
        }

        if ($authUser && method_exists($authUser, 'isApproved') && !$authUser->isApproved()) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'ACCOUNT_PENDING_APPROVAL',
                    'message' => 'Your account is awaiting admin approval. Mobile app download will be enabled once approved.',
                ], 403);
            }

            return redirect()->route('website.home')->with('account_pending_download', true);
        }

        $activeApp = \App\Models\Application::where('status', 'ACTIVE')->first();

        // Track download record if possible
        try {
            $userId = $authUser ? ($authUser->user_id ?? $authUser->id ?? null) : null;
            \App\Models\Download::create([
                'user_id'        => $userId,
                'application_id' => $activeApp ? $activeApp->application_id : 1,
                'download_type'  => 'APK',
                'download_date'  => now(),
            ]);
        } catch (\Throwable $e) {}

        $activeFilename = $activeApp?->filename ?: 'agrico-app-v1.0.6.apk';
        if (!str_ends_with(strtolower($activeFilename), '.apk')) {
            $activeFilename .= '.apk';
        }

        // Resolve release URL & resilient GitHub API asset fallback
        $releaseUrl = ($activeApp && $activeApp->file_path && (str_starts_with($activeApp->file_path, 'http://') || str_starts_with($activeApp->file_path, 'https://')))
            ? $activeApp->file_path
            : 'https://github.com/kemmm221-wq/agriCo/releases/download/v1.0.6/agrico-app-v1.0.6.apk';

        if (str_contains($releaseUrl, 'agrico-releases')) {
            $releaseUrl = str_replace('agrico-releases', 'agriCo', $releaseUrl);
        }

        // If client explicitly requests direct redirect, send to canonical GitHub URL
        if (request()->query('mode') === 'redirect') {
            return redirect()->away($releaseUrl);
        }

        // 1. If local APK exists on disk, download it directly with correct MIME type and range support
        $candidatePaths = [
            storage_path('app/public/applications/' . $activeFilename),
            storage_path('app/public/applications/agrico-app-v1.0.6.apk'),
            public_path('storage/applications/' . $activeFilename),
            public_path('storage/applications/agrico-app-v1.0.6.apk'),
            public_path('applications/' . $activeFilename),
            public_path('applications/agrico-app-v1.0.6.apk'),
        ];

        foreach ($candidatePaths as $candidate) {
            if (file_exists($candidate) && filesize($candidate) > 1048576) {
                return response()->download($candidate, $activeFilename, [
                    'Content-Type'        => 'application/vnd.android.package-archive',
                    'Content-Disposition' => 'attachment; filename="' . $activeFilename . '"',
                ]);
            }
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('applications/' . $activeFilename)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->download('applications/' . $activeFilename, $activeFilename, [
                'Content-Type'        => 'application/vnd.android.package-archive',
                'Content-Disposition' => 'attachment; filename="' . $activeFilename . '"',
            ]);
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('applications/agrico-app-v1.0.6.apk')) {
            return \Illuminate\Support\Facades\Storage::disk('public')->download('applications/agrico-app-v1.0.6.apk', 'agrico-app-v1.0.6.apk', [
                'Content-Type'        => 'application/vnd.android.package-archive',
                'Content-Disposition' => 'attachment; filename="agrico-app-v1.0.6.apk"',
            ]);
        }

        // 2. Stream candidates in order of reliability:
        //    a) kemmm221-wq/agriCo release URL
        //    b) Verified GitHub API asset endpoint for v1.0.6 (bypasses GitHub web 504 Gateway Time-out)
        //    c) General release URL
        $streamUrls = [
            'https://github.com/kemmm221-wq/agriCo/releases/download/v1.0.6/agrico-app-v1.0.6.apk',
            'https://api.github.com/repos/agricosite77-oss/agrico/releases/assets/674167976',
            $releaseUrl,
        ];


        foreach ($streamUrls as $url) {
            try {
                $ctx = stream_context_create([
                    'http' => [
                        'follow_location' => 1,
                        'max_redirects'   => 5,
                        'user_agent'      => 'Mozilla/5.0 (Android; Mobile; rv:130.0) AgriCo-Downloader/1.0',
                        'header'          => "Accept: application/octet-stream\r\n",
                        'timeout'         => 30,
                    ],
                    'ssl' => [
                        'verify_peer'      => true,
                        'verify_peer_name' => true,
                    ],
                ]);

                $stream = @fopen($url, 'rb', false, $ctx);
                if ($stream) {
                    $contentLength = null;
                    if (isset($http_response_header) && is_array($http_response_header)) {
                        // Check if the stream returned an HTTP error (such as 504 or 404)
                        $statusLine = $http_response_header[0] ?? '';
                        if (str_contains($statusLine, '504') || str_contains($statusLine, '502') || str_contains($statusLine, '404')) {
                            fclose($stream);
                            continue;
                        }

                        foreach ($http_response_header as $hdr) {
                            if (stripos($hdr, 'content-length:') === 0) {
                                $len = trim(substr($hdr, 15));
                                if (is_numeric($len) && (int)$len > 0) {
                                    $contentLength = (int)$len;
                                }
                            }
                        }
                    }

                    $headers = [
                        'Content-Type'        => 'application/vnd.android.package-archive',
                        'Content-Disposition' => 'attachment; filename="' . $activeFilename . '"',
                        'Cache-Control'       => 'public, max-age=3600',
                        'Accept-Ranges'       => 'bytes',
                    ];
                    if ($contentLength) {
                        $headers['Content-Length'] = (string) $contentLength;
                    }

                    return response()->stream(function () use ($stream) {
                        while (!feof($stream) && !connection_aborted()) {
                            $chunk = fread($stream, 1024 * 128);
                            if ($chunk === false) break;
                            echo $chunk;
                            flush();
                        }
                        fclose($stream);
                    }, 200, $headers);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[WebsiteController] Direct stream failed for ' . $url . ': ' . $e->getMessage());
            }
        }

        // Fallback: redirect directly to canonical GitHub release URL
        return redirect()->away($releaseUrl);


    }


    /**
     * User Profile page (auth required).
     */
    public function profile()
    {
        /** @var \App\Models\User|\App\Models\Admin $user */
        $user = Auth::user() ?? Auth::guard('admin')->user();
        $userId = $user->user_id ?? $user->admin_id ?? null;

        $scanCount = $userId ? SoilScan::where('user_id', $userId)->count() : 0;

        $notifications = $userId
            ? \App\Models\UserNotification::where('user_id', $userId)->orderBy('created_at', 'desc')->limit(30)->get()
            : collect();
        $unreadCount = $userId
            ? \App\Models\UserNotification::where('user_id', $userId)->where('is_read', false)->count()
            : 0;

        $pendingDeletionRequest = null;
        if ($user && !empty($user->email)) {
            $pendingDeletionRequest = \App\Models\UserRequest::where('user_email', $user->email)
                ->where('type', 'Account Deletion Request')
                ->where('status', 'Pending')
                ->latest()
                ->first();
        }

        return view('website.profile', compact('user', 'scanCount', 'notifications', 'unreadCount', 'pendingDeletionRequest'));
    }

    /**
     * Update User Account Information.
     */
    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return back()->withErrors(['error' => 'You must be logged in to update profile.']);
        }

        $userRec = null;
        $adminRec = null;

        if ($user instanceof \App\Models\User) {
            $userRec = $user;
            $adminRec = \App\Models\Admin::where('email', $user->email)->orWhere('admin_id', $user->user_id)->first();
        } elseif ($user instanceof \App\Models\Admin) {
            $adminRec = $user;
            $userRec = \App\Models\User::where('email', $user->email)->orWhere('user_id', $user->admin_id)->first();
        }

        $ignoreId = $userRec?->id ?? ($user instanceof \App\Models\User ? $user->id : null);

        $emailRules = ['sometimes', 'required', 'email', 'max:255'];
        if ($ignoreId) {
            $emailRules[] = \Illuminate\Validation\Rule::unique('users', 'email')->ignore($ignoreId);
        }

        $request->validate([
            'name'       => ['sometimes', 'required', 'string', 'max:255'],
            'email'      => $emailRules,
            'password'   => ['nullable', 'string', 'min:8', 'confirmed'],
            'student_id' => ['nullable', 'string', 'max:50'],
            'section'    => ['nullable', 'string', 'max:100'],
            'photo'      => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $updateUserData = [];
        $updateAdminData = [];

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            if ($userRec && $userRec->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($userRec->profile_photo_path);
            }
            if ($adminRec && $adminRec->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($adminRec->profile_photo_path);
            }
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
            $updateUserData['profile_photo_path'] = $photoPath;
            $updateAdminData['profile_photo_path'] = $photoPath;
        }

        if ($request->filled('name')) {
            $updateUserData['name'] = $request->name;
            $updateAdminData['full_name'] = $request->name;
        }
        if ($request->filled('email')) {
            $updateUserData['email'] = $request->email;
            $updateAdminData['email'] = $request->email;
        }
        if ($request->has('student_id')) {
            $updateUserData['student_id'] = $request->student_id ? trim($request->student_id) : null;
        }
        if ($request->has('section')) {
            $updateUserData['section'] = $request->section ? trim($request->section) : null;
        }

        if ($request->filled('password')) {
            $checkPass = $user->password;
            if (empty($request->current_password) || (!empty($checkPass) && !\Illuminate\Support\Facades\Hash::check($request->current_password, $checkPass))) {
                return back()->withErrors(['current_password' => 'The current password is incorrect.']);
            }
            $hashed = \Illuminate\Support\Facades\Hash::make($request->password);
            $updateUserData['password'] = $hashed;
            $updateAdminData['password'] = $hashed;
        }

        $userUpdated = false;
        $adminUpdated = false;

        if ($userRec && !empty($updateUserData)) {
            $userUpdated = $userRec->update($updateUserData);
        }
        if ($adminRec && !empty($updateAdminData)) {
            $adminUpdated = $adminRec->update($updateAdminData);
        }

        if ((!empty($updateUserData) || !empty($updateAdminData)) && !$userUpdated && !$adminUpdated) {
            \Illuminate\Support\Facades\Log::error("Profile update claimed success but updated 0 rows for {$user->email}");
            return back()->withErrors(['error' => 'Could not update your account information. Please try again.']);
        }

        return back()->with('success', 'Account information updated successfully!');
    }

    /**
     * Update personal profile information immediately (name, section, student_id, photo).
     */
    public function updatePersonalInfo(\Illuminate\Http\Request $request)
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'student_id' => ['nullable', 'string', 'max:50'],
            'section'    => ['nullable', 'string', 'max:100'],
            'photo'      => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $userRec = null;
        $adminRec = null;
        if ($user instanceof \App\Models\User) {
            $userRec = $user;
            $adminRec = \App\Models\Admin::where('email', $user->email)->orWhere('admin_id', $user->user_id)->first();
        } elseif ($user instanceof \App\Models\Admin) {
            $adminRec = $user;
            $userRec = \App\Models\User::where('email', $user->email)->orWhere('user_id', $user->admin_id)->first();
        }

        $updateUserData = [];
        $updateAdminData = [];

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            if ($userRec && $userRec->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($userRec->profile_photo_path);
            }
            if ($adminRec && $adminRec->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($adminRec->profile_photo_path);
            }
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
            $updateUserData['profile_photo_path'] = $photoPath;
            $updateAdminData['profile_photo_path'] = $photoPath;
        }

        $newName = trim($request->input('name'));
        if (!empty($newName)) {
            $updateUserData['name'] = $newName;
            $updateAdminData['full_name'] = $newName;
        }

        if ($request->has('student_id')) {
            $updateUserData['student_id'] = $request->student_id ? trim($request->student_id) : null;
        }
        if ($request->has('section')) {
            $updateUserData['section'] = $request->section ? trim($request->section) : null;
        }

        if ($userRec && !empty($updateUserData)) {
            $userRec->update($updateUserData);
        }
        if ($adminRec && !empty($updateAdminData)) {
            $adminRec->update($updateAdminData);
        }

        \Illuminate\Support\Facades\Log::info("User {$user->email} updated personal information successfully.");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your personal information was updated successfully!',
                'name'    => $newName,
            ]);
        }

        return back()->with('success', 'Your personal information was updated successfully!');
    }

    /**
     * Send 6-digit email change verification code to new email.
     */
    public function sendEmailChangeCode(\Illuminate\Http\Request $request)
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $request->validate([
            'new_email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $newEmail = strtolower(trim((string) $request->input('new_email')));
        $currentEmail = strtolower(trim((string) $user->email));

        if ($newEmail === $currentEmail) {
            return response()->json([
                'success' => false,
                'message' => 'The new email address cannot be the same as your current email.'
            ], 422);
        }

        $userId = $user->user_id ?? $user->admin_id ?? null;

        // Check if new email is in use by another user
        $userExists = \App\Models\User::whereRaw('LOWER(email) = ?', [$newEmail])
            ->where('user_id', '!=', $userId)
            ->exists();
        $adminExists = \App\Models\Admin::whereRaw('LOWER(email) = ?', [$newEmail])
            ->where('admin_id', '!=', $userId)
            ->exists();

        if ($userExists || $adminExists) {
            return response()->json([
                'success' => false,
                'message' => 'This email address is already in use by another account.'
            ], 422);
        }

        // Rate limiting: 4 requests per 10 minutes
        $rateKey = 'email_change_rate_' . ($userId ?: $request->ip());
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateKey, 4)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($rateKey);
            $mins = ceil($seconds / 60);
            return response()->json([
                'success' => false,
                'message' => "Too many verification code attempts. Please wait {$mins} minute(s) before requesting another code."
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rateKey, 600);

        // Generate 6-digit numeric verification code
        $code = sprintf('%06d', random_int(100000, 999999));

        // Persist code in cache (15 mins) and in password_reset_tokens
        \Illuminate\Support\Facades\Cache::put('email_change_' . $userId, [
            'code'      => $code,
            'new_email' => $newEmail,
            'old_email' => $currentEmail,
            'user_id'   => $userId,
        ], now()->addMinutes(15));

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'email_change:' . $userId],
            [
                'token'      => $code . '|' . $newEmail,
                'created_at' => now(),
            ]
        );

        // Dispatch email via Resend / Mail
        \App\Services\AgriCoMailService::send(
            $newEmail,
            "Your AgriCo Email Verification Code: {$code}",
            'emails.email-change-code',
            [
                'code'     => $code,
                'newEmail' => $newEmail,
                'userName' => $user->name,
            ],
            new \App\Mail\EmailChangeVerificationMail($code, $newEmail, $user->name)
        );

        \Illuminate\Support\Facades\Log::info("Sent email change verification code to {$newEmail} for user {$userId}");

        return response()->json([
            'success' => true,
            'message' => "A 6-digit verification code has been sent to {$newEmail}. Please enter it below to confirm your change."
        ]);
    }

    /**
     * Verify 6-digit code and apply email change.
     */
    public function verifyEmailChangeCode(\Illuminate\Http\Request $request)
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $request->validate([
            'code'      => ['required', 'string', 'size:6'],
            'new_email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $code = trim((string) $request->input('code'));
        $newEmail = strtolower(trim((string) $request->input('new_email')));
        $userId = $user->user_id ?? $user->admin_id ?? null;

        $cached = \Illuminate\Support\Facades\Cache::get('email_change_' . $userId);
        $dbToken = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', 'email_change:' . $userId)
            ->first();

        $valid = false;
        if ($cached && isset($cached['code'], $cached['new_email'])) {
            if ($cached['code'] === $code && strtolower($cached['new_email']) === $newEmail) {
                $valid = true;
            }
        } elseif ($dbToken && !empty($dbToken->token)) {
            $parts = explode('|', $dbToken->token);
            if (count($parts) >= 2 && $parts[0] === $code && strtolower($parts[1]) === $newEmail) {
                $tokenCreated = \Carbon\Carbon::parse($dbToken->created_at);
                if ($tokenCreated->addMinutes(15)->isFuture()) {
                    $valid = true;
                }
            }
        }

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'The verification code is invalid or has expired. Please request a new code.'
            ], 422);
        }

        $oldEmail = $user->email;

        // Apply new email
        $userRec = null;
        $adminRec = null;
        if ($user instanceof \App\Models\User) {
            $userRec = $user;
            $adminRec = \App\Models\Admin::where('email', $user->email)->orWhere('admin_id', $user->user_id)->first();
        } elseif ($user instanceof \App\Models\Admin) {
            $adminRec = $user;
            $userRec = \App\Models\User::where('email', $user->email)->orWhere('user_id', $user->admin_id)->first();
        }

        if ($userRec) {
            $userRec->update(['email' => $newEmail]);
        }
        if ($adminRec) {
            $adminRec->update(['email' => $newEmail]);
        }

        // Clean up code
        \Illuminate\Support\Facades\Cache::forget('email_change_' . $userId);
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', 'email_change:' . $userId)->delete();

        // Send security notice to OLD email
        \App\Services\AgriCoMailService::send(
            $oldEmail,
            'Security Alert: Your AgriCo account email was updated',
            'emails.email-changed-notice',
            [
                'oldEmail' => $oldEmail,
                'newEmail' => $newEmail,
                'userName' => $user->name,
            ],
            new \App\Mail\EmailChangedNoticeMail($oldEmail, $newEmail, $user->name)
        );

        \Illuminate\Support\Facades\Log::info("User {$userId} successfully changed email from {$oldEmail} to {$newEmail}");

        return response()->json([
            'success'   => true,
            'message'   => "Your account email has been successfully updated to {$newEmail}!",
            'new_email' => $newEmail,
        ]);
    }

    /**
     * Change user account password with security checks and session invalidation.
     */
    public function updatePassword(\Illuminate\Http\Request $request)
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (empty($user->password) || !\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The current password you entered is incorrect.'
            ], 422);
        }

        $newPass = (string) $request->input('password');
        if (!preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) || !preg_match('/[0-9]/', $newPass) || !preg_match('/[^A-Za-z0-9]/', $newPass)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must contain at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character.'
            ], 422);
        }

        $hashed = \Illuminate\Support\Facades\Hash::make($newPass);

        $userRec = null;
        $adminRec = null;
        if ($user instanceof \App\Models\User) {
            $userRec = $user;
            $adminRec = \App\Models\Admin::where('email', $user->email)->orWhere('admin_id', $user->user_id)->first();
        } elseif ($user instanceof \App\Models\Admin) {
            $adminRec = $user;
            $userRec = \App\Models\User::where('email', $user->email)->orWhere('user_id', $user->admin_id)->first();
        }

        if ($userRec) {
            $userRec->update(['password' => $hashed]);
        }
        if ($adminRec) {
            $adminRec->update(['password' => $hashed]);
        }

        // Sign out other sessions
        try {
            Auth::logoutOtherDevices($newPass);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Could not logout other devices: " . $e->getMessage());
        }

        // Send security alert to user email
        \App\Services\AgriCoMailService::send(
            $user->email,
            'Security Alert: Your AgriCo account password was changed',
            'emails.password-changed-notice',
            [
                'userEmail' => $user->email,
                'userName'  => $user->name,
                'ipAddress' => $request->ip(),
                'changedAt' => now()->format('M d, Y h:i A'),
            ],
            new \App\Mail\PasswordChangedNoticeMail($user->email, $user->name, $request->ip())
        );

        \Illuminate\Support\Facades\Log::info("User {$user->email} changed password from IP {$request->ip()}");

        return response()->json([
            'success' => true,
            'message' => 'Your password was changed successfully! Other device sessions have been signed out.',
        ]);
    }

    /**
     * Submit an Account Deletion Request.
     */
    public function requestAccountDeletion(\Illuminate\Http\Request $request)
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        // Admins cannot delete via this user flow
        $role = strtolower(trim((string) ($user->role ?? '')));
        if (in_array($role, ['admin', 'superadmin', 'super admin']) || in_array(strtolower($user->email), ['agricosite77@gmail.com', 'admin@agrico.com'])) {
            return response()->json([
                'success' => false,
                'message' => 'Administrative accounts cannot be deleted through this user flow. Please contact system administrators.'
            ], 403);
        }

        $request->validate([
            'password'     => ['required', 'string'],
            'confirmation' => ['accepted'],
            'reason'       => ['nullable', 'string', 'max:255'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($user->password) || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The password you entered is incorrect.'
            ], 422);
        }

        // Check if an active deletion request already exists
        $existing = \App\Models\UserRequest::where('user_email', $user->email)
            ->where('type', 'Account Deletion Request')
            ->where('status', 'Pending')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "You already have an active deletion request ({$existing->request_code}) currently pending review."
            ], 422);
        }

        // Rate limiting
        $rateKey = 'deletion_req_' . ($user->user_id ?? $request->ip());
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateKey, 2)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many deletion requests submitted. Please wait before submitting another request.'
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rateKey, 3600);

        // Generate request code
        $count = \App\Models\UserRequest::count() + 1;
        $requestCode = 'REQ-' . date('Y') . '-' . str_pad((string)$count, 3, '0', STR_PAD_LEFT);

        $reasonText = $request->input('reason') ?: 'No reason specified';
        $notesText = $request->input('notes') ? ' | Notes: ' . trim($request->input('notes')) : '';

        $userRequest = \App\Models\UserRequest::create([
            'request_code' => $requestCode,
            'user_name'    => $user->name,
            'user_email'   => $user->email,
            'type'         => 'Account Deletion Request',
            'description'  => "Reason: {$reasonText}{$notesText} | Requested by User ID: {$user->user_id}",
            'status'       => 'Pending',
            'submitted_at' => now(),
        ]);

        // Secure signed cancellation link valid for 30 days
        $cancelUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'website.profile.delete-cancel-signed',
            now()->addDays(30),
            ['id' => $userRequest->user_request_id]
        );

        // Send ONE confirmation email to user's Gmail
        \App\Services\AgriCoMailService::send(
            $user->email,
            "We received your AgriCo account deletion request ({$requestCode})",
            'emails.account-deletion-requested',
            [
                'requestCode' => $requestCode,
                'userEmail'   => $user->email,
                'userName'    => $user->name,
                'cancelUrl'   => $cancelUrl,
                'reason'      => $reasonText,
                'submittedAt' => now()->format('M d, Y h:i A'),
            ],
            new \App\Mail\AccountDeletionRequestedMail(
                $requestCode,
                $user->email,
                $user->name,
                $cancelUrl,
                $reasonText,
                now()->format('M d, Y h:i A')
            )
        );

        \Illuminate\Support\Facades\Log::info("User {$user->email} submitted account deletion request {$requestCode}");

        return response()->json([
            'success'      => true,
            'message'      => 'Your account deletion request has been submitted for review. A confirmation email has been sent to your Gmail with request details and a link to cancel.',
            'request_code' => $requestCode,
        ]);
    }

    /**
     * Cancel an active account deletion request from the profile settings.
     */
    public function cancelAccountDeletion(\Illuminate\Http\Request $request)
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $pending = \App\Models\UserRequest::where('user_email', $user->email)
            ->where('type', 'Account Deletion Request')
            ->where('status', 'Pending')
            ->latest()
            ->first();

        if (!$pending) {
            return response()->json([
                'success' => false,
                'message' => 'No pending deletion request was found for your account.'
            ], 404);
        }

        $pending->update(['status' => 'Cancelled']);

        // Send cancellation notice email
        \App\Services\AgriCoMailService::send(
            $user->email,
            "Your AgriCo account deletion request has been cancelled ({$pending->request_code})",
            'emails.account-deletion-cancelled',
            [
                'requestCode' => $pending->request_code,
                'userEmail'   => $user->email,
                'userName'    => $user->name,
            ],
            new \App\Mail\AccountDeletionCancelledMail($pending->request_code, $user->email, $user->name)
        );

        \Illuminate\Support\Facades\Log::info("User {$user->email} cancelled deletion request {$pending->request_code}");

        return response()->json([
            'success' => true,
            'message' => "Your account deletion request ({$pending->request_code}) has been successfully cancelled.",
        ]);
    }

    /**
     * Signed cancellation endpoint clicked from user's Gmail.
     */
    public function cancelAccountDeletionSigned(\Illuminate\Http\Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('website.profile')->withErrors([
                'error' => 'This cancellation link is invalid or has expired.'
            ]);
        }

        $req = \App\Models\UserRequest::find($id);
        if (!$req || !str_contains(strtolower($req->type), 'delet')) {
            return redirect()->route('website.profile')->withErrors([
                'error' => 'Deletion request was not found.'
            ]);
        }

        if ($req->status === 'Cancelled') {
            return redirect()->route('website.profile')->with('success', "Your deletion request ({$req->request_code}) was already cancelled.");
        }

        if ($req->status !== 'Pending') {
            return redirect()->route('website.profile')->withErrors([
                'error' => "This deletion request has already been {$req->status} and cannot be cancelled."
            ]);
        }

        $req->update(['status' => 'Cancelled']);

        \App\Services\AgriCoMailService::send(
            $req->user_email,
            "Your AgriCo account deletion request has been cancelled ({$req->request_code})",
            'emails.account-deletion-cancelled',
            [
                'requestCode' => $req->request_code,
                'userEmail'   => $req->user_email,
                'userName'    => $req->user_name ?? 'AgriCo User',
            ],
            new \App\Mail\AccountDeletionCancelledMail($req->request_code, $req->user_email, $req->user_name ?? 'AgriCo User')
        );

        \Illuminate\Support\Facades\Log::info("Deletion request {$req->request_code} cancelled via signed URL for {$req->user_email}");

        return redirect()->route('website.profile')->with('success', "Your account deletion request ({$req->request_code}) was successfully cancelled!");
    }

    /**
     * Verify current password via AJAX.
     */
    public function verifyPassword(\Illuminate\Http\Request $request)
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json(['valid' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $currentPassword = $request->input('current_password');
        if (empty($currentPassword)) {
            return response()->json(['valid' => false, 'message' => 'Please enter your current password.']);
        }

        $isValid = false;
        if (!empty($user->password)) {
            $isValid = \Illuminate\Support\Facades\Hash::check($currentPassword, $user->password);
        }

        return response()->json([
            'valid'   => $isValid,
            'message' => $isValid ? 'Current password verified' : 'Incorrect current password',
        ]);
    }

    /**
     * Display the AgriCo Privacy Policy.
     */
    public function privacy(): \Illuminate\Contracts\View\View
    {
        return view('website.privacy');
    }

    /**
     * Display the AgriCo Terms of Service.
     */
    public function terms(): \Illuminate\Contracts\View\View
    {
        return view('website.terms');
    }
}
