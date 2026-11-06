<?php

namespace App\Livewire\Admin\User;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ManageUser extends Component
{
    use WithPagination, WithFileUploads;

    #[On('user-suspended')]
    #[On('user-unsuspended')]
    #[On('refresh-notifications')]
    public function refreshUserList()
    {
        // empty body is fine — Livewire re-renders after any method call,
        // which re-runs render() and pulls fresh status/counts from the DB
    }

    public function mount()
    {
        // Seeding handled by database seeder
    }

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $connectivityFilter = '';
    public $selectedDate = '';

    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // EDIT MODAL STATE
    public $showEditModal = false;
    public $isVerified = false;
    public $editUserId = null;
    public $editName = '';
    public $editEmail = '';
    public $editPassword = '';
    public $editPasswordConfirmation = '';
    public $editRole = 'user';
    public $editStudentId = '';
    public $editSection = '';
    public $editStatus = 'Active';
    public $editPhoto;
    public $adminPassword = '';
    public $adminPasswordError = '';
    public $verifiedAdminId = null;
    public $admin_id = '';
    public $userRoleName = 'User';

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
    public $cancelTargetUser = null;

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

    protected function getLoggedInAdminId(): string
    {
        $user = Auth::guard('admin')->user();
        if (!$user) {
            return 'ADM-001';
        }

        if ($user instanceof \App\Models\Admin) {
            return \App\Models\Admin::formatId($user->Admin_ID);
        }

        if (!empty($user->email)) {
            $adminRecord = \App\Models\Admin::where('email', $user->email)->first();
            if ($adminRecord && !empty($adminRecord->id)) {
                return \App\Models\Admin::formatId($adminRecord->id);
            }
        }

        if (!empty($user->Admin_ID)) {
            return \App\Models\Admin::formatId($user->Admin_ID);
        }

        if (!empty($user->last_admin_id)) {
            return \App\Models\Admin::formatId($user->last_admin_id);
        }

        return \App\Models\Admin::formatId($user->id);
    }

    public function openEditModal($id)
    {
        $user = User::find($id);
        if ($user) {
            $this->editUserId = $user->id;
            $this->editName = $user->name;
            $this->editEmail = $user->email;
            $this->editPassword = '';
            $this->editPasswordConfirmation = '';
            $userRole = strtolower(trim($user->role ?: 'user'));
            if ($userRole === 'member') {
                $userRole = 'user';
            }
            $this->editRole = $userRole;
            $this->editStudentId = $user->student_id ?: '';
            $this->editSection = $user->section ?: '';
            $this->editStatus = (ucfirst($user->status) === 'Suspended' || strtolower($user->status) === 'inactive') ? 'Suspended' : 'Active';
            $this->editPhoto = null;
            $this->userRoleName = in_array($this->editRole, ['superadmin', 'super admin']) ? 'Super Admin' : ucfirst($this->editRole);
            $this->adminPassword = '';
            $this->adminPasswordError = '';
            $this->verifiedAdminId = null;
            $this->admin_id = $this->getLoggedInAdminId();
            $this->isVerified = false;
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
        $this->roleFilter = '';
        $this->statusFilter = '';
        $this->connectivityFilter = '';
        $this->resetPage();
        $this->reset([
            'editUserId',
            'editName',
            'editEmail',
            'editPassword',
            'editPasswordConfirmation',
            'editRole',
            'editStudentId',
            'editSection',
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
            $adminId = strtoupper(trim($authAdmin->admin_id ?? $authAdmin->Admin_ID ?? ''));
            if ($email === 'admin@agrico.com' || $email === 'agricosite77@gmail.com' || $adminId === 'ADM-001' || $adminId === '1') {
                return true;
            }
        }

        $webUser = Auth::user();
        if ($webUser) {
            $email = strtolower(trim($webUser->email ?? ''));
            $role = strtolower(trim($webUser->role ?? ''));
            if ($email === 'admin@agrico.com' || $email === 'agricosite77@gmail.com' || in_array($role, ['superadmin', 'super admin'])) {
                return true;
            }
        }

        if (!empty($authAdmin?->email ?? '')) {
            $userRec = User::whereRaw('LOWER(email) = ?', [strtolower(trim($authAdmin->email))])->first();
            if ($userRec && (in_array(strtolower(trim($userRec->role)), ['superadmin', 'super admin']) || strtolower(trim($userRec->email)) === 'admin@agrico.com' || strtolower(trim($userRec->email)) === 'agricosite77@gmail.com')) {
                return true;
            }
        }

        return false;
    }

    public function updatedEditRole(mixed $value): void
    {
        if (is_string($value) && strtolower($value) === 'student' && empty($this->editStudentId)) {
            $user = $this->editUserId ? User::find($this->editUserId) : null;
            if ($user instanceof User) {
                $userIdentifier = (string)($user->user_id ?: $user->id);
                $this->editStudentId = $user->student_id ?: (preg_match('/^\d{4}-\d+$/', $userIdentifier) ? $userIdentifier : date('Y') . '-' . str_pad($userIdentifier, 5, '0', STR_PAD_LEFT));
            }
        }
    }

    public function saveUser()
    {
        if (!$this->isVerified) {
            $this->adminPasswordError = 'Please verify your admin password first.';
            return;
        }

        $rules = [
            'editName' => 'required|string|max:255',
            'editEmail' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editUserId, 'user_id'),
            ],
            'editRole' => ['required', 'string', Rule::in(['superadmin', 'admin', 'user', 'student'])],
            'editStatus' => 'required|string',
            'editPhoto' => 'nullable|image|max:2048',
        ];

        if (strtolower($this->editRole) === 'student') {
            $rules['editStudentId'] = 'nullable|string|max:50';
            $rules['editSection'] = 'nullable|string|max:100';
        }

        if (!empty($this->editPassword)) {
            if (strlen($this->editPassword) < 8) {
                $this->addError('editPassword', 'Password must be at least 8 characters long.');
                return;
            }
            if (!preg_match('/[0-9]/', $this->editPassword)) {
                $this->addError('editPassword', 'Password must contain at least one number.');
                return;
            }
            if (!preg_match('/[A-Z]/', $this->editPassword)) {
                $this->addError('editPassword', 'Password must contain at least one uppercase letter.');
                return;
            }
            if (!preg_match('/[a-z]/', $this->editPassword)) {
                $this->addError('editPassword', 'Password must contain at least one lowercase letter.');
                return;
            }
            if (!preg_match('/[^A-Za-z0-9]/', $this->editPassword)) {
                $this->addError('editPassword', 'Password must contain at least one special character.');
                return;
            }
            if ($this->editPassword !== $this->editPasswordConfirmation) {
                $this->addError('editPasswordConfirmation', 'The password confirmation does not match.');
                return;
            }
        }

        $this->validate($rules);

        if ($this->editUserId) {
            $user = User::find($this->editUserId);
            if ($user instanceof User) {
                $currentLowerRole = strtolower(trim($user->role));
                $newLowerRole = strtolower(trim($this->editRole));

                $adminRoles = ['admin', 'superadmin', 'super admin', 'administrator'];
                $isChangingAdminStatus = in_array($currentLowerRole, $adminRoles) || in_array($newLowerRole, $adminRoles);

                if ($isChangingAdminStatus && !$this->isSuperAdminActor()) {
                    $this->addError('editRole', 'Only the Super Admin can promote to or demote from Admin.');
                    return;
                }

                if (strtolower(trim($user->email)) === 'admin@agrico.com' && !in_array($newLowerRole, ['superadmin', 'admin'])) {
                    $this->addError('editRole', 'The main Super Admin role cannot be changed.');
                    return;
                }

                $previousStatus = $user->status;
                $user->name = $this->editName;
                $user->email = $this->editEmail;
                $user->status = $this->editStatus;

                if (strtolower($this->editRole) === 'student') {
                    $studentId = trim($this->editStudentId ?: '');
                    if ($studentId === '') {
                        if (!empty($user->student_id)) {
                            $studentId = $user->student_id;
                        } elseif (!empty($user->user_id) && preg_match('/^\d{4}-\d+$/', (string)$user->user_id)) {
                            $studentId = (string)$user->user_id;
                        } else {
                            $userIdentifier = (string)($user->user_id ?: $user->id);
                            $studentId = date('Y') . '-' . str_pad($userIdentifier, 5, '0', STR_PAD_LEFT);
                        }
                    }
                    $user->student_id = $studentId;
                    $user->section = trim($this->editSection ?: '') ?: null;
                    $user->student_record_status = 'Active';
                } else {
                    $user->student_id = null;
                    $user->section = null;
                }

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

                $user->syncRoleAndAdminId($this->editRole);

                $user->save();

                // Also synchronize UserRequest status
                $targetReqStatus = (ucfirst($this->editStatus) === 'Active') ? 'Approved' : 'Disapproved';
                \App\Models\UserRequest::where('user_email', $user->email)->update([
                    'status'   => $targetReqStatus,
                    'admin_id' => $adminIdToSave,
                ]);

                // Send cross-platform notifications if account was approved or disapproved
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

                    $this->dispatch('user-unsuspended');
                    $this->dispatch('student-unsuspended');
                    $this->dispatch('refresh-notifications');
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
                            $adminIdToSave = $firstAdmin->Admin_ID;
                        }
                    }

                    $user->status = 'Suspended';
                    $user->suspend_reason = $this->suspendReason;
                    $user->suspended_until = now()->addDays((int)$this->suspendDays);
                    $user->suspended_by_admin_id = $adminIdToSave;
                    $user->save();

                    // If suspended user is a student, notify their assigned admin
                    if (strtolower(trim($user->role)) === 'student' || !empty($user->student_id)) {
                        $user->notifyAdminOfSuspension(
                            reason: $this->suspendReason,
                            until: $user->suspended_until,
                            suspendedBy: $user->suspended_by_admin_detail
                        );
                    }

                    $this->dispatch('user-suspended');
                    $this->dispatch('student-suspended');
                    $this->dispatch('refresh-notifications');
                }
                $user->save();
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

        // Prevent suspending super admins
        if (strtolower(trim($user->email)) === 'admin@agrico.com' || in_array(strtolower(trim($user->role)), ['superadmin', 'super admin'])) {
            return;
        }

        $adminIdToSave = $this->getLoggedInAdminId();

        $user->status = 'Suspended';
        $user->suspend_reason = '1-Day Suspension';
        $user->suspended_until = now()->addDay();
        $user->suspended_by_admin_id = $adminIdToSave;
        $user->last_admin_id = $adminIdToSave;
        $user->save();

        // If suspended user is a student, notify their assigned admin
        if (strtolower(trim($user->role)) === 'student' || !empty($user->student_id)) {
            $user->notifyAdminOfSuspension(
                reason: '1-Day Suspension',
                until: $user->suspended_until,
                suspendedBy: $user->suspended_by_admin_detail
            );
        }

        $this->dispatch('user-suspended');
        $this->dispatch('student-suspended');
        $this->dispatch('refresh-notifications');
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

            $desc = "Reactivation requested by Admin {$requestingAdminName} ({$requestingAdminId}) for user {$user->name} ({$user->email}). ";
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
            $this->roleFilter = '';
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

                // Synchronize UserRequest status to Approved
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
        $allowedFields = ['user_id', 'id', 'name', 'email', 'role', 'status', 'soil_scans_count', 'location_mappings_count', 'updated_at', 'created_at'];

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

    public function updatedRoleFilter()
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

    public function selectSuggestion($value)
    {
        $this->search = $value;
        $this->roleFilter = '';
        $this->statusFilter = '';
        $this->connectivityFilter = '';
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    protected function scopeApprovedUsers($query)
    {
        return $query->where(function ($q) {
            $q->where('status', '!=', 'Pending')
              ->orWhereNull('status');
        });
    }

    public function render()
    {
        $currentUser = Auth::guard('admin')->user();
        $currentEmail = strtolower(trim($currentUser?->Email ?? $currentUser?->email ?? ''));
        $isSuperAdmin = $this->isSuperAdminActor();

        $usersQuery = $this->scopeApprovedUsers(User::query())
            ->withCount(['soilScans', 'locationMappings'])
            ->when($currentEmail, function ($query) use ($currentEmail) {
                $query->where('email', '!=', $currentEmail);
            })
            ->when(!$isSuperAdmin, function ($query) {
                $query->where(function ($q) {
                    $q->whereIn('role', ['member', 'user', 'student'])
                      ->orWhereNull('role');
                });
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
                          ->orWhere('role', 'like', '%'.$searchStr.'%')
                          ->orWhere('user_id', 'like', '%'.$searchStr.'%')
                          ->orWhere('student_id', 'like', '%'.$searchStr.'%');
                    }
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->where('role', $this->roleFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            });

        $baseScopedQuery = $this->scopeApprovedUsers(User::query())
            ->when($currentEmail, fn($q) => $q->where('email', '!=', $currentEmail))
            ->when(!$isSuperAdmin, fn($q) => $q->where(fn($sub) => $sub->whereIn('role', ['member', 'user', 'student'])->orWhereNull('role')));

        // Single conditional aggregation query instead of multiple cloned counts
        $counts = (clone $baseScopedQuery)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_online IS TRUE THEN 1 ELSE 0 END) as online')
            ->first();

        $totalRegistered = (int) ($counts->total ?? 0);
        $onlineCount = (int) ($counts->online ?? 0);
        $offlineCount = max(0, $totalRegistered - $onlineCount);

        if ($this->connectivityFilter === 'Online') {
            $usersQuery->where('is_online', true);
        } elseif ($this->connectivityFilter === 'Offline') {
            $usersQuery->where('is_online', false);
        }

        $users = $usersQuery
            ->orderBy($this->sortField === 'id' ? 'user_id' : $this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $searchSuggestions = [];
        $trimmedSearch = trim($this->search);
        if (strlen($trimmedSearch) >= 1) {
            $searchSuggestions = $this->scopeApprovedUsers(User::query())
                ->when($currentEmail, function ($query) use ($currentEmail) {
                    $query->where('email', '!=', $currentEmail);
                })
                ->when(!$isSuperAdmin, function ($query) {
                    $query->where(function ($q) {
                        $q->whereIn('role', ['member', 'user', 'student'])
                          ->orWhereNull('role');
                    });
                })
                ->where(function ($q) use ($trimmedSearch) {
                    if (str_starts_with($trimmedSearch, '#')) {
                        $idNum = ltrim($trimmedSearch, '#');
                        $q->where('user_id', 'like', '%'.$idNum.'%')
                          ->orWhere('student_id', 'like', '%'.$idNum.'%');
                    } else {
                        $q->where('name', 'like', '%'.$trimmedSearch.'%')
                          ->orWhere('email', 'like', '%'.$trimmedSearch.'%')
                          ->orWhere('role', 'like', '%'.$trimmedSearch.'%')
                          ->orWhere('user_id', 'like', '%'.$trimmedSearch.'%')
                          ->orWhere('student_id', 'like', '%'.$trimmedSearch.'%');
                    }
                })
                ->limit(8)
                ->get();
        }

        // Only query modal targets when the modal is actually open
        $editTargetUser = ($this->showEditModal && $this->editUserId) ? User::find($this->editUserId) : null;
        $editProfilePhotoUrl = $editTargetUser?->profile_photo_url;
        if ($this->editPhoto) {
            $editProfilePhotoUrl = $this->editPhoto->temporaryUrl();
        }
        $editUserInitial = strtoupper(substr($this->editName ?: 'U', 0, 1));
        $viewTargetUser = ($this->showViewModal && $this->viewUserId) ? User::withCount(['soilScans', 'locationMappings'])->find($this->viewUserId) : null;

        return view('admin.livewire.users.manage-user', [
            'users' => $users,
            'searchSuggestions' => $searchSuggestions,
            'totalRegistered' => $totalRegistered,
            'onlineCount' => $onlineCount,
            'offlineCount' => $offlineCount,
            'editProfilePhotoUrl' => $editProfilePhotoUrl,
            'editUserInitial' => $editUserInitial,
            'viewTargetUser' => $viewTargetUser,
        ]);
    }
}
