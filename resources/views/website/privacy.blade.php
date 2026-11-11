<x-website-layout>
    <x-slot:title>Privacy Policy</x-slot:title>

    {{-- SEO Meta Tags --}}
    @push('meta')
        <meta name="description" content="Learn how AgriCo collects, uses, and safeguards your soil scans, location data, and personal information in accordance with the Philippine Data Privacy Act." />
        <meta property="og:title" content="Privacy Policy — AgriCo" />
        <meta property="og:description" content="Learn how AgriCo collects, uses, and safeguards your soil scans, location data, and personal information." />
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
            font-size: 13.5px;
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
            max-height: 320px;
            overflow-y: auto;
        }
        .legal-mobile-toc-list.is-open {
            display: block;
        }
        .legal-mobile-toc-list a {
            display: block;
            padding: 7px 10px;
            font-size: 13.5px;
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

        /* ── HIGHLIGHTED INFO BOX ── */
        .legal-callout-box {
            background: #f0fdf4;
            border: 1.5px solid #bbf7d0;
            border-left: 4px solid #22c55e;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 14.5px;
        }
        .legal-callout-box p {
            margin: 0 !important;
            color: #166534 !important;
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
        html:not(.light-mode) .legal-callout-box {
            background: rgba(11, 43, 38, 0.85);
            border: 1.5px solid rgba(74, 222, 128, 0.4);
            border-left: 4px solid #22c55e;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        }
        html:not(.light-mode) .legal-callout-box p {
            color: #DAF1DE !important;
        }
        html:not(.light-mode) .legal-callout-box strong {
            color: #4ADE80 !important;
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
            <h1 class="legal-title">Privacy Policy</h1>
            <p class="legal-subtitle">How we collect, use, and protect your information.</p>
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
                    <a href="#introduction">1. Introduction and Who We Are</a>
                    <a href="#information-we-collect">2. Information We Collect</a>
                    <a href="#how-we-use-information">3. How We Use Your Information</a>
                    <a href="#public-map-visibility">4. Public Soil Map and Visibility</a>
                    <a href="#sharing-and-third-parties">5. Sharing & Third-Party Services</a>
                    <a href="#data-storage-and-security">6. Data Storage, Security & Retention</a>
                    <a href="#your-rights">7. Your Rights and Choices</a>
                    <a href="#children-and-minors">8. Minors & Age Eligibility</a>
                    <a href="#cookies-and-storage">9. Cookies and Local Storage</a>
                    <a href="#changes-to-policy">10. Changes to This Policy</a>
                    <a href="#contact-us">11. Contact Us</a>
                </div>
            </div>

            {{-- Desktop Sticky Sidebar TOC --}}
            <aside class="legal-toc-sidebar" aria-label="Table of contents">
                <div class="legal-toc-title">Contents</div>
                <nav class="legal-toc-nav" id="legalTocNav">
                    <a href="#introduction" class="legal-toc-link is-active">1. Introduction & Who We Are</a>
                    <a href="#information-we-collect" class="legal-toc-link">2. Information We Collect</a>
                    <a href="#how-we-use-information" class="legal-toc-link">3. How We Use Information</a>
                    <a href="#public-map-visibility" class="legal-toc-link">4. Public Map & Visibility</a>
                    <a href="#sharing-and-third-parties" class="legal-toc-link">5. Third-Party Services</a>
                    <a href="#data-storage-and-security" class="legal-toc-link">6. Storage, Security & Retention</a>
                    <a href="#your-rights" class="legal-toc-link">7. Your Rights & Choices</a>
                    <a href="#children-and-minors" class="legal-toc-link">8. Minors & Age Eligibility</a>
                    <a href="#cookies-and-storage" class="legal-toc-link">9. Cookies & Local Storage</a>
                    <a href="#changes-to-policy" class="legal-toc-link">10. Changes to This Policy</a>
                    <a href="#contact-us" class="legal-toc-link">11. Contact Us</a>
                </nav>
            </aside>

            {{-- Policy Document Content --}}
            <article class="legal-content-canvas">
                {{-- Section 1 --}}
                <section class="legal-section" id="introduction">
                    <div class="legal-section-header">
                        <span class="legal-section-num">1.</span>
                        <h2 class="legal-section-title">Introduction and Who We Are</h2>
                    </div>
                    <p>
                        Welcome to AgriCo ("we," "our," or "us"). AgriCo is a specialized agricultural software platform designed to assist farmers, agricultural extension workers, researchers, and students in determining soil color characteristics through digital Munsell color classification, recording localized soil observations, and exploring geographic soil patterns.
                    </p>
                    <p>
                        AgriCo is operated by the <strong>AgriCo Project Team</strong> in partnership with the <strong>College of Information and Computing Sciences, Cagayan State University – Lal-lo Campus</strong>, located at Sta. Maria, Lal-lo, Cagayan 3511, Philippines.
                    </p>
                    <p>
                        This Privacy Policy explains how we collect, process, store, and safeguard your personal information when you access our website (<a href="https://agrico-web-production.up.railway.app/" style="color:#16a34a; font-weight:600;">agrico-web-production.up.railway.app</a>), use the AgriCo mobile application, or interact with our interactive soil mapping features. By creating an account or using AgriCo, you acknowledge that you have read and understood the practices described in this policy.
                    </p>
                </section>

                {{-- Section 2 --}}
                <section class="legal-section" id="information-we-collect">
                    <div class="legal-section-header">
                        <span class="legal-section-num">2.</span>
                        <h2 class="legal-section-title">Information We Collect</h2>
                    </div>
                    <p>We collect only the information strictly necessary to provide, protect, and improve AgriCo's soil determination services:</p>
                    <ul>
                        <li>
                            <strong>Account Registration Details:</strong> When you register for an account, we collect your full name, email address, password (stored strictly as an encrypted Bcrypt cryptographic hash), and role (General Farmer/User or Student). For student accounts, we also collect your Student ID Number and Section to verify academic status.
                        </li>
                        <li>
                            <strong>Soil Photographs & Color Scans:</strong> When you analyze a soil sample, you upload or capture a photo. The system extracts color metrics (RGB and Lab values), compares them against our calibrated Munsell spectral library (Hue, Value, Chroma e.g., 10YR 4/4), calculates confidence percentages, and saves your optional field notes.
                        </li>
                        <li>
                            <strong>Geographic & GPS Location:</strong> When you submit a soil pin or geotag a scan, we collect the geographic coordinates (latitude and longitude) and resolve the nearest barangay within the Municipality of Lal-lo. Location access requires your explicit permission through your device or browser.
                        </li>
                        <li>
                            <strong>Technical Telemetry:</strong> We record standard technical logs including device type, operating system, platform tag (<code>web</code> or <code>mobile</code>), IP address, and browser characteristics to safeguard platform stability.
                        </li>
                    </ul>
                </section>

                {{-- Section 3 --}}
                <section class="legal-section" id="how-we-use-information">
                    <div class="legal-section-header">
                        <span class="legal-section-num">3.</span>
                        <h2 class="legal-section-title">How We Use Your Information</h2>
                    </div>
                    <p>Your information is used solely for legitimate agricultural, educational, and operational purposes:</p>
                    <ul>
                        <li><strong>Soil Color Analysis:</strong> Running our machine learning color classification model to provide instantaneous Munsell color estimates and soil type descriptions.</li>
                        <li><strong>Account Administration & Security:</strong> Evaluating registration requests through administrator review to maintain platform security and prevent spam.</li>
                        <li><strong>Transactional Notifications:</strong> Sending essential system emails via our email delivery provider (Resend), including account approval notices, pin moderation updates, and 6-digit password verification codes.</li>
                        <li><strong>Community Soil Mapping:</strong> Displaying verified soil color distribution points across Lal-lo to help local farming communities visualize regional soil profiles.</li>
                        <li><strong>System Maintenance:</strong> Diagnosing performance bottlenecks, addressing software bugs, and continually refining algorithmic accuracy.</li>
                    </ul>
                    <div class="legal-callout-box">
                        <p><strong>Commitment to Data Privacy:</strong> We do not sell, rent, monetize, or trade your personal information or scan data to advertising networks or third-party data brokers.</p>
                    </div>
                </section>

                {{-- Section 4 --}}
                <section class="legal-section" id="public-map-visibility">
                    <div class="legal-section-header">
                        <span class="legal-section-num">4.</span>
                        <h2 class="legal-section-title">Public Soil Map and Visibility</h2>
                    </div>
                    <p>AgriCo features an interactive public soil map. It is essential to distinguish between public and private data:</p>
                    <ul>
                        <li>
                            <strong>Publicly Visible Data:</strong> Once an administrator verifies and approves a soil pin, the detected Munsell code (e.g., 7.5YR 3/4), the matched soil type, the scan date, and the geographic pin location on the map are visible to all visitors.
                        </li>
                        <li>
                            <strong>Strictly Private Data:</strong> Your account email address, password hash, Student ID Number, account security logs, unsubmitted scan drafts, and rejected pins are <strong>never</strong> displayed on the public map.
                        </li>
                    </ul>
                </section>

                {{-- Section 5 --}}
                <section class="legal-section" id="sharing-and-third-parties">
                    <div class="legal-section-header">
                        <span class="legal-section-num">5.</span>
                        <h2 class="legal-section-title">Sharing & Third-Party Services</h2>
                    </div>
                    <p>We work with trusted infrastructure providers who process data strictly under technical confidentiality standards:</p>
                    <ul>
                        <li><strong>Railway:</strong> Cloud application hosting, SSL/TLS encryption, and execution environment.</li>
                        <li><strong>Supabase:</strong> Managed PostgreSQL cloud database hosting where records are securely stored behind access controls.</li>
                        <li><strong>Resend:</strong> Transactional email service utilized exclusively to dispatch account security codes and moderation notices.</li>
                        <li><strong>OpenStreetMap & Leaflet.js:</strong> Mapping engines utilized to render spatial coordinates and map tiles.</li>
                    </ul>
                </section>

                {{-- Section 6 --}}
                <section class="legal-section" id="data-storage-and-security">
                    <div class="legal-section-header">
                        <span class="legal-section-num">6.</span>
                        <h2 class="legal-section-title">Data Storage, Security & Retention</h2>
                    </div>
                    <p>
                        <strong>Security Protections:</strong> Passwords are never saved in plaintext; they are protected with salted Bcrypt cryptographic hashing (rounds 12). Web traffic between your device and AgriCo is encrypted with HTTPS/TLS. Sensitive actions like password resets utilize 6-digit one-time tokens valid for only 15 minutes.
                    </p>
                    <p>
                        <strong>Rolling Scan Limit:</strong> To ensure sustainable cloud storage, the system maintains an automated rolling retention cap on stored soil scan records, retaining up to 300 historical scans system-wide.
                    </p>
                    <p>
                        <strong>Account Deletion:</strong> Users may request the deletion of their account and associated personal data at any time by contacting our administrators at <a href="mailto:agricosite77@gmail.com" style="color:#16a34a; font-weight:600;">agricosite77@gmail.com</a>. Upon administrative review, an administrator executes permanent deletion through the Admin User Management panel, permanently purging your personal credentials and identifiers.
                    </p>
                </section>

                {{-- Section 7 --}}
                <section class="legal-section" id="your-rights">
                    <div class="legal-section-header">
                        <span class="legal-section-num">7.</span>
                        <h2 class="legal-section-title">Your Rights and Choices</h2>
                    </div>
                    <p>
                        In accordance with the <strong>Philippine Data Privacy Act of 2012 (Republic Act No. 10173)</strong>, you are entitled to specific statutory data subject rights:
                    </p>
                    <ul>
                        <li><strong>Right to be Informed:</strong> You have the right to know how your personal data is collected and processed, as detailed in this policy.</li>
                        <li><strong>Right to Access:</strong> You may request confirmation and copies of personal data held about you in the system.</li>
                        <li><strong>Right to Rectification:</strong> You may update or correct inaccurate profile information anytime via your account settings.</li>
                        <li><strong>Right to Erasure or Blocking:</strong> You may request the deletion of your account and removal of personal identifiers by submitting a request to our administration team.</li>
                        <li><strong>Device Permissions:</strong> You can grant or revoke camera and GPS location permissions at any time through your mobile device or browser settings.</li>
                    </ul>
                </section>

                {{-- Section 8 --}}
                <section class="legal-section" id="children-and-minors">
                    <div class="legal-section-header">
                        <span class="legal-section-num">8.</span>
                        <h2 class="legal-section-title">Minors & Age Eligibility</h2>
                    </div>
                    <p>
                        AgriCo is designed for farmers, agricultural professionals, researchers, and students who are <strong>15 years of age or older</strong>. Students under the age of 18 are expected to register with academic consent or under institutional supervision. We do not knowingly collect personal information from individuals below 15 years old. If we learn that an account belongs to a user under 15, we will take immediate steps to delete the account and associated personal data.
                    </p>
                </section>

                {{-- Section 9 --}}
                <section class="legal-section" id="cookies-and-storage">
                    <div class="legal-section-header">
                        <span class="legal-section-num">9.</span>
                        <h2 class="legal-section-title">Cookies and Local Storage</h2>
                    </div>
                    <p>AgriCo uses strictly necessary cookies and local storage tokens for core functionality:</p>
                    <ul>
                        <li><code>agrico_session</code>: Manages authenticated sessions across page requests (expires when you log out or close your browser).</li>
                        <li><code>XSRF-TOKEN</code>: Cryptographic token defending your account against Cross-Site Request Forgery (CSRF).</li>
                        <li><code>remember_web_*</code>: Secure cookie that keeps you signed in if you check "Remember Me".</li>
                        <li><code>localStorage (agrico-theme)</code>: Client-side storage maintaining your light or dark theme viewing preference.</li>
                    </ul>
                    <p>We do not use advertising, retargeting, or behavioral tracking cookies.</p>
                </section>

                {{-- Section 10 --}}
                <section class="legal-section" id="changes-to-policy">
                    <div class="legal-section-header">
                        <span class="legal-section-num">10.</span>
                        <h2 class="legal-section-title">Changes to This Policy</h2>
                    </div>
                    <p>
                        We may update this Privacy Policy from time to time to accommodate new features, system enhancements, or statutory requirements. When modifications occur, we will update the "Last updated" date at the top of this document. Continued use of AgriCo after updates signifies your acceptance of the revised terms.
                    </p>
                </section>

                {{-- Section 11 --}}
                <section class="legal-section" id="contact-us">
                    <div class="legal-section-header">
                        <span class="legal-section-num">11.</span>
                        <h2 class="legal-section-title">Contact Us</h2>
                    </div>
                    <p>If you have questions, concerns, or requests regarding this Privacy Policy or your personal information:</p>
                    <ul>
                        <li><strong>Official Email:</strong> <a href="mailto:agricosite77@gmail.com" style="color:#16a34a; font-weight:700;">agricosite77@gmail.com</a></li>
                        <li><strong>Campus Address:</strong> Cagayan State University – Lal-lo Campus, Sta. Maria, Lal-lo, Cagayan 3511, Philippines</li>
                        <li><strong>Support Center:</strong> <a href="{{ route('website.faq') }}" style="color:#16a34a; font-weight:600;">Frequently Asked Questions (FAQ)</a></li>
                    </ul>
                </section>

                {{-- Cross-Page Navigation Banner --}}
                <div class="legal-cross-banner">
                    <div class="legal-cross-text">
                        <h4>Looking for our Terms of Service?</h4>
                        <p>Learn about the rules, acceptable use, and disclaimer governing the use of AgriCo.</p>
                    </div>
                    <a href="{{ route('website.terms') }}" class="legal-cross-btn">
                        <span>Read Terms of Service</span>
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
