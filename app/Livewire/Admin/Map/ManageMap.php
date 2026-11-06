<?php

namespace App\Livewire\Admin\Map;

use App\Models\LocationMapping;
use App\Models\Soil;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ManageMap extends Component
{
    use WithPagination;

    public $selectedSoil = '';
    public $selectedStatus = '';
    public $selectedDate = '';
    public $search = '';

    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $perPage = 10;

    #[On('mapping-deleted')]
    #[On('mapping-updated')]
    public function refreshTable()
    {
        // Livewire re-renders automatically after this runs — no body needed
    }

    public function mount()
    {
        // Seeding is handled via database seeders or explicit user action in seedSamplePins()
    }

    public function seedSamplePins()
    {
        (new \Database\Seeders\AdminRecordSeeder())->seedLocationMappings();
        session()->flash('message', 'Sample location map pins have been seeded successfully.');
        $this->resetPage();
    }

    public function selectSuggestion($value)
    {
        $this->search = $value;
        $this->selectedSoil = '';
        $this->selectedStatus = '';
        $this->resetPage();
    }

    #[On('toggle-pin-status')]
    public function toggleStatus($id)
    {
        if (is_array($id)) {
            $id = $id['id'] ?? ($id[0] ?? null);
        }
        $id = ltrim((string) $id, '#');

        $mapping = LocationMapping::find($id);
        if ($mapping) {
            $isInactive = strtolower(trim($mapping->status ?? '')) === 'inactive';
            $mapping->status = $isInactive ? 'Active' : 'Inactive';
            $mapping->save();
        }
    }

    public function sortBy($field)
    {
        $allowedFields = ['id', 'latitude', 'longitude', 'barangay', 'timestamp', 'status', 'created_at', 'soil_name', 'scan_note'];

        if (! in_array($field, $allowedFields)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedSoil()
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        // 1. ALL MAP LOCATIONS (Loaded once for Leaflet GIS, searched instantly in browser)
        $rawMapLocations = LocationMapping::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('location_mapping_id', 'latitude', 'longitude', 'barangay', 'status', 'soil_id', 'soil_scan_id')
            ->with([
                'soil' => function($q) {
                    $q->select('soil_id', 'soil_name', 'full_code', 'soil_type', 'color_id', 'hue', 'hex_color')
                      ->with('munsellColor:munsell_color_id,soil_type,color_name,hex_code');
                },
                'soilScan:soil_scan_id,scan_note',
            ])
            ->when($this->selectedSoil, function ($q) {
                $q->where('soil_id', $this->selectedSoil);
            })
            ->when($this->selectedStatus, function ($q) {
                $q->where('status', $this->selectedStatus);
            })
            ->when($this->selectedDate, function ($q) {
                $q->where(function ($sq) {
                    $sq->whereDate('timestamp', $this->selectedDate)
                       ->orWhereDate('created_at', $this->selectedDate);
                });
            })
            ->get();

        $allMapLocations = $rawMapLocations->map(function ($loc) {
            $soil = $loc->soil;
            $munsell = $soil?->munsellColor;

            $fullCode = $soil?->full_code ?? $munsell?->full_code ?? '';
            $soilType = $soil?->soil_type ?? $munsell?->soil_type ?? 'Leached grayish brown';
            $hexColor = $soil?->hex_color ?? $munsell?->hex_code ?? '#8F694E';
            if ($hexColor && !str_starts_with($hexColor, '#')) {
                $hexColor = '#' . $hexColor;
            }

            $rawBrgy = $loc->barangay;
            if (empty($rawBrgy) || strtolower($rawBrgy) === 'lal-lo overall' || strtolower($rawBrgy) === 'all barangays') {
                $rawBrgy = LocationMapping::resolveNearestBarangay((float) $loc->latitude, (float) $loc->longitude);
            }

            $covStr = '500 m²';
            if ($loc->soilScan?->scan_note && preg_match('/\[Covered Area:\s*([^\]]+)\]/i', $loc->soilScan->scan_note, $cm)) {
                $covStr = trim($cm[1]);
            }

            return [
                'location_mapping_id' => $loc->location_mapping_id,
                'id' => $loc->location_mapping_id,
                'latitude' => (float) $loc->latitude,
                'longitude' => (float) $loc->longitude,
                'barangay' => $rawBrgy,
                'status' => $loc->status ?: 'Active',
                'coverage' => $covStr,
                'soil_id' => $loc->soil_id,
                'soil_scan_id' => $loc->soil_scan_id,
                'soil' => [
                    'soil_id' => $soil?->soil_id,
                    'soil_name' => $soil?->soil_name ?: 'Soil Specimen',
                    'full_code' => $fullCode,
                    'soil_type' => $soilType,
                    'soil_type_label' => $soilType,
                    'hex_color' => $hexColor,
                    'munsell_color' => [
                        'hex_code' => $hexColor,
                        'color_name' => $munsell?->color_name ?: $soil?->soil_name,
                        'soil_type' => $soilType,
                    ],
                ],
                'soil_scan' => [
                    'soil_scan_id' => $loc->soil_scan_id,
                    'scan_note' => $loc->soilScan?->scan_note ?: '',
                ],
                'regions' => [],
            ];
        })->values()->toArray();

        // 2. TABLE QUERY (Dedicated to records search & filters)
        $searchStr = trim($this->search);
        $cleanSearch = preg_replace('/^(brgy\.?|barangay\.?)\s*/i', '', $searchStr);
        $isGenericBrgySearch = in_array(strtolower($searchStr), ['brgy', 'brgy.', 'barangay', 'barangay.']);

        $tableQuery = LocationMapping::query()
            ->with(['soil', 'soilScan'])
            ->when($this->selectedSoil, function ($q) {
                $q->where('soil_id', $this->selectedSoil);
            })
            ->when($this->selectedStatus, function ($q) {
                $q->where('status', $this->selectedStatus);
            })
            ->when($this->selectedDate, function ($q) {
                $q->where(function ($sq) {
                    $sq->whereDate('timestamp', $this->selectedDate)
                      ->orWhereDate('created_at', $this->selectedDate);
                });
            })
            ->when($searchStr, function ($query) use ($searchStr, $cleanSearch, $isGenericBrgySearch) {
                $query->where(function ($q) use ($searchStr, $cleanSearch, $isGenericBrgySearch) {
                    if (str_starts_with($searchStr, '#')) {
                        $idNum = ltrim($searchStr, '#');
                        $q->where('location_mapping_id', $idNum);
                    } elseif ($isGenericBrgySearch) {
                        $q->whereNotNull('barangay');
                    } else {
                        $q->where('barangay', 'like', '%' . ($cleanSearch ?: $searchStr) . '%')
                          ->orWhere('latitude', 'like', '%' . $searchStr . '%')
                          ->orWhere('longitude', 'like', '%' . $searchStr . '%')
                          ->orWhereHas('soil', function ($sq) use ($searchStr) {
                              $sq->where('soil_name', 'like', '%' . $searchStr . '%')
                                 ->orWhere('full_code', 'like', '%' . $searchStr . '%')
                                 ->orWhere('soil_type', 'like', '%' . $searchStr . '%')
                                 ->orWhere('hue', 'like', '%' . $searchStr . '%');
                          })
                          ->orWhereHas('soilScan', function ($ssq) use ($searchStr) {
                              $ssq->where('scan_note', 'like', '%' . $searchStr . '%');
                          });
                    }
                });
            });

        if ($this->sortField === 'soil_name') {
            $tableQuery->orderBy(
                Soil::select('soil_name')->whereColumn('soils.soil_id', 'location_mappings.soil_id'),
                $this->sortDirection
            );
        } elseif ($this->sortField === 'scan_note') {
            $tableQuery->orderBy(
                \App\Models\SoilScan::select('scan_note')->whereColumn('soil_scans.soil_scan_id', 'location_mappings.soil_scan_id'),
                $this->sortDirection
            );
        } else {
            $tableQuery->orderBy($this->sortField === 'id' ? 'location_mapping_id' : $this->sortField, $this->sortDirection);
        }

        $paginatedMappings = $tableQuery->paginate($this->perPage);

        // 3. TABLE SEARCH AUTO-SUGGESTIONS (Instant preview dropdown for table search only)
        $tableSearchSuggestions = [];
        if (strlen($searchStr) >= 1) {
            $tableSearchSuggestions = LocationMapping::query()
                ->with(['soil:soil_id,soil_name,full_code,soil_type', 'soilScan:soil_scan_id,scan_note'])
                ->where(function ($q) use ($searchStr, $cleanSearch, $isGenericBrgySearch) {
                    if (str_starts_with($searchStr, '#')) {
                        $idNum = ltrim($searchStr, '#');
                        $q->where('location_mapping_id', $idNum);
                    } elseif ($isGenericBrgySearch) {
                        $q->whereNotNull('barangay');
                    } else {
                        $q->where('barangay', 'like', '%' . ($cleanSearch ?: $searchStr) . '%')
                          ->orWhere('latitude', 'like', '%' . $searchStr . '%')
                          ->orWhere('longitude', 'like', '%' . $searchStr . '%')
                          ->orWhereHas('soil', function ($sq) use ($searchStr) {
                              $sq->where('soil_name', 'like', '%' . $searchStr . '%')
                                 ->orWhere('full_code', 'like', '%' . $searchStr . '%')
                                 ->orWhere('soil_type', 'like', '%' . $searchStr . '%')
                                 ->orWhere('hue', 'like', '%' . $searchStr . '%');
                          });
                    }
                })
                ->limit(8)
                ->get();
        }

        $mappingCounts = LocationMapping::selectRaw("
            count(*) as total,
            count(case when status = 'Active' then 1 end) as active,
            count(case when status = 'Inactive' then 1 end) as inactive
        ")->first();

        $stats = [
            'total' => (int) ($mappingCounts->total ?? 0),
            'active' => (int) ($mappingCounts->active ?? 0),
            'inactive' => (int) ($mappingCounts->inactive ?? 0),
            'soils_count' => \Illuminate\Support\Facades\Cache::remember('soils_munsell_count', 300, fn() => Soil::has('munsellColor')->count()),
        ];

        $soils = \Illuminate\Support\Facades\Cache::remember('all_soils_list', 300, fn() => Soil::select('soil_id', 'soil_name', 'full_code')->orderBy('soil_name')->get());

        return view('admin.livewire.map.manage-map', [
            'allMapLocations' => $allMapLocations,
            'tableMappings' => $paginatedMappings,
            'searchSuggestions' => $tableSearchSuggestions,
            'tableSearchSuggestions' => $tableSearchSuggestions,
            'soils' => $soils,
            'stats' => $stats,
            'search' => $this->search,
            'selectedSoil' => $this->selectedSoil,
            'selectedStatus' => $this->selectedStatus,
            'selectedDate' => $this->selectedDate,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ])->layout('admin.layouts.app');
    }
}
