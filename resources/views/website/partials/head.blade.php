<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'AgriCo') : config('app.name', 'AgriCo') }}
</title>

<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/images/agrico-logo.png" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="stylesheet" href="{{ asset('css/animations.css') }}">

<script>
    try {
        localStorage.removeItem('theme');
        localStorage.removeItem('agrico_theme');
        localStorage.removeItem('flux.appearance');
        document.documentElement.classList.remove('dark');
        document.documentElement.style.colorScheme = 'light';
    } catch(e) {}
</script>

<style>
    /* Global Chrome & Browser Autofill Gray/Blue Background Override */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
        -webkit-text-fill-color: #0f172a !important;
        caret-color: #0f172a !important;
        transition: background-color 5000s ease-in-out 0s !important;
    }

    /* Prevent forced scrollbars on auth screens when content fits */
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
