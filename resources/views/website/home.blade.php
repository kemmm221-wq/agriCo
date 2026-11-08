<x-website-layout>
    <x-slot name="title">Home</x-slot>

    <style>
        /* ── HERO ──────────────────────────────────────── */
        .ws-hero {
            position: relative;
            min-height: 92vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(5, 31, 32, 0.88) 0%, rgba(11, 43, 38, 0.85) 50%, rgba(5, 31, 32, 0.92) 100%), url('/images/hero-bg.jpg') center/cover no-repeat;
        }
        .ws-hero-bg-circle {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(35, 83, 71, 0.35), transparent 70%);
            animation: floatCircle 8s ease-in-out infinite;
        }
        @keyframes floatCircle {
            0%,100% { transform: translateY(0) scale(1); }
            50%      { transform: translateY(-24px) scale(1.04); }
        }
        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(32px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 860px;
            margin: 0 auto;
            padding: 90px 24px;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(11, 43, 38, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid #8EB69B;
            color: #DAF1DE;
            font-size: 13px;
            font-weight: 800;
            padding: 6px 20px;
            border-radius: 999px;
            margin-bottom: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            animation: fadeInUp 0.6s ease both;
        }
        .hero-badge span {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #DAF1DE;
            display: inline-block;
            box-shadow: 0 0 8px #DAF1DE;
            animation: pulse 2s ease infinite;
        }
        @keyframes pulse {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:0.5; transform:scale(1.4); }
        }
        .hero-title {
            font-size: clamp(2.4rem, 6vw, 4.2rem);
            font-weight: 900;
            color: #ffffff;
            line-height: 1.12;
            letter-spacing: -1.5px;
            margin: 0 0 20px 0;
            text-shadow: 0 4px 24px rgba(0, 0, 0, 0.9);
            animation: fadeInUp 0.7s ease 0.1s both;
        }
        .hero-title .highlight {
            color: #4ade80;
            background: linear-gradient(135deg, #ffffff 0%, #86efac 50%, #4ade80 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-sub {
            font-size: clamp(1rem, 2.2vw, 1.2rem);
            color: #DAF1DE;
            font-weight: 500;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.9);
            max-width: 720px;
            margin: 0 auto 40px;
            line-height: 1.7;
            animation: fadeInUp 0.7s ease 0.2s both;
        }
        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            animation: fadeInUp 0.7s ease 0.3s both;
        }
        .hero-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 34px;
            background: linear-gradient(135deg, #235347, #163832);
            color: #DAF1DE;
            font-size: 15px;
            font-weight: 700;
            border-radius: 12px;
            border: 1.5px solid #8EB69B;
            text-decoration: none;
            box-shadow: 0 10px 30px rgba(35, 83, 71, 0.6);
            transition: all 0.25s ease;
        }
        .hero-btn-primary:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #163832, #0B2B26);
            box-shadow: 0 14px 38px rgba(35, 83, 71, 0.8);
            border-color: #DAF1DE;
        }
        .hero-btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 30px;
            border: 1.5px solid rgba(142, 182, 155, 0.6);
            background: rgba(11, 43, 38, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #DAF1DE;
            font-size: 15px;
            font-weight: 700;
            border-radius: 12px;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            transition: all 0.25s ease;
        }
        .hero-btn-outline:hover {
            background: rgba(35, 83, 71, 0.8);
            border-color: #4ade80;
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Stats strip */
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 32px;
            flex-wrap: wrap;
            margin-top: 56px;
            animation: fadeInUp 0.7s ease 0.45s both;
        }
        .hero-stat {
            text-align: center;
            background: rgba(11, 43, 38, 0.5);
            border: 1px solid rgba(142, 182, 155, 0.2);
            padding: 16px 28px;
            border-radius: 16px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        }
        .hero-stat:hover {
            transform: translateY(-3px);
            border-color: rgba(74, 222, 128, 0.4);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        .hero-stat-num {
            font-size: 2.4rem;
            font-weight: 900;
            color: #DAF1DE;
            line-height: 1;
            letter-spacing: -1px;
            text-shadow: 0 2px 14px rgba(218, 241, 222, 0.3);
        }
        .hero-stat-label {
            font-size: 13.5px;
            color: #8EB69B;
            margin-top: 4px;
            font-weight: 600;
        }

        /* Responsive 2-per-row stats on mobile & tablet */
        @media (max-width: 820px) {
            .hero-content {
                padding: 60px 16px 50px !important;
            }
            .hero-stats {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 14px !important;
                margin-top: 38px !important;
                width: 100% !important;
                max-width: 460px !important;
                margin-left: auto !important;
                margin-right: auto !important;
                padding: 0 4px !important;
                box-sizing: border-box !important;
            }
            .hero-stat {
                width: 100% !important;
                box-sizing: border-box !important;
                padding: 16px 12px !important;
                border-radius: 16px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                min-width: 0 !important;
            }
            .hero-stat-num {
                font-size: clamp(1.75rem, 6vw, 2.15rem) !important;
                letter-spacing: -0.5px !important;
                line-height: 1.1 !important;
            }
            .hero-stat-label {
                font-size: 12.5px !important;
                margin-top: 5px !important;
                line-height: 1.3 !important;
                white-space: normal !important;
                word-break: break-word !important;
                text-align: center !important;
            }
        }

        @media (max-width: 400px) {
            .hero-content {
                padding: 50px 12px 40px !important;
            }
            .hero-stats {
                gap: 10px !important;
                padding: 0 2px !important;
                margin-top: 30px !important;
            }
            .hero-stat {
                padding: 13px 8px !important;
                border-radius: 14px !important;
            }
            .hero-stat-num {
                font-size: 1.6rem !important;
            }
            .hero-stat-label {
                font-size: 11.5px !important;
            }
        }

        /* ── FEATURES ──────────────────────────────────── */
        .ws-section {
            padding: 96px 24px;
            background: var(--ws-section-bg, #051F20);
        }
        .ws-section-inner {
            max-width: 1200px;
            margin: 0 auto;
        }
        .ws-section-label {
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #8EB69B;
            margin-bottom: 14px;
        }
        .ws-section-title {
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 900;
            color: #DAF1DE;
            letter-spacing: -0.8px;
            margin: 0 0 16px 0;
            line-height: 1.15;
        }
        .ws-section-sub {
            font-size: 16px;
            color: #8EB69B;
            max-width: 520px;
            line-height: 1.7;
            margin: 0 0 56px 0;
        }

        /* Feature cards */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }
        .feature-card {
            background: linear-gradient(145deg, #0B2B26 0%, #163832 100%);
            border-radius: 20px;
            padding: 32px 28px;
            border: 1px solid rgba(142, 182, 155, 0.2);
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.35);
            transition: all 0.3s ease;
            cursor: default;
        }
        .feature-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 16px 40px rgba(34, 197, 94, 0.25), 0 20px 50px rgba(0, 0, 0, 0.45);
            border-color: #22c55e !important;
        }
        .feature-icon {
            width: 52px; height: 52px;
            border-radius: 16px;
            background: rgba(5, 31, 32, 0.7);
            border: 1.5px solid rgba(142, 182, 155, 0.3);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 20px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .feature-icon svg { transition: stroke 0.25s ease; stroke: #4ADE80 !important; }
        .feature-card:hover .feature-icon {
            background: linear-gradient(135deg, #235347, #163832);
            border-color: #DAF1DE;
            box-shadow: 0 4px 16px rgba(35, 83, 71, 0.5);
            transform: scale(1.08);
        }
        .feature-card:hover .feature-icon svg {
            stroke: #DAF1DE !important;
        }
        .feature-card h3 {
            font-size: 17px;
            font-weight: 800;
            color: #DAF1DE;
            margin: 0 0 10px 0;
            transition: color 0.25s ease;
        }
        .feature-card:hover h3 {
            color: #FFFFFF !important;
        }
        .feature-card p {
            font-size: 14px;
            color: #8EB69B;
            line-height: 1.65;
            margin: 0 0 20px 0;
        }
        .feature-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 700;
            color: #DAF1DE;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .feature-link:hover,
        .feature-card:hover .feature-link { gap: 8px; color: #DAF1DE !important; }

        /* ── HOW IT WORKS ──────────────────────────────── */
        .how-section { background: var(--how-section-bg, #0B2B26); }
        .how-section .ws-section-title { color: #DAF1DE; }
        .how-section .ws-section-sub   { color: #8EB69B; }
        .how-section .ws-section-label { color: #8EB69B; }
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
        }
        .step-card {
            background: linear-gradient(145deg, #163832, #051F20);
            border: 1px solid rgba(142, 182, 155, 0.2);
            border-radius: 20px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .step-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.25), 0 10px 24px rgba(0, 0, 0, 0.4) !important;
            border-color: #22c55e !important;
            background: linear-gradient(145deg, #1c453e, #0B2B26);
        }
        .step-num {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #235347, #0B2B26);
            border: 1px solid #8EB69B;
            color: #DAF1DE;
            font-size: 18px;
            font-weight: 900;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px;
            box-shadow: 0 4px 14px rgba(35, 83, 71, 0.5);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .step-card:hover .step-num {
            background: #22c55e !important;
            border-color: #22c55e !important;
            color: #FFFFFF !important;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.45) !important;
            transform: scale(1.08);
        }
        .step-card h4, .step-card h3 {
            font-size: 16px;
            font-weight: 800;
            color: #DAF1DE;
            margin: 0 0 8px 0;
            transition: color 0.2s ease;
        }
        .step-card:hover h4,
        .step-card:hover h3 {
            color: #4ade80 !important;
        }
        .step-card p {
            font-size: 13.5px;
            color: #8EB69B;
            line-height: 1.6;
            margin: 0;
        }

        /* ── CTA BANNER ────────────────────────────────── */
        .cta-banner {
            background: linear-gradient(135deg, #0B2B26 0%, #163832 50%, #235347 100%);
            border: 1px solid #8EB69B;
            border-radius: 28px;
            padding: 64px 48px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin: 0 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .cta-banner::before {
            content:'';
            position:absolute; inset:0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23DAF1DE' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .cta-banner h2 {
            font-size: clamp(1.8rem,4vw,2.8rem);
            font-weight: 900;
            color: #DAF1DE;
            margin: 0 0 14px 0;
            letter-spacing: -0.6px;
            position: relative;
        }
        .cta-banner p {
            font-size: 16px;
            color: #8EB69B;
            margin: 0 0 36px 0;
            max-width: 480px;
            margin-left: auto; margin-right: auto;
            position: relative;
        }
        .cta-banner-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 38px;
            background: #DAF1DE;
            color: #051F20;
            font-size: 15px;
            font-weight: 800;
            border-radius: 12px;
            text-decoration: none;
            box-shadow: 0 10px 30px rgba(218, 241, 222, 0.3);
            transition: all 0.25s ease;
            position: relative;
        }
        .cta-banner-btn:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 14px 40px rgba(218, 241, 222, 0.45);
        }

        /* ── LIGHT MODE: Ultra-Luxurious State-of-the-Art Design ── */

        /* Hero Overlay */
        html.light-mode .ws-hero {
            background:
                linear-gradient(170deg,
                    rgba(244, 251, 246, 0.96) 0%,
                    rgba(232, 245, 237, 0.94) 50%,
                    rgba(248, 252, 249, 0.98) 100%
                ),
                url('/images/hero-bg.jpg') center/cover no-repeat;
        }
        html.light-mode .ws-hero-bg-circle {
            background: radial-gradient(circle, rgba(34, 197, 94, 0.16), transparent 70%);
        }
        html.light-mode .hero-badge {
            background: rgba(255, 255, 255, 0.95);
            border: 1.5px solid #22c55e;
            color: #0f172a;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.15);
        }
        html.light-mode .hero-badge span {
            background: #22c55e;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.6);
        }
        html.light-mode .hero-title {
            color: #0f172a;
            text-shadow: none;
        }
        html.light-mode .hero-title .highlight {
            background: linear-gradient(135deg, #15803d 0%, #22c55e 50%, #0d9488 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 8px rgba(34, 197, 94, 0.2));
        }
        html.light-mode .hero-sub {
            color: #334155;
            font-weight: 500;
            text-shadow: none;
        }
        html.light-mode .hero-btn-primary {
            background: #22c55e !important;
            border-color: #22c55e !important;
            color: #FFFFFF !important;
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.35) !important;
        }
        html.light-mode .hero-btn-primary:hover {
            background: #16a34a !important;
            box-shadow: 0 12px 32px rgba(34, 197, 94, 0.5) !important;
        }
        html.light-mode .hero-btn-outline {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: #0f172a;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }
        html.light-mode .hero-btn-outline:hover {
            background: #f0fdf4 !important;
            border-color: #22c55e !important;
            color: #16a34a !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.35) !important;
        }
        html.light-mode .hero-stat {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(12px);
        }
        html.light-mode .hero-stat-num {
            color: #15803d;
            text-shadow: none;
        }
        html.light-mode .hero-stat-label { color: #475569; font-weight: 700; }

        /* Sections backgrounds */
        html.light-mode {
            --ws-section-bg: #F8FAFC;
            --how-section-bg: #F0FDF4;
            --cta-section-bg: #F8FAFC;
        }
        html.light-mode .ws-section-label    { color: #15803d; font-weight: 800; letter-spacing: 0.1em; }
        html.light-mode .ws-section-title    { color: #0f172a; }
        html.light-mode .ws-section-sub      { color: #475569; }
        html.light-mode .how-section .ws-section-title { color: #0f172a; }
        html.light-mode .how-section .ws-section-sub   { color: #475569; }
        html.light-mode .how-section .ws-section-label { color: #15803d; }

        /* Feature cards */
        html.light-mode .feature-card {
            background: #FFFFFF !important;
            border: 1.5px solid #e2e8f0 !important;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05) !important;
            border-radius: 20px !important;
        }
        html.light-mode .feature-card:hover {
            border-color: #22c55e !important;
            box-shadow: 0 16px 36px rgba(34, 197, 94, 0.18) !important;
            transform: translateY(-6px) scale(1.02) !important;
        }
        html.light-mode .feature-icon {
            background: #f0fdf4 !important;
            border: 1.5px solid #86efac !important;
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.1) !important;
        }
        html.light-mode .feature-icon svg { stroke: #16a34a !important; }
        html.light-mode .feature-card:hover .feature-icon {
            background: #22c55e !important;
            border-color: #22c55e !important;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.35) !important;
            transform: scale(1.08) !important;
        }
        html.light-mode .feature-card:hover .feature-icon svg { stroke: #FFFFFF !important; }
        html.light-mode .feature-card h3 { color: #0f172a !important; font-weight: 800 !important; }
        html.light-mode .feature-card:hover h3 { color: #16a34a !important; }
        html.light-mode .feature-card p  { color: #334155 !important; font-weight: 500 !important; line-height: 1.65 !important; }
        html.light-mode .feature-link    { color: #16a34a !important; font-weight: 800 !important; }
        html.light-mode .feature-link:hover,
        html.light-mode .feature-card:hover .feature-link { color: #15803d !important; gap: 8px !important; }

        /* Step cards */
        html.light-mode .step-card {
            background: #FFFFFF !important;
            border: 1.5px solid #e2e8f0 !important;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05) !important;
            border-radius: 20px !important;
        }
        html.light-mode .step-card:hover {
            background: #FFFFFF !important;
            border-color: #22c55e !important;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.25), 0 10px 24px rgba(15, 23, 42, 0.08) !important;
            transform: translateY(-2px) !important;
        }
        html.light-mode .step-num {
            background: linear-gradient(135deg, #22c55e, #16a34a) !important;
            border-color: #22c55e !important;
            color: #FFFFFF !important;
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35) !important;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        html.light-mode .step-card:hover .step-num {
            background: linear-gradient(135deg, #22c55e, #16a34a) !important;
            border-color: #22c55e !important;
            color: #FFFFFF !important;
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.45) !important;
            transform: scale(1.1) !important;
        }
        html.light-mode .step-card h4,
        html.light-mode .step-card h3 { color: #0f172a !important; font-weight: 800 !important; font-size: 16px !important; }
        html.light-mode .step-card:hover h4,
        html.light-mode .step-card:hover h3 { color: #16a34a !important; }
        html.light-mode .step-card p  { color: #334155 !important; font-weight: 500 !important; line-height: 1.6 !important; }

        /* CTA Banner */
        html.light-mode .cta-banner {
            background: linear-gradient(135deg, #15803d 0%, #16a34a 50%, #059669 100%);
            border: 1px solid #86efac;
            box-shadow: 0 16px 48px rgba(34, 197, 94, 0.25);
        }
        html.light-mode .cta-banner h2,
        html.light-mode .cta-banner p { color: #FFFFFF !important; }
        html.light-mode .cta-banner-btn {
            background: #FFFFFF;
            color: #15803d;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
        }
        html.light-mode .cta-banner-btn:hover {
            background: #f0fdf4;
            color: #16a34a;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.25);
            transform: translateY(-3px);
        }
    </style>

    {{-- ── HERO ─────────────────────────────────────────────── --}}
    <section class="ws-hero">
        {{-- BG decoration circles --}}
        <div class="ws-hero-bg-circle" style="width:600px;height:600px;top:-180px;right:-160px;animation-delay:0s;"></div>
        <div class="ws-hero-bg-circle" style="width:400px;height:400px;bottom:-120px;left:-100px;animation-delay:3s;"></div>

        <div class="hero-content">


            <h1 class="hero-title ui-reveal" style="font-size: clamp(2rem, 4.5vw, 3.4rem); line-height: 1.15;">
                <span class="highlight">AgriCo</span>: Web-Based<br/>
                Soil Color Determination<br/>
                &amp; Scan Reporting System
            </h1>

            <p class="hero-sub ui-reveal" style="max-width: 680px;">
                A Web-Based Soil Color Determination and Scan-Based Soil Reporting System with Location Mapping and Offline Mobile Application Support.
            </p>

@php
    $hUser = Auth::guard('web')->user() ?? Auth::user() ?? Auth::guard('admin')->user();
    if (!$hUser && session()->has('user_id')) {
        $hUser = \App\Models\User::where('user_id', session('user_id'))->first();
    }
    if (!$hUser && session()->has('admin_id')) {
        $hUser = \App\Models\User::where('user_id', session('admin_id'))->first();
    }
    $isHAuth = (bool) $hUser;
    $isHAdmin = $isHAuth && (in_array(strtolower($hUser->role ?? ''), ['admin', 'superadmin', 'super admin']) || Auth::guard('admin')->check());
    $isHApproved = $isHAuth && ($isHAdmin || in_array(strtolower(trim($hUser->status ?? '')), ['active', 'approved']));
    $isHPending  = $isHAuth && !$isHApproved;
@endphp

            <div class="hero-actions ui-reveal">
                <a href="{{ route('website.scan') }}"
                   @if($isHPending) onclick="event.preventDefault(); showAccountPendingModal('Account Pending Approval', 'Your account is awaiting admin approval. Scanning and Map access will be enabled once approved.');"
                   @elseif(!$isHAuth) onclick="event.preventDefault(); showGlobalLoginModal('scan');" @endif
                   class="hero-btn-primary">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                    Scan Soil
                </a>
                <a href="{{ route('website.download') }}"
                   @if(\App\Models\Application::isMaintenanceMode()) onclick="event.preventDefault(); showSystemMaintenanceModal();" @endif
                   class="hero-btn-outline">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download Application
                </a>
            </div>

            {{-- Live Stats --}}
            <div class="hero-stats">
                <div class="hero-stat ui-reveal">
                    <div class="hero-stat-num">{{ number_format($totalScans) }}</div>
                    <div class="hero-stat-label">Soil Scans</div>
                </div>
                <div class="hero-stat ui-reveal">
                    <div class="hero-stat-num">{{ number_format($totalColors) }}</div>
                    <div class="hero-stat-label">Munsell Colors</div>
                </div>
                <div class="hero-stat ui-reveal">
                    <div class="hero-stat-num">{{ number_format($totalUsers) }}</div>
                    <div class="hero-stat-label">Total Users</div>
                </div>
                <div class="hero-stat ui-reveal">
                    <div class="hero-stat-num">{{ number_format($totalSoils) }}</div>
                    <div class="hero-stat-label">Pin Soil Location</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── FEATURES ─────────────────────────────────────────── --}}
    <section class="ws-section" id="features">
        <div class="ws-section-inner">
            <span class="ws-section-label ui-scroll-reveal">What We Offer</span>
            <h2 class="ws-section-title ui-scroll-reveal">Everything You Need<br/>For Soil Analysis</h2>
            <p class="ws-section-sub ui-scroll-reveal">
                A complete toolkit for soil scanning, Munsell classification, geographic mapping, and user management — all in one platform.
            </p>

            <div class="features-grid">
                {{-- Scan --}}
                <div class="feature-card ui-scroll-reveal">
                    <div class="feature-icon" style="background:linear-gradient(135deg,#0B2B26,#235347); border:1px solid #8EB69B;">
                        <svg width="26" height="26" fill="none" stroke="#DAF1DE" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <h3>Soil Scan History</h3>
                    <p>Track every soil scan with detailed records including date, location, and Munsell classification data.</p>
                    <a href="{{ route('website.scan') }}"
                       @if($isHPending) onclick="event.preventDefault(); showAccountPendingModal('Account Pending Approval', 'Your account is awaiting admin approval. Scanning and Map access will be enabled once approved.');"
                       @elseif(!$isHAuth) onclick="event.preventDefault(); showGlobalLoginModal('scan');" @endif
                       class="feature-link">
                        View Scans <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Map --}}
                <div class="feature-card ui-scroll-reveal">
                    <div class="feature-icon" style="background:linear-gradient(135deg,#0B2B26,#235347); border:1px solid #8EB69B;">
                        <svg width="26" height="26" fill="none" stroke="#DAF1DE" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/>
                            <line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>
                        </svg>
                    </div>
                    <h3>Interactive Soil Map</h3>
                    <p>Visualize soil scan locations on an interactive map with real-time pins showing soil classification data.</p>
                    <a href="{{ route('website.map') }}"
                       class="feature-link">
                        Open Map <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Munsell --}}
                <div class="feature-card ui-scroll-reveal">
                    <div class="feature-icon" style="background:linear-gradient(135deg,#0B2B26,#235347); border:1px solid #8EB69B;">
                        <svg width="26" height="26" fill="none" stroke="#DAF1DE" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="3"/>
                            <line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>
                        </svg>
                    </div>
                    <h3>Munsell Color Chart</h3>
                    <p>Browse the complete Munsell soil color reference chart with hue, value, and chroma classifications.</p>
                    <a href="{{ route('website.munsell') }}" class="feature-link">
                        Browse Colors <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- FAQ --}}
                <div class="feature-card ui-scroll-reveal">
                    <div class="feature-icon" style="background:linear-gradient(135deg,#0B2B26,#235347); border:1px solid #8EB69B;">
                        <svg width="26" height="26" fill="none" stroke="#DAF1DE" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <h3>Frequently Asked Questions</h3>
                    <p>Find answers to common questions about soil scanning, Munsell classification, and the AgriCo platform.</p>
                    <a href="{{ route('website.faq') }}" class="feature-link">
                        Read FAQs <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ── HOW IT WORKS ─────────────────────────────────────── --}}
    <section class="ws-section how-section">
        <div class="ws-section-inner">
            <span class="ws-section-label ui-scroll-reveal">Simple Process</span>
            <h2 class="ws-section-title ui-scroll-reveal">How AgriCo Works</h2>
            <p class="ws-section-sub ui-scroll-reveal">
                From field to data in four easy steps using our mobile application and web platform.
            </p>

            <div class="steps-grid">
                <div class="step-card ui-scroll-reveal">
                    <div class="step-num">1</div>
                    <h4>Collect Soil Sample</h4>
                    <p>Take a soil sample from your field and photograph it using the AgriCo mobile app.</p>
                </div>
                <div class="step-card ui-scroll-reveal">
                    <div class="step-num">2</div>
                    <h4>Scan & Classify</h4>
                    <p>The app automatically classifies the soil using Munsell color science and AI analysis.</p>
                </div>
                <div class="step-card ui-scroll-reveal">
                    <div class="step-num">3</div>
                    <h4>Map Your Data</h4>
                    <p>Each scan is geo-tagged and pinned on the interactive soil map for your farm.</p>
                </div>
                <div class="step-card ui-scroll-reveal">
                    <div class="step-num">4</div>
                    <h4>Make Decisions</h4>
                    <p>Use your scan history and map data to make data-driven farming and fertilization decisions.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── CTA BANNER ───────────────────────────────────────── --}}
    <section class="ws-section ws-cta-section" style="padding:80px 24px;">
        <div class="cta-banner ui-scroll-reveal" style="max-width:1200px; margin:0 auto;">
            <h2>Ready to Understand Your Soil?</h2>
            <p style="opacity: 0.95;">Start exploring soil scan data, Munsell color charts, and interactive maps — all free.</p>
            <a href="{{ route('website.munsell') }}" class="cta-banner-btn">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="3"/>
                    <line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
                Explore the Platform
            </a>
        </div>
    </section>

    @if(session('maintenance_download_blocked'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof showSystemMaintenanceModal === 'function') {
                    showSystemMaintenanceModal();
                }
            });
        </script>
    @endif

    @if(session('login_required_download'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof showGlobalLoginModal === 'function') {
                    showGlobalLoginModal('download');
                }
            });
        </script>
    @endif

    @if(session('account_pending_download'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof showAccountPendingModal === 'function') {
                    showAccountPendingModal('Account Pending Approval', 'Your account is awaiting admin approval. Mobile app download will be enabled once approved.');
                }
            });
        </script>
    @endif

</x-website-layout>
