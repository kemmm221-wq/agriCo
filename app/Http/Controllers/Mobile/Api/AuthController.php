<?php

namespace App\Http\Controllers\Mobile\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle mobile API registration.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status' => 'Active',
        ]);

        $token = $user->createToken('mobile_sanctum_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'token' => $token,
            'user' => new \App\Http\Resources\UserResource($user),
        ], 201);
    }

    /**
     * Handle mobile API login.
     *
     * Checks the users table first, then falls back to the admins table
     * so admin accounts can also log in from the mobile app.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $rawEmail = (string) $request->email;
        $cleanEmail = preg_replace('/^[\s\x{00a0}\x{200b}-\x{200d}\x{feff}]+|[\s\x{00a0}\x{200b}-\x{200d}\x{feff}]+$/u', '', $rawEmail);
        $cleanEmail = strtolower($cleanEmail);

        $rawPassword = (string) $request->password;
        $cleanPassword = preg_replace('/^[\s\x{00a0}\x{200b}-\x{200d}\x{feff}]+|[\s\x{00a0}\x{200b}-\x{200d}\x{feff}]+$/u', '', $rawPassword);
        $normalizedPassword = str_replace(
            ['！', '＠', '＃', '＄', '％', '＾', '＆', '＊', '（', '）', '·'],
            ['!',  '@',  '#',  '$',  '%',  '^',  '&',  '*',  '(',  ')',  '.'],
            $cleanPassword
        );

        $passwordCandidates = array_unique(array_filter([
            $rawPassword,
            trim($rawPassword),
            $cleanPassword,
            $normalizedPassword,
        ]));

        // ── 1. Try the users table first ──────────────────────────────────
        $user = User::whereRaw('LOWER(email) = ?', [$cleanEmail])->first();

        $userPassMatch = false;
        if ($user) {
            foreach ($passwordCandidates as $candidate) {
                if (Hash::check($candidate, $user->password)) {
                    $userPassMatch = true;
                    break;
                }
            }
        }

        if ($user && $userPassMatch) {
            // Account status gate
            $status = strtolower($user->status ?? '');

            if ($status === 'suspended') {
                $pendingReq = UserRequest::where('user_email', $user->email)
                    ->where('type', 'Account Reactivation Request')
                    ->where('status', 'Pending')
                    ->first();

                return response()->json([
                    'status'     => 'suspended',
                    'message'    => 'Your account has been suspended.',
                    'suspension' => [
                        'reason'                    => $user->suspend_reason ?: 'Administrative Suspension',
                        'suspended_by'              => $user->suspended_by_admin_detail ?: 'System (SuperAdmin)',
                        'suspended_until'           => $user->suspended_until ? $user->suspended_until->format('M d, Y') : 'Manual',
                        'time_remaining'            => $user->suspension_countdown ?? 'Permanent / Indefinite',
                        'has_pending_reactivation'  => (bool) $pendingReq,
                        'pending_request_code'      => $pendingReq?->request_code,
                    ],
                ], 403);
            }

            if ($status === 'pending') {
                return response()->json([
                    'status'  => 'pending',
                    'message' => 'Your account is awaiting administrator approval. You will be able to log in once an admin approves your registration.',
                ], 403);
            }

            if (!in_array($status, ['active', ''])) {
                return response()->json([
                    'status'  => 'inactive',
                    'message' => 'Your account is not active. Please contact support.',
                ], 403);
            }

            $token = $user->createToken('mobile_sanctum_token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Login successful',
                'token'   => $token,
                'user'    => new \App\Http\Resources\UserResource($user),
            ]);
        }

        // ── 2. Fallback: check the admins table ───────────────────────────
        $admin = \App\Models\Admin::whereRaw('LOWER(email) = ?', [$cleanEmail])->first();

        $adminPassMatch = false;
        if ($admin) {
            foreach ($passwordCandidates as $candidate) {
                if (Hash::check($candidate, $admin->password ?? '')) {
                    $adminPassMatch = true;
                    break;
                }
            }
        }

        if ($admin && $adminPassMatch) {
            // Ensure a matching user record exists so Sanctum tokens work
            $userRecord = User::whereRaw('LOWER(email) = ?', [$cleanEmail])->first();

            if (!$userRecord) {
                $targetId = $admin->admin_id;
                if (!$targetId || User::where('user_id', $targetId)->exists()) {
                    $nextNum   = User::count() + 1;
                    $targetId  = 'ADM-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                    while (User::where('user_id', $targetId)->exists()) {
                        $nextNum++;
                        $targetId = 'ADM-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                    }
                }

                $userRecord = User::create([
                    'user_id'  => $targetId,
                    'name'     => $admin->full_name ?? $admin->name ?? 'Admin User',
                    'email'    => $email,
                    'password' => $admin->password ?? Hash::make('Admin@Test2026!'),
                    'role'     => 'admin',
                    'status'   => 'Active',
                ]);
            }

            $token = $userRecord->createToken('mobile_sanctum_token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Login successful',
                'token'   => $token,
                'user'    => new \App\Http\Resources\UserResource($userRecord),
            ]);
        }

        // ── 3. No match found ─────────────────────────────────────────────
        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    /**
     * Handle mobile API logout.
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Handle profile photo update or removal from mobile application.
     */
    public function updateProfilePhoto(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?? $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        if ($request->boolean('remove_photo')) {
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
                $user->update(['profile_photo_path' => null]);
            }
            $admin = \App\Models\Admin::where('email', $user->email)->orWhere('admin_id', $user->user_id)->first();
            if ($admin && $admin->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($admin->profile_photo_path);
                $admin->update(['profile_photo_path' => null]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Profile photo removed successfully',
                'profile_photo_url' => null,
                'user' => new \App\Http\Resources\UserResource($user->fresh()),
            ]);
        }

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
            $user->update(['profile_photo_path' => $photoPath]);

            $admin = \App\Models\Admin::where('email', $user->email)->orWhere('admin_id', $user->user_id)->first();
            if ($admin) {
                if ($admin->profile_photo_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($admin->profile_photo_path);
                }
                $admin->update(['profile_photo_path' => $photoPath]);
            }

            $fresh = $user->fresh();
            return response()->json([
                'status' => 'success',
                'message' => 'Profile photo updated successfully',
                'profile_photo_url' => $fresh->profile_photo_url,
                'user' => new \App\Http\Resources\UserResource($fresh),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No valid photo uploaded',
        ], 422);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?? $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name'             => ['sometimes', 'required', 'string', 'max:255'],
            'email'            => ['sometimes', 'required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'student_id'       => ['nullable', 'string', 'max:50'],
            'section'          => ['nullable', 'string', 'max:100'],
            'current_password' => ['nullable', 'string'],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo'            => ['nullable', 'image', 'max:5120'],
        ]);

        $updateUserData = [];
        $updateAdminData = [];

        if ($request->boolean('remove_photo')) {
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }
            $updateUserData['profile_photo_path'] = null;
            $updateAdminData['profile_photo_path'] = null;
        } elseif ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
            $updateUserData['profile_photo_path'] = $photoPath;
            $updateAdminData['profile_photo_path'] = $photoPath;
        }

        if ($request->filled('name')) {
            $updateUserData['name'] = trim($request->name);
            $updateAdminData['full_name'] = trim($request->name);
        }

        if ($request->filled('email')) {
            $newEmail = strtolower(trim($request->email));
            $updateUserData['email'] = $newEmail;
            $updateAdminData['email'] = $newEmail;
        }

        if ($request->has('student_id')) {
            $updateUserData['student_id'] = $request->student_id ? trim($request->student_id) : null;
        }

        if ($request->has('section')) {
            $updateUserData['section'] = $request->section ? trim($request->section) : null;
        }

        if ($request->filled('password')) {
            $currentHashed = $user->password;
            if (!empty($currentHashed) && (empty($request->current_password) || !\Illuminate\Support\Facades\Hash::check($request->current_password, $currentHashed))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The current password you entered is incorrect.',
                    'errors' => ['current_password' => ['The current password is incorrect.']]
                ], 422);
            }
            $newHash = \Illuminate\Support\Facades\Hash::make($request->password);
            $updateUserData['password'] = $newHash;
            $updateAdminData['password'] = $newHash;
        }

        $oldEmail = $user->email;

        if (!empty($updateUserData)) {
            $user->update($updateUserData);
        }

        $admin = \App\Models\Admin::where('admin_id', $user->user_id)
            ->orWhere('email', $oldEmail)
            ->orWhere('email', $user->email)
            ->first();
        if ($admin && !empty($updateAdminData)) {
            $admin->update($updateAdminData);
        }

        $fresh = $user->fresh();

        return response()->json([
            'status'            => 'success',
            'message'           => 'Profile updated successfully',
            'profile_photo_url' => $fresh->profile_photo_url,
            'user'              => new \App\Http\Resources\UserResource($fresh),
        ]);
    }
}
