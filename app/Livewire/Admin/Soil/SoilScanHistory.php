<?php

namespace App\Livewire\Admin\Soil;

use App\Models\MunsellColor;
use App\Models\Soil;
use App\Models\SoilScan;
use Livewire\Component;
use Livewire\WithPagination;

class SoilScanHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $hue = '';
    public $selectedDate = '';

    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $listeners = [
        'soil-updated'   => '$refresh',
        'soil-deleted'   => '$refresh',
        'scan-moderated' => '$refresh',
    ];

    public function mount()
    {
        // Seeding handled by database seeder or explicit button click in seedSampleRecords()
    }

    public function seedSampleRecords()
    {
        (new \Database\Seeders\AdminRecordSeeder())->seedSoilScans();
        session()->flash('message', 'Sample soil scan records have been seeded successfully.');
        $this->resetPage();
    }

    public function selectSuggestion($value)
    {
        $this->search = $value;
        $this->hue = '';
        $this->status = '';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        $allowedFields = [
            'id',
            'soil_name',
            'color_id',
            'full_code',
            'hue',
            'scan_count',
            'scan_note',
            'admin_id',
            'created_at',
        ];

        if (! in_array($field, $allowedFields)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedHue()
    {
        $this->resetPage();
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
    }

    public function approveScan($id)
    {
        $scan = SoilScan::approveScan($id);
        if ($scan) {
            session()->flash('message', "Soil Scan #{$scan->soil_scan_id} approved. Mark verified and map pin published!");
        }
    }

    public function rejectScan($id)
    {
        $scan = SoilScan::rejectScan($id);
        if ($scan) {
            session()->flash('message', "Soil Scan #{$scan->soil_scan_id} rejected. Pin hidden from public map.");
        }
    }

    public function toggleStatus($id)
    {
        $scan = SoilScan::with('soil')->find($id);
        if ($scan && $scan->soil) {
            $scan->soil->status = ($scan->soil->status === 'Active') ? 'Inactive' : 'Active';
            $scan->soil->save();
        }
    }

    public function render()
    {
        $query = SoilScan::with(['soil.munsellColor', 'user']);

        if ($this->selectedDate) {
            $query->whereDate('scan_date', $this->selectedDate);
        }

        if ($this->status) {
            $st = $this->status;
            $query->where(function ($q) use ($st) {
                $q->where('verification_status', $st)
                  ->orWhereHas('soil', function ($sq) use ($st) {
                      $sq->where('status', $st);
                  });
            });
        }

        if ($this->search) {
            $searchStr = trim($this->search);
            $query->where(function ($q) use ($searchStr) {
                if (str_starts_with($searchStr, '#')) {
                    $idNum = ltrim($searchStr, '#');
                    $q->where('soil_scan_id', $idNum);
                } else {
                    $q->where('user_id', 'like', '%'.$searchStr.'%')
                        ->orWhere('scan_note', 'like', '%'.$searchStr.'%')
                        ->orWhereHas('soil', function ($sq) use ($searchStr) {
                            $sq->where('soil_name', 'like', '%'.$searchStr.'%')
                               ->orWhere('full_code', 'like', '%'.$searchStr.'%')
                               ->orWhere('hue', 'like', '%'.$searchStr.'%')
                               ->orWhere('description', 'like', '%'.$searchStr.'%');
                        })
                        ->orWhereHas('user', function ($uq) use ($searchStr) {
                            $uq->where('name', 'like', '%'.$searchStr.'%')
                               ->orWhere('email', 'like', '%'.$searchStr.'%');
                        });
                }
            });
        }

        if ($this->hue) {
            $query->whereHas('soil', function ($sq) {
                $sq->where('hue', $this->hue)
                   ->orWhereHas('munsellColor', function ($mq) {
                       $mq->where('hue', $this->hue);
                   });
            });
        }

        if ($this->sortField === 'created_at') {
            $query->orderBy('scan_date', $this->sortDirection);
        } elseif ($this->sortField === 'scan_count') {
            $query->join('soils', 'soils.soil_id', '=', 'soil_scans.soil_id')
                  ->select('soil_scans.*')
                  ->orderBy('soils.scan_count', $this->sortDirection)
                  ->orderBy('soil_scans.soil_scan_id', 'desc');
        } elseif ($this->sortField === 'hue') {
            $query->leftJoin('soils', 'soils.soil_id', '=', 'soil_scans.soil_id')
                  ->select('soil_scans.*')
                  ->orderBy('soils.hue', $this->sortDirection)
                  ->orderBy('soil_scans.soil_scan_id', 'desc');
        } else {
            $query->orderBy('soil_scan_id', $this->sortDirection);
        }

        $scans = $query->paginate($this->perPage);

        // Auto-recover to page 1 if current page is out of range (e.g. perPage increased from 10 to 50 while on page 2+)
        if ($scans->isEmpty() && $scans->total() > 0) {
            $this->resetPage();
            $scans = $query->paginate($this->perPage);
        }

        $searchSuggestions = [];
        $trimmedSearch = trim($this->search);
        if (strlen($trimmedSearch) >= 1) {
            $searchSuggestions = SoilScan::with(['soil.munsellColor', 'user'])
                ->where(function ($q) use ($trimmedSearch) {
                    if (str_starts_with($trimmedSearch, '#')) {
                        $idNum = ltrim($trimmedSearch, '#');
                        $q->where('soil_scan_id', $idNum);
                    } else {
                        $q->where('user_id', 'like', '%'.$trimmedSearch.'%')
                            ->orWhere('scan_note', 'like', '%'.$trimmedSearch.'%')
                            ->orWhereHas('soil', function ($sq) use ($trimmedSearch) {
                                $sq->where('soil_name', 'like', '%'.$trimmedSearch.'%')
                                   ->orWhere('full_code', 'like', '%'.$trimmedSearch.'%')
                                   ->orWhere('hue', 'like', '%'.$trimmedSearch.'%')
                                   ->orWhere('description', 'like', '%'.$trimmedSearch.'%');
                            });
                    }
                })
                ->limit(8)
                ->get();
        }

        $munsellHues = MunsellColor::select('hue')
            ->distinct()
            ->orderBy('hue')
            ->pluck('hue');

        return view('admin.livewire.soil.soil-scan-history', [
            'soils' => $scans,
            'scans' => $scans,
            'searchSuggestions' => $searchSuggestions,
            'munsellHues' => $munsellHues,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);
    }
}
