<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="color-scheme" content="light" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'AgriCo') : config('app.name', 'AgriCo') }}
</title>

<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/images/agrico-logo.png" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Leaflet CSS & JS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" data-navigate-track />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" data-navigate-track></script>
<script src="{{ asset('js/lallo-boundary.js') }}" data-navigate-track></script>
<script src="{{ asset('js/agrico-map-focus.js') }}" data-navigate-track></script>
<script src="{{ asset('js/agrico-pin.js') }}" data-navigate-track></script>

<style>
    html, html.admin-portal, body, body.admin-portal, #appMainContent, .admin-portal {
        background-color: #E8F5E9 !important;
        color: #0f172a !important;
        color-scheme: light !important;
    }
    /* Prevent forced scrollbars on admin auth screens when content fits */
    html.auth-page,
    html:has(body.auth-body) {
        scrollbar-gutter: auto !important;
        overflow-y: auto !important;
    }
    @media (min-height: 620px) {
        html.auth-page,
        html:has(body.auth-body),
        body.auth-body {
            overflow-y: hidden !important;
        }
    }
</style>

<script>
    try {
        localStorage.removeItem('theme');
        localStorage.removeItem('agrico_theme');
        localStorage.removeItem('agrico-theme');
        localStorage.removeItem('flux.appearance');
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.remove('light-mode');
        document.documentElement.classList.add('light', 'admin-portal');
        document.documentElement.style.colorScheme = 'light';
        document.documentElement.setAttribute('data-theme', 'light');
    } catch(e) {}
</script>
