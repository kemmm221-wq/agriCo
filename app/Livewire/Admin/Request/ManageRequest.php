<?php

namespace App\Livewire\Admin\Request;

use App\Models\Admin;
use App\Models\UserRequest;
use Livewire\Component;
use Livewire\WithPagination;

class ManageRequest extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $selectedDate = '';
    public $perPage = 10;

    public $selectedRequestId = null;
    public $showViewModal = false;

    public function mount()
    {
        // Seeding handled by database seeder
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingSelectedDate()
    {
        $this->resetPage();
    }

    public function viewRequest($id)
    {
        $this->selectedRequestId = $id;
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->selectedRequestId = null;
    }

    public function approve(int|string|null $id = null): void
    {
        $id = $id ?? $this->selectedRequestId;
        if (!$id) return;

        $result = UserRequest::approveRequest($id, Admin::currentAdminId());

        if ($result['success']) {
            session()->flash('message', $result['message']);
            $this->dispatch('request-updated');
            $this->dispatch('sidebar-counts-updated');
        } else {
            session()->flash('error', $result['message']);
        }

        $this->showViewModal = false;
        $this->selectedRequestId = null;
    }

    public function disapprove(int|string|null $id = null): void
    {
        $id = $id ?? $this->selectedRequestId;
        if (!$id) return;

        $result = UserRequest::disapproveRequest($id, Admin::currentAdminId());

        if ($result['success']) {
            session()->flash('message', $result['message']);
            $this->dispatch('request-updated');
            $this->dispatch('sidebar-counts-updated');
        } else {
            session()->flash('error', $result['message']);
        }

        $this->showViewModal = false;
        $this->selectedRequestId = null;
    }

    public function render()
    {
        $query = UserRequest::query();

        if (!empty($this->selectedDate)) {
            $query->whereDate('created_at', $this->selectedDate);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('user_name', 'like', '%' . $this->search . '%')
                  ->orWhere('user_email', 'like', '%' . $this->search . '%')
                  ->orWhere('type', 'like', '%' . $this->search . '%')
                  ->orWhere('request_code', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $userRequests = $query->orderBy('user_request_id', 'desc')->paginate($this->perPage);

        // Single aggregated query instead of 3 separate COUNT queries
        $counts = UserRequest::selectRaw("
            SUM(CASE WHEN status = 'Pending'     THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Approved'    THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'Disapproved' THEN 1 ELSE 0 END) as disapproved
        ")->first();

        $selectedRequest = $this->selectedRequestId ? UserRequest::find($this->selectedRequestId) : null;
        $selectedRequestLocation = $this->getSelectedRequestLocation($selectedRequest);

        return view('admin.livewire.request.manage-request', [
            'userRequests'            => $userRequests,
            'pendingCount'            => (int) ($counts->pending     ?? 0),
            'approvedCount'           => (int) ($counts->approved    ?? 0),
            'disapprovedCount'        => (int) ($counts->disapproved ?? 0),
            'selectedRequest'         => $selectedRequest,
            'selectedRequestLocation' => $selectedRequestLocation,
            'showViewModal'           => $this->showViewModal,
        ]);
    }

    protected function getSelectedRequestLocation(?UserRequest $req): ?array
    {
        if (!$req) return null;

        $desc = (string) ($req->description ?? '');
        $type = strtolower((string) ($req->type ?? ''));

        $isSoilCoord = str_contains($type, 'coordinate')
            || str_contains($type, 'location')
            || str_contains($type, 'pin')
            || str_contains($type, 'soil')
            || str_contains(strtolower($desc), 'soil sample location pin')
            || str_contains(strtolower($desc), 'coords:');

        if (!$isSoilCoord) return null;

        $locationMapping = null;
        if (preg_match('/Location Pin #(\d+)/i', $desc, $pinMatches)) {
            $locationMapping = \App\Models\LocationMapping::with(['soil.munsellColor', 'soilScan'])->find($pinMatches[1]);
        }

        $lat = null;
        $lng = null;
        if ($locationMapping && $locationMapping->latitude && $locationMapping->longitude) {
            $lat = (float) $locationMapping->latitude;
            $lng = (float) $locationMapping->longitude;
        } elseif (preg_match('/Coords:\s*\(([^,]+),\s*([^)]+)\)/i', $desc, $c)) {
            $lat = (float) trim($c[1]);
            $lng = (float) trim($c[2]);
        }

        if (!$lat || !$lng) return null;

        $isPinAllText = (bool) preg_match('/Pin All:\s*(\d+)/i', $desc);
        if (!$locationMapping && $lat && $lng) {
            $mappingsQuery = \App\Models\LocationMapping::with(['soil.munsellColor', 'soilScan'])
                ->where('latitude', $lat)
                ->where('longitude', $lng);
            $locationMapping = $mappingsQuery->latest('location_mapping_id')->first();
        }

        $isAllPinned = $isPinAllText;
        if (!$isAllPinned && !preg_match('/Location Pin #/i', $desc) && $locationMapping && $locationMapping->soil_scan_id) {
            $countPinsForScan = \App\Models\LocationMapping::where('soil_scan_id', $locationMapping->soil_scan_id)->count();
            if ($countPinsForScan > 1 && $locationMapping->pinned_soil_index === null) {
                $isAllPinned = true;
            }
        }

        $coverage = '';
        if (preg_match('/\[Covered Area:\s*([^\]]+)\]/i', $desc, $cov)) {
            $coverage = trim($cov[1]);
        }

        $brgy = $locationMapping?->barangay;
        if (!$brgy && preg_match('/Barangay:\s*([^.\[]+)/i', $desc, $bMatch)) {
            $brgy = trim($bMatch[1]);
        }

        $soilName = $locationMapping?->soil?->soil_name ?? '';
        $munsellCode = $locationMapping?->soil?->full_code ?? '';
        $soilType = $locationMapping?->soil?->soil_type ?? $locationMapping?->soil?->munsellColor?->soil_type ?? '';
        $hexColor = $locationMapping?->soil?->hex_color ?? $locationMapping?->soil?->munsellColor?->hex_code ?? '#8F694E';

        if (!$soilName && preg_match('/classified as ([^(]+)\s*\(([^)]+)\)/i', $desc, $soilMatch)) {
            $soilName = trim($soilMatch[1]);
            $munsellCode = trim($soilMatch[2]);
        }

        $soilScan = $locationMapping?->soilScan;
        if (!$soilScan && $locationMapping?->soil_scan_id) {
            $soilScan = \App\Models\SoilScan::find($locationMapping->soil_scan_id);
        }

        // Pinned soil index on this mapping
        $pinnedIdx = $locationMapping?->pinned_soil_index ?? 0;

        // Extract scan image (AFTER normalized image)
        $normalizedImgUrl = null;
        if ($soilScan) {
            $normalizedImgUrl = $soilScan->scan_image_url ?: \App\Helpers\SoilImageHelper::scanImageUrl($soilScan, false);
        }
        if (!$normalizedImgUrl && $locationMapping) {
            $normalizedImgUrl = $locationMapping->scan_image_url ?: \App\Helpers\SoilImageHelper::scanImageUrl($locationMapping, false);
        }

        // Extract thumbnail of pinned soil
        $pinnedThumb = null;
        if ($locationMapping && !empty($locationMapping->region_thumbnail)) {
            $pinnedThumb = $locationMapping->region_thumbnail;
            if (!str_starts_with($pinnedThumb, 'data:')) {
                $pinnedThumb = 'data:image/png;base64,' . $pinnedThumb;
            }
        } elseif ($locationMapping) {
            $pinnedThumb = \App\Helpers\SoilImageHelper::scanImageUrl($locationMapping, 'thumb');
        } elseif ($soilScan) {
            $pinnedThumb = \App\Helpers\SoilImageHelper::scanImageUrl($soilScan, 'thumb');
        }

        // Extract regions
        $allScanRegions = [];
        if ($soilScan && !empty($soilScan->regions) && is_array($soilScan->regions)) {
            $allScanRegions = $soilScan->regions;
        } elseif ($locationMapping && !empty($locationMapping->regions) && is_array($locationMapping->regions)) {
            $allScanRegions = $locationMapping->regions;
        }

        // Determine render_regions:
        // If Pin All: include ALL detected regions
        // If Single Pin: include ONLY the single pinned soil layer!
        $renderRegions = [];
        if ($isAllPinned && count($allScanRegions) > 0) {
            $renderRegions = array_values($allScanRegions);
        } elseif (count($allScanRegions) > 0) {
            $targetRegion = null;
            if (isset($allScanRegions[$pinnedIdx])) {
                $targetRegion = $allScanRegions[$pinnedIdx];
            } else {
                foreach ($allScanRegions as $r) {
                    if ((isset($r['soil_id']) && $r['soil_id'] == $locationMapping?->soil_id) ||
                        (isset($r['munsell']) && $r['munsell'] == $munsellCode) ||
                        (isset($r['munsell_code']) && $r['munsell_code'] == $munsellCode)) {
                        $targetRegion = $r;
                        break;
                    }
                }
                if (!$targetRegion) {
                    $targetRegion = $allScanRegions[0];
                }
            }
            if ($targetRegion) {
                $targetRegion['is_primary'] = true;
                $targetRegion['pinned_index'] = $pinnedIdx;
                $renderRegions = [$targetRegion];
            }
        }

        // Fallback single region if no regions array was found
        if (empty($renderRegions) && ($munsellCode || $soilName)) {
            $meta = is_array($locationMapping?->soil_layer_meta) ? $locationMapping->soil_layer_meta : json_decode($locationMapping?->soil_layer_meta ?? '[]', true);
            $renderRegions = [[
                'is_primary'       => true,
                'munsell'          => $munsellCode ?: 'Soil Specimen',
                'munsell_code'     => $munsellCode ?: 'Soil Specimen',
                'label'            => $soilName ?: 'Soil Color',
                'color_name'       => $soilName ?: 'Soil Color',
                'hex'              => $hexColor,
                'percent'          => (float) filter_var($locationMapping?->soil_percentage ?? ($meta['percent'] ?? 100), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 100,
                'confidence'       => $locationMapping?->soil_confidence ?? ($meta['confidence'] ?? '95%'),
                'soil_type'        => $soilType,
                'organic_matter'   => $meta['organic_matter'] ?? ($locationMapping?->soil?->effective_organic_matter ?? 'Moderate'),
                'description'      => $meta['description'] ?? ($locationMapping?->soil?->description ?? ''),
                'box'              => $meta['box'] ?? ($meta['bounding_box'] ?? ['x' => 0.25, 'y' => 0.25, 'w' => 0.5, 'h' => 0.5]),
                'region_thumbnail' => $pinnedThumb,
                'pinned_index'     => $pinnedIdx,
            ]];
        }

        $pinnedPercentage = $locationMapping?->soil_percentage;
        if (!$pinnedPercentage && !empty($renderRegions[0]['percent'])) {
            $pinnedPercentage = $renderRegions[0]['percent'] . '%';
        }
        $pinnedConfidence = $locationMapping?->soil_confidence;
        if (!$pinnedConfidence && !empty($renderRegions[0]['confidence'])) {
            $pinnedConfidence = $renderRegions[0]['confidence'];
        }
        if (is_numeric($pinnedConfidence)) {
            $pinnedConfidence = round((float) $pinnedConfidence, 1) . '%';
        }

        return [
            'lat'                  => $lat,
            'lng'                  => $lng,
            'barangay'             => $brgy ?: 'Lal-lo Jurisdiction',
            'soil_name'            => $soilName ?: 'Soil Specimen',
            'munsell_code'         => $munsellCode,
            'soil_type'            => $soilType,
            'hex_color'            => $hexColor,
            'coverage'             => $coverage,
            'mapping_id'           => $locationMapping?->location_mapping_id,
            'is_all_pinned'        => $isAllPinned,
            'pinned_soil_index'    => $pinnedIdx,
            'pinned_soil_image'    => $pinnedThumb,
            'normalized_image_url' => $normalizedImgUrl,
            'render_regions'       => $renderRegions,
            'pinned_percentage'    => $pinnedPercentage ?: '100%',
            'pinned_confidence'    => $pinnedConfidence ?: '95%',
        ];
    }
}
