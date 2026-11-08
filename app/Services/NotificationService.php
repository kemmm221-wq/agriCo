<?php

namespace App\Services;

use App\Mail\AccountApprovedMail;
use App\Mail\AccountDisapprovedMail;
use App\Mail\SoilPinApprovedMail;
use App\Mail\SoilPinDisapprovedMail;
use App\Mail\SoilPinSubmittedMail;
use App\Mail\SoilScanModeratedMail;
use App\Models\LocationMapping;
use App\Models\SoilScan;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Pin submission notification (Disabled per user requirements: only admin decisions generate notifications/emails).
     */
    public static function notifyPinSubmitted(LocationMapping $location, ?User $user = null, string $platform = 'application'): void
    {
        // No-op: pin submissions do not generate notifications or emails.
        return;
    }

    /**
     * Send mobile notifications (Database + Email) when admin approves or rejects a scan.
     */
    public static function notifyScanModerated(SoilScan $scan, string $status, ?string $adminId = null): void
    {
        try {
            // Find target user
            $user = null;
            if (!empty($scan->user_id)) {
                $user = User::where('user_id', $scan->user_id)->first();
            }

            if (!$user && empty($scan->user_id)) {
                return;
            }

            $userId = $user?->user_id ?? $scan->user_id;
            $isVerified = $status === 'Verified';
            $type = $isVerified ? 'scan_approved' : 'scan_rejected';

            // Idempotency guard: prevent duplicate moderation notification for same scan & decision
            $alreadyNotified = UserNotification::where('user_id', $userId)
                ->where('type', $type)
                ->where('data->soil_scan_id', $scan->soil_scan_id)
                ->exists();

            if ($alreadyNotified) {
                Log::info("[NotificationService] Scan moderation notification already exists for scan #{$scan->soil_scan_id}. Skipping duplicate.");
                return;
            }

            $title = $isVerified
                ? 'Soil Scan Approved & Published'
                : 'Soil Scan Disapproved';

            $message = $isVerified
                ? "Your soil sample scan #{$scan->soil_scan_id} has been approved by an administrator! Your location pin is now live on the AgriCo Map."
                : "Your soil sample scan #{$scan->soil_scan_id} was reviewed and disapproved. The location pin will remain hidden.";

            $location = $scan->location ?? LocationMapping::where('soil_scan_id', $scan->soil_scan_id)->first();

            // 1. Create In-App Notification in Database (Mobile Platform Only)
            UserNotification::create([
                'user_id'  => $userId,
                'title'    => $title,
                'message'  => $message,
                'type'     => $type,
                'platform' => 'mobile',
                'data'     => [
                    'soil_scan_id' => $scan->soil_scan_id,
                    'soil_id'      => $scan->soil_id,
                    'status'       => $status,
                    'admin_id'     => $adminId,
                    'barangay'     => $location?->barangay,
                    'latitude'     => $location?->latitude ? (float) $location->latitude : null,
                    'longitude'    => $location?->longitude ? (float) $location->longitude : null,
                ],
                'is_read' => false,
            ]);

            // 2. Dispatch Email Notification
            if ($user && !empty($user->email) && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $barangay = $location?->barangay ?? 'Centro (Poblacion)';
                $latitude = $location?->latitude ? (string) $location->latitude : '18.1965';
                $longitude = $location?->longitude ? (string) $location->longitude : '121.6621';

                if ($isVerified) {
                    $mailable = new SoilPinApprovedMail(
                        userName: $user->name ?? 'AgriCo Contributor',
                        scanId: $scan->soil_scan_id,
                        barangay: $barangay,
                        latitude: $latitude,
                        longitude: $longitude,
                        munsellCode: $scan->soil?->munsell_notation ?? 'Verified',
                        soilName: $scan->soil?->soil_name ?? 'Soil Sample'
                    );
                    $renderedHtml = (string) view('emails.soil-pin-approved', [
                        'userName'    => $user->name ?? 'AgriCo Contributor',
                        'scanId'      => $scan->soil_scan_id,
                        'barangay'    => $barangay,
                        'latitude'    => $latitude,
                        'longitude'   => $longitude,
                        'munsellCode' => $scan->soil?->munsell_notation ?? 'Verified',
                        'soilName'    => $scan->soil?->soil_name ?? 'Soil Sample',
                    ])->render();
                    $subject = "[AgriCo] Your Soil Pin #{$scan->soil_scan_id} Has Been Approved & Published!";
                } else {
                    $mailable = new SoilPinDisapprovedMail(
                        userName: $user->name ?? 'AgriCo Contributor',
                        scanId: $scan->soil_scan_id,
                        barangay: $barangay,
                        reason: 'Ambiguous soil sample illumination or coordinate mismatch with barangay boundary.'
                    );
                    $renderedHtml = (string) view('emails.soil-pin-disapproved', [
                        'userName' => $user->name ?? 'AgriCo Contributor',
                        'scanId'   => $scan->soil_scan_id,
                        'barangay' => $barangay,
                        'reason'   => 'Ambiguous soil sample illumination or coordinate mismatch with barangay boundary.',
                    ])->render();
                    $subject = "[AgriCo] Notice Regarding Soil Pin Submission #{$scan->soil_scan_id}";
                }

                self::sendEmail(
                    toEmail: $user->email,
                    subject: $subject,
                    html: $renderedHtml,
                    mailable: $mailable
                );
            }
        } catch (\Throwable $e) {
            Log::error('[NotificationService@notifyScanModerated] ' . $e->getMessage(), [
                'exception' => $e,
                'soil_scan_id' => $scan->soil_scan_id ?? null,
            ]);
        }
    }

    /**
     * Send website notifications (Database + Email) when admin approves an account registration/reactivation.
     */
    public static function notifyAccountApproved(User $user, ?string $adminId = null): void
    {
        try {
            $userId = $user->user_id ?? $user->id;

            // Idempotency guard: prevent duplicate approval notification for same account
            $alreadyNotified = UserNotification::where('user_id', $userId)
                ->where('type', 'account_approved')
                ->exists();

            if ($alreadyNotified) {
                Log::info("[NotificationService] Account approval notification already exists for user {$userId}. Skipping duplicate.");
                return;
            }

            // 1. In-App / Database Notification (Website Platform Only)
            UserNotification::create([
                'user_id'  => $userId,
                'title'    => 'Account Registration Approved',
                'message'  => 'Welcome to AgriCo! Your account registration has been reviewed and approved by an administrator. Scanning, mapping, and all platform features are now unlocked.',
                'type'     => 'account_approved',
                'platform' => 'website',
                'data'     => [
                    'user_id'  => $userId,
                    'role'     => $user->role,
                    'admin_id' => $adminId,
                    'status'   => 'Active',
                ],
                'is_read' => false,
            ]);

            // 2. Dispatch Welcome / Approval Email
            if (!empty($user->email) && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $userName = trim((string) $user->name);
                $userEmail = trim((string) $user->email);
                $mailable = new AccountApprovedMail(
                    user: $user,
                    adminId: $adminId,
                    loginUrl: 'https://agrico-web-production.up.railway.app/login',
                    userName: $userName,
                    userEmail: $userEmail
                );
                $renderedHtml = (string) view('emails.account-approved', [
                    'user'      => $user,
                    'userName'  => $userName,
                    'userEmail' => $userEmail,
                    'adminId'   => $adminId,
                    'loginUrl'  => 'https://agrico-web-production.up.railway.app/login',
                ])->render();

                self::sendEmail(
                    toEmail: $user->email,
                    subject: '[AgriCo] Account Registration Approved - Welcome to AgriCo!',
                    html: $renderedHtml,
                    mailable: $mailable
                );
            }
        } catch (\Throwable $e) {
            Log::error('[NotificationService@notifyAccountApproved] ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => $user->user_id ?? null,
            ]);
        }
    }

    /**
     * Send website notifications (Database + Email) when admin disapproves an account registration.
     */
    public static function notifyAccountDisapproved(User|string $userOrEmail, ?string $userName = null, ?string $adminId = null, ?string $reason = null): void
    {
        try {
            $user = $userOrEmail instanceof User ? $userOrEmail : null;
            $email = $user ? $user->email : (string) $userOrEmail;
            $name = $userName ?: ($user?->name ?? 'AgriCo Applicant');
            $userId = $user?->user_id ?? ($user ? ($user->user_id ?? $user->id) : null);

            // If we don't have user object but have email, look up user
            if (!$userId && !empty($email)) {
                $foundUser = User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
                $userId = $foundUser?->user_id;
            }

            // Idempotency guard: prevent duplicate disapproval notification
            if ($userId) {
                $alreadyNotified = UserNotification::where('user_id', $userId)
                    ->where('type', 'account_disapproved')
                    ->exists();

                if ($alreadyNotified) {
                    Log::info("[NotificationService] Account disapproval notification already exists for user {$userId}. Skipping duplicate.");
                    return;
                }

                // 1. In-App Notification (Website Platform Only)
                UserNotification::create([
                    'user_id'  => $userId,
                    'title'    => 'Account Registration Disapproved',
                    'message'  => 'Your account registration was reviewed and could not be approved at this time' . ($reason ? ": {$reason}" : '.'),
                    'type'     => 'account_disapproved',
                    'platform' => 'website',
                    'data'     => [
                        'user_id'  => $userId,
                        'admin_id' => $adminId,
                        'reason'   => $reason,
                        'status'   => 'Disapproved',
                    ],
                    'is_read'  => false,
                ]);
            }

            // 2. Dispatch Disapproval Email
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mailable = new AccountDisapprovedMail(
                    user: $user,
                    userName: $name,
                    userEmail: $email,
                    adminId: $adminId,
                    reason: $reason
                );
                $renderedHtml = (string) view('emails.account-disapproved', [
                    'user'      => $user,
                    'userName'  => $name,
                    'userEmail' => $email,
                    'adminId'   => $adminId,
                    'reason'    => $reason,
                ])->render();

                self::sendEmail(
                    toEmail: $email,
                    subject: '[AgriCo] Account Registration Update - Disapproved',
                    html: $renderedHtml,
                    mailable: $mailable
                );
            }
        } catch (\Throwable $e) {
            Log::error('[NotificationService@notifyAccountDisapproved] ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Dispatch email via Resend HTTP API (fallback to Laravel Mail driver).
     */
    public static function sendEmail(string $toEmail, string $subject, string $html, Mailable $mailable): bool
    {
        $sent = false;

        $fromEmail = (string) config('services.resend.from', 'AgriCo Support <onboarding@resend.dev>');
        if (str_starts_with($fromEmail, 'RESEND_FROM=')) {
            $fromEmail = substr($fromEmail, strlen('RESEND_FROM='));
        }
        $fromEmail = trim($fromEmail, " \t\n\r\0\x0B\"'") ?: 'AgriCo Support <onboarding@resend.dev>';

        $resendKey = config('services.resend.key') ?: env('RESEND_API_KEY');
        if (is_string($resendKey)) {
            $resendKey = trim($resendKey);
            if (str_starts_with($resendKey, 'RESEND_API_KEY=')) {
                $resendKey = substr($resendKey, strlen('RESEND_API_KEY='));
            }
            $resendKey = trim($resendKey, " \t\n\r\0\x0B\"'");
        }

        // 1. Try configured Laravel Mail driver (e.g. Gmail SMTP) first with fast pre-check
        $mailDefault = config('mail.default', 'smtp');
        if ($mailDefault === 'smtp' && !empty(config('mail.mailers.smtp.host'))) {
            $smtpHost = config('mail.mailers.smtp.host');
            $smtpPort = config('mail.mailers.smtp.port', 587);
            $canConnect = false;

            $socket = @fsockopen($smtpHost, (int) $smtpPort, $errno, $errstr, 3);
            if ($socket) {
                $canConnect = true;
                fclose($socket);
            }

            if ($canConnect) {
                try {
                    Mail::to($toEmail)->send($mailable);
                    $sent = true;
                    Log::info("[NotificationService] Email sent via Mail driver ({$mailDefault}) to {$toEmail}: {$subject}");
                } catch (\Throwable $e) {
                    Log::warning("[NotificationService] Mail driver ({$mailDefault}) failed for {$toEmail}: " . $e->getMessage());
                }
            } else {
                Log::warning("[NotificationService] Outbound SMTP port {$smtpPort} to {$smtpHost} unreachable or blocked by cloud network. Skipping blocking SMTP send.");
            }
        }

        // 2. Fallback / alternative: Resend HTTP API
        if (!$sent && class_exists(\Resend::class) && !empty($resendKey)) {
            try {
                $resend = \Resend::client($resendKey);
                $resend->emails->send([
                    'from'    => $fromEmail,
                    'to'      => [$toEmail],
                    'subject' => $subject,
                    'html'    => $html,
                ]);
                $sent = true;
                Log::info("[NotificationService] Email sent via Resend HTTP API to {$toEmail}: {$subject}");
            } catch (\Throwable $re) {
                Log::warning("[NotificationService] Resend HTTP API error for {$toEmail}: " . $re->getMessage());
            }
        }

        // 3. Final fallback to default Mail driver if not yet attempted
        if (!$sent && !empty($canConnect)) {
            try {
                Mail::to($toEmail)->send($mailable);
                $sent = true;
                Log::info("[NotificationService] Email sent via fallback Mail driver to {$toEmail}: {$subject}");
            } catch (\Throwable $e) {
                Log::error("[NotificationService] Failed to send email via fallback Mail driver to {$toEmail}: " . $e->getMessage());
            }
        }

        return $sent;
    }
}
