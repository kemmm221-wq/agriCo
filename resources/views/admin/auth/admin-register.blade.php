<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="auth-page">

<head>
    @include('admin.partials.head')
    <title>Admin Registration — AgriCo</title>
    <style>
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
    <a href="{{ route('dashboard') }}" class="auth-brand" style="position: fixed; top: 28px; left: 32px; z-index: 10; display: flex; align-items: center; gap: 10px; text-decoration: none;">
        <img src="{{ asset('images/agrico-logo.png') }}" style="width: 44px; height: 44px; object-fit: contain;" alt="AgriCo">
        <span class="auth-brand-text" style="font-size: 22px; font-weight: 900; color: #22c55e; letter-spacing: -0.02em;">AgriCo</span>
        <span class="admin-pill">ADMIN</span>
    </a>

    <!-- Auth Card -->
    <div class="auth-card" style="position: relative; z-index: 1; width: 100%; max-width: 440px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(28px); -webkit-backdrop-filter: blur(28px); border: 1px solid rgba(255, 255, 255, 0.95); border-top: 4px solid #22c55e; border-radius: 28px; padding: 38px 38px 44px 38px; box-shadow: 0 20px 50px rgba(21, 128, 61, 0.08), 0 2px 10px rgba(0, 0, 0, 0.02); box-sizing: border-box;">

        <!-- Top Icon Badge -->
        <div class="auth-icon-badge" style="width: 52px; height: 52px; background: #f0fdf4; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 22px auto; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.12); border: 1px solid #bbf7d0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="#22c55e" style="width: 24px; height: 24px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
            </svg>
        </div>

        <h1 class="auth-title" style="text-align: center; font-size: 1.45rem; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; letter-spacing: -0.02em;">Create Admin Account</h1>
        <p class="auth-subtitle" style="text-align: center; font-size: 0.88rem; color: #64748b; margin: 0 0 26px 0; line-height: 1.5; font-weight: 400;">Register a new administrator account with full privileges<br>for AgriCo system operations.</p>

        <!-- Errors -->
        @if ($errors->any())
            <div class="auth-error" style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; border-radius: 12px; padding: 12px 14px; margin-bottom: 18px; font-size: 0.85rem;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.register.store') }}" data-turbo="false" pjax-disable style="margin: 0;">
            @csrf

            <!-- Name -->
            <div class="auth-input-group" style="position: relative; margin-bottom: 14px;">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; pointer-events: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Full Administrator Name"
                    style="width: 100%; padding: 14px 44px 14px 46px; background: #f1f5f9; border: 1.5px solid transparent; border-radius: 14px; font-size: 0.92rem; color: #0f172a; outline: none; box-sizing: border-box; transition: all 0.25s ease;"
                />
            </div>

            <!-- Email -->
            <div class="auth-input-group" style="position: relative; margin-bottom: 14px;">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; pointer-events: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="Admin Email Address"
                    style="width: 100%; padding: 14px 44px 14px 46px; background: #f1f5f9; border: 1.5px solid transparent; border-radius: 14px; font-size: 0.92rem; color: #0f172a; outline: none; box-sizing: border-box; transition: all 0.25s ease;"
                />
            </div>

            <!-- Password -->
            <div class="auth-input-group" style="position: relative; margin-bottom: 14px;">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; pointer-events: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Admin Password"
                    style="width: 100%; padding: 14px 44px 14px 46px; background: #f1f5f9; border: 1.5px solid transparent; border-radius: 14px; font-size: 0.92rem; color: #0f172a; outline: none; box-sizing: border-box; transition: all 0.25s ease;"
                />
                <button type="button" class="toggle-password" onmousedown="event.preventDefault()" onclick="togglePasswordVisibility(this)" aria-label="Toggle password visibility" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; padding: 4px; display: flex; align-items: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>

            <!-- Confirm Password -->
            <div class="auth-input-group" style="position: relative; margin-bottom: 20px;">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; pointer-events: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm Admin Password"
                    style="width: 100%; padding: 14px 44px 14px 46px; background: #f1f5f9; border: 1.5px solid transparent; border-radius: 14px; font-size: 0.92rem; color: #0f172a; outline: none; box-sizing: border-box; transition: all 0.25s ease;"
                />
                <button type="button" class="toggle-password" onmousedown="event.preventDefault()" onclick="togglePasswordVisibility(this)" aria-label="Toggle password visibility" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; padding: 4px; display: flex; align-items: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                </button>
            </div>

            <!-- Submit Button (SYSTEM GREEN THEME) -->
            <button type="submit" class="auth-submit-btn" style="width: 100%; height: 50px; background: #22c55e; color: #ffffff; border: none; border-radius: 14px; font-size: 0.95rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35); transition: all 0.25s ease;" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                Register Admin Account
            </button>
        </form>

        <!-- Bottom links -->
        <div class="auth-bottom-link" style="text-align: center; font-size: 0.88rem; color: #64748b; margin-top: 22px;">
            Already have an Admin account?
            <a href="{{ route('login') }}" style="color: #22c55e; font-weight: 700; text-decoration: none; margin-left: 4px;">Log in</a>
        </div>

        <div style="text-align: center; margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(226,232,240,0.6);">
            <a href="{{ route('register') }}" style="font-size: 0.82rem; font-weight: 600; color: #475569; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>Back to User Website Sign Up</span>
            </a>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(btn) {
            const input = btn.parentElement.querySelector('input');
            const currentValue = input.value;
            const cursorPos = input.selectionStart;

            input.focus();

            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>`;
            } else {
                input.type = 'password';
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>`;
            }

            input.value = currentValue;
            input.setSelectionRange(cursorPos, cursorPos);
        }
    </script>

</body>
</html>
