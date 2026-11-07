<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="auth-page">

<head>
    @include('website.partials.head')
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Create Account — AgriCo User Website</title>
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
            height: 42px;
            padding: 6px 34px 6px 36px;
            font-size: 0.82rem;
            background: #ffffff !important;
            background-color: #ffffff !important;
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            box-shadow: 0 0 0 1000px #ffffff inset !important;
            -webkit-text-fill-color: #0f172a !important;
            caret-color: #0f172a !important;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px !important;
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
            left: 36px !important;
            top: 50% !important;
            transform: translateY(-50%) scale(1) !important;
            transform-origin: left center !important;
            font-size: 0.80rem !important;
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
            left: 10px !important;
            transform: translateY(-50%) scale(0.74) !important;
            background: #ffffff !important;
            color: #64748b !important;
            font-weight: 700 !important;
            border-radius: 4px !important;
            padding: 0 4px !important;
        }

        /* 1a. FOCUS BORDER & GLOW */
        .floating-input:focus {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(100, 116, 139, 0.15) !important;
            box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(100, 116, 139, 0.15) !important;
            background-color: #ffffff !important;
            background: #ffffff !important;
            border-color: #64748b !important;
        }

        /* 2. IF CORRECT / VALID STATE: ALL GREEN BORDER, RING & LABEL */
        .floating-input.is-valid {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(34, 197, 94, 0.25) !important;
            box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(34, 197, 94, 0.25) !important;
            background-color: #ffffff !important;
            background: #ffffff !important;
            border-color: #22c55e !important;
        }
        .floating-input.is-valid:focus {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(34, 197, 94, 0.3) !important;
            box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(34, 197, 94, 0.3) !important;
            background-color: #ffffff !important;
            background: #ffffff !important;
            border-color: #16a34a !important;
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
            width: 16px;
            height: 16px;
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

        /* 3. IF WRONG / INVALID STATE: RED BORDER & RED LABEL (FORCED OVER FLOATED DEFAULTS) */
        .floating-input.is-invalid {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
            box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
            background-color: #ffffff !important;
            background: #ffffff !important;
            border-color: #ef4444 !important;
        }
        .floating-input.is-invalid:focus {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3.5px rgba(239, 68, 68, 0.25) !important;
            box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 3.5px rgba(239, 68, 68, 0.25) !important;
            background-color: #ffffff !important;
            background: #ffffff !important;
            border-color: #dc2626 !important;
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

        /* Ensure all inputs inside auth card strictly use white background */
        .auth-card input {
            background-color: #ffffff !important;
        }

        /* Disabled Input State */
        .floating-input:disabled {
            background-color: #f8fafc !important;
            background: #f8fafc !important;
            -webkit-box-shadow: 0 0 0 1000px #f8fafc inset !important;
            box-shadow: 0 0 0 1000px #f8fafc inset !important;
            border-color: #e2e8f0 !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
        }
        .floating-input:disabled ~ .floating-label {
            color: #94a3b8 !important;
            cursor: not-allowed !important;
        }
        .auth-input-group:has(.floating-input:disabled) .input-icon {
            stroke: #94a3b8 !important;
            opacity: 0.6 !important;
        }
        .auth-input-group:has(.floating-input:disabled) .field-btn-right {
            cursor: not-allowed !important;
            opacity: 0.35 !important;
            pointer-events: none !important;
        }

        /* Password field & input styling matching reference */
        .password-field-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            height: 36px;
            background: #ffffff !important;
            background-color: #ffffff !important;
            border: 1.5px solid #cbd5e1;
            border-radius: 9px;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .password-field-wrap:focus-within {
            border-color: #94a3b8;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.15);
            background: #ffffff !important;
            background-color: #ffffff !important;
        }

        .password-field-wrap.has-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
            background: #ffffff !important;
            background-color: #ffffff !important;
        }

        .password-input {
            width: 100%;
            height: 100%;
            padding: 4px 34px 4px 36px;
            border: none;
            background: transparent !important;
            background-color: transparent !important;
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            box-shadow: 0 0 0 1000px #ffffff inset !important;
            -webkit-text-fill-color: #0f172a !important;
            caret-color: #0f172a !important;
            font-size: 0.82rem;
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
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            color: #64748b;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .field-btn-right {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #64748b;
            padding: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: color 0.15s ease;
        }

        .field-btn-right:hover {
            color: #0f172a;
        }

        /* Compact Requirement Pills */
        .req-pill-container {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin: 3px 0 5px 0;
        }

        .req-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .req-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px 2px 3px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            font-size: 0.69rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s ease;
            user-select: none;
            line-height: 1;
        }

        .req-pill-icon {
            width: 13px;
            height: 13px;
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
            width: 14px;
            height: 3px;
            border-radius: 9999px;
            background: #e2e8f0;
            display: inline-block;
            transition: background-color 0.2s ease;
        }

        /* Floating Toast Notification */
        .toast-container {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }

        .auth-toast {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            min-width: 280px;
            max-width: 400px;
            padding: 10px 14px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), 0 2px 6px rgba(0,0,0,0.05);
            border-left: 4px solid #ef4444;
            font-size: 0.82rem;
            color: #0f172a;
            animation: toastSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateY(-16px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes shakeInput {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .shake {
            animation: shakeInput 0.4s ease-in-out;
        }
        /* Universal Fluid Auth Viewport */
        html, body {
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0 !important;
            padding: 0 !important;
            overflow-x: hidden !important;
            scrollbar-width: thin;
        }

        /* ==========================================================
           DESKTOP & TABLET MONITOR DESIGN (Default >= 640px)
           ========================================================== */
        body.auth-body {
            min-height: 100vh !important;
            min-height: 100dvh !important;
            height: auto !important;
            max-height: none !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-start !important;
            position: relative !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            margin: 0 !important;
            padding: 16px 16px !important;
            box-sizing: border-box !important;
            font-family: 'Inter', system-ui, sans-serif !important;
            -webkit-text-size-adjust: 100% !important;
            touch-action: manipulation !important;
        }

        .toast-container {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 9999 !important;
            pointer-events: none !important;
        }

        .auth-arc {
            position: absolute;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 0;
        }
        .auth-arc-1 {
            width: 640px;
            height: 640px;
            border: 1.5px solid rgba(187, 247, 208, 0.55);
        }
        .auth-arc-2 {
            width: 820px;
            height: 820px;
            border: 1.5px dashed rgba(187, 247, 208, 0.35);
        }

        .auth-brand {
            position: fixed !important;
            top: 20px !important;
            left: 28px !important;
            z-index: 20 !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            text-decoration: none !important;
            max-width: 100% !important;
        }
        .auth-brand img {
            width: 34px !important;
            height: 34px !important;
            object-fit: contain !important;
        }
        .auth-brand .auth-brand-text {
            font-size: 20px !important;
            font-weight: 900 !important;
            color: #22c55e !important;
            letter-spacing: -0.02em !important;
            white-space: nowrap !important;
        }
        @media (min-width: 1024px) {
            .auth-brand {
                top: 24px !important;
                left: 36px !important;
            }
            .auth-brand img {
                width: 38px !important;
                height: 38px !important;
            }
            .auth-brand .auth-brand-text {
                font-size: 22px !important;
            }
        }
        .auth-card {
            margin: auto !important;
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px !important;
            background: #ffffff !important;
            border: 1px solid rgba(218, 241, 222, 0.95) !important;
            border-top: 4px solid #22c55e !important;
            border-radius: 20px !important;
            padding: 16px 24px !important;
            box-shadow: 0 16px 40px rgba(21, 128, 61, 0.08), 0 2px 10px rgba(0, 0, 0, 0.02) !important;
            box-sizing: border-box !important;
        }

        .auth-title {
            text-align: center !important;
            font-size: 1.25rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            margin: 0 0 2px 0 !important;
            letter-spacing: -0.02em !important;
        }

        .auth-subtitle {
            text-align: center !important;
            font-size: 0.78rem !important;
            color: #64748b !important;
            margin: 0 0 8px 0 !important;
            line-height: 1.35 !important;
            font-weight: 400 !important;
        }

        .role-selector-wrap {
            margin-bottom: 5px !important;
        }
        .role-selector-label {
            display: block !important;
            font-size: 0.68rem !important;
            font-weight: 700 !important;
            color: #475569 !important;
            margin-bottom: 2px !important;
            text-align: left !important;
        }
        .role-selector-box {
            display: flex !important;
            background: #f1f5f9 !important;
            padding: 2.5px !important;
            border-radius: 8px !important;
            gap: 3px !important;
            border: 1px solid #e2e8f0 !important;
        }
        .role-btn {
            flex: 1 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 4px !important;
            padding: 4px 6px !important;
            height: 28px !important;
            border: none !important;
            border-radius: 6px !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .role-btn svg {
            width: 12px !important;
            height: 12px !important;
        }

        .auth-input-group {
            position: relative !important;
            z-index: 2 !important;
            margin-bottom: 5px !important;
        }

        .floating-input {
            width: 100% !important;
            height: 36px !important;
            padding: 5px 36px 5px 36px !important;
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 9px !important;
            font-size: 0.82rem !important;
            color: #0f172a !important;
            outline: none !important;
            box-sizing: border-box !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
        }
        .floating-input::placeholder {
            opacity: 0 !important;
            color: transparent !important;
        }

        .floating-label {
            position: absolute !important;
            left: 36px !important;
            top: 50% !important;
            transform: translateY(-50%) scale(1) !important;
            transform-origin: left center !important;
            font-size: 0.78rem !important;
            color: #94a3b8 !important;
            font-weight: 500 !important;
            pointer-events: none !important;
            background: transparent !important;
            padding: 0 4px !important;
            z-index: 4 !important;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), top 0.2s cubic-bezier(0.4, 0, 0.2, 1), left 0.2s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s ease, background-color 0.2s ease !important;
        }

        .floating-input:focus ~ .floating-label,
        .floating-input:not(:placeholder-shown) ~ .floating-label,
        .floating-input:-webkit-autofill ~ .floating-label,
        .floating-input:autofill ~ .floating-label,
        .floating-input.is-filled ~ .floating-label {
            top: 0px !important;
            left: 12px !important;
            transform: translateY(-50%) scale(0.75) !important;
            background: #ffffff !important;
            color: #64748b !important;
            font-weight: 700 !important;
            border-radius: 4px !important;
            padding: 0 4px !important;
            z-index: 5 !important;
        }
        .floating-input:focus ~ .floating-label {
            color: #64748b !important;
        }
        .floating-input:focus {
            border-color: #64748b !important;
            box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.15) !important;
        }

        .has-error .floating-input {
            border-color: #ef4444 !important;
        }

        .input-icon {
            position: absolute !important;
            left: 11px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 15px !important;
            height: 15px !important;
            pointer-events: none !important;
            z-index: 3 !important;
        }

        .password-wrap-group {
            margin-bottom: 5px !important;
        }

        .sec-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            margin-bottom: 2px !important;
            padding: 0 2px !important;
        }
        .sec-header-title {
            font-size: 0.68rem !important;
            color: #64748b !important;
            font-weight: 600 !important;
        }

        .sec-progress-bar {
            height: 3px !important;
            width: 16px !important;
            border-radius: 9999px;
            background: #e2e8f0;
            transition: all 0.3s ease;
        }

        .req-pill-container {
            display: flex !important;
            flex-direction: column !important;
            gap: 2.5px !important;
            margin: 2px 0 4px 0 !important;
        }
        .req-pill-row {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 3px !important;
        }
        .req-pill {
            font-size: 0.63rem !important;
            padding: 2px 5px !important;
            border-radius: 5px !important;
            gap: 3px !important;
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .req-pill.is-met {
            background: #dcfce7 !important;
            color: #15803d !important;
            border-color: #86efac !important;
        }
        .req-pill-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 10px !important;
            height: 10px !important;
            border-radius: 50%;
            background: #e2e8f0;
            transition: all 0.2s ease;
        }
        .req-pill.is-met .req-pill-icon {
            background: #22c55e !important;
        }

        .auth-submit-btn {
            width: 100% !important;
            height: 38px !important;
            background: #22c55e !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 10px !important;
            font-size: 0.88rem !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 3px 10px rgba(34, 197, 94, 0.30) !important;
            transition: all 0.2s ease !important;
            margin-top: 4px !important;
        }
        .auth-submit-btn:hover:not(:disabled) {
            background: #16a34a !important;
            box-shadow: 0 5px 14px rgba(34, 197, 94, 0.40) !important;
            transform: translateY(-1px) !important;
        }
        .auth-submit-btn:disabled,
        .auth-submit-btn[disabled] {
            opacity: 0.55 !important;
            cursor: not-allowed !important;
            background: #22c55e !important;
            color: #ffffff !important;
            border: none !important;
            box-shadow: none !important;
            transform: none !important;
            pointer-events: none !important;
        }
        .auth-submit-btn:disabled:hover,
        .auth-submit-btn[disabled]:hover {
            background: #22c55e !important;
            color: #ffffff !important;
            box-shadow: none !important;
            transform: none !important;
        }

        .auth-bottom-wrap {
            text-align: center !important;
            margin-top: 6px !important;
            padding-top: 0 !important;
            border-top: none !important;
        }
        .auth-bottom-link {
            font-size: 0.76rem !important;
            color: #64748b !important;
            margin: 0 0 3px 0 !important;
        }
        .auth-bottom-link a {
            color: #22c55e !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            margin-left: 4px !important;
        }
        .auth-guest-wrap {
            text-align: center !important;
            margin-top: 5px !important;
            padding-top: 5px !important;
            border-top: 1px solid #e2e8f0 !important;
        }
        .auth-guest-link {
            font-size: 0.74rem !important;
            font-weight: 600 !important;
            color: #64748b !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
            transition: color 0.2s ease !important;
        }
        .auth-guest-link:hover {
            color: #22c55e !important;
        }

        /* Custom Checkbox Design (Centered Row, Checkbox on Left) */
        .terms-policy-wrap {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            margin-top: 5px !important;
            margin-bottom: 6px !important;
            text-align: center !important;
        }
        .terms-policy-label {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            cursor: pointer !important;
            user-select: none !important;
            padding: 2px !important;
            max-width: 100% !important;
        }
        .terms-policy-text {
            font-size: 0.72rem !important;
            color: #475569 !important;
            line-height: 1.3 !important;
            text-align: left !important;
            flex: initial !important;
        }
        .terms-policy-text a {
            color: #16a34a !important;
            font-weight: 600 !important;
            text-decoration: underline !important;
            transition: color 0.15s ease !important;
        }
        .terms-policy-text a:hover {
            color: #15803d !important;
        }
        .custom-checkbox-container {
            position: relative !important;
            width: 16px !important;
            height: 16px !important;
            flex-shrink: 0 !important;
            display: inline-block !important;
        }
        .custom-checkbox-input {
            position: absolute !important;
            opacity: 0 !important;
            width: 100% !important;
            height: 100% !important;
            top: 0 !important;
            left: 0 !important;
            margin: 0 !important;
            cursor: pointer !important;
            z-index: 5 !important;
        }
        .custom-checkbox-box {
            width: 16px !important;
            height: 16px !important;
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 4px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-sizing: border-box !important;
        }
        .terms-policy-label:hover .custom-checkbox-box,
        .custom-checkbox-input:hover + .custom-checkbox-box {
            border-color: #94a3b8 !important;
            background: #f8fafc !important;
        }
        .custom-checkbox-input:checked + .custom-checkbox-box {
            background: #22c55e !important;
            border-color: #22c55e !important;
            box-shadow: 0 1px 4px rgba(34, 197, 94, 0.3) !important;
        }
        .custom-checkbox-icon {
            width: 10px !important;
            height: 10px !important;
            color: #ffffff !important;
            opacity: 0 !important;
            transform: scale(0.6) !important;
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
            pointer-events: none !important;
        }
        .custom-checkbox-input:checked + .custom-checkbox-box .custom-checkbox-icon {
            opacity: 1 !important;
            transform: scale(1) !important;
        }
        .custom-checkbox-input:focus-visible + .custom-checkbox-box {
            outline: 2px solid #22c55e !important;
            outline-offset: 2px !important;
        }
        .custom-checkbox-box.is-invalid {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
        }


        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ==========================================================
           MOBILE SEPARATED DESIGN (max-width: 639px)
           ========================================================== */
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden !important;
                max-width: 100vw !important;
            }
            body.auth-body::before,
            body.auth-body::after {
                display: none !important;
                width: 0 !important;
                height: 0 !important;
            }
            .auth-arc {
                display: none !important;
                width: 0 !important;
                height: 0 !important;
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
                padding: 22px 18px !important;
                border-radius: 18px !important;
                max-width: 440px !important;
                width: 100% !important;
                margin: auto !important;
                border-top-width: 4px !important;
                box-shadow: 0 10px 30px rgba(21, 128, 61, 0.08) !important;
            }
            .auth-title {
                font-size: 1.28rem !important;
                margin-bottom: 2px !important;
            }
            .auth-subtitle {
                font-size: 0.76rem !important;
                margin-bottom: 8px !important;
                line-height: 1.3 !important;
            }
            .role-selector-wrap {
                margin-bottom: 6px !important;
            }
            .role-selector-label {
                font-size: 0.70rem !important;
                margin-bottom: 2px !important;
            }
            .role-selector-box {
                padding: 2.5px !important;
                border-radius: 8px !important;
                gap: 3px !important;
            }
            .role-btn {
                height: 34px !important;
                font-size: 0.78rem !important;
                padding: 3px 6px !important;
            }
            .role-btn svg {
                width: 13px !important;
                height: 13px !important;
            }
            .auth-input-group {
                margin-bottom: 5px !important;
            }
            .floating-input {
                height: 42px !important;
                padding: 6px 36px 6px 38px !important;
                font-size: 16px !important; /* Prevents iOS Safari zoom */
                border-radius: 10px !important;
            }
            .floating-label {
                font-size: 0.80rem !important;
                left: 38px !important;
            }
            .floating-input:focus ~ .floating-label,
            .floating-input:not(:placeholder-shown) ~ .floating-label,
            .floating-input:-webkit-autofill ~ .floating-label,
            .floating-input:autofill ~ .floating-label,
            .floating-input.is-filled ~ .floating-label {
                top: 0px !important;
                left: 10px !important;
                transform: translateY(-50%) scale(0.75) !important;
                font-size: 0.80rem !important;
                padding: 0 3px !important;
            }
            .input-icon {
                width: 15px !important;
                height: 15px !important;
                left: 12px !important;
            }
            .password-wrap-group {
                margin-bottom: 5px !important;
            }
            .sec-header {
                margin-bottom: 2px !important;
            }
            .sec-header-title {
                font-size: 0.68rem !important;
            }
            .sec-progress-bar {
                height: 3px !important;
                width: 18px !important;
            }
            .req-pill-container {
                gap: 3px !important;
                margin: 3px 0 4px 0 !important;
            }
            .req-pill-row {
                gap: 3px !important;
            }
            .req-pill {
                font-size: 0.64rem !important;
                padding: 2px 6px !important;
                gap: 3px !important;
            }
            .req-pill-icon {
                width: 11px !important;
                height: 11px !important;
            }
            .auth-submit-btn {
                height: 42px !important;
                font-size: 0.90rem !important;
                margin-top: 6px !important;
                border-radius: 14px !important;
            }
            .auth-bottom-wrap {
                margin-top: 8px !important;
                padding-top: 0 !important;
                border-top: none !important;
            }
            .auth-bottom-link {
                font-size: 0.76rem !important;
                margin-bottom: 6px !important;
            }
            .auth-guest-wrap {
                margin-top: 6px !important;
                padding-top: 6px !important;
                border-top: 1px solid #e2e8f0 !important;
            }
            .auth-guest-link {
                font-size: 0.72rem !important;
            }
        }

        @media (max-height: 480px) and (orientation: landscape) {
            html, body {
                overflow-y: auto !important;
            }
            body.auth-body {
                overflow-y: auto !important;
                justify-content: flex-start !important;
                padding: max(56px, calc(env(safe-area-inset-top) + 40px)) 16px 24px 16px !important;
            }
            .auth-subtitle {
                display: none !important;
            }
            .auth-card {
                margin: 0 auto !important;
            }
        }
    </style>
</head>
<body class="auth-body" style="background: linear-gradient(180deg, #eefbe7 0%, #f0fdf4 45%, #ffffff 100%) !important;">

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Decorative arcs (Tablet & Desktop Monitor only) clipped in container -->
    <div style="position: absolute; inset: 0; width: 100%; height: 100%; overflow: hidden; pointer-events: none; z-index: 0;">
        <div class="auth-arc auth-arc-1"></div>
        <div class="auth-arc auth-arc-2"></div>
    </div>

    <!-- Responsive AgriCo Header Logo -->
    <a href="{{ route('website.home') }}" class="auth-brand">
        <img src="{{ asset('images/agrico-logo.png') }}" alt="AgriCo Logo" />
        <span class="auth-brand-text">AgriCo</span>
    </a>

    <!-- Auth Card -->
    <div class="auth-card">
        <h1 class="auth-title">Create an Account</h1>
        <p class="auth-subtitle">Join AgriCo to access soil analysis & agricultural tools.</p>

        <!-- Dynamic Inline Alert Container -->
        <div id="passwordAlert" style="display: none; background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 10px; padding: 6px 10px; margin-bottom: 6px; font-size: 0.74rem; color: #991b1b; animation: toastSlideIn 0.25s ease;">
            <div style="display: flex; align-items: flex-start; gap: 6px;">
                <svg style="width: 14px; height: 14px; color: #ef4444; flex-shrink: 0; margin-top: 1px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #b91c1c; font-size: 0.74rem;" id="passwordAlertTitle">Password requirements not met</div>
                    <div id="passwordAlertMessage" style="font-size: 0.69rem; color: #7f1d1d; line-height: 1.2;"></div>
                </div>
                <x-close-button size="sm" onclick="dismissPasswordAlert()" ariaLabel="Dismiss alert" title="Dismiss alert" />
            </div>
        </div>

        <form id="registerForm" method="POST" action="{{ route('register.store') }}" novalidate data-turbo="false" pjax-disable style="margin: 0;">
            @csrf

            <!-- Account Type Selector (User or Student) -->
            <input type="hidden" name="role" id="roleInput" value="{{ old('role', 'user') }}">
            <div class="role-selector-wrap">
                <label class="role-selector-label">Register As</label>
                <div class="role-selector-box">
                    <button type="button" id="roleBtnUser" class="role-btn" onclick="setRegisterRole('user')" style="background: {{ old('role', 'user') === 'user' ? '#ffffff' : 'transparent' }}; color: {{ old('role', 'user') === 'user' ? '#15803d' : '#64748b' }}; box-shadow: {{ old('role', 'user') === 'user' ? '0 1px 4px rgba(0,0,0,0.08)' : 'none' }};">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                        </svg>
                        <span>User</span>
                    </button>
                    <button type="button" id="roleBtnStudent" class="role-btn" onclick="setRegisterRole('student')" style="background: {{ old('role') === 'student' ? '#ffffff' : 'transparent' }}; color: {{ old('role') === 'student' ? '#15803d' : '#64748b' }}; box-shadow: {{ old('role') === 'student' ? '0 1px 4px rgba(0,0,0,0.08)' : 'none' }};">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                        </svg>
                        <span>Student</span>
                    </button>
                </div>
            </div>

            <!-- Student ID Number (Visible only when Student is selected) -->
            <div id="studentIdGroup" class="auth-input-group" style="display: {{ old('role') === 'student' ? 'block' : 'none' }};">
                <div style="position: relative;">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $errors->has('student_id') ? '#ef4444' : '#64748b' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15A2.25 2.25 0 002.25 6.75v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                    </svg>
                    <input
                        id="student_id"
                        name="student_id"
                        type="text"
                        value="{{ old('student_id') }}"
                        autocomplete="off"
                        placeholder=" "
                        class="floating-input"
                        style="border-color: {{ $errors->has('student_id') ? '#ef4444' : '#cbd5e1' }};"
                    />
                    <label for="student_id" class="floating-label">Student ID Number</label>
                </div>
                @error('student_id')
                    <div style="color: #dc2626; font-size: 0.70rem; margin-top: 1px; font-weight: 600; text-align: left; padding-left: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Student Section (Visible only when Student is selected) -->
            <div id="studentSectionGroup" class="auth-input-group" style="display: {{ old('role') === 'student' ? 'block' : 'none' }};">
                <div style="position: relative;">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $errors->has('section') ? '#ef4444' : '#64748b' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    <input
                        id="section"
                        name="section"
                        type="text"
                        value="{{ old('section') }}"
                        autocomplete="off"
                        placeholder=" "
                        class="floating-input {{ $errors->has('section') ? 'is-invalid' : '' }}"
                        style="border-color: {{ $errors->has('section') ? '#ef4444' : '#cbd5e1' }};"
                        onblur="handleSectionBlur(this)"
                        oninput="handleSectionInput(this)"
                    />
                    <label for="section" class="floating-label" style="{{ $errors->has('section') ? 'color: #ef4444 !important; font-weight: 700;' : '' }}">Section (e.g. BSIT 1A, BSA 1A, or BSA 3-Animal Science)</label>
                </div>
                <div id="sectionClientError" style="color: #dc2626; font-size: 0.70rem; margin-top: 1px; font-weight: 600; text-align: left; padding-left: 4px; display: none;"></div>
                @error('section')
                    <div id="sectionServerError" style="color: #dc2626; font-size: 0.70rem; margin-top: 1px; font-weight: 600; text-align: left; padding-left: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Name -->
            <div class="auth-input-group">
                <div style="position: relative;">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $errors->has('name') ? '#ef4444' : '#64748b' }}">
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
                        autocapitalize="words"
                        spellcheck="false"
                        placeholder=" "
                        class="floating-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                        style="border-color: {{ $errors->has('name') ? '#ef4444' : '#cbd5e1' }};"
                        onblur="handleNameBlur(this)"
                        oninput="handleNameInput(this)"
                    />
                    <label for="name" class="floating-label" style="{{ $errors->has('name') ? 'color: #ef4444 !important; font-weight: 700;' : '' }}">Full Name</label>
                </div>
                <div id="nameClientError" style="color: #dc2626; font-size: 0.70rem; margin-top: 1px; font-weight: 600; text-align: left; padding-left: 4px; display: none;"></div>
                @error('name')
                    <div id="nameServerError" style="color: #dc2626; font-size: 0.70rem; margin-top: 1px; font-weight: 600; text-align: left; padding-left: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Email -->
            <div class="auth-input-group">
                <div style="position: relative;">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $errors->has('email') ? '#ef4444' : '#64748b' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="username email"
                        placeholder=" "
                        class="floating-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                        style="border-color: {{ $errors->has('email') ? '#ef4444' : '#cbd5e1' }};"
                        oninput="validateLiveEmail(this, 'register')"
                        onblur="handleEmailBlur(this)"
                    />
                    <label for="email" class="floating-label" style="{{ $errors->has('email') ? 'color: #ef4444 !important; font-weight: 700;' : '' }}">Email Address</label>

                    <!-- Status Icon (Right side) with Pop-Ping Animation -->
                    <div class="email-status-icon" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); display: {{ $errors->has('email') ? 'flex' : 'none' }}; align-items: center; justify-content: center; pointer-events: none; z-index: 3;">
                        <svg class="email-icon-valid" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#22c55e" style="width: 16px; height: 16px; display: none;">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                        </svg>
                        <svg class="email-icon-invalid" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ef4444" style="width: 16px; height: 16px; display: {{ $errors->has('email') ? 'block' : 'none' }};">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Inline Typo Suggestion Box (Mailcheck) -->
                <div id="mailcheckSuggestionBox" style="display: none; margin-top: 3px; font-size: 0.72rem; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 6px; padding: 3px 6px; align-items: center; justify-content: space-between; gap: 5px; line-height: 1.25;">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#3b82f6" style="width: 12px; height: 12px; flex-shrink: 0;">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
                        </svg>
                        <span>
                            Did you mean <button type="button" id="mailcheckSuggestionLink" onclick="acceptMailcheckSuggestion()" style="background: none; border: none; padding: 0; color: #1d4ed8; font-weight: 700; text-decoration: underline; cursor: pointer; font-size: inherit; font-family: inherit;"></button>?
                        </span>
                    </div>
                    <button type="button" onclick="dismissMailcheckSuggestion()" style="background: none; border: none; padding: 0; color: #64748b; font-size: 0.68rem; font-weight: 600; cursor: pointer; text-decoration: underline; margin-left: 6px; flex-shrink: 0;" title="Keep original typed email">It's correct, use this</button>
                </div>

                <div class="email-subtext" style="font-size: 0.70rem; margin-top: 1px; font-weight: 600; text-align: left; padding-left: 4px; display: none;"></div>
                @error('email')
                    <div style="color: #dc2626; font-size: 0.70rem; margin-top: 1px; font-weight: 600; text-align: left; padding-left: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Password -->
            <div class="password-wrap-group">
                <div class="sec-header">
                    <span class="sec-header-title">Password Security</span>
                    <div id="securityBars" style="display: flex; align-items: center; gap: 3px;">
                        <span class="sec-progress-bar" id="sec-bar-1"></span>
                        <span class="sec-progress-bar" id="sec-bar-2"></span>
                        <span class="sec-progress-bar" id="sec-bar-3"></span>
                        <span class="sec-progress-bar" id="sec-bar-4"></span>
                        <span class="sec-progress-bar" id="sec-bar-5"></span>
                    </div>
                </div>

                <div class="auth-input-group" style="margin-bottom: 3px;">
                    <div style="position: relative;">
                        <!-- Lock Icon Left -->
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $errors->has('password') ? '#ef4444' : '#64748b' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder=" "
                            class="floating-input"
                            style="border-color: {{ $errors->has('password') ? '#ef4444' : '#cbd5e1' }};"
                            oninput="checkPasswordRequirements(this.value)"
                        />
                        <label for="password" class="floating-label">Password</label>

                        <!-- Eye Toggle Right -->
                        <button type="button" class="field-btn-right" onclick="togglePass('password', this)" title="Show / Hide Password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; padding: 4px; display: flex; align-items: center; z-index: 6;">
                            <svg class="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="width: 17px; height: 17px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                            <svg class="eye-open-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#22c55e" style="display: none; width: 17px; height: 17px;">
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
                                <svg class="icon-x" width="7" height="7" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                <svg class="icon-check" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span>8+ characters</span>
                        </div>

                        <div id="pill-number" class="req-pill">
                            <span class="req-pill-icon">
                                <svg class="icon-x" width="7" height="7" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                <svg class="icon-check" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span>Number</span>
                        </div>

                        <div id="pill-upper" class="req-pill">
                            <span class="req-pill-icon">
                                <svg class="icon-x" width="7" height="7" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                <svg class="icon-check" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span>Uppercase letter</span>
                        </div>
                    </div>

                    <!-- Row 2: Lowercase letter, Special character -->
                    <div class="req-pill-row">
                        <div id="pill-lower" class="req-pill">
                            <span class="req-pill-icon">
                                <svg class="icon-x" width="7" height="7" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                <svg class="icon-check" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span>Lowercase letter</span>
                        </div>

                        <div id="pill-special" class="req-pill">
                            <span class="req-pill-icon">
                                <svg class="icon-x" width="7" height="7" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="3.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                <svg class="icon-check" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="display: none;"><path d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span>Special character</span>
                        </div>
                    </div>
                </div>

                @error('password')
                    <div style="color: #dc2626; font-size: 0.70rem; margin-top: 1px; font-weight: 600; text-align: left; padding-left: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Confirm Password (Always visible, disabled until password meets all 5 requirements) -->
            <div id="confirmPasswordGroup" class="auth-input-group" style="margin-bottom: 8px;">
                <div style="position: relative;">
                    <!-- Shield Icon Left -->
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $errors->has('password_confirmation') ? '#ef4444' : '#64748b' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>

                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        disabled
                        autocomplete="new-password"
                        placeholder=" "
                        class="floating-input"
                        style="border-color: {{ $errors->has('password_confirmation') ? '#ef4444' : '#cbd5e1' }};"
                        title="Please complete all password security requirements above to confirm your password"
                    />
                    <label for="password_confirmation" class="floating-label">Confirm Password</label>

                    <!-- Eye Toggle Right -->
                    <button type="button" id="confirmPassToggleBtn" class="field-btn-right" onclick="togglePass('password_confirmation', this)" title="Show / Hide Confirm Password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; padding: 4px; display: flex; align-items: center; z-index: 6;">
                        <svg class="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#64748b" style="width: 17px; height: 17px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                        <svg class="eye-open-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#22c55e" style="display: none; width: 17px; height: 17px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>
                @error('password_confirmation')
                    <div style="color: #dc2626; font-size: 0.70rem; margin-top: 1px; font-weight: 600; text-align: left; padding-left: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Terms of Service & Privacy Policy Checkbox (Centered Row, Checkbox on Left) -->
            <div class="terms-policy-wrap">
                <label for="terms_agreed" class="terms-policy-label">
                    <div class="custom-checkbox-container">
                        <input
                            type="checkbox"
                            id="terms_agreed"
                            name="terms_agreed"
                            value="1"
                            {{ old('terms_agreed') ? 'checked' : '' }}
                            class="custom-checkbox-input"
                            onchange="handleTermsChange(this)"
                        />
                        <div class="custom-checkbox-box" id="termsCheckboxBox">
                            <svg class="custom-checkbox-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <span class="terms-policy-text">
                        I agree to the <a href="{{ route('website.terms') }}" target="_blank" onclick="event.stopPropagation();">Terms of Service</a> and <a href="{{ route('website.privacy') }}" target="_blank" onclick="event.stopPropagation();">Privacy Policy</a>.
                    </span>
                </label>
            </div>
            <div id="termsClientError" style="color: #dc2626; font-size: 0.70rem; margin-top: -6px; margin-bottom: 10px; font-weight: 600; text-align: center; display: none;">
                Please agree to the Terms of Service and Privacy Policy to continue.
            </div>
            @error('terms_agreed')
                <div id="termsServerError" style="color: #dc2626; font-size: 0.70rem; margin-top: -6px; margin-bottom: 10px; font-weight: 600; text-align: center;">{{ $message }}</div>
            @enderror

            <!-- Submit Button (#22c55e GREEN PILL BUTTON, Disabled by default until terms are agreed) -->
            <div id="submitBtnWrapper" style="width: 100%; cursor: {{ old('terms_agreed') ? 'default' : 'not-allowed' }}; margin-top: 4px;">
                <button type="submit" id="submitBtn" class="auth-submit-btn" style="width: 100%; height: 38px; background: #22c55e !important; color: #ffffff !important; border: none; border-radius: 10px; font-size: 0.88rem; font-weight: 700; cursor: {{ old('terms_agreed') ? 'pointer' : 'not-allowed' }} !important; display: flex; align-items: center; justify-content: center; box-shadow: {{ old('terms_agreed') ? '0 3px 10px rgba(34, 197, 94, 0.30)' : 'none' }} !important; opacity: {{ old('terms_agreed') ? '1' : '0.55' }} !important; transition: all 0.2s ease; margin-top: 0 !important;" {{ old('terms_agreed') ? '' : 'disabled' }}>
                    Create Account
                </button>
            </div>
        </form>

        <!-- Bottom links -->
        <div class="auth-bottom-wrap">
            <div class="auth-bottom-link">
                Already have an account?
                <a href="{{ route('login') }}">Log in</a>
            </div>
            <div style="margin-top: -4px; margin-bottom: 8px; font-size: 0.76rem;">
                <a href="{{ route('website.faq') }}#account-approval" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#64748b'">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span>Questions about account approval? Read FAQ</span>
                </a>
            </div>
            <div class="auth-guest-wrap">
                <a href="{{ route('website.home') }}" class="auth-guest-link">
                    <span>Continue as Guest</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Mailcheck.js for Sign Up email typo checking -->
    <script src="{{ asset('js/mailcheck.min.js') }}" onerror="this.onerror=null;this.src='https://cdnjs.cloudflare.com/ajax/libs/mailcheck/1.1.2/mailcheck.min.js';"></script>

    <script>
        function isValidFullName(name) {
            const trimmed = name.trim();
            const parts = trimmed.split(/\s+/).filter(Boolean);

            if (parts.length < 2) {
                return { valid: false, message: 'Please enter your full name (first and last name).' };
            }

            const namePattern = /^[A-Za-zÀ-ÖØ-öø-ÿ'.\-]+$/u;
            for (const part of parts) {
                if (!namePattern.test(part)) {
                    return { valid: false, message: 'Full name should only contain letters, spaces, hyphens, or apostrophes.' };
                }
            }

            return { valid: true, message: '' };
        }

        function validateFullNameField(inputEl, isBlur = false, isSubmit = false) {
            if (!inputEl) return true;
            const val = inputEl.value;
            const parent = inputEl.closest('.auth-input-group');
            const clientErrorEl = document.getElementById('nameClientError');
            const serverErrorEl = document.getElementById('nameServerError');
            const leftIcon = parent ? parent.querySelector('.input-icon') : null;
            const label = parent ? parent.querySelector('.floating-label') : null;

            if (val.trim() === '') {
                if (isSubmit) {
                    inputEl.classList.add('is-invalid');
                    inputEl.style.borderColor = '#ef4444';
                    if (clientErrorEl) {
                        clientErrorEl.innerText = 'Please enter your full name (first and last name).';
                        clientErrorEl.style.display = 'block';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                    if (label) {
                        label.style.setProperty('color', '#ef4444', 'important');
                        label.style.setProperty('font-weight', '700', 'important');
                    }
                    return false;
                } else {
                    // Empty blur or typing: keep neutral gray
                    inputEl.classList.remove('is-invalid');
                    inputEl.style.borderColor = '#cbd5e1';
                    if (clientErrorEl) {
                        clientErrorEl.style.display = 'none';
                        clientErrorEl.innerText = '';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
                    if (label) {
                        label.style.removeProperty('color');
                        label.style.removeProperty('font-weight');
                    }
                    return true;
                }
            }

            if (isBlur || isSubmit) {
                const res = isValidFullName(val);
                if (!res.valid) {
                    inputEl.classList.add('is-invalid');
                    inputEl.style.borderColor = '#ef4444';
                    if (clientErrorEl) {
                        clientErrorEl.innerText = res.message;
                        clientErrorEl.style.display = 'block';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                    if (label) {
                        label.style.setProperty('color', '#ef4444', 'important');
                        label.style.setProperty('font-weight', '700', 'important');
                    }
                    return false;
                } else {
                    inputEl.value = toProperNameCase(inputEl.value);
                    inputEl.classList.remove('is-invalid');
                    inputEl.style.borderColor = '#cbd5e1';
                    if (clientErrorEl) {
                        clientErrorEl.style.display = 'none';
                        clientErrorEl.innerText = '';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
                    if (label) {
                        label.style.removeProperty('color');
                        label.style.removeProperty('font-weight');
                    }
                    return true;
                }
            } else {
                inputEl.classList.remove('is-invalid');
                inputEl.style.borderColor = '#cbd5e1';
                if (clientErrorEl) {
                    clientErrorEl.style.display = 'none';
                    clientErrorEl.innerText = '';
                }
                if (serverErrorEl) serverErrorEl.style.display = 'none';
                if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
                if (label) {
                    label.style.removeProperty('color');
                    label.style.removeProperty('font-weight');
                }
                return true;
            }
        }

        function toProperNameCase(name) {
            if (!name) return '';
            return name
                .trim()
                .toLowerCase()
                .split(' ')
                .map(word => {
                    return word
                        .split('-')
                        .map(part => {
                            return part
                                .split("'")
                                .map(sub => sub.charAt(0).toUpperCase() + sub.slice(1))
                                .join("'");
                        })
                        .join('-');
                })
                .join(' ');
        }

        function handleNameBlur(inputEl) {
            validateFullNameField(inputEl, true, false);
        }

        function handleNameInput(inputEl) {
            inputEl.classList.remove('is-invalid');
            inputEl.style.borderColor = '#cbd5e1';
            const parent = inputEl.closest('.auth-input-group');
            const clientErrorEl = document.getElementById('nameClientError');
            const serverErrorEl = document.getElementById('nameServerError');
            const leftIcon = parent ? parent.querySelector('.input-icon') : null;
            const label = parent ? parent.querySelector('.floating-label') : null;
            if (clientErrorEl) {
                clientErrorEl.style.display = 'none';
                clientErrorEl.innerText = '';
            }
            if (serverErrorEl) serverErrorEl.style.display = 'none';
            if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
            if (label) {
                label.style.removeProperty('color');
                label.style.removeProperty('font-weight');
            }
        }

        function formatAndValidateSection(raw) {
            const trimmed = (raw || '').trim();
            if (!trimmed) {
                return { valid: false, formatted: '', empty: true, message: 'Format must be e.g. BSIT 1A, BSA 1A, or BSA 3-Animal Science.' };
            }

            let clean = trimmed.replace(/^\(+|\)+$/g, '').trim();

            // Check if Year 3-4 BSA/DAT with Major (Major at least 3 characters)
            const majorPattern = /^([A-Za-z]+)\s*([3-4])\s*[\-\s:]+\s*([A-Za-z]{3,}(?:\s+[A-Za-z]+)*)$/;
            const majorMatch = clean.match(majorPattern);
            if (majorMatch) {
                const course = majorMatch[1].toUpperCase();
                const year = majorMatch[2];
                const rawMajor = majorMatch[3].trim();
                const major = rawMajor
                    .toLowerCase()
                    .split(/\s+/)
                    .map(w => w.charAt(0).toUpperCase() + w.slice(1))
                    .join(' ');

                return {
                    valid: true,
                    formatted: `${course} ${year}-${major}`,
                    message: ''
                };
            }

            // Check Course + Year + optional Section (e.g. BSIT 1A, BSA 1A, BSA 1, DAT 2B)
            const sectionPattern = /^([A-Za-z]+)\s*([1-4])\s*(?:[\-\(\s]*([A-Za-z0-9]{1,2})[\)\s]*)?$/;
            const sectionMatch = clean.match(sectionPattern);
            if (sectionMatch) {
                const course = sectionMatch[1].toUpperCase();
                const year = sectionMatch[2];
                const sec = sectionMatch[3] ? sectionMatch[3].toUpperCase() : '';

                if ((course === 'BSA' || course === 'DAT') && (year === '3' || year === '4')) {
                    return {
                        valid: false,
                        formatted: '',
                        message: 'Format must be e.g. BSIT 1A, BSA 1A, or BSA 3-Animal Science.'
                    };
                }

                const formatted = sec ? `${course} ${year}${sec}` : `${course} ${year}`;
                return {
                    valid: true,
                    formatted: formatted,
                    message: ''
                };
            }

            return {
                valid: false,
                formatted: '',
                message: 'Format must be e.g. BSIT 1A, BSA 1A, or BSA 3-Animal Science.'
            };
        }

        function validateSectionField(inputEl, isBlur = false, isSubmit = false) {
            if (!inputEl) return true;
            const roleInput = document.getElementById('roleInput');
            if (roleInput && roleInput.value !== 'student') return true;

            const val = inputEl.value;
            const parent = inputEl.closest('.auth-input-group');
            const clientErrorEl = document.getElementById('sectionClientError');
            const serverErrorEl = document.getElementById('sectionServerError');
            const leftIcon = parent ? parent.querySelector('.input-icon') : null;
            const label = parent ? parent.querySelector('.floating-label') : null;

            if (val.trim() === '') {
                if (isSubmit) {
                    inputEl.classList.add('is-invalid');
                    inputEl.style.borderColor = '#ef4444';
                    if (clientErrorEl) {
                        clientErrorEl.innerText = 'Format must be e.g. BSIT 1A, BSA 1A, or BSA 3-Animal Science.';
                        clientErrorEl.style.display = 'block';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                    if (label) {
                        label.style.setProperty('color', '#ef4444', 'important');
                        label.style.setProperty('font-weight', '700', 'important');
                    }
                    return false;
                } else {
                    // Empty blur or typing: keep neutral gray
                    inputEl.classList.remove('is-invalid');
                    inputEl.style.borderColor = '#cbd5e1';
                    if (clientErrorEl) {
                        clientErrorEl.style.display = 'none';
                        clientErrorEl.innerText = '';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
                    if (label) {
                        label.style.removeProperty('color');
                        label.style.removeProperty('font-weight');
                    }
                    return true;
                }
            }

            if (isBlur || isSubmit) {
                const res = formatAndValidateSection(val);
                if (!res.valid) {
                    inputEl.classList.add('is-invalid');
                    inputEl.style.borderColor = '#ef4444';
                    if (clientErrorEl) {
                        clientErrorEl.innerText = res.message;
                        clientErrorEl.style.display = 'block';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                    if (label) {
                        label.style.setProperty('color', '#ef4444', 'important');
                        label.style.setProperty('font-weight', '700', 'important');
                    }
                    return false;
                } else {
                    inputEl.value = res.formatted;
                    inputEl.classList.remove('is-invalid');
                    inputEl.style.borderColor = '#cbd5e1';
                    if (clientErrorEl) {
                        clientErrorEl.style.display = 'none';
                        clientErrorEl.innerText = '';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
                    if (label) {
                        label.style.removeProperty('color');
                        label.style.removeProperty('font-weight');
                    }
                    return true;
                }
            } else {
                inputEl.classList.remove('is-invalid');
                inputEl.style.borderColor = '#cbd5e1';
                if (clientErrorEl) {
                    clientErrorEl.style.display = 'none';
                    clientErrorEl.innerText = '';
                }
                if (serverErrorEl) serverErrorEl.style.display = 'none';
                if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
                if (label) {
                    label.style.removeProperty('color');
                    label.style.removeProperty('font-weight');
                }
                return true;
            }
        }

        function handleSectionBlur(inputEl) {
            validateSectionField(inputEl, true, false);
        }

        function handleSectionInput(inputEl) {
            inputEl.classList.remove('is-invalid');
            inputEl.style.borderColor = '#cbd5e1';
            const parent = inputEl.closest('.auth-input-group');
            const clientErrorEl = document.getElementById('sectionClientError');
            const serverErrorEl = document.getElementById('sectionServerError');
            const leftIcon = parent ? parent.querySelector('.input-icon') : null;
            const label = parent ? parent.querySelector('.floating-label') : null;
            if (clientErrorEl) {
                clientErrorEl.style.display = 'none';
                clientErrorEl.innerText = '';
            }
            if (serverErrorEl) serverErrorEl.style.display = 'none';
            if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
            if (label) {
                label.style.removeProperty('color');
                label.style.removeProperty('font-weight');
            }
        }

        function checkFullNameValidity(name) {
            const trimmed = (name || '').trim();
            if (!trimmed) {
                return { valid: false, message: 'Please enter your full name.' };
            }
            const parts = trimmed.split(/\s+/).filter(Boolean);
            if (parts.length < 2) {
                return { valid: false, message: 'Please enter your full name (first and last name).' };
            }
            const namePartRegex = /^[A-Za-zÀ-ÖØ-öø-ÿ'.\-]+$/;
            for (const part of parts) {
                if (!namePartRegex.test(part)) {
                    return { valid: false, message: 'Full name should only contain letters, spaces, hyphens, or apostrophes.' };
                }
            }
            return { valid: true, message: '' };
        }

        function validateFullNameField(inputEl, isBlur = false, isSubmit = false) {
            if (!inputEl) return true;
            const val = inputEl.value;
            const parent = inputEl.closest('.auth-input-group');
            const clientErrorEl = document.getElementById('nameClientError');
            const serverErrorEl = document.getElementById('nameServerError');
            const leftIcon = parent ? parent.querySelector('.input-icon') : null;
            const label = parent ? parent.querySelector('.floating-label') : null;

            if (val.trim() === '') {
                if (isSubmit) {
                    inputEl.classList.add('is-invalid');
                    inputEl.style.borderColor = '#ef4444';
                    if (clientErrorEl) {
                        clientErrorEl.innerText = 'Please enter your full name (first and last name).';
                        clientErrorEl.style.display = 'block';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                    if (label) {
                        label.style.setProperty('color', '#ef4444', 'important');
                        label.style.setProperty('font-weight', '700', 'important');
                    }
                    return false;
                } else {
                    inputEl.classList.remove('is-invalid');
                    inputEl.style.borderColor = '#cbd5e1';
                    if (clientErrorEl) {
                        clientErrorEl.style.display = 'none';
                        clientErrorEl.innerText = '';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
                    if (label) {
                        label.style.removeProperty('color');
                        label.style.removeProperty('font-weight');
                    }
                    return true;
                }
            }

            const res = checkFullNameValidity(val);
            if (!res.valid) {
                if (isBlur || isSubmit) {
                    inputEl.classList.add('is-invalid');
                    inputEl.style.borderColor = '#ef4444';
                    if (clientErrorEl) {
                        clientErrorEl.innerText = res.message;
                        clientErrorEl.style.display = 'block';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                    if (label) {
                        label.style.setProperty('color', '#ef4444', 'important');
                        label.style.setProperty('font-weight', '700', 'important');
                    }
                    return false;
                }
                return false;
            } else {
                inputEl.classList.remove('is-invalid');
                inputEl.style.borderColor = '#cbd5e1';
                if (clientErrorEl) {
                    clientErrorEl.style.display = 'none';
                    clientErrorEl.innerText = '';
                }
                if (serverErrorEl) serverErrorEl.style.display = 'none';
                if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
                if (label) {
                    label.style.removeProperty('color');
                    label.style.removeProperty('font-weight');
                }
                return true;
            }
        }

        function autoCapitalizeWords(str) {
            if (!str) return '';
            return str.replace(/(?:^|[\s\-\'])([a-zà-öø-ÿ])/g, function(match, char) {
                return match.replace(char, char.toUpperCase());
            });
        }

        function handleNameBlur(inputEl) {
            if (inputEl && inputEl.value) {
                inputEl.value = autoCapitalizeWords(inputEl.value);
            }
            validateFullNameField(inputEl, true, false);
        }

        function handleNameInput(inputEl) {
            if (inputEl && inputEl.value) {
                const start = inputEl.selectionStart;
                const end = inputEl.selectionEnd;
                const originalVal = inputEl.value;
                const capitalizedVal = autoCapitalizeWords(originalVal);
                if (originalVal !== capitalizedVal) {
                    inputEl.value = capitalizedVal;
                    if (start !== null && end !== null) {
                        inputEl.setSelectionRange(start, end);
                    }
                }
            }

            const clientErrorEl = document.getElementById('nameClientError');
            const serverErrorEl = document.getElementById('nameServerError');
            const parent = inputEl.closest('.auth-input-group');
            const leftIcon = parent ? parent.querySelector('.input-icon') : null;
            const label = parent ? parent.querySelector('.floating-label') : null;

            if (inputEl.classList.contains('is-invalid')) {
                const res = checkFullNameValidity(inputEl.value);
                if (res.valid) {
                    inputEl.classList.remove('is-invalid');
                    inputEl.style.borderColor = '#cbd5e1';
                    if (clientErrorEl) {
                        clientErrorEl.style.display = 'none';
                        clientErrorEl.innerText = '';
                    }
                    if (serverErrorEl) serverErrorEl.style.display = 'none';
                    if (leftIcon) leftIcon.setAttribute('stroke', '#64748b');
                    if (label) {
                        label.style.removeProperty('color');
                        label.style.removeProperty('font-weight');
                    }
                } else if (clientErrorEl && clientErrorEl.style.display !== 'none') {
                    clientErrorEl.innerText = res.message;
                }
            }
        }

        function handleTermsChange(checkboxEl) {
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnWrapper = document.getElementById('submitBtnWrapper');
            const clientError = document.getElementById('termsClientError');
            const serverError = document.getElementById('termsServerError');
            const termsBox = document.getElementById('termsCheckboxBox');

            const isChecked = checkboxEl && checkboxEl.checked;

            if (isChecked) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.removeAttribute('disabled');
                    submitBtn.style.setProperty('background', '#22c55e', 'important');
                    submitBtn.style.setProperty('color', '#ffffff', 'important');
                    submitBtn.style.setProperty('opacity', '1', 'important');
                    submitBtn.style.setProperty('cursor', 'pointer', 'important');
                    submitBtn.style.setProperty('box-shadow', '0 4px 14px rgba(34, 197, 94, 0.35)', 'important');
                    submitBtn.style.setProperty('pointer-events', 'auto', 'important');
                }
                if (submitBtnWrapper) {
                    submitBtnWrapper.style.cursor = 'default';
                }
                if (clientError) clientError.style.display = 'none';
                if (serverError) serverError.style.display = 'none';
                if (termsBox) termsBox.classList.remove('is-invalid');
            } else {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.setAttribute('disabled', 'disabled');
                    submitBtn.style.setProperty('background', '#22c55e', 'important');
                    submitBtn.style.setProperty('color', '#ffffff', 'important');
                    submitBtn.style.setProperty('opacity', '0.55', 'important');
                    submitBtn.style.setProperty('cursor', 'not-allowed', 'important');
                    submitBtn.style.setProperty('box-shadow', 'none', 'important');
                    submitBtn.style.setProperty('pointer-events', 'none', 'important');
                }
                if (submitBtnWrapper) {
                    submitBtnWrapper.style.cursor = 'not-allowed';
                }
            }
        }

        function setRegisterRole(role) {
            const roleInput = document.getElementById('roleInput');
            const userBtn = document.getElementById('roleBtnUser');
            const studentBtn = document.getElementById('roleBtnStudent');
            const studentIdGroup = document.getElementById('studentIdGroup');
            const studentIdInput = document.getElementById('student_id');
            const studentSectionGroup = document.getElementById('studentSectionGroup');
            const sectionInput = document.getElementById('section');

            if (!roleInput || !userBtn || !studentBtn) return;

            roleInput.value = role;

            if (role === 'student') {
                studentBtn.style.background = '#ffffff';
                studentBtn.style.color = '#15803d';
                studentBtn.style.boxShadow = '0 1px 4px rgba(0,0,0,0.08)';

                userBtn.style.background = 'transparent';
                userBtn.style.color = '#64748b';
                userBtn.style.boxShadow = 'none';

                if (studentIdGroup) studentIdGroup.style.display = 'block';
                if (studentSectionGroup) studentSectionGroup.style.display = 'block';

                if (studentIdInput) {
                    studentIdInput.setAttribute('required', 'required');
                    setTimeout(() => {
                        studentIdInput.focus();
                        if (studentIdInput.value.trim() !== '') {
                            studentIdInput.classList.add('is-filled');
                        }
                    }, 50);
                }
                if (sectionInput) {
                    sectionInput.setAttribute('required', 'required');
                    if (sectionInput.value.trim() !== '') {
                        sectionInput.classList.add('is-filled');
                    }
                }
            } else {
                userBtn.style.background = '#ffffff';
                userBtn.style.color = '#15803d';
                userBtn.style.boxShadow = '0 1px 4px rgba(0,0,0,0.08)';

                studentBtn.style.background = 'transparent';
                studentBtn.style.color = '#64748b';
                studentBtn.style.boxShadow = 'none';

                if (studentIdGroup) studentIdGroup.style.display = 'none';
                if (studentSectionGroup) studentSectionGroup.style.display = 'none';

                if (studentIdInput) {
                    studentIdInput.removeAttribute('required');
                    studentIdInput.value = '';
                    studentIdInput.classList.remove('is-filled');
                    studentIdInput.style.borderColor = '#cbd5e1';
                }
                if (sectionInput) {
                    sectionInput.removeAttribute('required');
                    sectionInput.value = '';
                    sectionInput.classList.remove('is-filled');
                    sectionInput.style.borderColor = '#cbd5e1';
                }
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
            const colors = {
                1: '#ef4444', // Red
                2: '#f97316', // Orange
                3: '#eab308', // Amber
                4: '#84cc16', // Lime
                5: '#22c55e', // Green
            };
            const activeColor = colors[score] || '#e2e8f0';

            for (let i = 1; i <= 5; i++) {
                const bar = document.getElementById('sec-bar-' + i);
                if (bar) {
                    bar.style.backgroundColor = (i <= score && score > 0) ? activeColor : '#e2e8f0';
                }
            }

            // Dynamically enable Confirm Password ONLY when all 5 strong password requirements pass
            const confirmInput = document.getElementById('password_confirmation');
            const confirmToggleBtn = document.getElementById('confirmPassToggleBtn');
            if (confirmInput) {
                if (score === 5) {
                    confirmInput.disabled = false;
                    confirmInput.setAttribute('required', 'required');
                    confirmInput.removeAttribute('title');
                    if (confirmToggleBtn) {
                        confirmToggleBtn.disabled = false;
                        confirmToggleBtn.style.pointerEvents = 'auto';
                        confirmToggleBtn.style.opacity = '1';
                    }
                } else {
                    confirmInput.disabled = true;
                    confirmInput.removeAttribute('required');
                    confirmInput.value = '';
                    confirmInput.classList.remove('is-invalid', 'is-valid');
                    confirmInput.style.borderColor = '#cbd5e1';
                    confirmInput.setAttribute('title', 'Please complete all password security requirements above to confirm your password');
                    if (confirmToggleBtn) {
                        confirmToggleBtn.disabled = true;
                        confirmToggleBtn.style.pointerEvents = 'none';
                        confirmToggleBtn.style.opacity = '0.35';
                    }
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
                    document.querySelectorAll('.password-field-wrap, .auth-input-group').forEach(el => el.classList.remove('has-error'));
                } else {
                    document.getElementById('passwordAlertMessage').innerText = 'Password does not meet all security requirements. Missing: ' + missing.join(', ') + '.';
                }
            }
        }

        const checkPasswordSecurity = checkPasswordRequirements;

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

        function togglePass(id, btn) {
            const input = document.getElementById(id);
            if (!input) return;
            const slashIcon = btn.querySelector('.eye-slash-icon');
            const openIcon = btn.querySelector('.eye-open-icon');

            if (input.type === 'password') {
                input.type = 'text';
                if (slashIcon) slashIcon.style.display = 'none';
                if (openIcon) openIcon.style.display = 'block';
            } else {
                input.type = 'password';
                if (slashIcon) slashIcon.style.display = 'block';
                if (openIcon) openIcon.style.display = 'none';
            }
            input.focus();
        }

        // Mailcheck.js domain extension configuration for Sign Up screen
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
            status: 'idle', // 'idle' | 'suggested' | 'accepted' | 'dismissed' | 'clean'
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
            inputEl.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.25)';
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

        function checkAvailabilityAndConfirm(inputEl, val, iconBox, validIcon, invalidIcon, subtext) {
            fetch('/auth/check-email?email=' + encodeURIComponent(val))
                .then(res => res.json())
                .then(data => {
                    if (inputEl.value.trim() !== val) return;
                    if (data.exists) {
                        inputEl.classList.remove('is-valid');
                        inputEl.classList.add('is-invalid');
                        inputEl.style.borderColor = '#ef4444';
                        inputEl.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                        triggerStatusIcon(iconBox, invalidIcon, validIcon, true);
                        if (subtext) {
                            subtext.style.display = 'block';
                            subtext.style.color = '#ef4444';
                            subtext.innerText = 'This email address is already registered.';
                        }
                        const parent = inputEl.closest('.auth-input-group');
                        const leftIcon = parent ? parent.querySelector('.input-icon') : null;
                        if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                        const label = parent ? parent.querySelector('.floating-label') : null;
                        if (label) {
                            label.style.setProperty('color', '#ef4444', 'important');
                            label.style.setProperty('font-weight', '700', 'important');
                        }
                    } else {
                        showEmailConfirmedState(inputEl, iconBox, validIcon, invalidIcon, subtext);
                    }
                })
                .catch(() => {
                    showEmailConfirmedState(inputEl, iconBox, validIcon, invalidIcon, subtext);
                });
        }

        function handleEmailBlur(inputEl) {
            const val = inputEl.value.trim();
            const parent = inputEl.closest('.auth-input-group');
            if (!parent) return;

            const iconBox = parent.querySelector('.email-status-icon');
            const validIcon = parent.querySelector('.email-icon-valid');
            const invalidIcon = parent.querySelector('.email-icon-invalid');
            const subtext = parent.querySelector('.email-subtext');
            const suggestionBox = document.getElementById('mailcheckSuggestionBox');
            const suggestionLink = document.getElementById('mailcheckSuggestionLink');

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (val === '' || !emailRegex.test(val)) {
                if (suggestionBox) suggestionBox.style.display = 'none';
                mailcheckState.status = 'idle';
                mailcheckState.currentSuggestion = null;
                return;
            }

            // If user already accepted suggestion for this exact value, keep confirmed
            if (mailcheckState.status === 'accepted' && mailcheckState.lastCheckedValue === val) {
                return;
            }

            if (typeof Mailcheck === 'undefined') {
                mailcheckState.status = 'clean';
                mailcheckState.lastCheckedValue = val;
                checkAvailabilityAndConfirm(inputEl, val, iconBox, validIcon, invalidIcon, subtext);
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

                    // Do NOT show green "Email confirmed" checkmark state while suggestion is pending
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

                    // No typo detected and basic format passed -> check availability and confirm
                    checkAvailabilityAndConfirm(inputEl, val, iconBox, validIcon, invalidIcon, subtext);
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

            const parent = inputEl.closest('.auth-input-group');
            const iconBox = parent ? parent.querySelector('.email-status-icon') : null;
            const validIcon = parent ? parent.querySelector('.email-icon-valid') : null;
            const invalidIcon = parent ? parent.querySelector('.email-icon-invalid') : null;
            const subtext = parent ? parent.querySelector('.email-subtext') : null;

            checkAvailabilityAndConfirm(inputEl, acceptedEmail, iconBox, validIcon, invalidIcon, subtext);
        }

        function dismissMailcheckSuggestion() {
            mailcheckState.status = 'dismissed';
            const suggestionBox = document.getElementById('mailcheckSuggestionBox');
            if (suggestionBox) suggestionBox.style.display = 'none';

            const inputEl = document.getElementById('email');
            if (inputEl) {
                inputEl.classList.remove('is-invalid');
                const val = inputEl.value.trim();
                const parent = inputEl.closest('.auth-input-group');
                const iconBox = parent ? parent.querySelector('.email-status-icon') : null;
                const validIcon = parent ? parent.querySelector('.email-icon-valid') : null;
                const invalidIcon = parent ? parent.querySelector('.email-icon-invalid') : null;
                const subtext = parent ? parent.querySelector('.email-subtext') : null;
                if (val !== '') {
                    checkAvailabilityAndConfirm(inputEl, val, iconBox, validIcon, invalidIcon, subtext);
                }
            }
        }

        let emailCheckTimeout = null;

        function validateLiveEmail(inputEl, mode = 'register') {
            const val = inputEl.value.trim();
            const parent = inputEl.closest('.auth-input-group');
            if (!parent) return;

            const iconBox = parent.querySelector('.email-status-icon');
            const validIcon = parent.querySelector('.email-icon-valid');
            const invalidIcon = parent.querySelector('.email-icon-invalid');
            const subtext = parent.querySelector('.email-subtext');
            const suggestionBox = document.getElementById('mailcheckSuggestionBox');

            function isCompleteEmail(email) {
                const pattern = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
                return pattern.test(email.trim());
            }

            if (emailCheckTimeout) clearTimeout(emailCheckTimeout);

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

            // If user modifies input after suggestion was shown ("typed on without correcting")
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

            if (!isCompleteEmail(val)) {
                inputEl.classList.remove('is-valid');
                inputEl.classList.add('is-invalid');
                inputEl.style.borderColor = '#ef4444';
                inputEl.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
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
                if (suggestionBox) suggestionBox.style.display = 'none';
                mailcheckState.status = 'idle';
                mailcheckState.currentSuggestion = null;
                return;
            }

            if (iconBox) iconBox.style.display = 'none';
            if (subtext) subtext.style.display = 'none';

            emailCheckTimeout = setTimeout(function() {
                fetch('/auth/check-email?email=' + encodeURIComponent(val))
                    .then(res => res.json())
                    .then(data => {
                        if (inputEl.value.trim() !== val) return;

                        if (data.exists) {
                            inputEl.classList.remove('is-valid');
                            inputEl.classList.add('is-invalid');
                            inputEl.style.borderColor = '#ef4444';
                            inputEl.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                            triggerStatusIcon(iconBox, invalidIcon, validIcon, true);
                            if (subtext) {
                                subtext.style.display = 'block';
                                subtext.style.color = '#ef4444';
                                subtext.innerText = 'This email address is already registered.';
                            }
                            if (leftIcon) leftIcon.setAttribute('stroke', '#ef4444');
                            if (label) {
                                label.style.setProperty('color', '#ef4444', 'important');
                                label.style.setProperty('font-weight', '700', 'important');
                            }
                            if (suggestionBox) suggestionBox.style.display = 'none';
                        } else {
                            if (typeof Mailcheck !== 'undefined') {
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
                                        showEmailConfirmedState(inputEl, iconBox, validIcon, invalidIcon, subtext);
                                    }
                                });
                            } else {
                                showEmailConfirmedState(inputEl, iconBox, validIcon, invalidIcon, subtext);
                            }
                        }
                    })
                    .catch(() => {});
            }, 250);
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

            const studentIdInput = document.getElementById('student_id');
            if (studentIdInput) {
                studentIdInput.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.style.borderColor = '#cbd5e1';
                    }
                });
            }

            const sectionInput = document.getElementById('section');
            if (sectionInput) {
                sectionInput.addEventListener('input', function() {
                    handleSectionInput(this);
                });
            }

            const termsCheckbox = document.getElementById('terms_agreed');
            if (termsCheckbox) {
                handleTermsChange(termsCheckbox);
                termsCheckbox.addEventListener('change', function() {
                    handleTermsChange(this);
                });
            }

            const submitBtnWrapper = document.getElementById('submitBtnWrapper');
            if (submitBtnWrapper) {
                submitBtnWrapper.addEventListener('click', function(e) {
                    const terms = document.getElementById('terms_agreed');
                    if (!terms || !terms.checked) {
                        e.preventDefault();
                        e.stopPropagation();
                        const termsBox = document.getElementById('termsCheckboxBox');
                        if (termsBox) termsBox.classList.add('is-invalid');
                        const termsClientError = document.getElementById('termsClientError');
                        if (termsClientError) {
                            termsClientError.style.display = 'block';
                            termsClientError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        if (terms) terms.focus();
                        return false;
                    }
                });
            }

            const passInputOnLoad = document.getElementById('password');
            if (passInputOnLoad && passInputOnLoad.value) {
                checkPasswordRequirements(passInputOnLoad.value);
            }

            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    const termsCheckbox = document.getElementById('terms_agreed');
                    const termsBox = document.getElementById('termsCheckboxBox');
                    if (termsCheckbox && !termsCheckbox.checked) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (termsBox) termsBox.classList.add('is-invalid');
                        const termsClientError = document.getElementById('termsClientError');
                        if (termsClientError) {
                            termsClientError.style.display = 'block';
                            termsClientError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        termsCheckbox.focus();
                        return false;
                    }

                    const nameInput = document.getElementById('name');
                    if (nameInput) {
                        const isNameValid = validateFullNameField(nameInput, false, true);
                        if (!isNameValid) {
                            e.preventDefault();
                            e.stopPropagation();
                            nameInput.focus();
                            return false;
                        }
                    }

                    const emailInput = document.getElementById('email');
                    const email = emailInput ? emailInput.value.trim().toLowerCase() : '';
                    const domain = email.split('@')[1] || '';

                    // Fast client-side pre-check: obviously non-Google consumer domains.
                    // This is NOT exhaustive (can't check MX records from the browser) —
                    // it's just an early nudge. The server-side MX check is authoritative.
                    const knownNonGoogleDomains = [
                        'yahoo.com', 'ymail.com', 'outlook.com', 'hotmail.com', 'live.com',
                        'msn.com', 'icloud.com', 'me.com', 'mac.com', 'aol.com', 'protonmail.com',
                    ];

                    if (knownNonGoogleDomains.includes(domain)) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (emailInput) {
                            emailInput.classList.add('is-invalid');
                            const subtext = emailInput.closest('.auth-input-group')?.querySelector('.email-subtext');
                            if (subtext) {
                                subtext.textContent = 'Please use an email that works with mail.google.com (Gmail or Google Workspace).';
                                subtext.style.display = 'block';
                                subtext.style.color = '#ef4444';
                            }
                            emailInput.focus();
                        }
                        return false;
                    }

                    // Still block submit if a Mailcheck typo suggestion is pending
                    // and hasn't been accepted or dismissed (from earlier fix).
                    if (typeof mailcheckState !== 'undefined' && mailcheckState.status === 'suggested') {
                        e.preventDefault();
                        e.stopPropagation();

                        const suggestionBox = document.getElementById('mailcheckSuggestionBox');
                        if (suggestionBox) {
                            suggestionBox.style.display = 'flex';
                            suggestionBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }

                        if (emailInput) {
                            emailInput.classList.add('is-invalid');
                            emailInput.focus();
                        }
                        return false;
                    }

                    const roleInput = document.getElementById('roleInput');
                    if (roleInput && roleInput.value === 'student') {
                        const sidInput = document.getElementById('student_id');
                        if (!sidInput || sidInput.value.trim() === '') {
                            e.preventDefault();
                            e.stopPropagation();

                            const msg = 'Student ID number is required for student registration.';
                            showInlineError(msg);
                            showToast(msg, 'error');

                            if (sidInput) {
                                sidInput.focus();
                                sidInput.style.borderColor = '#ef4444';
                            }
                            return false;
                        }

                        const secInput = document.getElementById('section');
                        if (secInput) {
                            const isSecValid = validateSectionField(secInput, false, true);
                            if (!isSecValid) {
                                e.preventDefault();
                                e.stopPropagation();
                                secInput.focus();
                                return false;
                            }
                        }
                    }

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
                        const wrap = passInput.closest('.auth-input-group') || passInput.closest('.password-field-wrap');
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
                        const wrap = confirmInput.closest('.auth-input-group') || confirmInput.closest('.password-field-wrap');
                        if (wrap) {
                            wrap.classList.add('has-error', 'shake');
                            setTimeout(() => wrap.classList.remove('shake'), 400);
                        }
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
