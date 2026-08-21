<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'XPanel')</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <link rel="shortcut icon" href="{{ asset('assets/media/app/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/app/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/media/app/favicon-16x16.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/xpanel.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body class="@yield('body_class', 'antialiased h-full text-base text-foreground bg-background')">
    <script>
        (function () {
            const stored = localStorage.getItem('kt-theme') || document.documentElement.getAttribute('data-kt-theme-mode') || 'light';
            const mode = stored === 'system'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : stored;

            document.documentElement.classList.add(mode);
            document.documentElement.setAttribute('data-kt-theme-mode', mode);
        })();
    </script>

    @yield('content')

    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
