<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="auth-page">

<head>
    @php
        $email = $email ?? request('email', '');
        $code = $code ?? request('token', request('code', ''));
    @endphp
    @include('website.partials.head')
    <title>Set New Password — AgriCo</title>
    <style>
        /* Password field & input styling matching reference */
        .password-field-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            height: 46px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .password-field-wrap:focus-within {
            border-color: #94a3b8;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.15);
        }

        .password-field-wrap.has-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        }

        .password-input {
            width: 100%;
            height: 100%;
            padding: 11px 44px 11px 46px;
            border: none;
            background: transparent;
            font-size: 0.95rem;
            color: #0f172a;
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
        }

        .password-input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }

        .masked-password-input {
            -webkit-text-security: disc !important;
        }

        .unmasked-password-input {
            -webkit-text-security: none !important;
        }

        .field-icon-left {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #64748b;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .field-btn-right {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #64748b;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: color 0.15s ease;
        }

        .field-btn-right:hover {
            color: #0f172a;
        }

        /* Requirement Pills Matching Reference Screenshot */
        .req-pill-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 10px 0 18px 0;
        }

        .req-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .req-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 11px 4px 5px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s ease;
            user-select: none;
            line-height: 1;
        }

        .req-pill-icon {
            width: 17px;
            height: 17px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .req-pill.is-met {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        .req-pill.is-met .req-pill-icon {
            background: #22c55e;
            color: #ffffff;
        }

        /* Security Level Progress Bars */
        .sec-progress-bar {
            width: 18px;
            height: 4px;
            border-radius: 9999px;
            background: #e2e8f0;
            display: inline-block;
            transition: background-color 0.2s ease;
        }

        /* Floating Toast Notification */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .auth-toast {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 320px;
            max-width: 440px;
            padding: 14px 18px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.14), 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid #ef4444;
            font-size: 0.88rem;
            color: #0f172a;
            animation: toastSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes shakeInput {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .shake {
            animation: shakeInput 0.4s ease-in-out;
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
            body {
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

<body class="auth-body" style="background: linear-gradient(180deg, #DAF1DE 0%, #f6faf7 45%, #ffffff 100%) !important; min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; overflow-x: hidden; margin: 0; padding: 16px 20px; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif;">

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Decorative background layer with strict overflow clipping -->
    <div style="position: absolute; inset: 0; width: 100%; height: 100%; overflow: hidden; pointer-events: none; z-index: 0;">
        <div class="auth-arc" style="position: absolute; width: 640px; height: 640px; border: 1.5px solid rgba(142, 182, 155, 0.4); border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;"></div>
    </div>

    <!-- Responsive AgriCo Header Logo -->
    <a href="{{ route('website.home') }}" class="auth-brand">
        <img src="{{ asset('images/agrico-logo.png') }}" alt="AgriCo Logo">
        <span class="auth-brand-text">AgriCo</span>
    </a>

    <!-- Auth Card -->
    <div style="position: relative; z-index: 1; width: 100%; max-width: 440px; background: #ffffff; border: 1px solid rgba(218, 241, 222, 0.9); border-top: 4px solid #22c55e; border-radius: 22px; padding: 32px 28px; box-shadow: 0 20px 50px rgba(21, 128, 61, 0.08); box-sizing: border-box;">

        <!-- Icon Badge -->
        <div style="width: 44px; height: 44px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="#22c55e" style="width: 22px; height: 22px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </div>

        <h1 style="text-align: center; font-size: 21.6px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">Set New Password</h1>
        <p style="text-align: center; font-size: 12.8px; color: #64748b; margin: 0 0 24px 0; line-height: 1.5;">
            Choose a strong password with at least 8 characters for <br>
            <strong style="color: #0f172a;">{{ $email }}</strong>
        </p>

        <!-- Dynamic Inline Alert Container -->
        <div id="passwordAlert" style="display: none; background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; padding: 12px 14px; margin-bottom: 18px; font-size: 0.82rem; color: #991b1b; animation: toastSlideIn 0.25s ease;">
            <div style="display: flex; align-items: flex-start; gap: 8px;">
                <svg style="width: 18px; height: 18px; color: #ef4444; flex-shrink: 0; margin-top: 1px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #b91c1c; margin-bottom: 2px;" id="passwordAlertTitle">Password requirements not met</div>
                    <div id="passwordAlertMessage" style="font-size: 0.78rem; color: #7f1d1d; line-height: 1.4;"></div>
                </div>
                <x-close-button size="sm" onclick="dismissPasswordAlert()" ariaLabel="Dismiss alert" title="Dismiss alert" />
            </div>
        </div>

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 10px; padding: 10px 14px; margin-bottom: 18px; font-size: 0.82rem; font-weight: 600; text-align: left;">
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form id="resetPasswordForm" method="POST" action="{{ route('password.reset.submit') }}" novalidate>
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="code" value="{{ $code }}">
            <input type="hidden" name="token" value="{{ $code }}">

            <!-- Security Level Indicator (Top Right of Password Field) -->
            <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 6px;">
                <div style="display: flex; align-items: center; gap: 7px; font-size: 13px; color: #64748b; font-weight: 500;">
                    <span>Security level</span>
                    <div id="securityBars" style="display: flex; align-items: center; gap: 4px;">
                        <span class="sec-progress-bar" id="sec-bar-1"></span>
                        <span class="sec-progress-bar" id="sec-bar-2"></span>
                        <span class="sec-progress-bar" id="sec-bar-3"></span>
                        <span class="sec-progress-bar" id="sec-bar-4"></span>
                        <span class="sec-progress-bar" id="sec-bar-5"></span>
                    </div>
                </div>
            </div>

            <!-- Password Field -->
            <div style="position: relative; margin-bottom: 10px;">
                <div class="password-field-wrap {{ $errors->has('password') ? 'has-error' : '' }}">
                    <!-- Lock Icon Left -->
                    <div class="field-icon-left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>

                    <input
                        id="password"
                        name="password"
                        type="text"
                        required
                        autocomplete="off"
                        data-lpignore="true"
                        data-1p-ignore="true"
                        data-form-type="other"
                        placeholder="Password"
                        class="password-input masked-password-input"
                        oninput="checkPasswordRequirements(this.value)"
                    />

                    <!-- Eye Toggle Right -->
                    <button type="button" class="field-btn-right" onclick="togglePass('password', this)" title="Show / Hide Password">
                        <svg class="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                        <svg class="eye-open-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#22c55e" style="width: 18px; height: 18px; display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Requirement Pills Matching Screenshot -->
            <div class="req-pill-container">
                <!-- Row 1: 8+ characters, Number, Uppercase letter -->
                <div class="req-pill-row">
                    <div id="pill-length" class="req-pill">
                        <span class="req-pill-icon">
                            <svg class="icon-x" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            <svg class="icon-check" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span>8+ characters</span>
                    </div>

                    <div id="pill-number" class="req-pill">
                        <span class="req-pill-icon">
                            <svg class="icon-x" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            <svg class="icon-check" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span>Number</span>
                    </div>

                    <div id="pill-upper" class="req-pill">
                        <span class="req-pill-icon">
                            <svg class="icon-x" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            <svg class="icon-check" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span>Uppercase letter</span>
                    </div>
                </div>

                <!-- Row 2: Lowercase letter, Special character -->
                <div class="req-pill-row">
                    <div id="pill-lower" class="req-pill">
                        <span class="req-pill-icon">
                            <svg class="icon-x" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            <svg class="icon-check" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span>Lowercase letter</span>
                    </div>

                    <div id="pill-special" class="req-pill">
                        <span class="req-pill-icon">
                            <svg class="icon-x" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            <svg class="icon-check" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span>Special character</span>
                    </div>
                </div>
            </div>

            <!-- Confirm Password Field (Shield Icon Left, Eye Toggle Right) -->
            <div style="position: relative; margin-bottom: 22px;">
                <div class="password-field-wrap {{ $errors->has('password_confirmation') ? 'has-error' : '' }}">
                    <!-- Shield Icon Left -->
                    <div class="field-icon-left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </div>

                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="text"
                        required
                        autocomplete="off"
                        data-lpignore="true"
                        data-1p-ignore="true"
                        data-form-type="other"
                        placeholder="Confirm Password"
                        class="password-input masked-password-input"
                    />

                    <!-- Eye Toggle Right -->
                    <button type="button" class="field-btn-right" onclick="togglePass('password_confirmation', this)" title="Show / Hide Confirm Password">
                        <svg class="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                        <svg class="eye-open-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#22c55e" style="width: 18px; height: 18px; display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit Button (Matches header nav design) -->
            <button type="submit" style="width: 100%; height: 44px; background: #22c55e; color: #ffffff; border: none; border-radius: 12px; font-size: 0.95rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35); transition: all 0.2s ease;" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                Update Password
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
            <a href="{{ route('login') }}" style="font-size: 12.8px; font-weight: 700; color: #22c55e; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                ← Back to Sign In
            </a>
        </div>
    </div>

    <script>
        function togglePass(id, btn) {
            const input = document.getElementById(id);
            if (!input) return;
            const slashIcon = btn.querySelector('.eye-slash-icon');
            const openIcon = btn.querySelector('.eye-open-icon');

            if (input.classList.contains('masked-password-input') || input.type === 'password') {
                input.classList.remove('masked-password-input');
                input.classList.add('unmasked-password-input');
                input.type = 'text';
                if (slashIcon) slashIcon.style.display = 'none';
                if (openIcon) openIcon.style.display = 'block';
            } else {
                input.classList.remove('unmasked-password-input');
                input.classList.add('masked-password-input');
                if (slashIcon) slashIcon.style.display = 'block';
                if (openIcon) openIcon.style.display = 'none';
            }
        }

        function checkPasswordRequirements(val) {
            const reqs = {
                length: val.length >= 8,
                number: /[0-9]/.test(val),
                upper: /[A-Z]/.test(val),
                lower: /[a-z]/.test(val),
                special: /[^A-Za-z0-9]/.test(val),
            };

            let score = 0;

            const updatePill = (id, isMet) => {
                const el = document.getElementById(id);
                if (!el) return;
                const iconX = el.querySelector('.icon-x');
                const iconCheck = el.querySelector('.icon-check');

                if (isMet) {
                    el.classList.add('is-met');
                    if (iconX) iconX.style.display = 'none';
                    if (iconCheck) iconCheck.style.display = 'block';
                    score++;
                } else {
                    el.classList.remove('is-met');
                    if (iconX) iconX.style.display = 'block';
                    if (iconCheck) iconCheck.style.display = 'none';
                }
            };

            updatePill('pill-length', reqs.length);
            updatePill('pill-number', reqs.number);
            updatePill('pill-upper', reqs.upper);
            updatePill('pill-lower', reqs.lower);
            updatePill('pill-special', reqs.special);

            // Update Security level bars (1-5)
            // Colors: 1=red, 2=orange, 3=amber, 4=lime (#84cc16 matching screenshot), 5=emerald (#22c55e)
            const colors = {
                1: '#ef4444',
                2: '#f97316',
                3: '#eab308',
                4: '#84cc16',
                5: '#22c55e',
            };
            const activeColor = colors[score] || '#e2e8f0';

            for (let i = 1; i <= 5; i++) {
                const bar = document.getElementById('sec-bar-' + i);
                if (bar) {
                    bar.style.backgroundColor = (i <= score && score > 0) ? activeColor : '#e2e8f0';
                }
            }

            // If alert is visible, live update or dismiss it
            const alertBox = document.getElementById('passwordAlert');
            if (alertBox && alertBox.style.display !== 'none') {
                const missing = [];
                if (!reqs.length) missing.push('8+ characters');
                if (!reqs.number) missing.push('Number');
                if (!reqs.upper) missing.push('Uppercase letter');
                if (!reqs.lower) missing.push('Lowercase letter');
                if (!reqs.special) missing.push('Special character');

                if (missing.length === 0) {
                    dismissPasswordAlert();
                    document.querySelectorAll('.password-field-wrap').forEach(el => el.classList.remove('has-error'));
                } else {
                    document.getElementById('passwordAlertMessage').innerText = 'Password does not meet all security requirements. Missing: ' + missing.join(', ') + '.';
                }
            }
        }

        function showToast(message, type = 'error') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'auth-toast';
            if (type === 'success') {
                toast.style.borderLeftColor = '#22c55e';
            }

            const iconHtml = type === 'success' 
                ? `<svg style="width: 20px; height: 20px; color: #22c55e; flex-shrink: 0; margin-top: 1px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`
                : `<svg style="width: 20px; height: 20px; color: #ef4444; flex-shrink: 0; margin-top: 1px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;

            toast.innerHTML = `
                ${iconHtml}
                <div style="flex: 1;">
                    <div style="font-weight: 700; font-size: 0.86rem; color: #0f172a; margin-bottom: 2px;">
                        ${type === 'success' ? 'Success' : 'Password Security Warning'}
                    </div>
                    <div style="font-size: 0.8rem; color: #475569; line-height: 1.4;">${message}</div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="agrico-close-btn agrico-close-btn--sm" aria-label="Dismiss message" title="Dismiss"><svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-15px) scale(0.95)';
                setTimeout(() => toast.remove(), 300);
            }, 6000);
        }

        function showInlineError(msg) {
            const alertBox = document.getElementById('passwordAlert');
            const alertMsg = document.getElementById('passwordAlertMessage');
            if (alertBox && alertMsg) {
                alertMsg.innerText = msg;
                alertBox.style.display = 'block';
            }
        }

        function dismissPasswordAlert() {
            const alertBox = document.getElementById('passwordAlert');
            if (alertBox) {
                alertBox.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('resetPasswordForm');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                const passInput = document.getElementById('password');
                const confirmInput = document.getElementById('password_confirmation');
                const passVal = passInput ? passInput.value : '';
                const confirmVal = confirmInput ? confirmInput.value : '';

                const missing = [];
                if (passVal.length < 8) missing.push('8+ characters');
                if (!/[0-9]/.test(passVal)) missing.push('Number');
                if (!/[A-Z]/.test(passVal)) missing.push('Uppercase letter');
                if (!/[a-z]/.test(passVal)) missing.push('Lowercase letter');
                if (!/[^A-Za-z0-9]/.test(passVal)) missing.push('Special character');

                if (missing.length > 0) {
                    e.preventDefault();
                    e.stopPropagation();

                    const msg = 'Password does not meet all security requirements. Missing: ' + missing.join(', ') + '.';
                    showInlineError(msg);
                    showToast(msg, 'error');

                    passInput.focus();
                    const wrap = passInput.closest('.password-field-wrap');
                    if (wrap) {
                        wrap.classList.add('has-error', 'shake');
                        setTimeout(() => wrap.classList.remove('shake'), 400);
                    }
                    return false;
                }

                if (passVal !== confirmVal) {
                    e.preventDefault();
                    e.stopPropagation();

                    const msg = 'Password confirmation does not match.';
                    showInlineError(msg);
                    showToast(msg, 'error');

                    confirmInput.focus();
                    const wrap = confirmInput.closest('.password-field-wrap');
                    if (wrap) {
                        wrap.classList.add('has-error', 'shake');
                        setTimeout(() => wrap.classList.remove('shake'), 400);
                    }
                    return false;
                }
            });
        });
    </script>
</body>
</html>
