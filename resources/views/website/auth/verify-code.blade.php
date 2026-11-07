<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="auth-page">

<head>
    @include('website.partials.head')
    <title>Enter Verification Code — AgriCo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        html, body {
            width: 100%;
            overflow-x: hidden;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #DAF1DE 0%, #f6faf7 45%, #ffffff 100%);
            padding: 16px 20px;
            position: relative;
        }

        /* Ambient Background Arc */
        .auth-arc {
            position: absolute;
            width: 640px;
            height: 640px;
            border: 1.5px solid rgba(142, 182, 155, 0.4);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 0;
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

        /* Card Container */
        .auth-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid rgba(218, 241, 222, 0.9);
            border-top: 4px solid #22c55e;
            border-radius: 22px;
            padding: 32px 28px;
            box-shadow: 0 20px 50px rgba(21, 128, 61, 0.08);
            text-align: center;
        }

        .icon-badge {
            width: 44px;
            height: 44px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            color: #22c55e;
        }

        .auth-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .auth-subtitle {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 24px;
            word-break: break-word;
        }

        /* 6 Digit Square Input Boxes — fluid width so they never overflow */
        .code-row {
            display: flex;
            justify-content: space-between;
            gap: clamp(4px, 1.8vw, 8px);
            margin-bottom: 24px;
        }
        .code-box {
            flex: 1 1 0;
            min-width: 0;
            aspect-ratio: 1 / 1.08;
            max-width: 48px;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            background: #ffffff;
            font-size: clamp(1rem, 4.2vw, 1.35rem);
            font-weight: 800;
            text-align: center;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
        }
        .code-box:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3.5px rgba(34, 197, 94, 0.2);
        }
        .code-box.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3.5px rgba(239, 68, 68, 0.2);
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            height: 44px;
            background: #22c55e;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
            transition: all 0.2s ease;
        }
        .submit-btn:hover {
            background: #16a34a;
        }
        .submit-btn:focus-visible,
        .code-box:focus-visible {
            outline: 2px solid #16a34a;
            outline-offset: 2px;
        }

        /* Resend & Back Links */
        .resend-row {
            margin-top: 18px;
            font-size: 0.82rem;
        }
        .resend-btn {
            background: none;
            border: none;
            font-weight: 700;
            color: #22c55e;
            cursor: pointer;
            padding: 0;
        }
        .timer-text {
            color: #94a3b8;
            display: none;
            margin-left: 4px;
        }
        .back-row {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .back-link {
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .back-link:hover {
            color: #22c55e;
        }

        /* ---------- MOBILE FIXES (Matches login/register top-left brand location) ---------- */
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
                padding: 24px 18px !important;
                border-radius: 18px !important;
                max-width: 410px !important;
                width: 100% !important;
                margin: auto !important;
            }
            .auth-title {
                font-size: 1.15rem !important;
            }
            .auth-subtitle {
                font-size: 0.8rem !important;
                margin-bottom: 20px !important;
            }
            .code-row {
                gap: 6px !important;
                margin-bottom: 20px !important;
            }
            .code-box {
                max-width: 44px !important;
                border-radius: 10px !important;
            }
            .submit-btn {
                height: 46px !important;
                font-size: 0.92rem !important;
            }
        }

        /* Very small phones (older iPhone SE, small Android) */
        @media (max-width: 340px) {
            body {
                padding: max(58px, env(safe-area-inset-top) + 44px) 12px 20px 12px !important;
            }
            .auth-brand {
                top: max(10px, env(safe-area-inset-top)) !important;
                left: 12px !important;
            }
            .auth-brand img {
                width: 28px !important;
                height: 28px !important;
            }
            .auth-brand .auth-brand-text {
                font-size: 17px !important;
            }
            .auth-card {
                padding: 20px 14px !important;
            }
            .code-row {
                gap: 4px !important;
            }
            .code-box {
                max-width: 38px !important;
                font-size: 1.05rem !important;
            }
        }
    </style>
</head>

<body class="auth-body">
    <!-- Decorative background layer with strict overflow clipping -->
    <div style="position: absolute; inset: 0; width: 100%; height: 100%; overflow: hidden; pointer-events: none; z-index: 0;">
        <div class="auth-arc"></div>
    </div>

    <!-- Responsive AgriCo Header Logo -->
    <a href="{{ route('website.home') }}" class="auth-brand">
        <img src="{{ asset('images/agrico-logo.png') }}" alt="AgriCo Logo">
        <span class="auth-brand-text">AgriCo</span>
    </a>

    <!-- Auth Card -->
    <div class="auth-card">
        <div class="icon-badge">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
        </div>

        <h1 class="auth-title">Enter Verification Code</h1>
        <p class="auth-subtitle">
            We sent a 6-digit code to<br>
            <strong style="color: #0f172a;" id="displayEmail">{{ $email }}</strong>
        </p>

        @if (session('status'))
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; border-radius: 10px; padding: 10px 14px; margin-bottom: 18px; font-size: 0.82rem; font-weight: 600; text-align: center;">
                {{ session('status') }}
            </div>
        @endif

        @error('code')
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 10px; padding: 10px 14px; margin-bottom: 18px; font-size: 0.82rem; font-weight: 600; text-align: center;">
                {{ $message }}
            </div>
        @enderror

        <form method="POST" action="{{ route('password.verify-code') }}" id="verifyForm">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="code" id="hiddenCode">

            <div class="code-row">
                @for ($i = 0; $i < 6; $i++)
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        class="code-box"
                        id="digit-{{ $i }}"
                        autocomplete="off"
                        required
                    >
                @endfor
            </div>

            <button type="submit" class="submit-btn">Verify Code</button>
        </form>

        <div class="resend-row">
            <form method="POST" action="{{ route('password.send-code') }}" id="resendForm" style="display: inline;">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <button type="submit" id="resendBtn" class="resend-btn">Resend Code</button>
            </form>
            <span id="timerText" class="timer-text"></span>
        </div>

        <div class="back-row">
            <a href="{{ route('password.request') }}" class="back-link">&larr; Change Email Address</a>
        </div>
        <div style="text-align: center; margin-top: 12px; font-size: 0.78rem;">
            <a href="{{ route('website.faq') }}#no-code" target="_blank" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#64748b'">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Didn't receive your code? Read FAQ</span>
            </a>
        </div>
    </div>

    <script>
        const inputs = Array.from(document.querySelectorAll('.code-box'));
        const hiddenCode = document.getElementById('hiddenCode');
        const form = document.getElementById('verifyForm');

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/[^0-9]/g, '');
                input.value = val ? val.slice(-1) : '';
                input.classList.remove('is-invalid');
                if (input.value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                updateCode();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    inputs[index - 1].focus();
                    inputs[index - 1].value = '';
                    updateCode();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const data = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                data.split('').forEach((char, idx) => {
                    if (inputs[idx]) inputs[idx].value = char;
                });
                updateCode();
                if (data.length === 6) {
                    inputs[5].focus();
                }
            });
        });

        function updateCode() {
            hiddenCode.value = inputs.map(i => i.value).join('');
        }

        form.addEventListener('submit', (e) => {
            updateCode();
            if (hiddenCode.value.length !== 6) {
                e.preventDefault();
                inputs.forEach(i => i.classList.add('is-invalid'));
            }
        });

        if (inputs[0]) inputs[0].focus();

        let cooldown = 60;
        const resendBtn = document.getElementById('resendBtn');
        const timerText = document.getElementById('timerText');

        function startCooldown() {
            resendBtn.disabled = true;
            resendBtn.style.color = '#94a3b8';
            resendBtn.style.cursor = 'not-allowed';
            timerText.style.display = 'inline';
            const interval = setInterval(() => {
                cooldown--;
                timerText.innerText = `(in ${cooldown}s)`;
                if (cooldown <= 0) {
                    clearInterval(interval);
                    resendBtn.disabled = false;
                    resendBtn.style.color = '#22c55e';
                    resendBtn.style.cursor = 'pointer';
                    timerText.style.display = 'none';
                    cooldown = 60;
                }
            }, 1000);
        }
        startCooldown();
    </script>
</body>
</html>
