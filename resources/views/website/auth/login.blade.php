<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="auth-page">

<head>
    @include('website.partials.head')
    <title>Log In — AgriCo</title>
    <style>
        /* Override WebKit / Chromium autofill & valid background fill to pure white */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active,
        input:autofill,
        .auth-card input:-webkit-autofill,
        .auth-card input:-webkit-autofill:hover, 
        .auth-card input:-webkit-autofill:focus, 
        .auth-card input:-webkit-autofill:active {
            animation-name: onAutoFillStart;
            transition: background-color 5000s ease-in-out 0s !important;
        }

        @keyframes onAutoFillStart {
            from { opacity: 1; }
            to { opacity: 1; }
        }

        .auth-card input[type="email"],
        .auth-card input[type="password"],
        .floating-input {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            box-shadow: 0 0 0 1000px #ffffff inset !important;
            -webkit-text-fill-color: #0f172a !important;
            caret-color: #0f172a !important;
            background-color: #ffffff !important;
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px !important;
            color: #0f172a !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
        }

        .floating-input::placeholder {
            opacity: 0 !important;
            color: transparent !important;
        }

        /* Base Floating Label Positioning & Crisp Text Rendering */
        .floating-label {
            position: absolute !important;
            left: 44px !important;
            top: 50% !important;
            transform: translateY(-50%) scale(1) !important;
            transform-origin: left center !important;
            font-size: 0.86rem !important;
            color: #94a3b8 !important;
            font-weight: 500 !important;
            pointer-events: none !important;
            background: transparent !important;
            padding: 0 4px !important;
            z-index: 4 !important;
            backface-visibility: hidden !important;
            -webkit-font-smoothing: antialiased !important;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), 
                        top 0.2s cubic-bezier(0.4, 0, 0.2, 1), 
                        left 0.2s cubic-bezier(0.4, 0, 0.2, 1), 
                        color 0.2s ease, 
                        background-color 0.2s ease !important;
        }

        /* 1. FOCUS / TYPING / CONTENT / AUTOFILL FLOATED LABEL STATE */
        .floating-input:focus ~ .floating-label,
        .floating-input:not(:placeholder-shown) ~ .floating-label,
        .floating-input:-webkit-autofill ~ .floating-label,
        .floating-input:autofill ~ .floating-label,
        .floating-input.is-filled ~ .floating-label {
            top: 0px !important;
            left: 16px !important;
            transform: translateY(-50%) scale(0.82) !important;
            background: #ffffff !important;
            color: #64748b !important;
            font-weight: 700 !important;
            border-radius: 4px !important;
            padding: 0 6px !important;
        }

        /* 1a. FOCUS BORDER & GLOW (ALWAYS KEEP WHITE INSET BOX SHADOW) */
        .auth-card input[type="email"]:focus,
        .auth-card input[type="password"]:focus,
        .floating-input:focus {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3.5px rgba(100, 116, 139, 0.15) !important;
            box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3.5px rgba(100, 116, 139, 0.15) !important;
            border-color: #64748b !important;
        }

        /* 2. MAINTAIN NEUTRAL GRAY BORDER FOR LOGIN CARD EMAIL WHEN VALID */
        .auth-card input[type="email"]:valid:not(.is-invalid),
        .auth-card input[type="email"].is-valid:not(.is-invalid) {
            border-color: #cbd5e1 !important;
        }
        .auth-card input[type="email"]:valid:not(.is-invalid):focus,
        .auth-card input[type="email"].is-valid:not(.is-invalid):focus {
            border-color: #64748b !important;
        }
        .auth-card input[type="email"]:valid:not(.is-invalid) ~ .floating-label,
        .auth-card input[type="email"].is-valid:not(.is-invalid) ~ .floating-label {
            color: #64748b !important;
        }

        /* 3. IF WRONG / INVALID STATE: RED BORDER & RED LABEL */
        .floating-input.is-invalid,
        .auth-card input.is-invalid {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3.5px rgba(239, 68, 68, 0.2) !important;
            box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3.5px rgba(239, 68, 68, 0.2) !important;
            border-color: #ef4444 !important;
        }
        .floating-input.is-invalid:focus,
        .auth-card input.is-invalid:focus {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3.5px rgba(239, 68, 68, 0.2) !important;
            box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3.5px rgba(239, 68, 68, 0.2) !important;
            border-color: #ef4444 !important;
        }
        .floating-input.is-invalid ~ .floating-label,
        .auth-card input.is-invalid ~ .floating-label,
        .floating-input.is-invalid:focus ~ .floating-label,
        .auth-card input.is-invalid:focus ~ .floating-label {
            color: #ef4444 !important;
        }

        /* Pop & Ring Animations for Account Suggestion & Field Feedback */
        @keyframes animPop {
            0% { transform: scale(0.92); opacity: 0.8; }
            60% { transform: scale(1.04); opacity: 1; }
            100% { transform: scale(1); }
        }
        .anim-pop {
            animation: animPop 0.32s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes animRingGreenPulse {
            0% { box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 0 rgba(34, 197, 94, 0.6); }
            50% { box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 6px rgba(34, 197, 94, 0.25); }
            100% { box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3.5px rgba(34, 197, 94, 0.15); }
        }
        .anim-ring-green {
            animation: animRingGreenPulse 0.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            border-color: #22c55e !important;
        }

        /* ==========================================================
           RESPONSIVE BRAND HEADER (Tablet to Monitor & Mobile)
           ========================================================== */
        .auth-brand {
            position: fixed !important;
            top: 24px !important;
            left: 32px !important;
            z-index: 20 !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            text-decoration: none !important;
            max-width: 100% !important;
        }
        .auth-brand img {
            width: 38px !important;
            height: 38px !important;
            object-fit: contain !important;
        }
        .auth-brand .auth-brand-text {
            font-size: 22px !important;
            font-weight: 900 !important;
            color: #22c55e !important;
            letter-spacing: -0.02em !important;
            white-space: nowrap !important;
        }
        @media (min-width: 1024px) {
            .auth-brand {
                top: 32px !important;
                left: 44px !important;
            }
            .auth-brand img {
                width: 42px !important;
                height: 42px !important;
            }
            .auth-brand .auth-brand-text {
                font-size: 24px !important;
            }
        }
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden !important;
                max-width: 100vw !important;
            }
            body.auth-body {
                overflow-x: hidden !important;
                max-width: 100vw !important;
                min-height: 100vh !important;
                min-height: 100dvh !important;
                height: auto !important;
                max-height: none !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                padding: max(60px, calc(env(safe-area-inset-top) + 48px)) 16px max(60px, calc(env(safe-area-inset-bottom) + 48px)) 16px !important;
                box-sizing: border-box !important;
            }
            .auth-brand {
                position: fixed !important;
                top: max(16px, env(safe-area-inset-top)) !important;
                left: 20px !important;
                margin: 0 !important;
                gap: 8px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                z-index: 30 !important;
            }
            .auth-brand img {
                width: 34px !important;
                height: 34px !important;
            }
            .auth-brand .auth-brand-text {
                font-size: 20px !important;
            }
            .auth-card {
                padding: 24px 20px !important;
                max-width: 410px !important;
                width: 100% !important;
                margin: auto !important;
            }
        }
    </style>
</head>

<body class="auth-body" style="background: linear-gradient(180deg, #DAF1DE 0%, #f6faf7 45%, #ffffff 100%) !important; min-height: 100vh; max-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; overflow-x: hidden; overflow-y: auto; max-width: 100vw; margin: 0; padding: 12px 16px; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif;">

    <!-- Decorative arcs clipped inside container -->
    <div style="position: absolute; inset: 0; width: 100%; height: 100%; overflow: hidden; pointer-events: none; z-index: 0;">
        <div class="auth-arc" style="position: absolute; width: 640px; height: 640px; border: 1.5px solid rgba(142, 182, 155, 0.4); border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;"></div>
    </div>

    <!-- Responsive AgriCo Header Logo -->
    <a href="{{ route('website.home') }}" class="auth-brand">
        <img 
            src="{{ asset('images/agrico-logo.png') }}" 
            alt="AgriCo Logo" 
        />
        <span class="auth-brand-text">AgriCo</span>
    </a>

    <!-- Auth Container Card (CLEAN VIBRANT LIGHT THEME) -->
    <div class="auth-card" style="position: relative; z-index: 1; width: 100%; max-width: 410px; background: #ffffff; border: 1px solid rgba(218, 241, 222, 0.9); border-top: 4px solid #22c55e; border-radius: 22px; padding: 26px 30px; box-shadow: 0 20px 50px rgba(21, 128, 61, 0.08), 0 2px 10px rgba(0, 0, 0, 0.02); box-sizing: border-box;">

        <!-- Top Icon Badge -->
        <div class="auth-icon-badge" style="width: 44px; height: 44px; background: #f0fdf4; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.12); border: 1px solid #bbf7d0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="#22c55e" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
            </svg>
        </div>

        <h1 class="auth-title" style="text-align: center; font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; letter-spacing: -0.02em;">Log In</h1>
        <p class="auth-subtitle" style="text-align: center; font-size: 0.8rem; color: #64748b; margin: 0 0 16px 0; line-height: 1.4; font-weight: 400;">Access your AgriCo user account to manage<br>soil scans, maps, and agricultural data.</p>

        <!-- Session Status -->
        @if (session('status') || session('status_message') || session('success'))
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; font-size: 0.82rem; font-weight: 600; line-height: 1.4; text-align: left; display: flex; align-items: flex-start; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#16a34a" style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                </svg>
                <span>{{ session('status') ?? session('status_message') ?? session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; font-size: 0.82rem; font-weight: 600; line-height: 1.4; text-align: left; display: flex; align-items: flex-start; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#dc2626" style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" novalidate data-turbo="false" pjax-disable autocomplete="off" style="margin: 0;">
            @csrf

            @php
                $emailErr = (string) $errors->first('email');
                $hasCredError = $errors->has('email') && str_contains($emailErr, 'credentials do not match');
                $hasThrottleError = $errors->has('email') && (str_contains(strtolower($emailErr), 'too many') || str_contains(strtolower($emailErr), 'wait'));
                $isAuthPairError = $hasCredError || $hasThrottleError;
                $emailHasError = $errors->has('email') || $isAuthPairError;
                $passwordHasError = $errors->has('password') || $isAuthPairError;
            @endphp

            <!-- Email -->
            <div class="auth-input-group" style="position: relative; z-index: 2; margin-bottom: 16px;">
                <div style="position: relative;">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $emailHasError ? '#ef4444' : '#64748b' }}" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; pointer-events: none; z-index: 3;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ $errors->has('password') ? '' : old('email') }}"
                        required
                        autocomplete="username"
                        readonly
                        onfocus="unlockCredentials()"
                        onpointerdown="unlockCredentials()"
                        placeholder=" "
                        class="floating-input {{ $emailHasError ? 'is-invalid' : '' }}"
                        style="width: 100%; height: 44px; padding: 10px 40px 10px 44px; background: #ffffff; border: 1.5px solid {{ $emailHasError ? '#ef4444' : '#cbd5e1' }}; border-radius: 12px; font-size: 0.88rem; color: #0f172a; outline: none; box-sizing: border-box; transition: border-color 0.2s ease, box-shadow 0.2s ease;"
                        oninput="clearFieldErrors(this)"
                    />
                    <label for="email" class="floating-label">Email Address</label>

                    <!-- Red circular icon with "!" (matches reference screenshot) -->
                    <div class="field-error-icon" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); display: {{ $emailHasError ? 'flex' : 'none' }}; align-items: center; justify-content: center; pointer-events: none; z-index: 3;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ef4444" style="width: 18px; height: 18px;">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                @if ($errors->has('email') && !$isAuthPairError)
                    <div class="server-error field-error-msg" style="color: #ef4444; font-size: 0.78rem; margin-top: 5px; font-weight: 600; text-align: left; padding-left: 4px; display: block;">{{ $errors->first('email') }}</div>
                @endif

                <!-- Inline Typo Suggestion Box (Mailcheck) -->
                <div id="mailcheckSuggestionBox" style="display: none; margin-top: 6px; font-size: 0.75rem; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 8px; padding: 6px 10px; align-items: center; justify-content: space-between; gap: 6px; line-height: 1.35; cursor: pointer;" onclick="acceptMailcheckSuggestion(event)">
                    <span style="cursor: pointer;">
                        Did you mean <button type="button" id="mailcheckSuggestionLink" onclick="acceptMailcheckSuggestion(event)" style="background: none; border: none; padding: 0; color: #1d4ed8; font-weight: 700; text-decoration: underline; cursor: pointer; font-size: inherit; font-family: inherit;"></button>?
                    </span>
                    <button type="button" onclick="dismissMailcheckSuggestion(event)" style="background: none; border: none; padding: 0; color: #64748b; font-size: 0.72rem; font-weight: 600; cursor: pointer; text-decoration: underline; margin-left: 6px; flex-shrink: 0;" title="Keep original typed email">Dismiss</button>
                </div>
            </div>

            <!-- Password -->
            <div class="auth-input-group" style="position: relative; z-index: 2; margin-bottom: 16px;">
                <div style="position: relative;">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $passwordHasError ? '#ef4444' : '#64748b' }}" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; pointer-events: none; z-index: 3;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        value=""
                        required
                        autocomplete="current-password"
                        readonly
                        onfocus="unlockCredentials()"
                        onpointerdown="unlockCredentials()"
                        placeholder=" "
                        class="floating-input {{ $passwordHasError ? 'is-invalid' : '' }}"
                        style="width: 100%; height: 44px; padding: 10px {{ $passwordHasError ? '66px' : '44px' }} 10px 44px; background: #ffffff; border: 1.5px solid {{ $passwordHasError ? '#ef4444' : '#cbd5e1' }}; border-radius: 12px; font-size: 0.88rem; color: #0f172a; outline: none; box-sizing: border-box; transition: border-color 0.2s ease, box-shadow 0.2s ease;"
                        oninput="clearFieldErrors(this)"
                    />
                    <label for="password" class="floating-label">Password</label>

                    <!-- Red circular icon with "!" (placed before the eye toggle button) -->
                    <div class="field-error-icon" style="position: absolute; right: 38px; top: 50%; transform: translateY(-50%); display: {{ $passwordHasError ? 'flex' : 'none' }}; align-items: center; justify-content: center; pointer-events: none; z-index: 3;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ef4444" style="width: 18px; height: 18px;">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <button type="button" class="toggle-password" onmousedown="event.preventDefault()" onclick="togglePasswordVisibility(this)" aria-label="Toggle password visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; padding: 4px; display: flex; align-items: center; z-index: 5;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 16px; height: 16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                @if ($hasCredError)
                    <div id="credentialsErrorMessage" class="server-error field-error-msg" style="color: #ef4444; font-size: 0.78rem; margin-top: 5px; font-weight: 600; text-align: left; padding-left: 4px; display: block;">These credentials do not match our user records.</div>
                @elseif ($hasThrottleError)
                    <div id="credentialsErrorMessage" class="server-error field-error-msg" style="color: #ef4444; font-size: 0.78rem; margin-top: 5px; font-weight: 600; text-align: left; padding-left: 4px; display: block;">{{ $errors->first('email') }}</div>
                @elseif ($errors->has('password'))
                    <div class="server-error field-error-msg" style="color: #ef4444; font-size: 0.78rem; margin-top: 5px; font-weight: 600; text-align: left; padding-left: 4px; display: block;">{{ $errors->first('password') }}</div>
                @endif
            </div>

            <!-- Forgot password -->
            <div class="auth-forgot-row" style="display: flex; justify-content: flex-end; margin-bottom: 12px; margin-top: 2px;">
                <a href="{{ route('password.request') }}" class="auth-forgot-link" style="font-size: 0.8rem; font-weight: 600; color: #22c55e; text-decoration: none;">Forgot password?</a>
            </div>

            <!-- Submit Button (Matches header nav design) -->
            <button type="submit" class="auth-submit-btn" style="width: 100%; height: 44px; background: #22c55e !important; color: #ffffff !important; border: none; border-radius: 12px; font-size: 0.95rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35); transition: all 0.2s ease; margin-top: 4px;" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                Log In
            </button>
        </form>



        <!-- Bottom links -->
        <div class="auth-bottom-link" style="text-align: center; font-size: 0.82rem; color: #64748b; margin-top: 10px; margin-bottom: 10px;">
            Don't have an account?
            <a href="{{ route('register') }}" style="color: #22c55e; font-weight: 700; text-decoration: none; margin-left: 4px;">Sign Up</a>
        </div>

        <div style="text-align: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; font-size: 0.80rem;">
            <a href="{{ route('website.faq') }}" style="font-weight: 600; color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: color 0.2s ease;" onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#64748b'">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Need help? FAQ</span>
            </a>
            <a href="{{ route('website.home') }}" style="font-weight: 600; color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: color 0.2s ease;" onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#64748b'">
                <span>Continue as Guest</span>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div style="text-align: center; margin-top: 12px; font-size: 0.74rem; color: #94a3b8; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <a href="{{ route('website.privacy') }}" target="_blank" style="color: #94a3b8; text-decoration: none; transition: color 0.15s ease;" onmouseover="this.style.color='#16a34a'" onmouseout="this.style.color='#94a3b8'">Privacy Policy</a>
            <span>&bull;</span>
            <a href="{{ route('website.terms') }}" target="_blank" style="color: #94a3b8; text-decoration: none; transition: color 0.15s ease;" onmouseover="this.style.color='#16a34a'" onmouseout="this.style.color='#94a3b8'">Terms of Service</a>
        </div>
    </div>

    <!-- Mailcheck.js for Login email typo checking -->
    <script src="{{ asset('js/mailcheck.min.js') }}" onerror="this.onerror=null;this.src='https://cdnjs.cloudflare.com/ajax/libs/mailcheck/1.1.2/mailcheck.min.js';"></script>

    <script>
        let mailcheckDebounceTimer = null;
        let mailcheckAcceptedOrDismissed = false;
        let mailcheckLastAcceptedEmail = '';

        function checkMailcheckSuggestion(val) {
            const suggestionBox = document.getElementById('mailcheckSuggestionBox');
            const suggestionLink = document.getElementById('mailcheckSuggestionLink');
            if (!suggestionBox || !suggestionLink) return;

            // If user already accepted this suggestion or dismissed it, stay hidden
            if (mailcheckAcceptedOrDismissed || (mailcheckLastAcceptedEmail && val.toLowerCase() === mailcheckLastAcceptedEmail.toLowerCase())) {
                suggestionBox.style.setProperty('display', 'none', 'important');
                return;
            }

            if (!val || !val.includes('@') || typeof Mailcheck === 'undefined') {
                suggestionBox.style.setProperty('display', 'none', 'important');
                return;
            }

            Mailcheck.run({
                email: val,
                domains: [
                    'gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'rocketmail.com',
                    'outlook.com', 'hotmail.com', 'live.com', 'msn.com',
                    'icloud.com', 'me.com', 'mac.com', 'protonmail.com', 'aol.com',
                    'up.edu.ph', 'gov.ph', 'da.gov.ph', 'dost.gov.ph'
                ],
                suggested: function(suggestion) {
                    if (mailcheckAcceptedOrDismissed) {
                        suggestionBox.style.setProperty('display', 'none', 'important');
                        return;
                    }
                    suggestionLink.innerText = suggestion.full;
                    suggestionBox.style.display = 'flex';
                    suggestionBox.dataset.suggestedEmail = suggestion.full;
                },
                empty: function() {
                    suggestionBox.style.setProperty('display', 'none', 'important');
                    delete suggestionBox.dataset.suggestedEmail;
                }
            });
        }

        function acceptMailcheckSuggestion(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            clearTimeout(mailcheckDebounceTimer);
            mailcheckAcceptedOrDismissed = true;

            const emailInput = document.getElementById('email');
            const suggestionBox = document.getElementById('mailcheckSuggestionBox');
            const suggestionLink = document.getElementById('mailcheckSuggestionLink');

            const suggestedEmail = (suggestionBox?.dataset?.suggestedEmail || suggestionLink?.innerText || '').trim();

            if (emailInput && suggestedEmail) {
                emailInput.value = suggestedEmail;
                mailcheckLastAcceptedEmail = suggestedEmail;
                clearFieldErrors(emailInput);
                emailInput.classList.add('is-filled');
            }

            if (suggestionBox) {
                suggestionBox.style.setProperty('display', 'none', 'important');
                delete suggestionBox.dataset.suggestedEmail;
            }

            // Move focus to password input so user can seamlessly type credentials without re-triggering email blur
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.focus();
            }
        }

        function dismissMailcheckSuggestion(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            clearTimeout(mailcheckDebounceTimer);
            mailcheckAcceptedOrDismissed = true;
            const suggestionBox = document.getElementById('mailcheckSuggestionBox');
            if (suggestionBox) {
                suggestionBox.style.setProperty('display', 'none', 'important');
                delete suggestionBox.dataset.suggestedEmail;
            }
        }

        // Clean up any previously stored account suggestion from localStorage
        try { localStorage.removeItem('agrico_saved_account'); } catch (e) {}

        function clearFieldErrors(input) {
            // Do not clear the error if the user hasn't changed/typed new text
            if (input.dataset.initialErrorVal !== undefined && input.value === input.dataset.initialErrorVal) {
                return;
            }

            const credMsg = document.getElementById('credentialsErrorMessage');
            if (credMsg && credMsg.style.display !== 'none') {
                credMsg.style.display = 'none';
                const emailInput = document.getElementById('email');
                const passwordInput = document.getElementById('password');
                [emailInput, passwordInput].forEach(inp => {
                    if (inp) {
                        inp.classList.remove('is-invalid');
                        delete inp.dataset.initialErrorVal;
                        inp.style.borderColor = '#cbd5e1';
                        if (inp.id === 'password') {
                            inp.style.paddingRight = '44px';
                        }
                        const grp = inp.closest('.auth-input-group');
                        if (grp) {
                            const errIcon = grp.querySelector('.field-error-icon');
                            if (errIcon) errIcon.style.display = 'none';
                            const icon = grp.querySelector('.input-icon');
                            if (icon) icon.setAttribute('stroke', '#64748b');
                        }
                    }
                });
            }

            if (input.classList.contains('is-invalid')) {
                input.classList.remove('is-invalid');
                delete input.dataset.initialErrorVal;
                input.style.borderColor = '#cbd5e1';
                if (input.id === 'password') {
                    input.style.paddingRight = '44px';
                }
                const group = input.closest('.auth-input-group');
                if (group) {
                    const errIcon = group.querySelector('.field-error-icon');
                    if (errIcon) errIcon.style.display = 'none';
                    const errMsg = group.querySelector('.server-error, .field-error-msg');
                    if (errMsg) errMsg.style.display = 'none';
                    const icon = group.querySelector('.input-icon');
                    if (icon) icon.setAttribute('stroke', '#64748b');
                }
            }
        }

        function togglePasswordVisibility(btn) {
            const input = btn.parentElement.querySelector('input');
            const currentValue = input.value; // capture BEFORE changing type
            const cursorPos = input.selectionStart;

            // Prevent the button click from blurring the input first —
            // a blur/refocus cycle is what often re-triggers the autofill popup.
            input.focus();

            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                `;
            } else {
                input.type = 'password';
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                `;
            }

            // Explicitly restore the value AFTER the type swap — don't trust
            // the browser to preserve it untouched during the switch.
            input.value = currentValue;
            input.setSelectionRange(cursorPos, cursorPos);
        }

        function unlockCredentials() {
            const email = document.getElementById('email');
            const pass = document.getElementById('password');
            if (email && email.hasAttribute('readonly')) email.removeAttribute('readonly');
            if (pass && pass.hasAttribute('readonly')) pass.removeAttribute('readonly');
        }

        function autofillSafetyCheck() {
            // Autofill only updates floating input visual state, never clears server errors
            checkFloatingInputs();
        }

        // Extra robust option: detect native browser autofill via the
        // :-webkit-autofill CSS pseudo-class animation trick (Chrome/Edge/Safari).
        const autofillStyle = document.createElement('style');
        autofillStyle.textContent = `
          input:-webkit-autofill {
            animation-name: onAutoFillStart;
          }
          @keyframes onAutoFillStart { from {} to {} }
        `;
        document.head.appendChild(autofillStyle);

        document.addEventListener('DOMContentLoaded', function () {
            const passwordHasError = Array.from(document.querySelectorAll('.server-error, .field-error-msg')).some(el => el.textContent.includes('Incorrect password'))
                || document.querySelector('.server-error')?.textContent.includes('Incorrect password');
            if (passwordHasError) {
                const emailField = document.getElementById('email');
                const passwordField = document.getElementById('password');
                const passwordVisibleField = document.getElementById('password-visible');

                if (emailField) { emailField.value = ''; emailField.classList.remove('is-filled'); }
                if (passwordField) { passwordField.value = ''; passwordField.classList.remove('is-filled'); }
                if (passwordVisibleField) passwordVisibleField.value = '';

                setTimeout(() => {
                    if (emailField) { emailField.value = ''; emailField.classList.remove('is-filled'); }
                    if (passwordField) { passwordField.value = ''; passwordField.classList.remove('is-filled'); }
                    if (passwordVisibleField) passwordVisibleField.value = '';
                }, 200);
            }

            // Record initial value of erroneous fields so we do NOT clear until user types new input
            document.querySelectorAll('.floating-input.is-invalid').forEach(function(input) {
                input.dataset.initialErrorVal = input.value;
            });

            const authForm = document.querySelector('form');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    if (mailcheckLastAcceptedEmail && emailInput.value.trim().toLowerCase() !== mailcheckLastAcceptedEmail.toLowerCase()) {
                        mailcheckAcceptedOrDismissed = false;
                        mailcheckLastAcceptedEmail = '';
                    }
                    clearTimeout(mailcheckDebounceTimer);
                    mailcheckDebounceTimer = setTimeout(() => {
                        checkMailcheckSuggestion(emailInput.value.trim());
                    }, 350);
                });
                emailInput.addEventListener('blur', function() {
                    checkMailcheckSuggestion(emailInput.value.trim());
                });
            }

            function checkFloatingInputs() {
                document.querySelectorAll('.floating-input').forEach(function(input) {
                    if (input.value && input.value.trim() !== '') {
                        input.classList.add('is-filled');
                    } else {
                        input.classList.remove('is-filled');
                    }
                });
            }

            document.querySelectorAll('.floating-input').forEach(function(input) {
                input.addEventListener('animationstart', function(e) {
                    if (e.animationName === 'onAutoFillStart') {
                        checkFloatingInputs();
                    }
                });
                input.addEventListener('input', function(e) {
                    clearFieldErrors(this, e);
                    checkFloatingInputs();
                });
                input.addEventListener('blur', checkFloatingInputs);
            });

            checkFloatingInputs();
        });
    </script>

    {{-- ======================================================== --}}
    {{-- SUSPENDED ACCOUNT DETAILS POPUP (MATCHING STUDENT RECORDS) --}}
    {{-- ======================================================== --}}
    @if(session('suspended_user_data'))
    @php
        $susp = session('suspended_user_data');
    @endphp
    <div id="suspensionModal" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:99999; display:flex; align-items:center; justify-content:center; padding:16px;" onclick="if(event.target === this) closeSuspensionModal()">
        <div class="modal-animated-card" style="background:#ffffff; border-radius:24px; width:100%; max-width:440px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid #f1f5f9; overflow:hidden; animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
            <div style="padding:22px 28px 12px 28px; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:40px; height:40px; border-radius:12px; background:#fee2e2; display:flex; align-items:center; justify-content:center; color:#ef4444; flex-shrink:0;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div>
                        <h3 style="font-size:16.5px; font-weight:800; color:#0f172a; margin:0; line-height:1.2;">Suspension Details</h3>
                        <p style="font-size:12px; color:#94a3b8; font-weight:500; margin:2px 0 0 0;">{{ $susp['name'] }}</p>
                    </div>
                </div>
                <x-close-button onclick="closeSuspensionModal()" ariaLabel="Close modal" title="Close modal" />
            </div>

            <div style="padding:14px 28px 20px 28px; display:flex; flex-direction:column; gap:14px;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:14px; display:flex; flex-direction:column; gap:8px;">
                    <div>
                        <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px;">Reason</span>
                        <div style="font-size:13.5px; font-weight:700; color:#0f172a;">{{ $susp['suspend_reason'] ?: 'Administrative Suspension' }}</div>
                    </div>
                    <div style="height:1px; background:#e2e8f0; margin:4px 0;"></div>
                    <div>
                        <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px;">Suspended By</span>
                        <div style="font-size:13px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:6px;">
                            <svg width="15" height="15" fill="none" stroke="#64748b" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span>{{ ($susp['suspended_by_admin_detail'] ?? null) ?: 'System (SuperAdmin)' }}</span>
                        </div>
                    </div>
                    <div style="height:1px; background:#e2e8f0; margin:4px 0;"></div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px;">Suspended Until</span>
                            <div style="font-size:13px; font-weight:700; color:#d97706;">{{ ($susp['suspended_until'] ?? null) ?: 'Manual' }}</div>
                        </div>
                        <div>
                            <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px;">Time Remaining</span>
                            <div id="suspension-countdown-display" style="font-size:13px; font-weight:700; color:#22c55e; font-family:monospace;">{{ ($susp['suspension_countdown'] ?? null) ?: 'Calculating...' }}</div>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 6px;">
                        <a href="{{ route('website.faq') }}#why-suspended" target="_blank" style="font-size: 12px; color: #16a34a; font-weight: 600; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px;">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span>Why was my account suspended? Read FAQ</span>
                        </a>
                    </div>
                </div>

                @if(!empty($susp['request_just_submitted']))
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:10px 14px; display:flex; align-items:center; gap:10px;">
                        <div style="font-size:18px;">✅</div>
                        <div>
                            <div style="font-size:12px; color:#15803d; font-weight:700;">Reactivation Request Submitted</div>
                            <div style="font-size:11px; color:#166534;">Request <strong>{{ $susp['pending_request_code'] ?? 'Submitted' }}</strong> has been sent to {{ $susp['pending_admin_id'] ?? 'Admin' }} for review.</div>
                        </div>
                    </div>
                @elseif(!empty($susp['has_pending_reactivation']))
                    <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:10px 14px; display:flex; align-items:center; gap:10px;">
                        <div style="font-size:18px;">⏳</div>
                        <div>
                            <div style="font-size:12px; color:#b45309; font-weight:700;">Reactivation Request Pending</div>
                            <div style="font-size:11px; color:#92400e;">Request <strong>{{ $susp['pending_request_code'] ?? 'Pending' }}</strong> was submitted to {{ $susp['pending_admin_id'] ?? 'Admin' }} for approval.</div>
                        </div>
                    </div>
                @endif
            </div>

            <div style="padding:14px 28px 20px 28px; display:flex; justify-content:space-between; align-items:center; gap:10px; background:#fafafa; border-top:1px solid #f1f5f9;">
                <button type="button" onclick="closeSuspensionModal()" style="padding:9px 18px; border-radius:12px; background:#f1f5f9; border:none; font-size:13px; font-weight:700; color:#475569; cursor:pointer; transition: background 0.15s ease;" onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">Close</button>
                @if(!empty($susp['has_pending_reactivation']))
                    <button type="button" disabled style="padding:9px 20px; border-radius:12px; background:#94a3b8; border:none; font-size:13px; font-weight:700; color:#fff; cursor:not-allowed; opacity:0.75;">Reactivation Pending</button>
                @else
                    <form method="POST" action="{{ route('account.request-reactivation') }}" style="margin:0;">
                        @csrf
                        <input type="hidden" name="email" value="{{ $susp['email'] }}">
                        <button type="submit" style="padding:9px 20px; border-radius:12px; background:#22c55e; border:none; font-size:13px; font-weight:700; color:#fff; cursor:pointer; box-shadow:0 4px 14px rgba(34,197,94,0.35); transition: transform 0.15s ease, background 0.15s ease;" onmouseover="this.style.background='#16a34a'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#22c55e'; this.style.transform='translateY(0)';" title="Submit reactivation request for administrative review">Request Reactivation</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <style>
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96) translateY(6px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>

    <script>
        function closeSuspensionModal() {
            const modal = document.getElementById('suspensionModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transition = 'opacity 0.2s ease';
                setTimeout(() => modal.remove(), 200);
            }
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSuspensionModal();
            }
        });

        @if(!empty($susp['suspended_until_iso']))
        (function() {
            const targetIso = "{{ $susp['suspended_until_iso'] }}";
            const targetTime = new Date(targetIso).getTime();
            const timerEl = document.getElementById('suspension-countdown-display');
            if (targetTime && timerEl) {
                function updateTimer() {
                    const diff = targetTime - Date.now();
                    if (diff <= 0) {
                        timerEl.textContent = '0d 0h 0m';
                        return;
                    }
                    const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((diff % (1000 * 60)) / 1000);
                    let str = '';
                    if (d > 0) str += d + 'd ';
                    if (h > 0 || d > 0) str += h + 'h ';
                    str += m + 'm ' + s + 's';
                    timerEl.textContent = str;
                }
                updateTimer();
                setInterval(updateTimer, 1000);
            }
        })();
        @endif
    </script>
    @endif

</body>
</html>
