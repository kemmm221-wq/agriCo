<?php

namespace App\Http\Controllers\Mobile\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RealSoilController extends Controller
{
    protected string $realSoilRoot;
    protected array $candidateRoots;
    protected string $fakeSoilRoot;
    protected array $candidateFakeRoots;

    public function __construct()
    {
        $pythonRoot = base_path('python/Scan_Soil_Image/Real_Soil');
        $scanRoot = base_path('Scan_Soil_Image/Real_Soil');
        $this->candidateRoots = array_filter([$pythonRoot, $scanRoot], 'is_dir');
        $this->realSoilRoot = is_dir($pythonRoot) ? $pythonRoot : $scanRoot;

        $pythonFakeRoot = base_path('python/Scan_Soil_Image/Fake_Soil');
        $scanFakeRoot = base_path('Scan_Soil_Image/Fake_Soil');
        $this->candidateFakeRoots = array_filter([$pythonFakeRoot, $scanFakeRoot], 'is_dir');
        $this->fakeSoilRoot = is_dir($pythonFakeRoot) ? $pythonFakeRoot : $scanFakeRoot;
    }

    /**
     * Get Stage 1 Soil Gate calibration parameters, thresholds, and curated real and fake soil reference samples.
     */
    public function getCalibration(Request $request)
    {
        $cacheKey = 'real_soil_calibration_v3';
        $data = Cache::remember($cacheKey, 1800, function () {
            $realSamples = $this->findRealSoilImages('All', '', 25);
            $fakeSamples = $this->findFakeSoilImages(20);

            $munsellColors = \App\Models\MunsellColor::select([
                'munsell_color_id', 'full_code', 'color_name', 'hue', 'value', 'chroma',
                'hex_code', 'soil_organic_index', 'soil_type', 'description'
            ])->get();

            $referenceCentroids = $munsellColors->map(function ($c) {
                $hex = ltrim($c->hex_code ?? '#5A4643', '#');
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                return [
                    'munsellCode'        => $c->full_code,
                    'colorName'          => $c->color_name,
                    'hue'                => $c->hue,
                    'hexCode'            => $c->hex_code,
                    'rgb'                => [$r, $g, $b],
                    'soil_organic_index' => $c->soil_organic_index,
                    'soil_type'          => $c->soil_type,
                ];
            });

            // Reference non-soil centroids (skin, paper, desks, walls, plastics) for offline rejection
            $fakeCentroids = [
                ['name' => 'Human Skin Tone 1', 'rgb' => [225, 175, 150], 'hexCode' => '#E1AF96', 'type' => 'skin'],
                ['name' => 'Human Skin Tone 2', 'rgb' => [185, 130, 105], 'hexCode' => '#B98269', 'type' => 'skin'],
                ['name' => 'Blank White Paper', 'rgb' => [245, 245, 245], 'hexCode' => '#F5F5F5', 'type' => 'paper'],
                ['name' => 'Office Desk Brown', 'rgb' => [175, 120, 75], 'hexCode' => '#AF784B', 'type' => 'furniture'],
                ['name' => 'Gray Concrete / Tile', 'rgb' => [180, 180, 180], 'hexCode' => '#B4B4B4', 'type' => 'tile'],
                ['name' => 'Dark Keyboard / Surface', 'rgb' => [35, 35, 35], 'hexCode' => '#232323', 'type' => 'plastic'],
                ['name' => 'Plant Foliage / Leaf', 'rgb' => [45, 160, 40], 'hexCode' => '#2DA028', 'type' => 'foliage'],
                ['name' => 'Blue Cloth / Object', 'rgb' => [45, 115, 205], 'hexCode' => '#2D73CD', 'type' => 'artificial'],
            ];

            return [
                'status'              => 'success',
                'model_version'       => '3.0.0',
                'engine'              => 'Stage 1-6 Soil Gate & Munsell Spectral Analyzer',
                'gate_config'         => [
                    'min_variance'    => 8.5,
                    'min_laplacian'   => 18.0,
                    'max_skin_ratio'  => 0.32,
                    'max_delta_e'     => 32.0,
                    'allowed_hues'    => ['10R', '2.5YR', '5YR', '7.5YR', '10YR', '2.5Y', '5Y', 'GLEY'],
                    'valid_lum_range' => [12, 85],
                ],
                'samples_count'       => count($realSamples) + count($fakeSamples),
                'real_samples_count'  => count($realSamples),
                'fake_samples_count'  => count($fakeSamples),
                'real_samples'        => $realSamples,
                'fake_samples'        => $fakeSamples,
                'reference_centroids' => $referenceCentroids,
                'fake_centroids'      => $fakeCentroids,
                'reference_samples'   => $realSamples, // Backwards compatibility
                'munsell_colors'      => $munsellColors,
                'updated_at'          => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Query real soil images from the Real_Soil store.
     * Supports filtering by Munsell code (e.g. "5YR 4/2") or Hue (e.g. "5YR").
     */
    public function getImages(Request $request)
    {
        $hue = trim((string) $request->query('hue', ''));
        $code = trim((string) $request->query('code', ''));
        $limit = max(1, min(50, (int) $request->query('limit', 15)));

        $cacheKey = 'real_soil_img_' . md5($hue . '|' . $code . '|' . $limit);

        $results = Cache::remember($cacheKey, 3600, function () use ($hue, $code, $limit) {
            return $this->findRealSoilImages($hue, $code, $limit);
        });

        return response()->json([
            'status' => 'success',
            'count'  => count($results),
            'data'   => $results,
        ]);
    }

    /**
     * Serve a real or fake soil image file directly with caching headers.
     */
    public function serveFile(string $path)
    {
        // Sanitize and prevent directory traversal
        $cleanPath = str_replace(['..', "\0"], '', $path);
        
        $isFake = str_starts_with($cleanPath, 'fake/');
        $isReal = str_starts_with($cleanPath, 'real/');
        $subPath = $cleanPath;
        if ($isFake) {
            $subPath = substr($cleanPath, 5);
        } elseif ($isReal) {
            $subPath = substr($cleanPath, 5);
        }

        $allRoots = $isFake
            ? array_merge($this->candidateFakeRoots, $this->candidateRoots)
            : array_merge($this->candidateRoots, $this->candidateFakeRoots);

        $fullPath = null;
        foreach ($allRoots as $root) {
            $candidate = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);
            if (file_exists($candidate) && !is_dir($candidate)) {
                $fullPath = $candidate;
                break;
            }
        }
        if (!$fullPath) {
            $fallbackRoot = $isFake ? $this->fakeSoilRoot : $this->realSoilRoot;
            $fullPath = $fallbackRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);
        }

        if (!file_exists($fullPath) || is_dir($fullPath)) {
            return response()->json(['status' => 'error', 'message' => 'Image not found'], 404);
        }

        $mimeType = match (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpeg', 'jpg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return new BinaryFileResponse($fullPath, 200, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }

    /**
     * Find curated non-soil / fake soil images from the Fake_Soil store.
     * Samples evenly across available fake images (hands, paper, desk, plastics, walls).
     */
    protected function findFakeSoilImages(int $limit = 20): array
    {
        if (!is_dir($this->fakeSoilRoot)) {
            return [];
        }

        $pattern = $this->fakeSoilRoot . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,JPG,JPEG,PNG}';
        $allFiles = glob($pattern, GLOB_BRACE) ?: [];
        $total = count($allFiles);
        if ($total === 0) {
            return [];
        }

        // Evenly sample across the dataset to get diverse non-soil surfaces
        $step = max(1, (int) floor($total / max(1, $limit)));
        $selected = [];
        for ($i = 0; $i < $total && count($selected) < $limit; $i += $step) {
            $selected[] = $allFiles[$i];
        }

        $output = [];
        foreach ($selected as $file) {
            $filename = basename($file);
            $jsonFile = preg_replace('/\.(jpg|jpeg|png)$/i', '.json', $file);
            $meta = [];
            if ($jsonFile && file_exists($jsonFile)) {
                $raw = @file_get_contents($jsonFile);
                if ($raw) {
                    $meta = json_decode($raw, true) ?: [];
                }
            }

            $output[] = [
                'id'         => 'fs_' . md5($filename),
                'url'        => request()->root() . '/api/real-soil/file/fake/' . $filename,
                'filename'   => $filename,
                'type'       => 'fake_soil',
                'label'      => 'Non-Soil Reference',
                'confidence' => $meta['confidence'] ?? 1.0,
                'source'     => $meta['source'] ?? 'reference_dataset',
            ];
        }

        return $output;
    }

    /**
     * Find real soil images from the folder hierarchy.
     */
    protected function findRealSoilImages(string $hue, string $code, int $limit): array
    {
        if (!is_dir($this->realSoilRoot)) {
            return [];
        }

        $files = [];

        // 1. If exact Munsell code requested (e.g. "5YR 4/2" or "5YR 4-2")
        if (!empty($code)) {
            $parsed = $this->parseMunsellCode($code);
            if ($parsed) {
                $codeHue = $parsed['hue'];
                $codeVal = $parsed['value'];
                $codeChr = $parsed['chroma'];

                $targetDir = $this->realSoilRoot . DIRECTORY_SEPARATOR . $codeHue . DIRECTORY_SEPARATOR . $codeHue . ' ' . $codeVal . DIRECTORY_SEPARATOR . $codeChr;
                if (is_dir($targetDir)) {
                    $pattern = $targetDir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,JPG,JPEG,PNG}';
                    $matched = glob($pattern, GLOB_BRACE) ?: [];
                    foreach ($matched as $file) {
                        $files[] = [
                            'path' => $file,
                            'hue' => $codeHue,
                            'value' => $codeVal,
                            'chroma' => $codeChr,
                            'code' => "{$codeHue} {$codeVal}/{$codeChr}",
                        ];
                        if (count($files) >= $limit) {
                            break;
                        }
                    }
                }

                // If fewer than limit found, widen search to same hue
                if (count($files) < $limit) {
                    $hue = $codeHue;
                }
            }
        }

        // 2. If hue is specified and not 'All'
        if (empty($files) && !empty($hue) && $hue !== 'All') {
            $hueDir = $this->realSoilRoot . DIRECTORY_SEPARATOR . $hue;
            if (is_dir($hueDir)) {
                $pattern = $hueDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,JPG,JPEG,PNG}';
                $matched = glob($pattern, GLOB_BRACE) ?: [];
                // Shuffle deterministically or sample evenly
                foreach ($matched as $file) {
                    $meta = $this->extractMetaFromPath($file);
                    $files[] = [
                        'path' => $file,
                        'hue' => $meta['hue'] ?: $hue,
                        'value' => $meta['value'] ?: '',
                        'chroma' => $meta['chroma'] ?: '',
                        'code' => $meta['code'] ?: $hue,
                    ];
                    if (count($files) >= $limit) {
                        break;
                    }
                }
            }
        }

        // 3. Fallback / "All": Sample across all available hues
        if (empty($files)) {
            $allHues = ['10YR', '7.5YR', '5YR', '2.5YR', '10R', '2.5Y'];
            $perHue = max(2, (int) ceil($limit / count($allHues)));

            foreach ($allHues as $h) {
                $hDir = $this->realSoilRoot . DIRECTORY_SEPARATOR . $h;
                if (is_dir($hDir)) {
                    $pattern = $hDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,JPG,JPEG,PNG}';
                    $matched = glob($pattern, GLOB_BRACE) ?: [];
                    $count = 0;
                    foreach ($matched as $file) {
                        $meta = $this->extractMetaFromPath($file);
                        $files[] = [
                            'path' => $file,
                            'hue' => $meta['hue'] ?: $h,
                            'value' => $meta['value'] ?: '',
                            'chroma' => $meta['chroma'] ?: '',
                            'code' => $meta['code'] ?: $h,
                        ];
                        $count++;
                        if ($count >= $perHue) {
                            break;
                        }
                    }
                }
                if (count($files) >= $limit) {
                    break;
                }
            }
        }

        // Format results
        $output = [];
        foreach ($files as $idx => $item) {
            $relPath = ltrim(str_replace([$this->realSoilRoot, '\\'], ['', '/'], $item['path']), '/');
            $filename = basename($item['path']);
            $output[] = [
                'id'          => 'rs_' . md5($relPath),
                'url'         => request()->root() . '/api/real-soil/file/' . $relPath,
                'munsellCode' => $item['code'],
                'hue'         => $item['hue'],
                'value'       => $item['value'],
                'chroma'      => $item['chroma'],
                'filename'    => $filename,
            ];
        }

        return $output;
    }

    /**
     * Parse string like "5YR 4/2", "5YR 4-2", or "5YR4/2".
     */
    protected function parseMunsellCode(string $code): ?array
    {
        if (preg_match('/^([0-9\.]+[A-Z]+)\s*([0-9\.]+)\s*[\/\-]\s*([0-9\.]+)$/i', trim($code), $matches)) {
            return [
                'hue'    => strtoupper($matches[1]),
                'value'  => $matches[2],
                'chroma' => $matches[3],
            ];
        }
        return null;
    }

    /**
     * Extract metadata from directory structure.
     * e.g. .../Real_Soil/5YR/5YR 4/2/real_soil_...
     */
    protected function extractMetaFromPath(string $file): array
    {
        $parts = explode(DIRECTORY_SEPARATOR, $file);
        $count = count($parts);
        if ($count >= 3) {
            $chroma = $parts[$count - 2];
            $hueVal = $parts[$count - 3];
            $hue = $parts[$count - 4] ?? '';

            // If $hueVal is e.g. "5YR 4"
            $valParts = explode(' ', $hueVal);
            $value = $valParts[1] ?? '';

            return [
                'hue'    => $hue ?: ($valParts[0] ?? ''),
                'value'  => $value,
                'chroma' => $chroma,
                'code'   => ($hue && $value && $chroma) ? "{$hue} {$value}/{$chroma}" : '',
            ];
        }

        return ['hue' => '', 'value' => '', 'chroma' => '', 'code' => ''];
    }
}
