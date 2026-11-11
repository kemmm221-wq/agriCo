@php
    $logoSrc = 'https://agrico-web-production.up.railway.app/images/agrico-logo.png';
    if (isset($message) && method_exists($message, 'embed') && file_exists(public_path('images/agrico-logo.png'))) {
        try {
            $logoSrc = $message->embed(public_path('images/agrico-logo.png'));
        } catch (\Throwable $e) {
            $logoSrc = 'https://agrico-web-production.up.railway.app/images/agrico-logo.png';
        }
    }
@endphp
<!-- Top Brand Header (Horizontal Lockup Matching Password Reset Code) -->
<tr>
    <td align="center" style="padding: 32px 32px 20px 32px; border-bottom: 1px solid #f1f5f9; text-align: center; background-color: #ffffff;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin: 0 auto; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: middle; padding-right: 12px;">
                    <a href="https://agrico-web-production.up.railway.app" target="_blank" style="text-decoration: none; display: block;">
                        <img src="{{ $logoSrc }}" 
                             alt="AgriCo Logo" 
                             width="48" 
                             height="48" 
                             border="0"
                             style="width: 48px; height: 48px; display: block; object-fit: contain; border: 0; outline: none; text-decoration: none;">
                    </a>
                </td>
                <td style="vertical-align: middle; text-align: left;">
                    <a href="https://agrico-web-production.up.railway.app" target="_blank" style="text-decoration: none;">
                        <div style="font-family: Arial, Helvetica, sans-serif; font-size: 26px; font-weight: 900; color: #22c55e; margin: 0; line-height: 1; letter-spacing: -0.02em;">AgriCo</div>
                    </a>
                </td>
            </tr>
        </table>
        <div style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #94a3b8; margin-top: 10px; text-align: center;">
            Agricultural Soil Analysis &amp; Mapping Platform
        </div>
    </td>
</tr>
