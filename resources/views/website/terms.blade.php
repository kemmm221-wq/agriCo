<x-website-layout>
    <x-slot:title>Terms of Service</x-slot:title>

    {{-- SEO Meta Tags --}}
    @push('meta')
        <meta name="description" content="Read the AgriCo Terms of Service governing soil scanning, Munsell chart estimation, community mapping, account registration, and acceptable use." />
        <meta property="og:title" content="Terms of Service — AgriCo" />
        <meta property="og:description" content="Read the AgriCo Terms of Service governing soil scanning, Munsell chart estimation, community mapping, and acceptable use." />
        <meta property="og:type" content="article" />
    @endpush

    <style>
        /* ── LEGAL PAGE STYLES (SOCIA-INSPIRED DESIGN FOR AGRICO) ── */
        .legal-page-wrapper {
            background-color: #ffffff;
            min-height: 80vh;
            padding-bottom: 80px;
            color: #334155;
            font-family: inherit;
        }

        /* ── HERO BANNER ── */
        .legal-hero {
            padding: 56px 20px 32px;
            max-width: 1180px;
            margin: 0 auto;
            text-align: left;
        }

        .legal-title {
            font-size: 42px;
            font-weight: 850;
            color: #0f172a;
            line-height: 1.15;
            margin: 0 0 10px 0;
            letter-spacing: -0.025em;
        }
        .legal-subtitle {
            font-size: 18px;
            color: #64748b;
            margin: 0 0 16px 0;
            font-weight: 500;
            line-height: 1.5;
        }
        .legal-meta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: #94a3b8;
            font-weight: 600;
        }
        .legal-divider {
            height: 1px;
            background: #e2e8f0;
            margin-top: 28px;
        }

        /* ── TWO-COLUMN MAIN LAYOUT ── */
        .legal-layout-container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 40px 20px 0;
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 56px;
            align-items: start;
        }

        /* ── STICKY SIDEBAR (TABLE OF CONTENTS) ── */
        .legal-toc-sidebar {
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
            padding-right: 12px;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        .legal-toc-sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .legal-toc-sidebar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }
        .legal-toc-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 12px;
            padding-left: 12px;
        }
        .legal-toc-nav {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .legal-toc-link {
            display: block;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
            border-left: 2.5px solid transparent;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            line-height: 1.4;
        }
        .legal-toc-link:hover {
            color: #166534;
            background: #f8fafc;
        }
        .legal-toc-link.is-active {
            color: #15803d;
            background: #f0fdf4;
            border-left-color: #22c55e;
            font-weight: 700;
        }

        /* ── MOBILE TOC COLLAPSIBLE ── */
        .legal-mobile-toc {
            display: none;
            margin-bottom: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
        }
        .legal-mobile-toc-toggle {
            width: 100%;
            padding: 14px 18px;
            background: none;
            border: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 14.5px;
            font-weight: 700;
            color: #0f172a;
            cursor: pointer;
        }
        .legal-mobile-toc-list {
            display: none;
            padding: 8px 14px 14px;
            border-top: 1px solid #e2e8f0;
            max-height: 340px;
            overflow-y: auto;
        }
        .legal-mobile-toc-list.is-open {
            display: block;
        }
        .legal-mobile-toc-list a {
            display: block;
            padding: 7px 10px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-decoration: none;
            border-radius: 6px;
        }
        .legal-mobile-toc-list a:hover {
            background: #f0fdf4;
            color: #15803d;
        }

        /* ── READING CANVAS & SECTIONS ── */
        .legal-content-canvas {
            max-width: 800px;
            line-height: 1.75;
            font-size: 15px;
        }
        .legal-section {
            scroll-margin-top: 100px;
            margin-bottom: 44px;
        }
        .legal-section-header {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 14px;
        }
        .legal-section-num {
            font-size: 24px;
            font-weight: 850;
            color: #16a34a;
            letter-spacing: -0.02em;
        }
        .legal-section-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.015em;
        }
        .legal-content-canvas p {
            margin: 0 0 16px 0;
            color: #334155;
        }
        .legal-content-canvas p strong {
            color: #0f172a;
            font-weight: 700;
        }
        .legal-content-canvas ul, .legal-content-canvas ol {
            margin: 0 0 18px 0;
            padding-left: 22px;
            color: #334155;
        }
        .legal-content-canvas li {
            margin-bottom: 8px;
        }
        .legal-content-canvas li::marker {
            color: #22c55e;
        }

        /* ── CRITICAL DISCLAIMER BOX ── */
        .legal-disclaimer-box {
            background: #fffbeb;
            border: 1.5px solid #fde68a;
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
            padding: 18px 20px;
            margin: 22px 0;
            font-size: 14px;
            line-height: 1.65;
        }
        .legal-disclaimer-box h4 {
            font-size: 14.5px;
            font-weight: 800;
            color: #b45309;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .legal-disclaimer-box p {
            margin: 0 !important;
            color: #92400e !important;
        }

        /* ── CROSS-PAGE NAVIGATION BANNER ── */
        .legal-cross-banner {
            margin-top: 48px;
            padding: 24px 28px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .legal-cross-text h4 {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
        }
        .legal-cross-text p {
            font-size: 13.5px;
            color: #64748b;
            margin: 0 !important;
        }
        .legal-cross-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: #22c55e;
            color: #ffffff !important;
            font-size: 13.5px;
            font-weight: 700;
            border-radius: 12px;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
        }
        .legal-cross-btn:hover {
            background: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.35);
        }

        /* ── BACK TO TOP FLOATING BUTTON ── */
        .legal-back-to-top {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 44px;
            height: 44px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 50%;
            color: #166534;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 99;
        }
        .legal-back-to-top.is-visible {
            opacity: 1;
            visibility: visible;
        }
        .legal-back-to-top:hover {
            background: #f0fdf4;
            border-color: #22c55e;
            color: #15803d;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(34, 197, 94, 0.25);
        }

        /* ── RESPONSIVE DESIGN ── */
        @media (max-width: 900px) {
            .legal-layout-container {
                grid-template-columns: 1fr;
                gap: 0;
                padding-top: 24px;
            }
            .legal-toc-sidebar {
                display: none;
            }
            .legal-mobile-toc {
                display: block;
            }
            .legal-title {
                font-size: 32px;
            }
            .legal-cross-banner {
                flex-direction: column;
                align-items: flex-start;
            }
            .legal-cross-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* ── PRINT-FRIENDLY RULES ── */
        @media print {
            nav, header, footer, .ws-header, .ws-footer, .legal-toc-sidebar, .legal-mobile-toc, .legal-back-to-top, .legal-cross-banner {
                display: none !important;
            }
            body, .legal-page-wrapper {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
            }
            .legal-layout-container {
                display: block !important;
                max-width: 100% !important;
                padding: 0 !important;
            }
            .legal-content-canvas {
                max-width: 100% !important;
            }
            .legal-section {
                page-break-inside: avoid;
            }
            .legal-title {
                font-size: 26pt;
                color: #000000 !important;
            }
            .legal-section-title {
                color: #000000 !important;
            }
            .legal-section-num {
                color: #000000 !important;
            }
            a {
                text-decoration: none !important;
                color: #000000 !important;
            }
        }

        /* ── DARK MODE OVERRIDES (AGRICO SIGNATURE FOREST DARK) ── */
        html:not(.light-mode) .legal-page-wrapper {
            background-color: #051F20;
            color: #DAF1DE;
        }
        html:not(.light-mode) .legal-title {
            color: #ffffff;
        }
        html:not(.light-mode) .legal-subtitle {
            color: #8EB69B;
        }
        html:not(.light-mode) .legal-meta {
            color: #8EB69B;
        }
        html:not(.light-mode) .legal-divider {
            background: rgba(142, 182, 155, 0.2);
        }
        html:not(.light-mode) .legal-toc-title {
            color: #8EB69B;
        }
        html:not(.light-mode) .legal-toc-sidebar {
            scrollbar-color: rgba(142, 182, 155, 0.3) transparent;
        }
        html:not(.light-mode) .legal-toc-sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(142, 182, 155, 0.3);
        }
        html:not(.light-mode) .legal-toc-link {
            color: #8EB69B;
        }
        html:not(.light-mode) .legal-toc-link:hover {
            color: #4ADE80;
            background: rgba(35, 83, 71, 0.4);
        }
        html:not(.light-mode) .legal-toc-link.is-active {
            color: #4ADE80;
            background: rgba(34, 197, 94, 0.15);
            border-left-color: #22c55e;
        }
        html:not(.light-mode) .legal-mobile-toc {
            background: #0B2B26;
            border-color: rgba(142, 182, 155, 0.28);
        }
        html:not(.light-mode) .legal-mobile-toc-toggle {
            color: #DAF1DE;
        }
        html:not(.light-mode) .legal-mobile-toc-list {
            border-top-color: rgba(142, 182, 155, 0.2);
        }
        html:not(.light-mode) .legal-mobile-toc-list a {
            color: #8EB69B;
        }
        html:not(.light-mode) .legal-mobile-toc-list a:hover {
            background: rgba(35, 83, 71, 0.4);
            color: #4ADE80;
        }
        html:not(.light-mode) .legal-section-num {
            color: #4ADE80;
        }
        html:not(.light-mode) .legal-section-title {
            color: #ffffff;
        }
        html:not(.light-mode) .legal-content-canvas p,
        html:not(.light-mode) .legal-content-canvas ul,
        html:not(.light-mode) .legal-content-canvas ol,
        html:not(.light-mode) .legal-content-canvas li {
            color: #DAF1DE;
        }
        html:not(.light-mode) .legal-content-canvas p strong,
        html:not(.light-mode) .legal-content-canvas li strong,
        html:not(.light-mode) .legal-content-canvas h3,
        html:not(.light-mode) .legal-content-canvas h4 {
            color: #ffffff;
        }
        html:not(.light-mode) .legal-content-canvas a {
            color: #4ADE80;
        }
        html:not(.light-mode) .legal-content-canvas a:hover {
            color: #86efac;
        }
        html:not(.light-mode) .legal-content-canvas code {
            background: rgba(35, 83, 71, 0.55);
            color: #86efac;
            border: 1px solid rgba(142, 182, 155, 0.3);
            padding: 2px 6px;
            border-radius: 6px;
        }
        html:not(.light-mode) .legal-disclaimer-box {
            background: rgba(245, 158, 11, 0.12);
            border: 1.5px solid rgba(251, 191, 36, 0.35);
            border-left: 4px solid #f59e0b;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        }
        html:not(.light-mode) .legal-disclaimer-box h4 {
            color: #fbbf24;
        }
        html:not(.light-mode) .legal-disclaimer-box p {
            color: #fef3c7 !important;
        }
        html:not(.light-mode) .legal-cross-banner {
            background: linear-gradient(135deg, #072722 0%, #0B2B26 100%);
            border-color: rgba(142, 182, 155, 0.28);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        html:not(.light-mode) .legal-cross-text h4 {
            color: #ffffff;
        }
        html:not(.light-mode) .legal-cross-text p {
            color: #8EB69B;
        }
        html:not(.light-mode) .legal-cross-btn {
            background: linear-gradient(135deg, #235347, #163832) !important;
            border: 1.5px solid #8EB69B !important;
            color: #DAF1DE !important;
            box-shadow: 0 4px 14px rgba(35, 83, 71, 0.4) !important;
        }
        html:not(.light-mode) .legal-cross-btn:hover {
            background: linear-gradient(135deg, #2e695a, #1c453d) !important;
            border-color: #DAF1DE !important;
            color: #ffffff !important;
            box-shadow: 0 6px 20px rgba(35, 83, 71, 0.55) !important;
            transform: translateY(-2px);
        }
        html:not(.light-mode) .legal-back-to-top {
            background: #0B2B26;
            border-color: rgba(142, 182, 155, 0.35);
            color: #4ADE80;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
        }
        html:not(.light-mode) .legal-back-to-top:hover {
            background: #22c55e;
            border-color: #22c55e;
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(34, 197, 94, 0.35);
        }
    </style>

    <div class="legal-page-wrapper">
        {{-- Hero Header --}}
        <header class="legal-hero">
            <h1 class="legal-title">Terms of Service</h1>
            <p class="legal-subtitle">The terms that govern your use of our website, tools, and mobile application.</p>
            <div class="legal-meta">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Last updated: October 15, 2026</span>
            </div>
            <div class="legal-divider"></div>
        </header>

        <main class="legal-layout-container">
            {{-- Mobile Collapsible TOC --}}
            <div class="legal-mobile-toc">
                <button type="button" class="legal-mobile-toc-toggle" id="legalMobileTocToggle" aria-expanded="false">
                    <span>Table of Contents</span>
                    <svg id="legalMobileChevron" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="transition: transform 0.2s ease;">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="legal-mobile-toc-list" id="legalMobileTocList">
                    <a href="#acceptance-of-terms">1. Acceptance of Terms</a>
                    <a href="#about-agrico-disclaimer">2. About AgriCo & Agricultural Disclaimer</a>
                    <a href="#account-registration">3. Account Registration & Approval</a>
                    <a href="#account-security">4. Account Security & Verification</a>
                    <a href="#acceptable-use">5. Acceptable Use Policy</a>
                    <a href="#user-content-license">6. User Content & Mapping License</a>
                    <a href="#moderation-suspension">7. Moderation, Content Removal & Suspension</a>
                    <a href="#mobile-app-updates">8. Mobile Application & Updates</a>
                    <a href="#disclaimer-liability">9. Disclaimer & Limitation of Liability</a>
                    <a href="#service-modifications">10. Modifications & Availability</a>
                    <a href="#termination">11. Account Termination</a>
                    <a href="#governing-law">12. Governing Law & Jurisdiction</a>
                    <a href="#revisions-to-terms">13. Revisions to Terms</a>
                    <a href="#contact-us">14. Contact Us</a>
                </div>
            </div>

            {{-- Desktop Sticky Sidebar TOC --}}
            <aside class="legal-toc-sidebar" aria-label="Table of contents">
                <div class="legal-toc-title">Contents</div>
                <nav class="legal-toc-nav" id="legalTocNav">
                    <a href="#acceptance-of-terms" class="legal-toc-link is-active">1. Acceptance of Terms</a>
                    <a href="#about-agrico-disclaimer" class="legal-toc-link">2. About AgriCo & Disclaimer</a>
                    <a href="#account-registration" class="legal-toc-link">3. Registration & Approval</a>
                    <a href="#account-security" class="legal-toc-link">4. Account Security</a>
                    <a href="#acceptable-use" class="legal-toc-link">5. Acceptable Use Policy</a>
                    <a href="#user-content-license" class="legal-toc-link">6. User Content & License</a>
                    <a href="#moderation-suspension" class="legal-toc-link">7. Moderation & Suspension</a>
                    <a href="#mobile-app-updates" class="legal-toc-link">8. Mobile App & Updates</a>
                    <a href="#disclaimer-liability" class="legal-toc-link">9. Disclaimer & Liability</a>
                    <a href="#service-modifications" class="legal-toc-link">10. Modifications & Uptime</a>
                    <a href="#termination" class="legal-toc-link">11. Termination</a>
                    <a href="#governing-law" class="legal-toc-link">12. Governing Law</a>
                    <a href="#revisions-to-terms" class="legal-toc-link">13. Revisions to Terms</a>
                    <a href="#contact-us" class="legal-toc-link">14. Contact Us</a>
                </nav>
            </aside>

            {{-- Terms Document Content --}}
            <article class="legal-content-canvas">
                {{-- Section 1 --}}
                <section class="legal-section" id="acceptance-of-terms">
                    <div class="legal-section-header">
                        <span class="legal-section-num">1.</span>
                        <h2 class="legal-section-title">Acceptance of Terms</h2>
                    </div>
                    <p>
                        By visiting or using the AgriCo website (<a href="https://agrico-web-production.up.railway.app/" style="color:#16a34a; font-weight:600;">agrico-web-production.up.railway.app</a>), downloading the AgriCo mobile application, or registering for an account, you agree to be legally bound by these Terms of Service ("Terms"). If you do not agree with any part of these Terms, you must discontinue your use of the platform immediately.
                    </p>
                    <p>
                        These Terms constitute a binding agreement between you and the <strong>AgriCo Project Team</strong> in partnership with the <strong>College of Information and Computing Sciences, Cagayan State University – Lal-lo Campus</strong>.
                    </p>
                </section>

                {{-- Section 2 --}}
                <section class="legal-section" id="about-agrico-disclaimer">
                    <div class="legal-section-header">
                        <span class="legal-section-num">2.</span>
                        <h2 class="legal-section-title">About AgriCo & Agricultural Disclaimer</h2>
                    </div>
                    <p>
                        AgriCo provides computer vision and machine learning tools designed to assist in determining soil color characteristics using the standardized Munsell Soil Color notation, accessing educational color charts (Hue, Value, Chroma), and mapping regional soil classifications across Lal-lo, Cagayan.
                    </p>
                    <div class="legal-disclaimer-box">
                        <h4>
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            CRITICAL AGRICULTURAL & SCIENTIFIC DISCLAIMER
                        </h4>
                        <p>
                            AgriCo’s soil scan outputs and Munsell color determinations are <strong>preliminary computational estimations</strong> intended solely for educational, academic, and initial field reference. They do <strong>not</strong> substitute for certified laboratory soil testing, chemical nutrient analyses (such as laboratory NPK determination, soil pH testing, or organic matter assays), or professional agricultural advice. Environmental variables—including ambient sunlight, artificial lighting, camera sensor calibration, soil moisture, and sample texture—directly affect color capture. Never make significant agricultural, financial, or chemical fertilization investments based solely on digital scan estimations. Consult accredited soil laboratories (such as Department of Agriculture soil testing laboratories) for definitive recommendations.
                        </p>
                    </div>
                </section>

                {{-- Section 3 --}}
                <section class="legal-section" id="account-registration">
                    <div class="legal-section-header">
                        <span class="legal-section-num">3.</span>
                        <h2 class="legal-section-title">Account Registration & Approval</h2>
                    </div>
                    <p>
                        <strong>Eligibility:</strong> AgriCo is available to farmers, agricultural professionals, researchers, and students who are <strong>15 years of age or older</strong>. Students registering under the student role represent that they are enrolled in an educational institution and have academic standing.
                    </p>
                    <p>
                        <strong>Truthful Information:</strong> You agree to provide accurate, truthful, and complete information during registration. Impersonating any individual or misrepresenting your student or farming credentials is a violation of these Terms.
                    </p>
                    <p>
                        <strong>Administrator Approval:</strong> To maintain system integrity and community safety, new account registrations are placed in a <code>Pending</code> state. Full platform access is unlocked only after an administrator reviews and approves your registration request.
                    </p>
                </section>

                {{-- Section 4 --}}
                <section class="legal-section" id="account-security">
                    <div class="legal-section-header">
                        <span class="legal-section-num">4.</span>
                        <h2 class="legal-section-title">Account Security & Verification</h2>
                    </div>
                    <p>
                        You are solely responsible for maintaining the confidentiality of your account credentials. You agree not to disclose your password or temporary authentication tokens to any third party.
                    </p>
                    <p>
                        <strong>Password Reset Codes:</strong> The 6-digit verification code sent to your email for password recovery is valid for exactly 15 minutes. AgriCo staff will never ask you to reveal this code. If you suspect unauthorized access, contact us immediately at <a href="mailto:agricosite77@gmail.com" style="color:#16a34a; font-weight:600;">agricosite77@gmail.com</a>.
                    </p>
                </section>

                {{-- Section 5 --}}
                <section class="legal-section" id="acceptable-use">
                    <div class="legal-section-header">
                        <span class="legal-section-num">5.</span>
                        <h2 class="legal-section-title">Acceptable Use Policy</h2>
                    </div>
                    <p>You agree to utilize AgriCo responsibly and ethically. You shall <strong>not</strong>:</p>
                    <ul>
                        <li>Upload images that do not contain soil samples (such as portraits of individuals, copyrighted art, or inappropriate media).</li>
                        <li>Submit fabricated, falsified, or deliberately inaccurate GPS coordinates or fraudulent soil color entries to the public map.</li>
                        <li>Attempt to reverse-engineer, decompile, extract, or exploit the machine learning models or underlying algorithms.</li>
                        <li>Use automated bots, scrapers, or scripts to overload our database or bypass rate limits.</li>
                        <li>Introduce malware, viruses, or any harmful code intended to damage platform infrastructure.</li>
                    </ul>
                </section>

                {{-- Section 6 --}}
                <section class="legal-section" id="user-content-license">
                    <div class="legal-section-header">
                        <span class="legal-section-num">6.</span>
                        <h2 class="legal-section-title">User Content & Mapping License</h2>
                    </div>
                    <p>
                        <strong>Content Ownership:</strong> You retain ownership of the photographs, notes, and raw observations you submit to AgriCo.
                    </p>
                    <p>
                        <strong>License to AgriCo:</strong> By submitting a soil scan or pinning a location to the interactive map, you grant AgriCo a non-exclusive, royalty-free, perpetual, and transferable license to process, store, analyze, and display the resulting color determinations, Munsell metrics, and location pins publicly on the community map and aggregate agricultural summaries.
                    </p>
                </section>

                {{-- Section 7 --}}
                <section class="legal-section" id="moderation-suspension">
                    <div class="legal-section-header">
                        <span class="legal-section-num">7.</span>
                        <h2 class="legal-section-title">Moderation, Content Removal & Suspension</h2>
                    </div>
                    <p>
                        <strong>Pin Moderation:</strong> All soil pins submitted to the public map are subject to administrative review. Pins containing inaccurate data, unverified coordinates, or inappropriate images may be disapproved or removed at the administrator's discretion.
                    </p>
                    <p>
                        <strong>Account Suspension:</strong> Administrators reserve the right to temporarily suspend accounts (e.g., for 24 hours or administrative duration) or permanently terminate access for violations of these Terms.
                    </p>
                    <p>
                        <strong>Reactivation Requests:</strong> If your account has been suspended, you may file an Account Reactivation Request through the login portal for review by system administrators.
                    </p>
                </section>

                {{-- Section 8 --}}
                <section class="legal-section" id="mobile-app-updates">
                    <div class="legal-section-header">
                        <span class="legal-section-num">8.</span>
                        <h2 class="legal-section-title">Mobile Application & Updates</h2>
                    </div>
                    <p>
                        The AgriCo mobile application provides offline soil scanning and synchronization. To operate correctly, the mobile app requires device permissions for your Camera, Photo Gallery, and GPS Location.
                    </p>
                    <p>
                        You are responsible for obtaining the authentic Android application package (<code>.apk</code>) distributed through our official download route (<a href="{{ route('website.download') }}" style="color:#16a34a; font-weight:600;">/download</a>) or verified version checks (<a href="/api/app-version" style="color:#16a34a; font-weight:600;">/api/app-version</a>) to ensure security and model accuracy.
                    </p>
                </section>

                {{-- Section 9 --}}
                <section class="legal-section" id="disclaimer-liability">
                    <div class="legal-section-header">
                        <span class="legal-section-num">9.</span>
                        <h2 class="legal-section-title">Disclaimer & Limitation of Liability</h2>
                    </div>
                    <p>
                        <strong>"As-Is" Provision:</strong> AgriCo is provided on an "AS IS" and "AS AVAILABLE" basis without warranties of any kind, whether express, implied, or statutory, including warranties of merchantability, fitness for a particular agricultural purpose, or non-infringement.
                    </p>
                    <p>
                        <strong>Limitation of Liability:</strong> To the fullest extent permitted by Philippine law, the AgriCo Project Team, student developers, faculty advisers, and hosting infrastructure providers shall not be liable for any direct, indirect, incidental, consequential, special, or exemplary damages, including loss of agricultural yields, financial losses, or crop damage resulting from:
                    </p>
                    <ul>
                        <li>Your reliance on Munsell color estimates, spectral charts, or matched soil type recommendations.</li>
                        <li>Service interruptions, system maintenance outages, or loss of cached scan records.</li>
                        <li>Rejection, modification, or removal of submitted map pins.</li>
                    </ul>
                </section>

                {{-- Section 10 --}}
                <section class="legal-section" id="service-modifications">
                    <div class="legal-section-header">
                        <span class="legal-section-num">10.</span>
                        <h2 class="legal-section-title">Modifications & Availability</h2>
                    </div>
                    <p>
                        We reserve the right to modify, enhance, temporarily pause, or discontinue features of AgriCo (including mobile downloads or specific Munsell color charts) at any time. While we endeavor to maintain dependable service, uninterrupted or error-free operation is not guaranteed.
                    </p>
                </section>

                {{-- Section 11 --}}
                <section class="legal-section" id="termination">
                    <div class="legal-section-header">
                        <span class="legal-section-num">11.</span>
                        <h2 class="legal-section-title">Account Termination</h2>
                    </div>
                    <p>
                        You may terminate your account at any time by sending a deletion request to <a href="mailto:agricosite77@gmail.com" style="color:#16a34a; font-weight:600;">agricosite77@gmail.com</a>. Upon administrative review, an administrator executes permanent deletion through the Admin User Management dashboard, permanently removing your credentials and identifying information from the system.
                    </p>
                    <p>
                        We reserve the right to suspend or terminate accounts that breach these Terms, submit harmful content, or compromise platform security.
                    </p>
                </section>

                {{-- Section 12 --}}
                <section class="legal-section" id="governing-law">
                    <div class="legal-section-header">
                        <span class="legal-section-num">12.</span>
                        <h2 class="legal-section-title">Governing Law & Jurisdiction</h2>
                    </div>
                    <p>
                        These Terms shall be governed by, interpreted, and enforced in accordance with the laws of the <strong>Republic of the Philippines</strong>, including the Electronic Commerce Act of 2000 (Republic Act No. 8792) and the Data Privacy Act of 2012 (Republic Act No. 10173).
                    </p>
                    <p>
                        Any legal dispute or claim arising out of or related to these Terms or the use of AgriCo shall be submitted to the exclusive jurisdiction of the competent courts of <strong>Lal-lo, Cagayan, Philippines</strong>.
                    </p>
                </section>

                {{-- Section 13 --}}
                <section class="legal-section" id="revisions-to-terms">
                    <div class="legal-section-header">
                        <span class="legal-section-num">13.</span>
                        <h2 class="legal-section-title">Revisions to Terms</h2>
                    </div>
                    <p>
                        We may update these Terms periodically. Any modifications will become effective immediately upon posting to this page with an updated "Last updated" date. Your continued use of the platform after updates indicates your agreement to the new Terms.
                    </p>
                </section>

                {{-- Section 14 --}}
                <section class="legal-section" id="contact-us">
                    <div class="legal-section-header">
                        <span class="legal-section-num">14.</span>
                        <h2 class="legal-section-title">Contact Us</h2>
                    </div>
                    <p>For questions, support, or notices regarding these Terms of Service:</p>
                    <ul>
                        <li><strong>Official Email:</strong> <a href="mailto:agricosite77@gmail.com" style="color:#16a34a; font-weight:700;">agricosite77@gmail.com</a></li>
                        <li><strong>Campus Address:</strong> Cagayan State University – Lal-lo Campus, Sta. Maria, Lal-lo, Cagayan 3511, Philippines</li>
                        <li><strong>Help Center:</strong> <a href="{{ route('website.faq') }}" style="color:#16a34a; font-weight:600;">AgriCo Frequently Asked Questions</a></li>
                    </ul>
                </section>

                {{-- Cross-Page Navigation Banner --}}
                <div class="legal-cross-banner">
                    <div class="legal-cross-text">
                        <h4>Looking for our Privacy Policy?</h4>
                        <p>Learn how we collect, store, and safeguard your soil scans, location, and account data.</p>
                    </div>
                    <a href="{{ route('website.privacy') }}" class="legal-cross-btn">
                        <span>Read Privacy Policy</span>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </article>
        </main>

        {{-- Floating Back to Top Button --}}
        <button type="button" class="legal-back-to-top" id="legalBackToTop" title="Back to top" aria-label="Back to top">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </button>
    </div>

    {{-- Interactive Table of Contents & Scroll-Spy Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('.legal-section');
            const tocLinks = document.querySelectorAll('.legal-toc-link');
            const backToTopBtn = document.getElementById('legalBackToTop');
            const mobileToggle = document.getElementById('legalMobileTocToggle');
            const mobileList = document.getElementById('legalMobileTocList');
            const mobileChevron = document.getElementById('legalMobileChevron');

            // 1. Mobile TOC Toggle
            if (mobileToggle && mobileList) {
                mobileToggle.addEventListener('click', () => {
                    const isOpen = mobileList.classList.toggle('is-open');
                    mobileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    if (mobileChevron) {
                        mobileChevron.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
                    }
                });

                // Close mobile TOC on clicking any item
                mobileList.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        mobileList.classList.remove('is-open');
                        mobileToggle.setAttribute('aria-expanded', 'false');
                        if (mobileChevron) mobileChevron.style.transform = 'rotate(0deg)';
                    });
                });
            }

            // 2. Scroll-Spy with IntersectionObserver
            const observerOptions = {
                root: null,
                rootMargin: '-20% 0px -70% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.getAttribute('id');
                        tocLinks.forEach(link => {
                            const isMatch = link.getAttribute('href') === `#${id}`;
                            link.classList.toggle('is-active', isMatch);
                        });
                    }
                });
            }, observerOptions);

            sections.forEach(section => observer.observe(section));

            // 3. Back to Top Button
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 350) {
                    backToTopBtn?.classList.add('is-visible');
                } else {
                    backToTopBtn?.classList.remove('is-visible');
                }
            }, { passive: true });

            backToTopBtn?.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>
</x-website-layout>
