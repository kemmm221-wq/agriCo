<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" class="light admin-portal" style="color-scheme: light;">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <script>
        (function() {
            try {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.remove('light-mode');
                document.documentElement.classList.add('light', 'admin-portal');
                document.documentElement.style.colorScheme = 'light';
                document.documentElement.setAttribute('data-theme', 'light');
            } catch(e) {}
        })();
    </script>
<script>document.documentElement.classList.add("ui-motion");</script>
<link rel="stylesheet" href="{{ asset('css/animations.css') }}?v={{ file_exists(public_path('css/animations.css')) ? filemtime(public_path('css/animations.css')) : time() }}">
<script src="{{ asset('js/animations.js') }}" defer></script>
    <script>
        (function() {
            // Unregister any active Service Workers to eliminate browser offline HTML caching
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    for (let registration of registrations) {
                        registration.unregister();
                    }
                });
                if ('caches' in window) {
                    caches.keys().then(function(names) {
                        for (let name of names) caches.delete(name);
                    });
                }
            }

            // Automatically force full reload when restored from browser Back/Forward Cache (BFCache)
            window.addEventListener('pageshow', function(event) {
                if (event.persisted || (window.performance && window.performance.getEntriesByType && window.performance.getEntriesByType("navigation")[0] && window.performance.getEntriesByType("navigation")[0].type === "back_forward")) {
                    window.location.reload();
                }
            });

            // Silently suppress Livewire's "This page has expired" confirm popup and reload automatically
            const _confirm = window.confirm;
            window.confirm = function(msg) {
                if (msg && typeof msg === 'string' && msg.toLowerCase().includes('page has expired')) {
                    window.location.reload();
                    return true;
                }
                return _confirm.apply(this, arguments);
            };

            const attachLivewireHook = () => {
                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('request', ({ fail }) => {
                        fail(({ status, preventDefault }) => {
                            if (status === 419) {
                                preventDefault();
                                window.location.reload();
                            }
                        });
                    });
                }
            };

            if (window.Livewire) {
                attachLivewireHook();
            } else {
                document.addEventListener('livewire:init', attachLivewireHook);
            }
        })();
    </script>
    <script>
        try {
            var isStoredCollapsed = (localStorage.getItem('agrico_sidebar_collapsed') === 'true' || localStorage.getItem('sidebarCollapsed') === 'true');
            if (isStoredCollapsed && window.innerWidth > 768) {
                document.documentElement.classList.add('sidebar-collapsed');
                document.documentElement.classList.add('sidebar-is-collapsed');
            }
        } catch (e) {}
    </script>
    <style>
        html.preload-no-transition *,
        html.preload-no-transition aside#appSidebar,
        html.preload-no-transition main#appMainContent {
            transition: none !important;
            animation: none !important;
        }

        #appSidebar,
        aside#appSidebar {
            width: 250px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #appMainContent,
        main#appMainContent {
            margin-left: 250px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (min-width: 769px) {
            html.sidebar-collapsed #appSidebar,
            html.sidebar-collapsed aside#appSidebar,
            html.sidebar-is-collapsed #appSidebar,
            html.sidebar-is-collapsed aside#appSidebar {
                width: 76px;
            }
            html.sidebar-collapsed #appMainContent,
            html.sidebar-collapsed main#appMainContent,
            html.sidebar-is-collapsed #appMainContent,
            html.sidebar-is-collapsed main#appMainContent {
                margin-left: 76px;
                padding-left: 44px !important;
                padding-top: 32px !important;
            }
            html.sidebar-collapsed .sidebar-hide-collapsed,
            html.sidebar-is-collapsed .sidebar-hide-collapsed {
                display: none !important;
            }
            html.sidebar-collapsed .sidebar-nav-badge,
            html.sidebar-is-collapsed .sidebar-nav-badge {
                display: none !important;
            }
        }

        @media (max-width: 1180px) {
            html.sidebar-is-collapsed main#appMainContent {
                padding-left: 42px !important;
                padding-top: 30px !important;
            }
        }

        @media (max-width: 900px) {
            main#appMainContent {
                padding: 24px 20px 36px 20px !important;
            }
            html.sidebar-is-collapsed main#appMainContent {
                padding: 26px 20px 36px 40px !important;
            }
        }
        /* â”€â”€ MOBILE TOP BURGER HEADER & OFF-CANVAS SIDEBAR (<= 768px) â”€â”€ */
        .mobile-top-header {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-top-header {
                display: flex !important;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: calc(60px + env(safe-area-inset-top, 0px));
                padding-top: env(safe-area-inset-top, 0px);
                background: #ffffff;
                border-bottom: 1px solid #e2e8f0;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                z-index: 950;
                align-items: center;
                justify-content: space-between;
                padding-left: 14px;
                padding-right: 14px;
                box-sizing: border-box;
            }

            /* Ensure all modal backdrops dim above the fixed mobile header (950) and sidebar (1000) */
            .fixed.inset-0.z-50,
            .modal-animated-backdrop,
            .munsell-detail-backdrop,
            .munsell-form-backdrop,
            .munsell-edit-backdrop,
            .soil-detail-backdrop,
            .soil-form-backdrop,
            .mapping-detail-backdrop,
            .user-view-backdrop,
            .record-popup-backdrop,
            .faq-form-backdrop,
            .faq-detail-backdrop {
                z-index: 9999 !important;
            }

            .sidebar-collapse-toggle-btn {
                display: none !important;
            }

            aside#appSidebar {
                position: fixed !important;
                top: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                height: 100% !important;
                height: 100dvh !important;
                max-height: 100% !important;
                max-height: 100dvh !important;
                width: 260px !important;
                max-width: 260px !important;
                z-index: 1000 !important;
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                box-shadow: 10px 0 35px rgba(0, 0, 0, 0.18) !important;
                border-top-right-radius: 20px !important;
                border-bottom-right-radius: 20px !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                overflow: hidden !important;
                overscroll-behavior: contain !important;
            }

            aside#appSidebar.mobile-nav-open {
                transform: translateX(0) !important;
            }

            .sidebar-top-logo-header {
                height: calc(56px + env(safe-area-inset-top, 0px)) !important;
                padding-top: env(safe-area-inset-top, 0px) !important;
                padding-left: 14px !important;
                padding-right: 14px !important;
                box-sizing: border-box !important;
                flex-shrink: 0 !important;
            }

            .sidebar-top-logo-header img {
                width: 32px !important;
                height: 32px !important;
            }

            .sidebar-top-logo-header .sidebar-hide-collapsed span {
                font-size: 19px !important;
            }

            .sidebar-top-logo-header .sidebar-hide-collapsed p {
                font-size: 10px !important;
            }

            .sidebar-goto-web {
                margin-top: 4px !important;
            }

            /* Ensure all collapsed labels, profile name, role, and logout text are fully visible in mobile drawer */
            aside#appSidebar .sidebar-hide-collapsed,
            aside#appSidebar.mobile-nav-open .sidebar-hide-collapsed,
            html.sidebar-is-collapsed aside#appSidebar .sidebar-hide-collapsed {
                display: block !important;
                opacity: 1 !important;
                max-width: none !important;
                width: auto !important;
                max-height: none !important;
                overflow: visible !important;
                pointer-events: auto !important;
                visibility: visible !important;
            }

            aside#appSidebar .sidebar-show-collapsed,
            aside#appSidebar.mobile-nav-open .sidebar-show-collapsed {
                display: none !important;
            }

            /* Responsive compact nav list for mobile phones */
            aside#appSidebar nav {
                flex: 1 1 auto !important;
                min-height: 0 !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                overscroll-behavior: contain !important;
                padding: 6px 8px !important;
                gap: 2px !important;
            }

            aside#appSidebar .sidebar-nav-link {
                padding: 4px 8px !important;
                border-radius: 12px !important;
                font-size: 13px !important;
                gap: 10px !important;
                min-height: 34px !important;
            }

            aside#appSidebar .sidebar-nav-icon-wrap {
                width: 30px !important;
                height: 30px !important;
                border-radius: 9px !important;
                flex-shrink: 0 !important;
            }

            aside#appSidebar .sidebar-nav-link svg {
                width: 17px !important;
                height: 17px !important;
            }

            /* Mobile compact bottom profile & logout card */
            aside#appSidebar .sidebar-bottom-profile-card {
                flex-shrink: 0 !important;
                margin-top: auto !important;
                padding: 6px 8px !important;
                padding-bottom: max(14px, calc(env(safe-area-inset-bottom, 0px) + 12px)) !important;
                background: #ffffff !important;
                z-index: 40 !important;
                overflow: visible !important;
                border-top: 1px solid #f1f5f9 !important;
                box-sizing: border-box !important;
            }

            aside#appSidebar .sidebar-bottom-profile-card .sidebar-action-item {
                padding: 4px 8px !important;
                gap: 10px !important;
                border-radius: 12px !important;
            }

            aside#appSidebar .sidebar-bottom-profile-card .sidebar-action-item img,
            aside#appSidebar .sidebar-bottom-profile-card .sidebar-action-item div:first-of-type {
                width: 30px !important;
                height: 30px !important;
                font-size: 13px !important;
                border-radius: 50% !important;
            }

            aside#appSidebar .sidebar-bottom-profile-card .sidebar-action-item p:first-of-type {
                font-size: 12.5px !important;
                line-height: 1.2 !important;
            }

            aside#appSidebar .sidebar-bottom-profile-card .sidebar-action-item p:last-of-type {
                font-size: 10.5px !important;
            }

            aside#appSidebar .sidebar-bottom-profile-card .sidebar-logout-item {
                padding: 4px 8px !important;
                gap: 10px !important;
                border-radius: 12px !important;
                font-size: 12.5px !important;
            }

            aside#appSidebar .sidebar-bottom-profile-card .sidebar-logout-item div:first-of-type {
                width: 30px !important;
                height: 30px !important;
                border-radius: 9px !important;
            }

            aside#appSidebar .sidebar-bottom-profile-card .sidebar-logout-item svg {
                width: 17px !important;
                height: 17px !important;
            }

            .mobile-drawer-close-btn {
                display: flex !important;
                align-items: center;
                justify-content: center;
            }

            /* Prevent touch devices and mobile views from retaining stuck hover on inactive links */
            aside#appSidebar .sidebar-nav-link:not(.is-active),
            aside#appSidebar .sidebar-nav-link:not(.is-active):hover,
            aside#appSidebar .sidebar-nav-link:not(.is-active):focus,
            aside#appSidebar .sidebar-nav-link:not(.is-active):active {
                background-color: transparent !important;
                color: #64748b !important;
            }
            aside#appSidebar .sidebar-nav-link:not(.is-active) svg,
            aside#appSidebar .sidebar-nav-link:not(.is-active):hover svg,
            aside#appSidebar .sidebar-nav-link:not(.is-active):focus svg,
            aside#appSidebar .sidebar-nav-link:not(.is-active):active svg {
                color: #94a3b8 !important;
            }

            main#appMainContent,
            html.sidebar-is-collapsed main#appMainContent {
                margin-left: 0 !important;
                padding-top: calc(76px + env(safe-area-inset-top, 0px)) !important;
                padding-left: 12px !important;
                padding-right: 12px !important;
                padding-bottom: 36px !important;
                width: 100% !important;
                max-width: 100vw !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
                overflow-x: hidden !important;
            }
        }

        /* System-wide Admin Modal Lock & Smooth Scrollbar Styling */
        html {
            overflow-x: hidden;
        }

        html:has(.modal-animated-backdrop),
        body:has(.modal-animated-backdrop),
        html:has(.user-view-backdrop),
        body:has(.user-view-backdrop),
        html:has(.record-popup-backdrop),
        body:has(.record-popup-backdrop),
        html:has(.soil-form-backdrop),
        body:has(.soil-form-backdrop),
        html:has(.soil-detail-backdrop),
        body:has(.soil-detail-backdrop),
        html:has(.munsell-edit-backdrop),
        body:has(.munsell-edit-backdrop),
        html:has(.munsell-form-backdrop),
        body:has(.munsell-form-backdrop),
        html:has(.munsell-detail-backdrop),
        body:has(.munsell-detail-backdrop),
        html:has(.mapping-detail-backdrop),
        body:has(.mapping-detail-backdrop),
        html:has(.faq-form-backdrop),
        body:has(.faq-form-backdrop),
        html:has(.faq-detail-backdrop),
        body:has(.faq-detail-backdrop),
        html:has(.profile-modal-overlay),
        body:has(.profile-modal-overlay),
        html:has([class*="-backdrop"]),
        body:has([class*="-backdrop"]),
        html.no-scroll, body.no-scroll {
            overflow: hidden !important;
            overflow-x: hidden !important;
            overflow-y: hidden !important;
            touch-action: none !important;
        }

        /* Standardized Screen-Fitting Blur Backdrop for ALL Popups & Modals */
        .modal-animated-backdrop,
        .user-view-backdrop,
        .record-popup-backdrop,
        .soil-detail-backdrop,
        .soil-form-backdrop,
        .munsell-edit-backdrop,
        .munsell-form-backdrop,
        .munsell-detail-backdrop,
        .mapping-detail-backdrop,
        .faq-form-backdrop,
        .faq-detail-backdrop,
        .profile-modal-overlay,
        div[class*="-backdrop"] {
            position: fixed !important;
            inset: 0 !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            min-width: 100% !important;
            height: 100vh !important;
            height: 100dvh !important;
            min-height: 100% !important;
            margin: 0 !important;
            box-sizing: border-box !important;
            z-index: 99999 !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            overscroll-behavior: contain !important;
        }


        /* â”€â”€ UNIVERSAL TEXT-OVERFLOW & CONTAINER CONTAINMENT (ADMIN) â”€â”€ */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        p, span, a, h1, h2, h3, h4, h5, h6, td, th, label, div, dt, dd {
            overflow-wrap: break-word;
            word-break: normal;
            hyphens: none;
        }

        /* Container clipping on cards, widgets, tables, and modals */
        .card, .stat-card, .table-container, .modal-card, .bg-white, .rounded-xl, .rounded-2xl {
            max-width: 100%;
        }

        /* Multi-Line Text Clamping Utilities */
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .line-clamp-4 {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .line-clamp-6 {
            display: -webkit-box;
            -webkit-line-clamp: 6;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Rounded Square Action Buttons (View, Edit, Delete, Suspend) â€” Card Icon Style */
        .agrico-view-btn,
        .agrico-edit-btn,
        .agrico-delete-btn,
        .agrico-suspend-btn,
        .agrico-unsuspend-btn {
            width: 34px !important;
            height: 34px !important;
            min-width: 34px !important;
            max-width: 34px !important;
            min-height: 34px !important;
            max-height: 34px !important;
            border-radius: 11px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            outline: none !important;
            text-decoration: none !important;
            box-sizing: border-box !important;
            padding: 0 !important;
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            color: #64748b !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .agrico-view-btn svg,
        .agrico-edit-btn svg,
        .agrico-delete-btn svg,
        .agrico-suspend-btn svg,
        .agrico-unsuspend-btn svg {
            width: 16px !important;
            height: 16px !important;
            fill: none !important;
            stroke-width: 1.9 !important;
            stroke-linecap: round !important;
            stroke-linejoin: round !important;
            stroke: #64748b !important;
            transition: stroke 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        /* View (Blue on hover) */
        .agrico-view-btn:hover {
            background-color: #eff6ff !important;
            border-color: #bfdbfe !important;
            color: #2563eb !important;
            transform: translateY(-1.5px) !important;
            box-shadow: 0 3px 8px rgba(37,99,235,0.18) !important;
        }
        .agrico-view-btn:hover svg {
            stroke: #2563eb !important;
        }

        /* Edit (Green on hover) */
        .agrico-edit-btn:hover {
            background-color: #f0fdf4 !important;
            border-color: #bbf7d0 !important;
            color: #16a34a !important;
            transform: translateY(-1.5px) !important;
            box-shadow: 0 3px 8px rgba(34,197,94,0.18) !important;
        }
        .agrico-edit-btn:hover svg {
            stroke: #16a34a !important;
        }

        /* Delete (Red on hover) */
        .agrico-delete-btn:hover {
            background-color: #fef2f2 !important;
            border-color: #fecaca !important;
            color: #ef4444 !important;
            transform: translateY(-1.5px) !important;
            box-shadow: 0 3px 8px rgba(239,68,68,0.18) !important;
        }
        .agrico-delete-btn:hover svg {
            stroke: #dc2626 !important;
        }

        /* Suspend (Orange on hover) */
        .agrico-suspend-btn:hover {
            background-color: #fff7ed !important;
            border-color: #fdba74 !important;
            color: #ea580c !important;
            transform: translateY(-1.5px) !important;
            box-shadow: 0 3px 8px rgba(234,88,12,0.18) !important;
        }
        .agrico-suspend-btn:hover svg {
            stroke: #ea580c !important;
        }

        /* Unsuspend / Reactivate (Emerald on hover) */
        .agrico-unsuspend-btn:hover {
            background-color: #ecfdf5 !important;
            border-color: #a7f3d0 !important;
            color: #059669 !important;
            transform: translateY(-1.5px) !important;
            box-shadow: 0 3px 8px rgba(5,150,105,0.18) !important;
        }
        .agrico-unsuspend-btn:hover svg {
            stroke: #059669 !important;
        }

        /* Dark Mode for Action Buttons */
        .dark .agrico-view-btn,
        .dark .agrico-edit-btn,
        .dark .agrico-delete-btn,
        .dark .agrico-suspend-btn,
        .dark .agrico-unsuspend-btn {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #94a3b8 !important;
        }
        .dark .agrico-view-btn svg,
        .dark .agrico-edit-btn svg,
        .dark .agrico-delete-btn svg,
        .dark .agrico-suspend-btn svg,
        .dark .agrico-unsuspend-btn svg {
            stroke: #94a3b8 !important;
        }
        .dark .agrico-view-btn:hover {
            background-color: rgba(37,99,235,0.2) !important;
            border-color: #3b82f6 !important;
            color: #60a5fa !important;
        }
        .dark .agrico-view-btn:hover svg {
            stroke: #60a5fa !important;
        }
        .dark .agrico-edit-btn:hover {
            background-color: rgba(34,197,94,0.2) !important;
            border-color: #22c55e !important;
            color: #4ade80 !important;
        }
        .dark .agrico-edit-btn:hover svg {
            stroke: #4ade80 !important;
        }
        .dark .agrico-delete-btn:hover {
            background-color: rgba(239,68,68,0.2) !important;
            border-color: #ef4444 !important;
            color: #f87171 !important;
        }
        .dark .agrico-delete-btn:hover svg {
            stroke: #f87171 !important;
        }


        /* Unified Sticky Action Column across all Admin Tables (Always Visible) */
        th.col-sticky-action,
        td.col-sticky-action {
            position: sticky !important;
            right: 0 !important;
            white-space: nowrap !important;
            box-shadow: none !important;
        }
        th.col-sticky-action {
            z-index: 20 !important;
            background-color: #F1FBF1 !important;
        }
        td.col-sticky-action {
            z-index: 10 !important;
            background-color: #ffffff !important;
        }
        tbody tr:hover td.col-sticky-action,
        tbody tr.hover\:bg-green-50\/40:hover td.col-sticky-action {
            background-color: #f7fdf7 !important;
        }
        tbody tr.bg-emerald-50\/80 td.col-sticky-action {
            background-color: #ecfdf5 !important;
        }
        .dark th.col-sticky-action {
            background-color: #1e293b !important;
        }
        .dark td.col-sticky-action {
            background-color: #0f172a !important;
        }

        /* Sticky ID Column (left: 0) */
        th.col-sticky-id,
        td.col-sticky-id {
            position: sticky !important;
            left: 0 !important;
            min-width: 125px !important;
            max-width: 125px !important;
            width: 125px !important;
            white-space: nowrap !important;
        }
        th.col-sticky-id {
            z-index: 21 !important;
            background-color: #F1FBF1 !important;
        }
        td.col-sticky-id {
            z-index: 11 !important;
            background-color: #ffffff !important;
        }
        tbody tr:hover td.col-sticky-id,
        tbody tr.hover\:bg-green-50\/40:hover td.col-sticky-id {
            background-color: #f7fdf7 !important;
        }
        tbody tr.bg-emerald-50\/80 td.col-sticky-id {
            background-color: #ecfdf5 !important;
        }
        .dark th.col-sticky-id {
            background-color: #1e293b !important;
        }
        .dark td.col-sticky-id {
            background-color: #0f172a !important;
        }

        /* Sticky Name Column (left: 125px, right next to ID) */
        th.col-sticky-name,
        td.col-sticky-name {
            position: sticky !important;
            left: 125px !important;
            white-space: nowrap !important;
            box-shadow: 4px 0 8px -2px rgba(0, 0, 0, 0.06) !important;
        }
        th.col-sticky-name {
            z-index: 20 !important;
            background-color: #F1FBF1 !important;
        }
        td.col-sticky-name {
            z-index: 10 !important;
            background-color: #ffffff !important;
        }
        tbody tr:hover td.col-sticky-name,
        tbody tr.hover\:bg-green-50\/40:hover td.col-sticky-name {
            background-color: #f7fdf7 !important;
        }
        tbody tr.bg-emerald-50\/80 td.col-sticky-name {
            background-color: #ecfdf5 !important;
        }
        .dark th.col-sticky-name {
            background-color: #1e293b !important;
        }
        .dark td.col-sticky-name {
            background-color: #0f172a !important;
        }

        /* Smooth animated collapse/expand for text and elements */
        .sidebar-hide-collapsed {
            opacity: 1;
            max-width: 200px;
            transition: opacity 0.2s ease, max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            white-space: nowrap;
        }
        @media (min-width: 701px) {
            html.sidebar-is-collapsed .sidebar-hide-collapsed {
                opacity: 0;
                max-width: 0;
                pointer-events: none;
            }
            html.sidebar-is-collapsed .sidebar-nav-badge {
                display: none !important;
            }
        }

        .sidebar-nav-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 9999px;
            flex-shrink: 0;
            line-height: 1;
        }

        .sidebar-show-collapsed {
            opacity: 0;
            max-height: 0;
            transition: opacity 0.2s ease, max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        html.sidebar-is-collapsed .sidebar-show-collapsed {
            opacity: 1;
            max-height: 200px;
        }

        .sidebar-nav-link {
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sidebar-goto-web {
            margin-top: auto;
        }
        @media (hover: hover) and (pointer: fine) {
            .sidebar-nav-link:not(.is-active):hover {
                background-color: #f3faf3 !important;
                color: #22c55e !important;
            }
            .sidebar-nav-link:not(.is-active):hover svg {
                color: #22c55e !important;
            }
            .sidebar-action-item:hover {
                background-color: #f0fdf4 !important;
            }
            .sidebar-logout-item:hover {
                background-color: #fef2f2 !important;
            }
        }
        .sidebar-nav-link.is-active {
            background-color: #eefbe7 !important;
            color: #22c55e !important;
        }
        .sidebar-nav-link.is-active svg {
            color: #22c55e !important;
        }

        html {
            scrollbar-gutter: stable;
            overflow-y: scroll;
        }

        html, body {
            color-scheme: light !important;
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }

        /* Sleek Modern Scrollbar for Admin Panel */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
            display: block !important;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb {
            background-color: #94a3b8;
            border-radius: 9999px;
            border: 2px solid #f1f5f9;
            background-clip: padding-box;
            transition: background-color 0.2s ease;
        }
        ::-webkit-scrollbar-thumb:hover {
            background-color: #22c55e;
        }
        ::-webkit-scrollbar-thumb:active {
            background-color: #16a34a;
        }

        .custom-sidebar-scroll {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        .custom-sidebar-scroll::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar,
        .soil-table-container::-webkit-scrollbar,
        .student-table-container::-webkit-scrollbar,
        .map-table-container::-webkit-scrollbar {
          display: none !important;
          width: 0 !important;
          height: 0 !important;
        }
        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar,
        .soil-table-container,
        .student-table-container,
        .map-table-container {
          -ms-overflow-style: none !important;
          scrollbar-width: none !important;
        }

        /* System-wide Admin Modal Lock & Smooth Scrollbar Styling */
        .admin-modal-scroll, .modal-animated-card, [data-admin-scroll] {
            scroll-behavior: smooth;
            scrollbar-gutter: stable;
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }
        .admin-modal-scroll::-webkit-scrollbar, .modal-animated-card::-webkit-scrollbar, [data-admin-scroll]::-webkit-scrollbar {
            width: 6px;
            display: block !important;
        }
        .admin-modal-scroll::-webkit-scrollbar-track, .modal-animated-card::-webkit-scrollbar-track, [data-admin-scroll]::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 9999px;
        }
        .admin-modal-scroll::-webkit-scrollbar-thumb, .modal-animated-card::-webkit-scrollbar-thumb, [data-admin-scroll]::-webkit-scrollbar-thumb {
            background-color: #94a3b8;
            border-radius: 9999px;
            border: 1.5px solid #f1f5f9;
            background-clip: padding-box;
            transition: background-color 0.2s ease;
        }
        .admin-modal-scroll::-webkit-scrollbar-thumb:hover, .modal-animated-card::-webkit-scrollbar-thumb:hover, [data-admin-scroll]::-webkit-scrollbar-thumb:hover {
            background-color: #22c55e;
        }

        /* ── Universal Modal Close / Exit Circle Button (Admin Standard) ── */
        .modal-close-circle-btn,
        .admin-close-btn {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
            border-radius: 50% !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            border: none !important;
            background: transparent !important;
            color: #94a3b8 !important;
            outline: none !important;
            padding: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            flex-shrink: 0 !important;
            transform: none !important;
            box-shadow: none !important;
        }
        .modal-close-circle-btn svg,
        .admin-close-btn svg {
            width: 18px !important;
            height: 18px !important;
            stroke-width: 2.2 !important;
            stroke: currentColor !important;
            display: block !important;
            pointer-events: none !important;
            transition: none !important;
            transform: none !important;
        }
        .modal-close-circle-btn:hover,
        .admin-close-btn:hover {
            background: #f1f5f9 !important;
            border: none !important;
            color: #0f172a !important;
            transform: scale(1.06) !important;
            box-shadow: none !important;
        }
        .modal-close-circle-btn:active,
        .admin-close-btn:active {
            background: #e2e8f0 !important;
            transform: scale(0.95) !important;
            box-shadow: none !important;
        }

        .modal-close-circle-btn--sm,
        .admin-close-btn--sm {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
        }
        .modal-close-circle-btn--sm svg,
        .admin-close-btn--sm svg {
            width: 16px !important;
            height: 16px !important;
        }
    </style>

    @include('admin.partials.head')

    @vite([
    'resources/css/app.css',
    'resources/js/app.js'
    ])

    @livewireStyles

</head>

<body class="admin-portal overflow-x-hidden bg-[#E8F5E9] text-slate-900 transition-colors duration-200">

    <div
        x-data="{
            collapsed: window.innerWidth <= 768 
                ? true 
                : (localStorage.getItem('sidebarCollapsed') === 'true' || localStorage.getItem('agrico_sidebar_collapsed') === 'true'),
            userToggled: localStorage.getItem('agrico_sidebar_user_toggled') === 'true',
            lastWidth: window.innerWidth,
            screenWidth: window.innerWidth,
            mobileNavOpen: false,
            showAdminProfileModal: false,
            init() {
                this.screenWidth = window.innerWidth;
                if (window.innerWidth <= 768) {
                    this.collapsed = true;
                }
                this.checkScreen();
                window.addEventListener('resize', () => this.checkScreen());
                this.updateClass();
                this.$watch('collapsed', value => {
                    if (window.innerWidth > 768) {
                        localStorage.setItem('sidebarCollapsed', value);
                        localStorage.setItem('agrico_sidebar_collapsed', value);
                    }
                    this.updateClass();
                });
                this.$watch('mobileNavOpen', value => {
                    if (value && window.innerWidth <= 768) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                });
                setTimeout(() => {
                    document.documentElement.classList.remove('preload-no-transition');
                }, 200);
            },
            toggleSidebar() {
                if (window.innerWidth <= 768) {
                    this.mobileNavOpen = !this.mobileNavOpen;
                    return;
                }
                this.collapsed = !this.collapsed;
                this.userToggled = true;
                if (window.innerWidth > 768) {
                    localStorage.setItem('sidebarCollapsed', this.collapsed);
                    localStorage.setItem('agrico_sidebar_collapsed', this.collapsed);
                    localStorage.setItem('agrico_sidebar_user_toggled', 'true');
                }
                document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
                document.documentElement.classList.toggle('sidebar-is-collapsed', this.collapsed);
            },
            checkScreen() {
                this.screenWidth = window.innerWidth;
                if (window.innerWidth > 768 && this.mobileNavOpen) {
                    this.mobileNavOpen = false;
                    document.body.style.overflow = '';
                }

                var isSmall = window.innerWidth <= 768;
                var wasSmall = this.lastWidth <= 768;
                this.lastWidth = window.innerWidth;

                if (isSmall && !wasSmall) {
                    this.collapsed = true;
                } else if (!isSmall && wasSmall) {
                    var stored = localStorage.getItem('sidebarCollapsed') ?? localStorage.getItem('agrico_sidebar_collapsed');
                    this.collapsed = stored === 'true' || stored === true;
                }
                this.updateClass();
            },
            updateClass() {
                document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
                document.documentElement.classList.toggle('sidebar-is-collapsed', this.collapsed);
            }
        }"
        class="min-h-screen flex bg-[#E8F5E9] w-full relative transition-colors duration-200"
    >

        {{-- MOBILE TOP BURGER HEADER (Visible ONLY <= 768px) --}}
        <header
            class="mobile-top-header"
            id="mobileTopHeader"
        >
            <div style="display: flex; align-items: center; gap: 10px;">
                {{-- Hamburger Menu Button --}}
                <button
                    type="button"
                    @click="mobileNavOpen = !mobileNavOpen"
                    class="mobile-burger-btn"
                    aria-label="Toggle navigation menu"
                    style="width: 40px; height: 40px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #1e293b; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 0;"
                    onmouseover="this.style.backgroundColor='#f1f5f9'; this.style.borderColor='#cbd5e1';"
                    onmouseout="this.style.backgroundColor='#f8fafc'; this.style.borderColor='#e2e8f0';"
                >
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                {{-- Branding / Logo --}}
                <a href="{{ route('dashboard') }}" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
                    <img
                        src="{{ asset('images/agrico-logo.png') }}"
                        style="width: 34px; height: 34px; object-fit: contain; flex-shrink: 0;"
                        alt="AgriCo"
                    >
                    <div style="display: flex; flex-direction: column; justify-content: center;">
                        <span style="font-size: 19px; font-weight: 900; color: #22c55e; line-height: 1; letter-spacing: -0.02em;">
                            AgriCo
                        </span>
                        <span style="font-size: 10px; color: #94a3b8; font-weight: 600; line-height: 1.15;">
                            Soil Management
                        </span>
                    </div>
                </a>
            </div>

            {{-- Right: Notification Part & Profile Avatar --}}
            @php
                $topAdminUser = auth()->user() ?? Auth::guard('admin')->user();
                $topAdminName = $topAdminUser?->name ?: ($topAdminUser?->Full_Name ?: 'Admin');
                $topAdminPhotoUrl = $topAdminUser?->profile_photo_url;
                $topAdminInitial = strtoupper(substr($topAdminName ?: 'A', 0, 1));
            @endphp

            <div style="display: flex; align-items: center; gap: 8px;">
                {{-- REAL-TIME NOTIFICATION BELL --}}
                <livewire:admin.notification-bell />

                {{-- USER PROFILE AVATAR --}}
                <div
                    @click="Livewire.dispatch('open-admin-profile')"
                    style="cursor: pointer; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; transition: all 0.2s ease;"
                    title="Admin Profile"
                    onmouseover="this.style.borderColor='#22c55e'"
                    onmouseout="this.style.borderColor='#e2e8f0'"
                >
                    @if($topAdminPhotoUrl)
                        <img
                            src="{{ $topAdminPhotoUrl }}"
                            alt="{{ $topAdminName }}"
                            style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;"
                        />
                    @else
                        <div style="width: 30px; height: 30px; border-radius: 50%; background: #22c55e; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">
                            {{ $topAdminInitial }}
                        </div>
                    @endif
                </div>
            </div>
        </header>

        {{-- MOBILE BACKDROP OVERLAY (Visible ONLY <= 768px when mobileNavOpen is true) --}}
        <div
            x-show="mobileNavOpen"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            @click="mobileNavOpen = false"
            style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 999;"
        ></div>

        @include('layouts.sidebar')

        <main
            id="appMainContent"
            style="flex: 1; min-width: 0; min-height: 100vh; padding: 32px 36px 48px 36px; box-sizing: border-box; transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1); width: 100%; background-color: #E8F5E9 !important;"
        >

            {{ $slot }}

        </main>

        {{-- ADMIN PROFILE MODAL --}}
        @livewire('admin.admin-profile-modal')

    </div>

    @livewireScripts
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419) {
                        preventDefault();
                        window.location.reload();
                    }
                });
            });
        });

        (function() {
            window.addEventListener('wheel', (e) => {
                const backdrop = e.target.closest('.modal-animated-backdrop, .profile-modal-overlay, .fixed.inset-0');
                if (!backdrop) return;
                const card = backdrop.querySelector('.modal-animated-card, .admin-modal-scroll, [data-admin-scroll], .profile-modal-card');
                if (card && e.target !== card && !card.contains(e.target)) {
                    card.scrollTop += e.deltaY;
                }
            }, { passive: true });
        })();
    </script>
</body>

</html>
