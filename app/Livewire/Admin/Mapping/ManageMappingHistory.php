<?php

namespace App\Livewire\Admin\Mapping;

use App\Models\LocationMapping;
use Livewire\Component;
use Livewire\WithPagination;

class ManageMappingHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $listeners = [
        'mapping-deleted' => '$refresh',
        'mapping-updated' => '$refresh',
    ];

    public function mount()
    {
        // Seeding handled by database seeder
    }

    public function toggleStatus($id)
    {
        $mapping = LocationMapping::find($id);

        if ($mapping) {
            $isInactive = (strtolower(trim($mapping->status ?? '')) === 'inactive');
            $mapping->status = $isInactive ? 'Active' : 'Inactive';
            $mapping->save();
        }
    }

    public function sortBy($field)
    {
        $allowedFields = ['id', 'latitude', 'longitude', 'timestamp', 'status', 'created_at'];

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

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $mappings = LocationMapping::query()
            ->with(['soil', 'soilScan'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('location_mapping_id', 'like', '%'.$this->search.'%')
                      ->orWhere('latitude', 'like', '%'.$this->search.'%')
                      ->orWhere('longitude', 'like', '%'.$this->search.'%')
                      ->orWhereHas('soil', function ($sq) {
                          $sq->where('soil_name', 'like', '%'.$this->search.'%')
                             ->orWhere('full_code', 'like', '%'.$this->search.'%');
                      })
                      ->orWhereHas('soilScan', function ($ssq) {
                          $ssq->where('scan_note', 'like', '%'.$this->search.'%');
                      });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            });

        $orderColumn = ($this->sortField === 'id') ? 'location_mapping_id' : $this->sortField;

        $mappings = $mappings->orderBy($orderColumn, $this->sortDirection)
            ->paginate($this->perPage);

        return view('admin.livewire.mapping.manage-mapping-history', [
            'mappings' => $mappings,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);
    }
}
