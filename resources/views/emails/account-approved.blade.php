<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your AgriCo account has been approved</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f7f5; font-family: Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased; color: #334155;">

    <!-- Hidden Preheader -->
    <div style="display: none; font-size: 1px; color: #f4f7f5; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        Welcome to AgriCo! Your account registration has been approved. You can now sign in to access full soil intelligence tools.
    </div>

    <!-- Outer Container Table -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f7f5; padding: 32px 16px;">
        <tr>
            <td align="center">
                <!-- 600px Email Card -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;">
                    
                    <!-- Top Brand Bar (Shared Centered Partial with Official Logo) -->
                    @include('emails.partials.header')

                    <!-- Status Banner & Title -->
                    <tr>
                        <td align="center" style="padding: 28px 32px 12px 32px; text-align: center;">
                            <!-- Soft Green Centered Badge -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto 16px auto; text-align: center;">
                                <tr>
                                    <td align="center" style="background-color: #dcfce7; border: 1px solid #86efac; border-radius: 20px; padding: 7px 18px; font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold; color: #15803d; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; text-align: center;">
                                        ✓ &nbsp; Account Registration Approved
                                    </td>
                                </tr>
                            </table>

                            @php
                                $displayName = trim((string)($userName ?? $user?->name ?? ''));
                            @endphp

                            <h1 style="margin: 0 0 10px 0; font-family: Arial, Helvetica, sans-serif; font-size: 22px; font-weight: bold; color: #0f172a; line-height: 1.3; text-align: center;">
                                Welcome to AgriCo!
                            </h1>
                            <p style="margin: 0 0 10px 0; font-family: Arial, Helvetica, sans-serif; font-size: 15px; color: #334155; line-height: 1.6; text-align: center;">
                                @if(!empty($displayName))
                                    Hello <strong>{{ $displayName }}</strong>,
                                @else
                                    Hello there,
                                @endif
                            </p>
                            <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 14.5px; color: #475569; line-height: 1.6; text-align: center;">
                                Great news! Your registration request has been reviewed and approved by an administrator. Your account is now fully active, and all platform features are unlocked.
                            </p>
                        </td>
                    </tr>

                    <!-- Details Box -->
                    <tr>
                        <td style="padding: 12px 32px 24px 32px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px;">
                                <tr>
                                    <td style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #64748b; padding-bottom: 8px; width: 38%;">Full Name:</td>
                                    <td style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; font-weight: bold; color: #0f172a; padding-bottom: 8px;">{{ !empty($displayName) ? $displayName : 'AgriCo Member' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #64748b; padding-bottom: 8px;">Registered Email:</td>
                                    <td style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #0f172a; padding-bottom: 8px;">{{ !empty(trim((string)($userEmail ?? $user?->email ?? ''))) ? trim((string)($userEmail ?? $user?->email)) : '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #64748b; padding-bottom: 8px;">Account Status:</td>
                                    <td style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold; color: #16a34a; padding-bottom: 8px;">ACTIVE / APPROVED</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #64748b;">Approval Date:</td>
                                    <td style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #0f172a;">{{ $approvalDate ?? date('d M Y') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Bulletproof CTA Button -->
                    <tr>
                        <td align="center" style="padding: 0 32px 32px 32px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="border-radius: 10px; background-color: #16a34a;">
                                        <a href="https://agrico-web-production.up.railway.app/login" target="_blank" style="display: inline-block; padding: 14px 32px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 10px; letter-spacing: 0.2px;">
                                            Sign in to AgriCo &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 20px 32px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #64748b; line-height: 1.5;">
                            AgriCo Soil Intelligence &bull; Municipality of Lal-lo, Cagayan<br />
                            Need help? Contact support at <a href="mailto:agricosite77@gmail.com" style="color: #16a34a; text-decoration: none;">agricosite77@gmail.com</a>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
