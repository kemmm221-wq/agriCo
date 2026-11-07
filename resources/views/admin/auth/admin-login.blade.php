<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="auth-page">

<head>
    @include('admin.partials.head')
    <title>Log In — AgriCo</title>
    <style>
        input[type="email"],
        input[type="password"],
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active,
        input:autofill {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            box-shadow: 0 0 0 1000px #ffffff inset !important;
            -webkit-text-fill-color: #051F20 !important;
            caret-color: #051F20 !important;
            background-color: #ffffff !important;
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #051F20 !important;
        }
        input[type="email"]:focus,
        input[type="password"]:focus {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(35, 83, 71, 0.15) !important;
            box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(35, 83, 71, 0.15) !important;
            border-color: #235347 !important;
        }
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden !important;
                max-width: 100vw !important;
            }
            body.auth-body::before,
            body.auth-body::after {
                display: none !important;
            }
            .auth-arc {
                display: none !important;
            }
            body.auth-body {
                overflow-x: hidden !important;
                max-width: 100vw !important;
                padding: max(60px, calc(env(safe-area-inset-top) + 48px)) 16px 24px 16px !important;
                height: auto !important;
                min-height: 100vh !important;
                max-height: none !important;
                overflow-y: auto !important;
            }
        }
    </style>
</head>

<body class="auth-body" style="background: linear-gradient(180deg, #DAF1DE 0%, #f6faf7 45%, #ffffff 100%) !important; min-height: 100vh; max-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; overflow-x: hidden; overflow-y: auto; max-width: 100vw; margin: 0; padding: 12px 16px; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif;">

    <!-- Decorative background layer with strict overflow clipping -->
    <div style="position: absolute; inset: 0; width: 100%; height: 100%; overflow: hidden; pointer-events: none; z-index: 0;">
        <div class="auth-arc" style="position: absolute; width: 640px; height: 640px; border: 1.5px solid rgba(142, 182, 155, 0.4); border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;"></div>
    </div>

    <!-- Brand Header -->
    <a href="{{ route('dashboard') }}" class="auth-brand" style="position: fixed; top: 14px; left: 16px; z-index: 10; display: flex; align-items: center; gap: 8px; text-decoration: none;">
        <img src="{{ asset('images/agrico-logo.png') }}" style="width: 32px; height: 32px; object-fit: contain;" alt="AgriCo">
        <span class="auth-brand-text" style="font-size: 18px; font-weight: 900; color: #235347; letter-spacing: -0.02em;">AgriCo</span>
    </a>

    <!-- Auth Container Card -->
    <div class="auth-card" style="position: relative; z-index: 1; background: #ffffff; border-radius: 20px; width: 100%; max-width: 420px; padding: 32px 36px; box-shadow: 0 20px 50px rgba(5, 31, 32, 0.08); border: 1px solid rgba(218, 241, 222, 0.8);">
        
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #DAF1DE; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; border: 1px solid #8EB69B;">
                <svg width="24" height="24" fill="none" stroke="#235347" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h1 style="font-size: 22px; font-weight: 800; color: #051F20; margin: 0 0 6px 0;">Log In</h1>
            <p style="font-size: 13px; color: #163832; margin: 0;">Access your AgriCo account to manage soil records, maps, and requests.</p>
        </div>

        @if($errors->any())
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                @foreach($errors->all() as $error)
                    <p style="margin: 0; font-size: 13px; color: #991b1b;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}" autocomplete="off">
            @csrf

            <!-- Email Address -->
            <div style="margin-bottom: 18px;">
                <label for="email" style="display: block; font-size: 13px; font-weight: 600; color: #163832; margin-bottom: 6px;">Email Address</label>
                <div style="position: relative;">
                    <input id="email" type="email" name="email" value="{{ $errors->any() ? old('email') : '' }}" required autocomplete="username email"
                           onfocus="this.style.borderColor='#235347';"
                           onblur="this.style.borderColor='#cbd5e1';"
                           placeholder="admin@agrico.com"
                           style="width: 100%; padding: 10px 14px 10px 38px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; box-sizing: border-box; outline: none; transition: border-color 0.2s;">
                    <svg width="18" height="18" fill="none" stroke="#163832" stroke-width="2" viewBox="0 0 24 24" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>
            </div>

            <!-- Password -->
            <div style="margin-bottom: 22px;">
                <label for="password" style="display: block; font-size: 13px; font-weight: 600; color: #163832; margin-bottom: 6px;">Password</label>
                <div style="position: relative;">
                    <input id="password" type="password" name="password" value="" required autocomplete="current-password"
                           onfocus="this.style.borderColor='#235347';"
                           onblur="this.style.borderColor='#cbd5e1';"
                           placeholder="••••••••"
                           style="width: 100%; padding: 10px 14px 10px 38px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; box-sizing: border-box; outline: none; transition: border-color 0.2s;">
                    <svg width="18" height="18" fill="none" stroke="#163832" stroke-width="2" viewBox="0 0 24 24" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" style="width: 100%; padding: 12px; background: #235347; color: #ffffff; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 12px rgba(35, 83, 71, 0.3);"
                    onmouseover="this.style.background='#163832';" onmouseout="this.style.background='#235347';">
                Log In
            </button>
        </form>

    </div>

    <script>
        let adminUserInteracted = false;
        function markAdminInteracted() {
            adminUserInteracted = true;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('focusin', markAdminInteracted, { capture: true });
                form.addEventListener('mousedown', markAdminInteracted, { capture: true });
                form.addEventListener('keydown', markAdminInteracted, { capture: true });
                form.addEventListener('touchstart', markAdminInteracted, { capture: true });
                form.addEventListener('pointerdown', markAdminInteracted, { capture: true });
            }

            function clearAdminOnLoad() {
                if (!adminUserInteracted) {
                    @if (!$errors->any())
                    const emailInput = document.getElementById('email');
                    const passwordInput = document.getElementById('password');
                    if (emailInput && !adminUserInteracted) emailInput.value = '';
                    if (passwordInput && !adminUserInteracted) passwordInput.value = '';
                    @endif
                }
            }

            window.addEventListener('pageshow', clearAdminOnLoad);
            clearAdminOnLoad();
            setTimeout(clearAdminOnLoad, 50);
            setTimeout(clearAdminOnLoad, 150);
            setTimeout(clearAdminOnLoad, 300);
        });
    </script>
</body>
</html>
