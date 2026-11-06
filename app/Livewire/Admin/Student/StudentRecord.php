<?php

namespace App\Livewire\Admin\Student;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class StudentRecord extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $statusFilter = '';
    public $connectivityFilter = '';
    public $sectionFilter = '';
    public $adminFilter = '';
    public $selectedDate = '';

    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $viewMode = 'grid';

    public function mount()
    {
        if ($this->isSuperAdminActor()) {
            $this->adminFilter = $this->getLoggedInAdminId();
        }
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    // POPUP MODAL FOR SOIL SCANS
    public $showSoilScansModal = false;
    public $selectedStudentForScans = null;
    public $studentScans = [];

    // POPUP MODAL FOR PIN MAP RECORDS
    public $showPinMapsModal = false;
    public $selectedStudentForPins = null;
    public $studentPins = [];

    // POPUP MODAL FOR ADD STUDENT
    public $showAddStudentModal = false;
    public $studentSearch = '';
    public $modalSectionFilter = '';
    public $addedStudentIds = [];

    // POPUP MODAL FOR SECTION RECORDS
    public $showSectionRecordsModal = false;
    public $sectionRecordsSearch = '';
    public $sectionRecordsSectionFilter = '';
    public $sectionRecordsColumnName = 'Scan Soil';
    public $sectionRecordsDateFilter = '';
    public $sectionRecordsStartTime = '';
    public $sectionRecordsEndTime = '';
    public $sectionRecordsShowScans = true;
    public $sectionRecordsShowPins = true;
    public $activityCards = [];
    public $documentTitle = 'Student Soil and Pin Location Activity Records';

    // EDIT MODAL STATE
    public $showEditModal = false;
    public $isVerified = false;
    public $editUserId = null;
    public $editStudentId = '';
    public $editSection = '';
    public $editName = '';
    public $editEmail = '';
    public $editPassword = '';
    public $editPasswordConfirmation = '';
    public $editRole = 'student';
    public $editStatus = 'Active';
    public $editPhoto;
    public $adminPassword = '';
    public $adminPasswordError = '';
    public $verifiedAdminId = null;
    public $admin_id = '';
    public $userRoleName = 'Student';

    // SUSPEND MODAL STATE
    public $showSuspendModal = false;
    public $suspendUserId = null;
    public $suspendTargetUser = null;
    public $suspendReason = '';
    public $suspendDays = 7;

    // VIEW SUSPEND MODAL STATE
    public $showViewSuspendModal = false;
    public $viewSuspendUserId = null;
    public $viewSuspendTargetUser = null;

    // DELETE MODAL STATE
    public $showDeleteModal = false;
    public $deleteUserId = null;
    public $deleteTargetUser = null;

    // CANCEL DELETION MODAL STATE
    public $showCancelDeletionModal = false;
    public $cancelUserId = null;
    // REMOVE STUDENT MODAL STATE (Removes from Student Records only, preserves user in Manage Users)
    public $showRemoveStudentModal = false;
    public $removeStudentId = null;
    public $removeTargetUser = null;

    // VIEW USER DETAILS MODAL STATE
    public $showViewModal = false;
    public $viewUserId = null;

    public function openViewModal($id)
    {
        $user = User::find($id);
        if ($user) {
            $this->viewUserId = $user->id;
            $this->showViewModal = true;
        }
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewUserId = null;
    }

    public function setViewMode($mode)
    {
        $this->viewMode = in_array($mode, ['grid', 'table']) ? $mode : 'grid';
    }

    public function openStudentSoilScansModal($id)
    {
        $student = User::find($id);
        if ($student) {
            $this->selectedStudentForScans = $student;
            $this->studentScans = \App\Models\SoilScan::where('user_id', $student->user_id)
                ->with('soil')
                ->latest('scan_date')
                ->latest('created_at')
                ->get();
            $this->showSoilScansModal = true;
        }
    }

    public function closeStudentSoilScansModal()
    {
        $this->showSoilScansModal = false;
        $this->selectedStudentForScans = null;
        $this->studentScans = [];
    }

    public function viewSoilScanDetail($id)
    {
        $this->dispatch('view-soil', id: $id);
    }

    public function openStudentPinMapsModal($id)
    {
        $student = User::find($id);
        if ($student) {
            $this->selectedStudentForPins = $student;
            $this->studentPins = \App\Models\LocationMapping::whereHas('soilScan', function ($q) use ($student) {
                $q->where('user_id', $student->user_id);
            })->with(['soilScan.soil', 'soil'])->latest('timestamp')->latest('created_at')->get();
            $this->showPinMapsModal = true;
        }
    }

    public function closeStudentPinMapsModal()
    {
        $this->showPinMapsModal = false;
        $this->selectedStudentForPins = null;
        $this->studentPins = [];
    }

    public function getSearchResultsProperty()
    {
        return User::where(function ($q) {
                $q->whereRaw('LOWER(role) = ?', ['student'])
                  ->orWhereNull('role')
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('student_id')->where('student_id', '!=', '');
                  })
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('section')->where('section', '!=', '');
                  });
            })
            ->where(function ($q) {
                $q->whereNotIn(DB::raw('LOWER(role)'), ['admin', 'superadmin', 'super admin'])
                  ->orWhereNull('role');
            })
            ->when($this->studentSearch, function ($q) {
                $searchStr = trim($this->studentSearch);
                $q->where(function ($sub) use ($searchStr) {
                    $sub->where('name', 'like', "%{$searchStr}%")
                        ->orWhere('student_id', 'like', "%{$searchStr}%")
                        ->orWhere('section', 'like', "%{$searchStr}%");
                });
            })
            ->when($this->modalSectionFilter, function ($q) {
                $q->where('section', trim($this->modalSectionFilter));
            })
            ->orderBy('name', 'asc')
            ->get();
    }

    public function getAvailableSectionsProperty()
    {
        return $this->scopeStudents(User::query())
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->distinct()
            ->pluck('section')
            ->map(fn($s) => trim($s))
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function getModalAvailableSectionsProperty()
    {
        return User::whereNotNull('section')
            ->where('section', '!=', '')
            ->distinct()
            ->pluck('section')
            ->map(fn($s) => trim($s))
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function getAvailableAdminsProperty()
    {
        if (!$this->isSuperAdminActor()) {
            return collect();
        }

        // Pull all admins from the admins table so new admins automatically appear in the filter
        $admins = \App\Models\Admin::orderBy('full_name')
            ->get(['admin_id', 'full_name'])
            ->keyBy(function ($item) {
                return \App\Models\Admin::formatId($item->admin_id);
            });

        // Also ensure any User with admin or superadmin role is included
        $adminUsers = User::whereIn(DB::raw('LOWER(role)'), ['admin', 'superadmin', 'super admin'])
            ->get();

        foreach ($adminUsers as $userAdmin) {
            $formattedId = \App\Models\Admin::formatId($userAdmin->user_id);
            if (!$admins->has($formattedId)) {
                $admins->put($formattedId, (object) [
                    'admin_id'  => $formattedId,
                    'Admin_ID'  => $formattedId,
                    'full_name' => $userAdmin->name,
                    'Full_Name' => $userAdmin->name,
                ]);
            }
        }

        return $admins->values()->sortBy('full_name');
    }

    public function openAddStudentModal()
    {
        $this->studentSearch = '';
        $this->modalSectionFilter = '';
        $this->addedStudentIds = [];
        $this->showAddStudentModal = true;
    }

    public function closeAddStudentModal()
    {
        $this->showAddStudentModal = false;
    }

    public function openSectionRecordsModal(string $section = '', string $date = '')
    {
        $this->sectionRecordsSearch = '';
        $this->sectionRecordsSectionFilter = $section;
        $defaultDate = $date ?: now()->format('Y-m-d');

        $this->activityCards = [
            [
                'id' => 1,
                'activity_name' => 'Activity #1',
                'scan_name' => 'Scan Soil',
                'pin_name' => 'Pin Soil Location',
                'date' => $defaultDate,
                'start_time' => '',
                'end_time' => '',
                'show_scans' => true,
                'show_pins' => true,
            ]
        ];

        $this->sectionRecordsDateFilter = $defaultDate;
        $this->sectionRecordsStartTime = '';
        $this->sectionRecordsEndTime = '';
        if (empty($this->sectionRecordsColumnName)) {
            $this->sectionRecordsColumnName = 'Scan Soil';
        }
        $this->sectionRecordsShowScans = true;
        $this->sectionRecordsShowPins = true;
        $this->showSectionRecordsModal = true;
    }

    public function addActivityCard()
    {
        if (count($this->activityCards) >= 8) {
            return;
        }

        $num = count($this->activityCards) + 1;
        $this->activityCards[] = [
            'id' => time() . '_' . $num,
            'activity_name' => 'Activity #' . $num,
            'scan_name' => 'Scan Soil',
            'pin_name' => 'Pin Soil Location',
            'date' => now()->format('Y-m-d'),
            'start_time' => '',
            'end_time' => '',
            'show_scans' => true,
            'show_pins' => true,
        ];
    }

    public function removeActivityCard(int $index)
    {
        if (count($this->activityCards) > 1 && isset($this->activityCards[$index])) {
            unset($this->activityCards[$index]);
            $this->activityCards = array_values($this->activityCards);
        }
    }

    public function setActivityCardDateToday(int $index)
    {
        if (isset($this->activityCards[$index])) {
            $this->activityCards[$index]['date'] = now()->format('Y-m-d');
        }
    }

    public function clearActivityCardDate(int $index)
    {
        if (isset($this->activityCards[$index])) {
            $this->activityCards[$index]['date'] = '';
        }
    }

    public function clearActivityCardTime(int $index)
    {
        if (isset($this->activityCards[$index])) {
            $this->activityCards[$index]['start_time'] = '';
            $this->activityCards[$index]['end_time'] = '';
        }
    }

    public function setSectionRecordsDateToday()
    {
        $this->sectionRecordsDateFilter = now()->format('Y-m-d');
        if (!empty($this->activityCards)) {
            $this->activityCards[0]['date'] = now()->format('Y-m-d');
        }
    }

    public function clearSectionRecordsDate()
    {
        $this->sectionRecordsDateFilter = '';
        if (!empty($this->activityCards)) {
            $this->activityCards[0]['date'] = '';
        }
    }

    public function clearSectionRecordsTime()
    {
        $this->sectionRecordsStartTime = '';
        $this->sectionRecordsEndTime = '';
        if (!empty($this->activityCards)) {
            $this->activityCards[0]['start_time'] = '';
            $this->activityCards[0]['end_time'] = '';
        }
    }

    public function closeSectionRecordsModal()
    {
        $this->showSectionRecordsModal = false;
        $this->sectionRecordsSearch = '';
        $this->sectionRecordsSectionFilter = '';
        $this->sectionRecordsDateFilter = '';
        $this->sectionRecordsStartTime = '';
        $this->sectionRecordsEndTime = '';
        $this->sectionRecordsShowScans = true;
        $this->sectionRecordsShowPins = true;
        $this->activityCards = [];
        $this->documentTitle = 'Student Soil and Pin Location Activity Records';
    }

    public function exportSectionDocx(\App\Services\StudentRecordsExportService $exportService)
    {
        $students = $this->sectionRecords;
        $adminName = $this->getLoggedInAdminName();
        return $exportService->exportDocx(
            $students,
            $this->activityCards,
            $this->sectionRecordsSectionFilter ?: 'All',
            $this->documentTitle ?: '',
            $adminName
        );
    }

    public function exportSectionXlsx(\App\Services\StudentRecordsExportService $exportService)
    {
        $students = $this->sectionRecords;
        $adminName = $this->getLoggedInAdminName();
        return $exportService->exportXlsx(
            $students,
            $this->activityCards,
            $this->sectionRecordsSectionFilter ?: 'All',
            $this->documentTitle ?: '',
            $adminName
        );
    }

    public function getSectionRecordsProperty()
    {
        $currentUser = Auth::guard('admin')->user();
        $currentEmail = strtolower(trim($currentUser?->Email ?? $currentUser?->email ?? ''));

        if (empty($this->activityCards)) {
            $this->activityCards = [
                [
                    'id' => 1,
                    'activity_name' => 'Activity #1',
                    'scan_name' => $this->sectionRecordsColumnName ?: 'Scan Soil',
                    'pin_name' => 'Pin Soil Location',
                    'date' => $this->sectionRecordsDateFilter ?: now()->format('Y-m-d'),
                    'start_time' => $this->sectionRecordsStartTime ?: '',
                    'end_time' => $this->sectionRecordsEndTime ?: '',
                    'show_scans' => $this->sectionRecordsShowScans ?? true,
                    'show_pins' => $this->sectionRecordsShowPins ?? true,
                ]
            ];
        }

        $query = $this->scopeStudents(User::query())
            ->when($currentEmail, fn($q) => $q->whereRaw('LOWER(email) != ?', [$currentEmail]))
            ->when($this->sectionRecordsSectionFilter, fn($q) => $q->where('section', trim($this->sectionRecordsSectionFilter)))
            ->when($this->sectionRecordsSearch, function ($q) {
                $s = trim($this->sectionRecordsSearch);
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('student_id', 'like', "%{$s}%")
                        ->orWhere('section', 'like', "%{$s}%");
                });
            });

        $withCount = [
            'soilScans',
            'locationMappings',
        ];

        foreach ($this->activityCards as $idx => $card) {
            $dateFilter = trim($card['date'] ?? '');
            $startTime = trim($card['start_time'] ?? '');
            $endTime = trim($card['end_time'] ?? '');

            $applyScanFilter = function ($q) use ($dateFilter, $startTime, $endTime) {
                if ($dateFilter && ($startTime || $endTime)) {
                    $startDt = $startTime ? "{$dateFilter} {$startTime}:00" : "{$dateFilter} 00:00:00";
                    $endDt = $endTime ? "{$dateFilter} {$endTime}:59" : "{$dateFilter} 23:59:59";
                    $q->where(function ($sq) use ($startDt, $endDt) {
                        $sq->whereBetween('scan_date', [$startDt, $endDt])
                           ->orWhereBetween('created_at', [$startDt, $endDt]);
                    });
                } elseif ($dateFilter) {
                    $q->where(function ($sq) use ($dateFilter) {
                        $sq->whereDate('scan_date', $dateFilter)
                           ->orWhereDate('created_at', $dateFilter);
                    });
                } elseif ($startTime || $endTime) {
                    $q->where(function ($sq) use ($startTime, $endTime) {
                        $sq->where(function ($inner) use ($startTime, $endTime) {
                            if ($startTime) $inner->whereTime('scan_date', '>=', $startTime);
                            if ($endTime) $inner->whereTime('scan_date', '<=', $endTime);
                        })->orWhere(function ($inner) use ($startTime, $endTime) {
                            if ($startTime) $inner->whereTime('created_at', '>=', $startTime);
                            if ($endTime) $inner->whereTime('created_at', '<=', $endTime);
                        });
                    });
                }
            };

            $applyPinFilter = function ($q) use ($dateFilter, $startTime, $endTime) {
                if ($dateFilter && ($startTime || $endTime)) {
                    $startDt = $startTime ? "{$dateFilter} {$startTime}:00" : "{$dateFilter} 00:00:00";
                    $endDt = $endTime ? "{$dateFilter} {$endTime}:59" : "{$dateFilter} 23:59:59";
                    $q->where(function ($sq) use ($startDt, $endDt) {
                        $sq->whereBetween('timestamp', [$startDt, $endDt])
                           ->orWhereBetween('location_mappings.created_at', [$startDt, $endDt]);
                    });
                } elseif ($dateFilter) {
                    $q->where(function ($sq) use ($dateFilter) {
                        $sq->whereDate('timestamp', $dateFilter)
                           ->orWhereDate('location_mappings.created_at', $dateFilter);
                    });
                } elseif ($startTime || $endTime) {
                    $q->where(function ($sq) use ($startTime, $endTime) {
                        $sq->where(function ($inner) use ($startTime, $endTime) {
                            if ($startTime) $inner->whereTime('timestamp', '>=', $startTime);
                            if ($endTime) $inner->whereTime('timestamp', '<=', $endTime);
                        })->orWhere(function ($inner) use ($startTime, $endTime) {
                            if ($startTime) $inner->whereTime('location_mappings.created_at', '>=', $startTime);
                            if ($endTime) $inner->whereTime('location_mappings.created_at', '<=', $endTime);
                        });
                    });
                }
            };

            $withCount["soilScans as card_{$idx}_scans_count"] = $applyScanFilter;
            $withCount["locationMappings as card_{$idx}_pins_count"] = $applyPinFilter;

            if ($idx === 0) {
                $withCount["soilScans as date_soil_scans_count"] = $applyScanFilter;
                $withCount["locationMappings as date_location_mappings_count"] = $applyPinFilter;
            }
        }

        $query->withCount($withCount);

        $firstCard = $this->activityCards[0] ?? null;
        if ($firstCard && !empty($firstCard['date'])) {
            $fDate = trim($firstCard['date']);
            $query->with([
                'soilScans' => function ($q) use ($fDate) {
                    $q->whereDate('scan_date', $fDate)
                      ->orWhereDate('created_at', $fDate)
                      ->with('soil')->latest('scan_date');
                }
            ]);
        }

        return $query->orderBy('section', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function addStudentToRecords($studentUserId)
    {
        $student = User::where('user_id', $studentUserId)->first();
        if ($student) {
            // Guard: If student is already active in records, prevent duplicate addition
            if ($student->student_record_status === 'Active') {
                return;
            }
            $student->student_record_status = 'Active';
            $student->last_admin_id = $this->getLoggedInAdminId();
            $student->save();
        }
        $this->addedStudentIds[] = $studentUserId;
    }

    protected function getLoggedInAdminId(): string
    {
        $user = Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            return 'ADM-001';
        }

        if ($user instanceof \App\Models\Admin) {
            return \App\Models\Admin::formatId($user->Admin_ID);
        }

        if (!empty($user->email)) {
            $adminRecord = \App\Models\Admin::where('email', $user->email)->first();
            if ($adminRecord && !empty($adminRecord->admin_id ?? $adminRecord->id)) {
                return \App\Models\Admin::formatId($adminRecord->admin_id ?? $adminRecord->id);
            }
        }

        if (!empty($user->Admin_ID ?? $user->admin_id)) {
            return \App\Models\Admin::formatId($user->Admin_ID ?? $user->admin_id);
        }

        if (!empty($user->user_id)) {
            return \App\Models\Admin::formatId($user->user_id);
        }

        if (!empty($user->last_admin_id)) {
            return \App\Models\Admin::formatId($user->last_admin_id);
        }

        return \App\Models\Admin::formatId($user->id ?? 'ADM-001');
    }

    protected function getLoggedInAdminName(): string
    {
        $user = Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            $first = \App\Models\Admin::first();
            return $first?->full_name ?: 'AgriCo';
        }

        if (!empty($user->full_name)) {
            return $user->full_name;
        }

        if (!empty($user->name)) {
            return $user->name;
        }

        if (!empty($user->Full_Name)) {
            return $user->Full_Name;
        }

        if (!empty($user->email)) {
            $adminRecord = \App\Models\Admin::where('email', $user->email)->first();
            if ($adminRecord && !empty($adminRecord->full_name)) {
                return $adminRecord->full_name;
            }
        }

        return 'AgriCo';
    }

    public function openEditModal($id)
    {
        $user = User::find($id);
        if ($user) {
            $this->editUserId = $user->id;
            $this->editStudentId = $user->student_id ?: '';
            $this->editSection = $user->section ?: '';
            $this->editName = $user->name;
            $this->editEmail = $user->email;
            $this->editPassword = '';
            $this->editPasswordConfirmation = '';
            $this->editRole = $user->role ?: 'student';
            $this->editStatus = (ucfirst($user->status) === 'Suspended' || strtolower($user->status) === 'inactive') ? 'Suspended' : 'Active';
            $this->editPhoto = null;
            $this->userRoleName = 'Student';
            $this->adminPassword = '';
            $this->adminPasswordError = '';
            $this->verifiedAdminId = null;
            $this->admin_id = $this->getLoggedInAdminId();
            $this->isVerified = false;
            $this->search = '';
            $this->statusFilter = '';
            $this->connectivityFilter = '';
            $this->resetPage();
            $this->showEditModal = true;
        }
    }

    public function goBackToVerification()
    {
        $this->isVerified = false;
        $this->adminPassword = '';
        $this->adminPasswordError = '';
        $this->verifiedAdminId = null;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->isVerified = false;
        $this->search = '';
        $this->statusFilter = '';
        $this->connectivityFilter = '';
        $this->resetPage();
        $this->reset([
            'editUserId',
            'editStudentId',
            'editSection',
            'editName',
            'editEmail',
            'editPassword',
            'editPasswordConfirmation',
            'editRole',
            'editStatus',
            'editPhoto',
            'adminPassword',
            'adminPasswordError',
            'verifiedAdminId',
            'admin_id'
        ]);
    }

    public function verifyAdminPassword()
    {
        $this->validate([
            'adminPassword' => 'required|string',
        ]);

        $passwordMatches = false;
        $matchedAdminId = null;

        $currentUser = Auth::guard('admin')->user();
        if ($currentUser) {
            if ($currentUser instanceof \App\Models\Admin && $currentUser->password && Hash::check($this->adminPassword, $currentUser->password)) {
                $passwordMatches = true;
                $matchedAdminId = $currentUser->admin_id;
            } elseif (!empty($currentUser->password) && Hash::check($this->adminPassword, $currentUser->password)) {
                $passwordMatches = true;
                $adminRec = \App\Models\Admin::where('email', $currentUser->email)->first();
                $matchedAdminId = $adminRec ? $adminRec->admin_id : $currentUser->id;
            }
        }

        if (!$passwordMatches) {
            $admins = \App\Models\Admin::all();
            foreach ($admins as $admin) {
                if ($admin->password && Hash::check($this->adminPassword, $admin->password)) {
                    $passwordMatches = true;
                    $matchedAdminId = $admin->admin_id;
                    break;
                }
            }
        }

        if (!$passwordMatches) {
            $userAdmins = User::where('role', 'admin')->get();
            foreach ($userAdmins as $uAdmin) {
                if ($uAdmin->password && Hash::check($this->adminPassword, $uAdmin->password)) {
                    $passwordMatches = true;
                    $adminRec = \App\Models\Admin::where('email', $uAdmin->email)->first();
                    $matchedAdminId = $adminRec ? $adminRec->admin_id : $uAdmin->id;
                    break;
                }
            }
        }

        if ($passwordMatches) {
            $this->isVerified = true;
            $this->verifiedAdminId = $matchedAdminId;
            $this->admin_id = \App\Models\Admin::formatId($matchedAdminId);
            $this->adminPasswordError = '';
        } else {
            $this->adminPasswordError = 'Incorrect admin password. Authorization denied.';
        }
    }

    public function isSuperAdminActor(): bool
    {
        $authAdmin = Auth::guard('admin')->user();
        if ($authAdmin) {
            $email = strtolower(trim($authAdmin->email ?? $authAdmin->Email ?? ''));
            if ($email === 'admin@agrico.com' || $email === 'agricosite77@gmail.com') {
                return true;
            }
        }

        $webUser = Auth::user();
        if ($webUser) {
            $email = strtolower(trim($webUser->email ?? ''));
            $role = strtolower(trim($webUser->role ?? ''));
            if ($email === 'admin@agrico.com' || $email === 'agricosite77@gmail.com' || $role === 'superadmin') {
                return true;
            }
        }

        if (!empty($authAdmin?->email ?? '')) {
            $userRec = User::whereRaw('LOWER(email) = ?', [strtolower(trim($authAdmin->email))])->first();
            if ($userRec && (strtolower(trim($userRec->role)) === 'superadmin' || strtolower(trim($userRec->email)) === 'admin@agrico.com' || strtolower(trim($userRec->email)) === 'agricosite77@gmail.com')) {
                return true;
            }
        }

        return false;
    }

    public function saveUser()
    {
        if (!$this->isVerified) {
            $this->adminPasswordError = 'Please verify your admin password first.';
            return;
        }

        $rules = [
            'editName' => 'required|string|max:255',
            'editStudentId' => 'nullable|string|max:50',
            'editSection' => 'nullable|string|max:100',
            'editEmail' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editUserId),
            ],
            'editStatus' => 'required|string',
            'editPhoto' => 'nullable|image|max:2048',
        ];

        if (!empty($this->editPassword)) {
            $rules['editPassword'] = 'required|string|min:6';
            if ($this->editPassword !== $this->editPasswordConfirmation) {
                $this->addError('editPasswordConfirmation', 'The password confirmation does not match.');
                return;
            }
        }

        $this->validate($rules);

        if ($this->editUserId) {
            $user = User::find($this->editUserId);
            if ($user) {
                $previousStatus = $user->status;
                $user->name = $this->editName;
                $user->email = $this->editEmail;
                $user->student_id = trim($this->editStudentId) ?: null;
                $user->section = trim($this->editSection) ?: null;
                $user->status = $this->editStatus;

                if ($this->editPhoto) {
                    $user->profile_photo_path = $this->editPhoto->store('profile-photos', 'public');
                }

                if (!empty($this->editPassword)) {
                    $user->password = Hash::make($this->editPassword);
                }

                $adminIdToSave = $this->verifiedAdminId ?: $this->admin_id;
                if (empty($adminIdToSave)) {
                    $adminIdToSave = $this->getLoggedInAdminId();
                }
                $user->last_admin_id = $adminIdToSave;
                $user->updated_at = now();

                $user->save();

                // Synchronize UserRequest status
                $targetReqStatus = (ucfirst($this->editStatus) === 'Active') ? 'Approved' : 'Disapproved';
                \App\Models\UserRequest::where('user_email', $user->email)->update([
                    'status'   => $targetReqStatus,
                    'admin_id' => $adminIdToSave,
                ]);

                // Send cross-platform notifications if student account was approved or disapproved
                if (ucfirst((string)$previousStatus) !== 'Active' && ucfirst((string)$this->editStatus) === 'Active') {
                    \App\Services\NotificationService::notifyAccountApproved($user, $adminIdToSave);
                } elseif (ucfirst((string)$previousStatus) !== 'Disapproved' && ucfirst((string)$this->editStatus) === 'Disapproved') {
                    \App\Services\NotificationService::notifyAccountDisapproved($user, $user->name, $adminIdToSave);
                }
            }
        }

        $this->closeEditModal();
    }

    public function openSuspendModal($id)
    {
        $this->suspendUserId = $id;
        $this->suspendTargetUser = User::find($id);
        if ($this->suspendTargetUser) {
            $this->suspendReason = $this->suspendTargetUser->suspend_reason ?: '';
            $this->suspendDays = 7;
            $this->search = '';
            $this->statusFilter = '';
            $this->connectivityFilter = '';
            $this->resetPage();
            $this->showSuspendModal = true;
        }
    }

    public function closeSuspendModal()
    {
        $this->showSuspendModal = false;
        $this->suspendUserId = null;
        $this->suspendTargetUser = null;
        $this->suspendReason = '';
        $this->suspendDays = 7;
    }

    public function confirmSuspend()
    {
        if ($this->suspendUserId) {
            $user = User::find($this->suspendUserId);
            if ($user) {
                $isCurrentlySuspended = (ucfirst($user->status) === 'Suspended' || strtolower($user->status) === 'inactive');
                if ($isCurrentlySuspended) {
                    $user->status = 'Active';
                    $user->suspend_reason = null;
                    $user->suspended_until = null;
                    $user->suspended_by_admin_id = null;
                    $user->save();

                    $this->dispatch('student-unsuspended');
                    $this->dispatch('refresh-notifications');
                    session()->flash('message', "Student {$user->name} has been unsuspended.");
                } else {
                    $this->validate([
                        'suspendReason' => 'required|string|max:255',
                        'suspendDays' => 'required|numeric|min:1|max:365',
                    ]);

                    $currentAuth = Auth::guard('admin')->user();
                    $adminIdToSave = 'ADM001';
                    if ($currentAuth && !empty($currentAuth->email)) {
                        $adminRecord = \App\Models\Admin::where('email', $currentAuth->email)->first();
                        if ($adminRecord) {
                            $adminIdToSave = $adminRecord->admin_id;
                        }
                    } else {
                        $firstAdmin = \App\Models\Admin::first();
                        if ($firstAdmin) {
                            $adminIdToSave = $firstAdmin->admin_id;
                        }
                    }

                    $user->status = 'Suspended';
                    $user->suspend_reason = $this->suspendReason;
                    $user->suspended_until = now()->addDays((int)$this->suspendDays);
                    $user->suspended_by_admin_id = $adminIdToSave;
                    $user->save();

                    // Notify the student's admin that the student is suspended
                    $user->notifyAdminOfSuspension(
                        reason: $this->suspendReason,
                        until: $user->suspended_until,
                        suspendedBy: $user->suspended_by_admin_detail
                    );

                    $this->dispatch('student-suspended');
                    $this->dispatch('refresh-notifications');

                    $adminName = $user->assigned_admin?->full_name ?: 'Admin';
                    session()->flash('message', "Student {$user->name} has been suspended for {$this->suspendDays} days. Notification dispatched to Admin ({$adminName}).");
                }
            }
        }
        $this->closeSuspendModal();
    }

    public function suspendOneDay($id)
    {
        $user = User::find($id);
        if (!$user) {
            return;
        }

        $adminIdToSave = $this->getLoggedInAdminId();

        $user->status = 'Suspended';
        $user->suspend_reason = '1-Day Suspension';
        $user->suspended_until = now()->addDay();
        $user->suspended_by_admin_id = $adminIdToSave;
        $user->last_admin_id = $adminIdToSave;
        $user->save();

        // Notify the student's admin that the student is suspended
        $user->notifyAdminOfSuspension(
            reason: '1-Day Suspension',
            until: $user->suspended_until,
            suspendedBy: $user->suspended_by_admin_detail
        );

        $this->dispatch('student-suspended');
        $this->dispatch('refresh-notifications');

        $adminName = $user->assigned_admin?->full_name ?: 'Admin';
        session()->flash('message', "Student {$user->name} has been suspended for 1 day. Notification dispatched to Admin ({$adminName}).");
    }

    public function openViewSuspendModal($id)
    {
        $this->viewSuspendUserId = $id;
        $this->viewSuspendTargetUser = User::find($id);
        if ($this->viewSuspendTargetUser) {
            $this->showViewSuspendModal = true;
        }
    }

    public function closeViewSuspendModal()
    {
        $this->showViewSuspendModal = false;
        $this->viewSuspendUserId = null;
        $this->viewSuspendTargetUser = null;
    }

    public function cancelSuspensionFromView()
    {
        if (!$this->viewSuspendUserId) {
            $this->closeViewSuspendModal();
            return;
        }

        $user = User::find($this->viewSuspendUserId);
        if (!$user) {
            $this->closeViewSuspendModal();
            return;
        }

        $isSuperAdmin = $this->isSuperAdminActor();

        if ($isSuperAdmin) {
            // Super Admin has master authority to directly reactivate
            $user->status = 'Active';
            $user->suspend_reason = null;
            $user->suspended_until = null;
            $user->save();

            $adminId = $this->getLoggedInAdminId();
            \App\Models\UserRequest::where('user_email', $user->email)
                ->where('type', 'Account Reactivation Request')
                ->where('status', 'Pending')
                ->update([
                    'status'   => 'Approved',
                    'admin_id' => $adminId,
                ]);

            session()->flash('message', "Account for {$user->name} has been reactivated successfully.");
            $this->dispatch('user-unsuspended');
            $this->dispatch('student-unsuspended');
            $this->dispatch('refresh-notifications');
            $this->dispatch('request-updated');
        } else {
            // Regular Admin: make a request to who issued the suspension (Super Admin, specific Admin, or System -> SuperAdmin)
            $existingPending = \App\Models\UserRequest::where('user_email', $user->email)
                ->where('type', 'Account Reactivation Request')
                ->where('status', 'Pending')
                ->first();

            if ($existingPending) {
                session()->flash('message', "A reactivation request for {$user->name} ({$existingPending->request_code}) is already pending review.");
                $this->closeViewSuspendModal();
                return;
            }

            // Determine target recipient (who issued the suspension)
            $targetAdminId = 'ADM-001';
            $targetName = 'SuperAdmin';

            if (!empty($user->suspended_by_admin_id) && !in_array(strtolower($user->suspended_by_admin_id), ['system', 'system(superadmin)', 'superadmin'])) {
                $issuer = \App\Models\Admin::where('admin_id', $user->suspended_by_admin_id)
                    ->orWhere('email', $user->suspended_by_admin_id)
                    ->first()
                    ?: \App\Models\User::where('user_id', $user->suspended_by_admin_id)
                    ->orWhere('email', $user->suspended_by_admin_id)
                    ->first();

                if ($issuer) {
                    $targetAdminId = $issuer->admin_id ?? $issuer->user_id ?? $user->suspended_by_admin_id;
                    $targetName = $issuer->full_name ?? $issuer->name ?? $targetAdminId;
                } else {
                    $targetAdminId = $user->suspended_by_admin_id;
                    $targetName = $user->suspended_by_admin_detail ?: $targetAdminId;
                }
            } else {
                // Suspended by System or SuperAdmin -> routed to SuperAdmin
                $superAdmin = \App\Models\User::whereIn('role', ['superadmin', 'super admin', 'SuperAdmin'])->first();
                $targetAdminId = $superAdmin?->user_id ?: 'ADM-001';
                $targetName = 'SuperAdmin (' . $targetAdminId . ')';
            }

            $count = \App\Models\UserRequest::count() + 1;
            $year = date('Y');
            $requestCode = 'REQ-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            while (\App\Models\UserRequest::where('request_code', $requestCode)->exists()) {
                $count++;
                $requestCode = 'REQ-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }

            $currentAuth = Auth::guard('admin')->user() ?? Auth::user();
            $requestingAdminName = $currentAuth?->name ?? $currentAuth?->Full_Name ?? 'Admin';
            $requestingAdminId = $this->getLoggedInAdminId();

            $desc = "Reactivation requested by Admin {$requestingAdminName} ({$requestingAdminId}) for student {$user->name} ({$user->email}). ";
            $desc .= "Account was suspended for: \"" . ($user->suspend_reason ?: 'Administrative policy violation') . "\". ";
            $desc .= "Originally suspended by: " . ($user->suspended_by_admin_detail ?: 'System (SuperAdmin)') . ".";

            \App\Models\UserRequest::create([
                'request_code' => $requestCode,
                'user_name'    => $user->name,
                'user_email'   => $user->email,
                'type'         => 'Account Reactivation Request',
                'description'  => $desc,
                'status'       => 'Pending',
                'admin_id'     => $targetAdminId,
                'submitted_at' => now(),
            ]);

            session()->flash('message', "Reactivation request {$requestCode} for {$user->name} has been sent to {$targetName} for approval.");
            $this->dispatch('request-updated');
            $this->dispatch('refresh-notifications');
        }

        $this->closeViewSuspendModal();
    }

    public function openDeleteModal($id)
    {
        $this->deleteUserId = $id;
        $this->deleteTargetUser = User::find($id);
        if ($this->deleteTargetUser) {
            $this->search = '';
            $this->statusFilter = '';
            $this->connectivityFilter = '';
            $this->resetPage();
            $this->showDeleteModal = true;
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteUserId = null;
        $this->deleteTargetUser = null;
    }

    public function confirmDelete()
    {
        if ($this->deleteUserId) {
            $user = User::find($this->deleteUserId);
            if ($user) {
                $user->status = 'Suspended';
                $user->scheduled_deletion_at = now()->addDays(5);
                $user->save();
            }
        }
        $this->closeDeleteModal();
    }

    public function cancelScheduledDeletion($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->status = 'Active';
            $user->scheduled_deletion_at = null;
            $user->save();
        }
    }

    public function openCancelDeletionModal($id)
    {
        $this->cancelUserId = $id;
        $this->cancelTargetUser = User::find($id);
        if ($this->cancelTargetUser) {
            $this->showCancelDeletionModal = true;
        }
    }

    public function closeCancelDeletionModal()
    {
        $this->showCancelDeletionModal = false;
        $this->cancelUserId = null;
        $this->cancelTargetUser = null;
    }

    public function confirmCancelDeletion()
    {
        if ($this->cancelUserId) {
            $user = User::find($this->cancelUserId);
            if ($user) {
                $user->status = 'Active';
                $user->scheduled_deletion_at = null;
                $user->save();

                $adminId = $this->getLoggedInAdminId();
                \App\Models\UserRequest::where('user_email', $user->email)
                    ->where('type', 'Account Access Request')
                    ->update([
                        'status'   => 'Approved',
                        'admin_id' => $adminId,
                    ]);
            }
        }
        $this->closeCancelDeletionModal();
    }

    public function sortBy($field)
    {
        $allowedFields = ['student_id', 'section', 'user_id', 'id', 'name', 'email', 'status', 'soil_scans_count', 'location_mappings_count', 'updated_at', 'created_at'];

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

    public function updatedConnectivityFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
    }

    public function updatedSectionFilter()
    {
        $this->resetPage();
    }

    public function updatedAdminFilter()
    {
        $this->resetPage();
    }

    public function selectSuggestion($value)
    {
        $this->search = $value;
        $this->statusFilter = '';
        $this->connectivityFilter = '';
        $this->sectionFilter = '';
        $this->adminFilter = '';
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function openRemoveStudentModal($id)
    {
        $this->removeStudentId = $id;
        $this->removeTargetUser = User::find($id);
        if ($this->removeTargetUser) {
            $this->showRemoveStudentModal = true;
        }
    }

    public function closeRemoveStudentModal()
    {
        $this->showRemoveStudentModal = false;
        $this->removeStudentId = null;
        $this->removeTargetUser = null;
    }

    public function confirmRemoveStudent()
    {
        if ($this->removeStudentId) {
            $user = User::find($this->removeStudentId);
            if ($user) {
                // Remove from Student Records view only — preserves user completely in Manage Users
                $user->student_record_status = 'Removed';
                $user->save();
            }
        }
        $this->closeRemoveStudentModal();
    }

    protected function scopeStudents($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw('LOWER(role) = ?', ['student'])
              ->orWhere(function ($sub) {
                  $sub->whereNotNull('student_id')->where('student_id', '!=', '');
              });
        })->where('student_record_status', 'Active')
        ->where(function ($q) {
            $q->where('status', '!=', 'Pending')
              ->orWhereNull('status');
        });
    }

    public function render()
    {
        // Auto-reactivate expired suspensions
        User::where('status', 'Suspended')
            ->whereNull('scheduled_deletion_at')
            ->whereNotNull('suspended_until')
            ->where('suspended_until', '<=', now())
            ->update([
                'status' => 'Active',
                'suspended_until' => null,
                'suspend_reason' => null,
            ]);

        $currentUser = Auth::guard('admin')->user();
        $currentEmail = strtolower(trim($currentUser?->Email ?? $currentUser?->email ?? ''));

        $studentsQuery = $this->scopeStudents(User::query())
            ->withCount(['soilScans', 'locationMappings'])
            ->when($currentEmail, function ($query) use ($currentEmail) {
                $query->whereRaw('LOWER(email) != ?', [$currentEmail]);
            })
            ->when($this->sectionFilter, function ($query) {
                $query->where('section', trim($this->sectionFilter));
            })
            // NEW — only super admins can use this filter; a regular admin has no reason to see it
            ->when($this->isSuperAdminActor() && $this->adminFilter, function ($query) {
                $filter = $this->adminFilter;
                $variants = array_unique([
                    $filter,
                    str_replace('-', '', $filter),
                    \App\Models\Admin::formatId($filter),
                ]);
                $myAdminId = $this->getLoggedInAdminId();
                $isSuperAdminOption = ($filter === $myAdminId || \App\Models\Admin::formatId($filter) === \App\Models\Admin::formatId($myAdminId));
                if ($isSuperAdminOption) {
                    $query->where(function ($q) use ($variants) {
                        $q->whereIn('last_admin_id', $variants)
                          ->orWhereNull('last_admin_id')
                          ->orWhere('last_admin_id', '');
                    });
                } else {
                    $query->whereIn('last_admin_id', $variants);
                }
            })
            // Regular admins only ever see their own students
            ->when(!$this->isSuperAdminActor(), function ($query) {
                $myAdminId = $this->getLoggedInAdminId();
                $variants = array_unique([
                    $myAdminId,
                    str_replace('-', '', $myAdminId),
                    \App\Models\Admin::formatId($myAdminId),
                ]);
                $query->whereIn('last_admin_id', $variants);
            })
            ->when($this->selectedDate, function ($query) {
                $query->whereDate('created_at', $this->selectedDate);
            })
            ->when($this->search, function ($query) {
                $searchStr = trim($this->search);
                $query->where(function ($q) use ($searchStr) {
                    if (str_starts_with($searchStr, '#')) {
                        $idNum = ltrim($searchStr, '#');
                        $q->where('user_id', 'like', '%'.$idNum.'%')
                          ->orWhere('student_id', 'like', '%'.$idNum.'%');
                    } else {
                        $q->where('name', 'like', '%'.$searchStr.'%')
                          ->orWhere('email', 'like', '%'.$searchStr.'%')
                          ->orWhere('student_id', 'like', '%'.$searchStr.'%')
                          ->orWhere('user_id', 'like', '%'.$searchStr.'%');
                    }
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            });

        $baseScopedQuery = $this->scopeStudents(User::query())
            ->when($currentEmail, fn($q) => $q->where('email', '!=', $currentEmail))
            ->when($this->sectionFilter, fn($q) => $q->where('section', trim($this->sectionFilter)))
            ->when($this->isSuperAdminActor() && $this->adminFilter, function ($q) {
                $filter = $this->adminFilter;
                $variants = array_unique([
                    $filter,
                    str_replace('-', '', $filter),
                    \App\Models\Admin::formatId($filter),
                ]);
                $myAdminId = $this->getLoggedInAdminId();
                $isSuperAdminOption = ($filter === $myAdminId || \App\Models\Admin::formatId($filter) === \App\Models\Admin::formatId($myAdminId));
                if ($isSuperAdminOption) {
                    $q->where(function ($sub) use ($variants) {
                        $sub->whereIn('last_admin_id', $variants)
                            ->orWhereNull('last_admin_id')
                            ->orWhere('last_admin_id', '');
                    });
                } else {
                    $q->whereIn('last_admin_id', $variants);
                }
            })
            ->when(!$this->isSuperAdminActor(), function ($q) {
                $myAdminId = $this->getLoggedInAdminId();
                $variants = array_unique([
                    $myAdminId,
                    str_replace('-', '', $myAdminId),
                    \App\Models\Admin::formatId($myAdminId),
                ]);
                $q->whereIn('last_admin_id', $variants);
            });

        $counts = (clone $baseScopedQuery)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_online IS TRUE THEN 1 ELSE 0 END) as online')
            ->first();

        $totalRegistered = (int) ($counts->total ?? 0);
        $onlineCount = (int) ($counts->online ?? 0);
        $offlineCount = max(0, $totalRegistered - $onlineCount);

        if ($this->connectivityFilter === 'Online') {
            $studentsQuery->where('is_online', true);
        } elseif ($this->connectivityFilter === 'Offline') {
            $studentsQuery->where('is_online', false);
        }

        $orderCol = $this->sortField;
        if ($orderCol === 'id') {
            $orderCol = 'user_id';
        }
        $students = $studentsQuery
            ->orderBy($orderCol, $this->sortDirection)
            ->paginate($this->perPage);

        $searchSuggestions = [];
        $trimmedSearch = trim($this->search);
        if (strlen($trimmedSearch) >= 1) {
            $searchSuggestions = $this->scopeStudents(User::query())
                ->when($currentEmail, function ($query) use ($currentEmail) {
                    $query->whereRaw('LOWER(email) != ?', [$currentEmail]);
                })
                ->when($this->isSuperAdminActor() && $this->adminFilter, function ($query) {
                    $filter = $this->adminFilter;
                    $variants = array_unique([
                        $filter,
                        str_replace('-', '', $filter),
                        \App\Models\Admin::formatId($filter),
                    ]);
                    $myAdminId = $this->getLoggedInAdminId();
                    $isSuperAdminOption = ($filter === $myAdminId || \App\Models\Admin::formatId($filter) === \App\Models\Admin::formatId($myAdminId));
                    if ($isSuperAdminOption) {
                        $query->where(function ($q) use ($variants) {
                            $q->whereIn('last_admin_id', $variants)
                              ->orWhereNull('last_admin_id')
                              ->orWhere('last_admin_id', '');
                        });
                    } else {
                        $query->whereIn('last_admin_id', $variants);
                    }
                })
                ->when(!$this->isSuperAdminActor(), function ($query) {
                    $myAdminId = $this->getLoggedInAdminId();
                    $variants = array_unique([
                        $myAdminId,
                        str_replace('-', '', $myAdminId),
                        \App\Models\Admin::formatId($myAdminId),
                    ]);
                    $query->whereIn('last_admin_id', $variants);
                })
                ->where(function ($q) use ($trimmedSearch) {
                    if (str_starts_with($trimmedSearch, '#')) {
                        $idNum = ltrim($trimmedSearch, '#');
                        $q->where('user_id', 'like', '%'.$idNum.'%')
                          ->orWhere('student_id', 'like', '%'.$idNum.'%');
                    } else {
                        $q->where('name', 'like', '%'.$trimmedSearch.'%')
                          ->orWhere('email', 'like', '%'.$trimmedSearch.'%')
                          ->orWhere('student_id', 'like', '%'.$trimmedSearch.'%')
                          ->orWhere('user_id', 'like', '%'.$trimmedSearch.'%');
                    }
                })
                ->limit(8)
                ->get();
        }

        $editTargetUser = $this->editUserId ? User::find($this->editUserId) : null;
        $editProfilePhotoUrl = $editTargetUser?->profile_photo_url;
        if ($this->editPhoto) {
            $editProfilePhotoUrl = $this->editPhoto->temporaryUrl();
        }
        $editUserInitial = strtoupper(substr($this->editName ?: 'S', 0, 1));
        $viewTargetUser = $this->viewUserId ? User::withCount(['soilScans', 'locationMappings'])->find($this->viewUserId) : null;

        return view('admin.livewire.student.student-record', [
            'students' => $students,
            'searchSuggestions' => $searchSuggestions,
            'totalRegistered' => $totalRegistered,
            'onlineCount' => $onlineCount,
            'offlineCount' => $offlineCount,
            'editProfilePhotoUrl' => $editProfilePhotoUrl,
            'editUserInitial' => $editUserInitial,
            'viewTargetUser' => $viewTargetUser,
            'search' => $this->search,
            'selectedDate' => $this->selectedDate,
            'sectionFilter' => $this->sectionFilter,
            'viewMode' => $this->viewMode,
            'statusFilter' => $this->statusFilter,
            'connectivityFilter' => $this->connectivityFilter,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'showEditModal' => $this->showEditModal,
            'showSuspendModal' => $this->showSuspendModal,
            'showViewSuspendModal' => $this->showViewSuspendModal,
            'showDeleteModal' => $this->showDeleteModal,
            'showCancelDeletionModal' => $this->showCancelDeletionModal,
            'showRemoveStudentModal' => $this->showRemoveStudentModal,
            'showSoilScansModal' => $this->showSoilScansModal,
            'showPinMapsModal' => $this->showPinMapsModal,
            'showAddStudentModal' => $this->showAddStudentModal,
            'showSectionRecordsModal' => $this->showSectionRecordsModal,
            'sectionRecordsSearch' => $this->sectionRecordsSearch,
            'sectionRecordsSectionFilter' => $this->sectionRecordsSectionFilter,
            'sectionRecordsColumnName' => $this->sectionRecordsColumnName,
            'sectionRecordsDateFilter' => $this->sectionRecordsDateFilter,
            'sectionRecordsStartTime' => $this->sectionRecordsStartTime,
            'sectionRecordsEndTime' => $this->sectionRecordsEndTime,
            'sectionRecordsShowScans' => $this->sectionRecordsShowScans,
            'sectionRecordsShowPins' => $this->sectionRecordsShowPins,
            'sectionRecords' => $this->showSectionRecordsModal ? $this->sectionRecords : collect(),
            'activityCards' => $this->activityCards,
            'documentTitle' => $this->documentTitle,
            'showViewModal' => $this->showViewModal,
            'addedStudentIds' => $this->addedStudentIds,
            'searchResults' => $this->searchResults,
            'availableSections' => $this->availableSections,
            'modalAvailableSections' => $this->modalAvailableSections,
            'adminFilter' => $this->adminFilter,
            'isSuperAdmin' => $this->isSuperAdminActor(),
            'availableAdmins' => $this->availableAdmins,
            'loggedInAdminId' => $this->getLoggedInAdminId(),
            'loggedInAdminName' => $this->getLoggedInAdminName(),
        ]);
    }
}
