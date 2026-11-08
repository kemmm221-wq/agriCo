<?php

namespace App\Http\Controllers\Mobile\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AppVersionController extends Controller
{
    /**
     * Resolve direct CDN / asset URL for GitHub release so clients get HTTP 200
     * and avoid client-side URL mutation or multi-hop redirect limits in RNFS.
     */
    public static function resolveDirectAssetUrl(string $githubUrl): string
    {
        // Sanitize agrico-releases to agrico if present
        if (str_contains($githubUrl, 'agrico-releases')) {
            $githubUrl = str_replace('agrico-releases', 'agrico', $githubUrl);
        }

        // Keep cache short (60s) because Azure SAS tokens have limited lifespans
        return Cache::remember('resolved_direct_apk_' . md5($githubUrl), 60, function () use ($githubUrl) {
            try {
                $ch = curl_init($githubUrl);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_exec($ch);
                $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && !empty($finalUrl) && str_starts_with($finalUrl, 'https://')) {
                    return $finalUrl;
                }
            } catch (\Throwable $e) {}

            return $githubUrl;
        });
    }

    /**
     * Check the latest available mobile application version.
     */
    public function checkVersion(Request $request)
    {
        $activeApp = Application::where('status', 'ACTIVE')->orderByDesc('application_id')->first();

        $defaultVersion = 'v1.0.8';
        $defaultNotes = "• Full Scan History account synchronization & persistent cloud linking\n• On-Device Real & Fake Soil Reference Datasets\n• High-resolution PNG Soil Record Card export on Save Copy\n• Fast Password Reset with instant verification code delivery";
        $defaultSize = '88.5 MB';
        $defaultUrl = 'https://github.com/kemmm221-wq/agriCo/releases/download/v1.0.8/agrico-app-v1.0.8.apk';

        $version = $activeApp?->version ?: $defaultVersion;

        $releaseNotes = $activeApp?->release_notes ?: $defaultNotes;
        $fileSize = $activeApp?->file_size ?: $defaultSize;

        $filePath = $activeApp?->file_path;
        if ($filePath && (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://'))) {
            $apkUrl = $filePath;
        } else {
            $apkUrl = $defaultUrl;
        }

        if (str_contains($apkUrl, 'agrico-releases')) {
            $apkUrl = str_replace('agrico-releases', 'agrico', $apkUrl);
        }

        $directApkUrl = self::resolveDirectAssetUrl($apkUrl);

        $isMaintenance = Application::isMaintenanceMode();
        $downloadUrl = $request->getSchemeAndHttpHost() . '/download';

        return response()->json([
            'status'         => 'success',
            'latest_version' => $version,
            'release_notes'  => $releaseNotes,
            'file_size'      => $fileSize,
            'download_url'   => $downloadUrl,
            'direct_apk_url' => $directApkUrl,
            'github_url'     => $apkUrl,
            'is_maintenance' => $isMaintenance,
            'published_at'   => $activeApp?->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ]);
    }
}
