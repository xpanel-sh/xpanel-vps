@php($publicHome = $home ?? \App\Support\PublicPageRegistry::content('home'))
<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="es">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $publicHome['company_name'])</title>
    <link href="{{ asset('assets/media/app/apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180">
    <link href="{{ asset('assets/media/app/favicon-32x32.png') }}" rel="icon" sizes="32x32" type="image/png">
    <link href="{{ asset('assets/media/app/favicon.ico') }}" rel="shortcut icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background [--header-height:100px] data-[kt-sticky-header=on]:[--header-height:60px]">
<script>
    const defaultThemeMode = 'light';
    let themeMode = localStorage.getItem('kt-theme') || document.documentElement.getAttribute('data-kt-theme-mode') || defaultThemeMode;
    if (themeMode === 'system') themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.classList.add(themeMode);
</script>
<div class="flex grow flex-col in-data-[kt-sticky-header=on]:pt-(--header-height)">
    <header class="flex items-center transition-[height] shrink-0 bg-background h-(--header-height)" data-kt-sticky="true" data-kt-sticky-class="transition-[height] fixed z-10 top-0 left-0 right-0 backdrop-blur-md bg-background/70 border-b border-border" data-kt-sticky-name="header" data-kt-sticky-offset="200px" id="header">
        <div class="kt-container-fixed flex justify-between items-center lg:gap-4" id="headerContainer">
            <a href="{{ route('home') }}" class="flex items-center gap-2 lg:gap-5">
                <img class="dark:hidden min-h-[42px]" src="{{ asset('assets/media/app/mini-logo-circle.svg') }}" alt="XPanel">
                <img class="hidden dark:inline-block min-h-[42px]" src="{{ asset('assets/media/app/mini-logo-circle-dark.svg') }}" alt="XPanel">
                <h3 class="text-secondary-foreground text-base hidden md:block">{{ $publicHome['company_name'] }}</h3>
                <span class="text-sm text-muted-foreground font-medium px-2.5 hidden md:inline">/</span>
                <span class="text-mono font-medium">Hosting</span>
            </a>
            <div class="flex items-center gap-2.5">@yield('nav_actions')<a class="kt-btn kt-btn-outline" href="{{ route('client.login') }}"><i class="ki-filled ki-profile-circle"></i> Mi cuenta</a></div>
        </div>
    </header>
    <div class="border-y border-border bg-background" id="navbar">
        <div class="kt-container-fixed flex items-center justify-between h-[60px]">
            <div class="kt-menu kt-menu-default" data-kt-menu="true">
                <div class="kt-menu-item"><a class="kt-menu-link" href="{{ route('home') }}"><span class="kt-menu-title">Inicio</span></a></div>
                <div class="kt-menu-item"><a class="kt-menu-link" href="{{ route('home') }}#planes"><span class="kt-menu-title">Planes</span></a></div>
                <div class="kt-menu-item"><a class="kt-menu-link" href="{{ route('pages.show', 'about') }}"><span class="kt-menu-title">Empresa</span></a></div>
                <div class="kt-menu-item"><a class="kt-menu-link" href="mailto:{{ $publicHome['support_email'] }}"><span class="kt-menu-title">Soporte</span></a></div>
            </div>
            <a class="kt-btn kt-btn-primary" href="{{ route('home') }}#planes"><i class="ki-filled ki-handcart"></i> Contratar hosting</a>
        </div>
    </div>
    <main class="grow" id="content" role="content">@yield('content')</main>
    <footer class="footer mt-auto shrink-0 border-t border-border"><div class="kt-container-fixed"><div class="flex flex-col md:flex-row justify-center md:justify-between items-center gap-3 py-5"><div class="flex order-2 md:order-1 gap-2 font-normal text-sm"><span class="text-secondary-foreground">{{ date('Y') }} ©</span><span class="text-mono">{{ $publicHome['company_name'] }}</span></div><nav class="flex order-1 md:order-2 gap-4 font-normal text-sm text-secondary-foreground"><a class="hover:text-primary" href="{{ route('pages.show', 'privacy') }}">Privacidad</a><a class="hover:text-primary" href="{{ route('pages.show', 'terms') }}">Términos</a><a class="hover:text-primary" href="{{ route('pages.show', 'refunds') }}">Reembolsos</a></nav></div></div></footer>
</div>
<script src="{{ asset('assets/js/core.bundle.js') }}"></script>
<script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
@stack('scripts')
</body>
</html>
