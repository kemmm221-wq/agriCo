<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="auth-page">

<head>
    @include('website.partials.head')
    @php
        $isAdmin    = Auth::guard('admin')->check() || (Auth::user() && in_array(strtolower(Auth::user()->role ?? ''), ['admin','superadmin']));
        $loginRoute = route('login');
        $loginText  = 'Back to Sign In';
        $titleText  = 'Forgot Password';
        $subTitleText = 'Enter your email address and we will send you<br>a 6-digit verification code to reset your password.';
        $portal     = $portal ?? 'web';
    @endphp
    <title>{{ $titleText }} — AgriCo</title>
    <style>
        /* Override Chrome & Browser Autofill Gray/Blue Background */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            -webkit-text-fill-color: #0f172a !important;
            caret-color: #0f172a !important;
            transition: background-color 5000s ease-in-out 0s !important;
            animation-name: onAutoFillStart;
        }

        @keyframes onAutoFillStart {
            from { opacity: 1; }
            to { opacity: 1; }
        }

        /* Animated Floating Label & Color State Styling */
        .floating-input {
            border-radius: 12px !important;
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

        /* 1a. FOCUS BORDER & GLOW */
        .floating-input:focus {
            border-color: #64748b !important;
            box-shadow: 0 0 0 3.5px rgba(100, 116, 139, 0.15) !important;
        }

        /* 2. IF CORRECT / VALID STATE: ALL GREEN BORDER, RING & LABEL */
        .floating-input.is-valid {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 3.5px rgba(34, 197, 94, 0.25) !important;
        }
        .floating-input.is-valid:focus {
            border-color: #16a34a !important;
            box-shadow: 0 0 0 3.5px rgba(34, 197, 94, 0.3) !important;
        }
        .floating-input.is-valid ~ .floating-label,
        .floating-input.is-valid:not(:placeholder-shown) ~ .floating-label,
        .floating-input.is-valid.is-filled ~ .floating-label,
        .floating-input.is-valid:focus ~ .floating-label {
            color: #16a34a !important;
            font-weight: 700 !important;
        }
        .auth-input-group:has(.floating-input.is-valid) .input-icon {
            stroke: #22c55e !important;
        }

        @keyframes iconPop {
            0% {
                transform: scale(0.2);
                opacity: 0;
            }
            60% {
                transform: scale(1.25);
                opacity: 1;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        @keyframes badgePing {
            0% {
                transform: scale(0.85);
                opacity: 0.85;
            }
            60% {
                transform: scale(1.9);
                opacity: 0.25;
            }
            100% {
                transform: scale(2.4);
                opacity: 0;
            }
        }

        .email-icon-valid {
            animation: iconPop 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            transform-origin: center center;
        }

        .email-icon-invalid {
            animation: iconPop 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            transform-origin: center center;
        }

        .email-ping-wave {
            position: absolute;
            width: 18px;
            height: 18px;
            border-radius: 9999px;
            pointer-events: none;
            animation: badgePing 0.65s cubic-bezier(0, 0, 0.2, 1) forwards;
            z-index: 1;
        }
        .email-ping-wave.ping-red {
            background: rgba(239, 68, 68, 0.55);
        }
        .email-ping-wave.ping-green {
            background: rgba(34, 197, 94, 0.55);
        }

        /* 3. IF WRONG / INVALID STATE: RED BORDER & RED LABEL */
        .floating-input.is-invalid {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3.5px rgba(239, 68, 68, 0.2) !important;
        }
        .floating-input.is-invalid:focus {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3.5px rgba(239, 68, 68, 0.25) !important;
        }
        .floating-input.is-invalid ~ .floating-label,
        .floating-input.is-invalid:not(:placeholder-shown) ~ .floating-label,
        .floating-input.is-invalid.is-filled ~ .floating-label,
        .floating-input.is-invalid:focus ~ .floating-label {
            color: #ef4444 !important;
            font-weight: 700 !important;
        }
        .auth-input-group:has(.floating-input.is-invalid) .input-icon {
            stroke: #ef4444 !important;
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

<body class="auth-body" style="background: linear-gradient(180deg, #eefbe7 0%, #f0fdf4 45%, #ffffff 100%) !important; min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; overflow-x: hidden; overflow-y: auto; max-width: 100vw; margin: 0; padding: 16px 20px; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif;">

    <!-- Decorative background layer with strict overflow clipping -->
    <div style="position: absolute; inset: 0; width: 100%; height: 100%; overflow: hidden; pointer-events: none; z-index: 0;">
        <div class="auth-arc" style="position: absolute; width: 640px; height: 640px; border: 1.5px solid rgba(187, 247, 208, 0.6); border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;"></div>
    </div>

    <!-- Brand Header -->
    <a href="{{ $isAdmin ? route('dashboard') : route('website.home') }}" class="auth-brand">
        <img src="{{ asset('images/agrico-logo.png') }}" alt="AgriCo Logo">
        <span class="auth-brand-text">AgriCo</span>
    </a>

    <!-- Auth Card -->
    <div class="auth-card" style="position: relative; z-index: 1; width: 100%; max-width: 440px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(28px); -webkit-backdrop-filter: blur(28px); border: 1px solid rgba(255, 255, 255, 0.95); border-top: 4px solid #22c55e; border-radius: 28px; padding: 38px 38px 44px 38px; box-shadow: 0 20px 50px rgba(21, 128, 61, 0.08), 0 2px 10px rgba(0, 0, 0, 0.02); box-sizing: border-box;">

        <!-- Top Icon Badge -->
        <div class="auth-icon-badge" style="width: 52px; height: 52px; background: #f0fdf4; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 22px auto; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.12); border: 1px solid #bbf7d0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="#22c55e" style="width: 24px; height: 24px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
            </svg>
        </div>

        <h1 class="auth-title" style="text-align: center; font-size: 1.45rem; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; letter-spacing: -0.02em;">{{ $titleText }}</h1>
        <p class="auth-subtitle" style="text-align: center; font-size: 0.88rem; color: #64748b; margin: 0 0 26px 0; line-height: 1.5; font-weight: 400;">{!! $subTitleText !!}</p>

        <!-- Session Status -->
        @if (session('status'))
            <div class="auth-success" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #22c55e; border-radius: 12px; padding: 12px 14px; margin-bottom: 18px; font-size: 0.85rem; font-weight: 600;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.send-code') }}" novalidate style="margin: 0;">
            @csrf
            <input type="hidden" name="portal" value="{{ $portal ?? 'web' }}">
            <!-- Email Input -->
            <div class="auth-input-group" style="position: relative; z-index: 2; margin-bottom: 20px;">
                <div style="position: relative;">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $errors->has('email') ? '#ef4444' : '#64748b' }}" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; pointer-events: none; z-index: 3;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username email"                        placeholder=" "
                        class="floating-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                        style="width: 100%; height: 44px; padding: 10px 40px 10px 44px; background: #ffffff; border: 1.5px solid {{ $errors->has('email') ? '#ef4444' : '#cbd5e1' }}; border-radius: 12px; font-size: 0.88rem; color: #0f172a; outline: none; box-sizing: border-box; transition: border-color 0.2s ease, box-shadow 0.2s ease;"
                        onblur="handleEmailBlur(this)"
                        oninput="validateLiveEmail(this, 'forgot')"
                    />
                    <label for="email" class="floating-label" style="{{ $errors->has('email') ? 'color: #ef4444 !important; font-weight: 700;' : '' }}">Email Address</label>

                    <!-- Status Icon (Right side) -->
                    <div class="email-status-icon" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); display: {{ $errors->has('email') ? 'flex' : 'none' }}; align-items: center; justify-content: center; pointer-events: none; z-index: 3;">
                        <svg class="email-icon-valid" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#22c55e" style="width: 18px; height: 18px; display: none;">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                        </svg>
                        <svg class="email-icon-invalid" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ef4444" style="width: 18px; height: 18px; display: {{ $errors->has('email') ? 'block' : 'none' }};">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Inline Typo Suggestion Box (Mailcheck) -->
                <div id="mailcheckSuggestionBox" style="display: none; margin-top: 5px; font-size: 0.78rem; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 8px; padding: 6px 10px; align-items: center; gap: 6px; line-height: 1.4;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#3b82f6" style="width: 15px; height: 15px; flex-shrink: 0;">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
                    </svg>
                    <span>
                        Did you mean <button type="button" id="mailcheckSuggestionLink" onclick="acceptMailcheckSuggestion()" style="background: none; border: none; padding: 0; color: #1d4ed8; font-weight: 700; text-decoration: underline; cursor: pointer; font-size: inherit; font-family: inherit;"></button>?
                    </span>
                </div>

                <div class="email-subtext" style="font-size: 0.78rem; margin-top: 4px; font-weight: 600; text-align: left; padding-left: 4px; display: none;"></div>

                @error('email')
                    <div style="color: #dc2626; font-size: 0.78rem; margin-top: 5px; font-weight: 600; text-align: left; padding-left: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Submit Button (Matches + Add Munsell Color design) -->
            <button type="submit" class="auth-submit-btn" style="width: 100%; height: 44px; background: #22c55e !important; color: #ffffff !important; border: none; border-radius: 12px; font-size: 0.95rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35); transition: all 0.2s ease;" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                Send 6-Digit Code
            </button>
        </form>

        <div style="text-align: center; margin-top: 22px; padding-top: 18px; border-top: 1px solid rgba(226,232,240,0.6);">
            <a href="{{ $loginRoute }}" style="font-size: 0.85rem; font-weight: 700; color: #22c55e; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>{{ $loginText }}</span>
            </a>
        </div>
        <div style="text-align: center; margin-top: 12px; font-size: 0.80rem;">
            <a href="{{ route('website.faq') }}#forgot-password" target="_blank" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#64748b'">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Need help with password reset? Read FAQ</span>
            </a>
        </div>
    </div>

    <!-- Mailcheck.js for Forgot Password email typo checking -->
    <script src="{{ asset('js/mailcheck.min.js') }}" onerror="this.onerror=null;this.src='https://cdnjs.cloudflare.com/ajax/libs/mailcheck/1.1.2/mailcheck.min.js';"></script>

    <script>
        // Mailcheck.js domain extension configuration for Forgot Password screen
        const extendedMailcheckDomains = [
            'gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'rocketmail.com',
            'outlook.com', 'hotmail.com', 'live.com', 'msn.com',
            'icloud.com', 'me.com', 'mac.com',
            'protonmail.com', 'proton.me',
            'aol.com', 'aim.com',
            'zoho.com', 'mail.com', 'gmx.com', 'web.de', 'yandex.com',
            'comcast.net', 'sbcglobal.net', 'verizon.net', 'att.net', 'cox.net',
            'bellsouth.net', 'charter.net', 'shaw.ca', 'earthlink.net', 'optonline.net',
            // Philippine-specific domains
            'up.edu.ph', 'gov.ph', 'da.gov.ph', 'dost.gov.ph', 'pcaarrd.dost.gov.ph',
            'clsu.edu.ph', 'dlsu.edu.ph', 'ateneo.edu.ph', 'ust.edu.ph', 'pup.edu.ph',
            'com.ph', 'net.ph', 'org.ph'
        ];

        const extendedMailcheckSecondLevel = [
            'yahoo', 'hotmail', 'mail', 'live', 'outlook', 'gmx',
            'proton', 'protonmail', 'up', 'gov', 'da', 'dost', 'pcaarrd',
            'dlsu', 'ateneo', 'ust', 'pup', 'clsu', 'edu', 'com', 'org'
        ];

        const extendedMailcheckTopLevel = [
            'com', 'net', 'org', 'edu', 'gov', 'ph', 'edu.ph', 'gov.ph', 'com.ph', 'org.ph', 'net.ph', 'me'
        ];

        let mailcheckState = {
            currentSuggestion: null,
            status: 'idle', // 'idle' | 'suggested' | 'accepted' | 'clean'
            lastCheckedValue: '',
            suggestedForValue: ''
        };

        function triggerStatusIcon(iconBox, targetIcon, otherIcon, isError = false) {
            if (otherIcon) otherIcon.style.display = 'none';
            if (!targetIcon) return;
            if (iconBox) iconBox.style.display = 'flex';
            targetIcon.style.display = 'block';
            targetIcon.style.animation = 'none';
            void targetIcon.offsetHeight; // force reflow for smooth popping replay
            targetIcon.style.animation = 'iconPop 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards';

            if (iconBox) {
                const oldPing = iconBox.querySelector('.email-ping-wave');
                if (oldPing) oldPing.remove();
                const ping = document.createElement('span');
                ping.className = 'email-ping-wave ' + (isError ? 'ping-red' : 'ping-green');
                iconBox.insertBefore(ping, targetIcon);
                setTimeout(() => ping.remove(), 700);
            }
        }

        function showEmailConfirmedState(inputEl, iconBox, validIcon, invalidIcon, subtext) {
            inputEl.classList.remove('is-invalid');
            inputEl.classList.add('is-valid');
            inputEl.style.borderColor = '#22c55e';
            inputEl.style.boxShadow = '0 0 0 3.5px rgba(34, 197, 94, 0.25)';
            triggerStatusIcon(iconBox, validIcon, invalidIcon, false);
            if (subtext) {
                subtext.style.display = 'block';
                subtext.style.color = '#16a34a';
                subtext.innerText = 'Email confirmed';
            }
            const parent = inputEl.closest('.auth-input-group');
            const leftIcon = parent ? parent.querySelector('.input-icon') : null;
            if (leftIcon) leftIcon.setAttribute('stroke', '#22c55e');
            const label = parent ? parent.querySelector('.floating-label') : null;
            if (label) {
                label.style.setProperty('color', '#16a34a', 'important');
                label.style.setProperty('font-weight', '700', 'important');
            }
        }

        function hideEmailConfirmedState(inputEl, iconBox, validIcon, subtext) {
            inputEl.classList.remove('is-valid');
            inputEl.style.borderColor = '';
            inputEl.style.boxShadow = '';
            if (validIcon) validIcon.style.display = 'none';
            if (iconBox) {
                const ping = iconBox.querySelector('.email-ping-wave');
                if (ping) ping.remove();
            }
            if (subtext && subtext.innerText === 'Email confirmed') {
                subtext.style.display = 'none';
                subtext.innerText = '';
            }
            const parent = inputEl.closest('.auth-input-group');
            const leftIcon = parent ? parent.querySelector('.input-icon') : null;
            if (leftIcon && !inputEl.classList.contains('is-invalid')) {
                leftIcon.setAttribute('stroke', '#64748b');
            }
            const label = parent ? parent.querySelector('.floating-label') : null;
            if (label && !inputEl.classList.contains('is-invalid')) {
                label.style.removeProperty('color');
                label.style.removeProperty('font-weight');
            }
        }

        let emailCheckTimeout = null;

        function validateLiveEmail(inputEl, mode = 'forgot') {
            const val = inputEl.value.trim();
            const parent = inputEl.closest('.auth-input-group');
            if (!parent) return;

            const iconBox = parent.querySelector('.email-status-icon');
            const validIcon = parent.querySelector('.email-icon-valid');
            const invalidIcon = parent.querySelector('.email-icon-invalid');
            const subtext = parent.querySelector('.email-subtext');
            const suggestionBox = document.getElementById('mailcheckSuggestionBox');

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (emailCheckTimeout) clearTimeout(emailCheckTimeout);

            // While typing, reset validation state
            inputEl.classList.remove('is-valid', 'is-invalid');
            inputEl.style.borderColor = '';
            inputEl.style.boxShadow = '';
            const leftIcon = parent.querySelector('.input-icon');
            if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
            const label = parent.querySelector('.floating-label');
            if (label) {
                label.style.removeProperty('color');
                label.style.removeProperty('font-weight');
            }

            // If user modified text after a suggestion was shown, hide the suggestion box
            if (suggestionBox && suggestionBox.style.display !== 'none' && val !== mailcheckState.suggestedForValue) {
                suggestionBox.style.display = 'none';
                mailcheckState.status = 'idle';
                mailcheckState.currentSuggestion = null;
            }

            if (val === '') {
                if (iconBox) {
                    iconBox.style.display = 'none';
                    const ping = iconBox.querySelector('.email-ping-wave');
                    if (ping) ping.remove();
                }
                if (validIcon) validIcon.style.display = 'none';
                if (invalidIcon) invalidIcon.style.display = 'none';
                if (subtext) {
                    subtext.style.display = 'none';
                    subtext.innerText = '';
                }
                if (suggestionBox) suggestionBox.style.display = 'none';
                mailcheckState.status = 'idle';
                mailcheckState.currentSuggestion = null;
                return;
            }

            // 1. Invalid email format -> Immediately show red border, (!) icon with pop-ping, and error subtext
            if (!emailRegex.test(val)) {
                inputEl.classList.remove('is-valid');
                inputEl.classList.add('is-invalid');
                inputEl.style.borderColor = '#ef4444';
                inputEl.style.boxShadow = '0 0 0 3.5px rgba(239, 68, 68, 0.2)';
                triggerStatusIcon(iconBox, invalidIcon, validIcon, true);
                if (subtext) {
                    subtext.style.display = 'block';
                    subtext.style.color = '#ef4444';
                    subtext.innerText = 'Please enter a valid email address';
                }
                if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                if (label) {
                    label.style.setProperty('color', '#ef4444', 'important');
                    label.style.setProperty('font-weight', '700', 'important');
                }
                return;
            }

            // 2. Format is valid -> Hide status while debounced backend check is in flight
            if (iconBox) iconBox.style.display = 'none';
            if (subtext) subtext.style.display = 'none';

            // 3. Debounced AJAX check against database
            emailCheckTimeout = setTimeout(function() {
                fetch('/auth/check-email?email=' + encodeURIComponent(val))
                    .then(res => res.json())
                    .then(data => {
                        if (inputEl.value.trim() !== val) return;

                        if (data.exists) {
                            showEmailConfirmedState(inputEl, iconBox, validIcon, invalidIcon, subtext);
                        } else {
                            inputEl.classList.remove('is-valid');
                            inputEl.classList.add('is-invalid');
                            inputEl.style.borderColor = '#ef4444';
                            inputEl.style.boxShadow = '0 0 0 3.5px rgba(239, 68, 68, 0.2)';
                            if (iconBox) iconBox.style.display = 'flex';
                            if (validIcon) validIcon.style.display = 'none';
                            if (invalidIcon) invalidIcon.style.display = 'block';
                            if (subtext) {
                                subtext.style.display = 'block';
                                subtext.style.color = '#ef4444';
                                subtext.innerText = 'This email address is not registered.';
                            }
                            if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                            if (label) {
                                label.style.color = '#dc2626';
                                label.style.fontWeight = '700';
                            }
                        }
                    })
                    .catch(() => {
                        showEmailConfirmedState(inputEl, iconBox, validIcon, invalidIcon, subtext);
                    });
            }, 250);
        }

        function handleEmailBlur(inputEl) {
            const val = inputEl.value.trim();
            const parent = inputEl.closest('.auth-input-group');
            const iconBox = parent ? parent.querySelector('.email-status-icon') : null;
            const validIcon = parent ? parent.querySelector('.email-icon-valid') : null;
            const subtext = parent ? parent.querySelector('.email-subtext') : null;
            const suggestionBox = document.getElementById('mailcheckSuggestionBox');
            const suggestionLink = document.getElementById('mailcheckSuggestionLink');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (val === '' || !emailRegex.test(val)) {
                if (suggestionBox) suggestionBox.style.display = 'none';
                mailcheckState.status = 'idle';
                mailcheckState.currentSuggestion = null;
                return;
            }

            if (mailcheckState.status === 'accepted' && mailcheckState.lastCheckedValue === val) {
                return;
            }

            if (typeof Mailcheck === 'undefined') {
                mailcheckState.status = 'clean';
                mailcheckState.lastCheckedValue = val;
                validateLiveEmail(inputEl, 'forgot');
                return;
            }

            Mailcheck.run({
                email: val,
                domains: extendedMailcheckDomains,
                secondLevelDomains: extendedMailcheckSecondLevel,
                topLevelDomains: extendedMailcheckTopLevel,
                suggested: function(suggestion) {
                    mailcheckState.currentSuggestion = suggestion;
                    mailcheckState.status = 'suggested';
                    mailcheckState.lastCheckedValue = val;
                    mailcheckState.suggestedForValue = val;

                    // Hide valid checkmark while suggestion is pending so user is alerted to the suggestion
                    hideEmailConfirmedState(inputEl, iconBox, validIcon, subtext);

                    if (suggestionBox && suggestionLink) {
                        suggestionLink.innerText = suggestion.full;
                        suggestionBox.style.display = 'flex';
                    }
                },
                empty: function() {
                    mailcheckState.currentSuggestion = null;
                    mailcheckState.status = 'clean';
                    mailcheckState.lastCheckedValue = val;

                    if (suggestionBox) suggestionBox.style.display = 'none';
                    validateLiveEmail(inputEl, 'forgot');
                }
            });
        }

        function acceptMailcheckSuggestion() {
            const inputEl = document.getElementById('email');
            const suggestionBox = document.getElementById('mailcheckSuggestionBox');
            const linkEl = document.getElementById('mailcheckSuggestionLink');

            const acceptedEmail = mailcheckState?.currentSuggestion?.full || linkEl?.innerText?.trim();
            if (inputEl && acceptedEmail) {
                inputEl.value = acceptedEmail;
                mailcheckState.status = 'accepted';
                mailcheckState.lastCheckedValue = acceptedEmail;
                mailcheckState.currentSuggestion = null;
                inputEl.classList.add('is-filled');
            }

            if (suggestionBox) {
                suggestionBox.style.setProperty('display', 'none', 'important');
            }

            if (inputEl) {
                validateLiveEmail(inputEl, 'forgot');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
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
                        input.classList.add('is-filled');
                    }
                });
                input.addEventListener('input', checkFloatingInputs);
                input.addEventListener('change', checkFloatingInputs);
                input.addEventListener('blur', checkFloatingInputs);
            });

            checkFloatingInputs();
            setTimeout(checkFloatingInputs, 100);
            setTimeout(checkFloatingInputs, 500);
        });
    </script>
</body>
</html>
