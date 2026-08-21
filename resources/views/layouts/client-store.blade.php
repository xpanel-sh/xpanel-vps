<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="es">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'XPanel Cliente')</title>
    <link href="{{ asset('assets/media/app/favicon.ico') }}" rel="shortcut icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background [--header-height:100px] data-[kt-sticky-header=on]:[--header-height:60px]">
<script>const defaultThemeMode='light';let themeMode=localStorage.getItem('kt-theme')||document.documentElement.getAttribute('data-kt-theme-mode')||defaultThemeMode;if(themeMode==='system')themeMode=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.classList.add(themeMode);</script>
@php($clientTenant = request()->attributes->get('tenant'))
<div class="flex grow flex-col in-data-[kt-sticky-header=on]:pt-(--header-height)">
    <header class="flex items-center transition-[height] shrink-0 bg-background h-(--header-height)" data-kt-sticky="true" data-kt-sticky-class="transition-[height] fixed z-10 top-0 left-0 right-0 backdrop-blur-md bg-background/70 border-b border-border" data-kt-sticky-name="header" data-kt-sticky-offset="200px" id="header">
        <div class="kt-container-fixed flex justify-between items-center lg:gap-4" id="headerContainer">
            <a href="{{ route('client.dashboard') }}" class="flex items-center gap-2 lg:gap-5"><img class="dark:hidden min-h-[42px]" src="{{ asset('assets/media/app/mini-logo-circle.svg') }}" alt="XPanel"><img class="hidden dark:inline-block min-h-[42px]" src="{{ asset('assets/media/app/mini-logo-circle-dark.svg') }}" alt="XPanel"><h3 class="text-secondary-foreground text-base hidden md:block">{{ $clientTenant?->name }}</h3><span class="text-sm text-muted-foreground font-medium px-2.5 hidden md:inline">/</span><span class="text-mono font-medium">Panel cliente</span></a>
            <div class="flex items-center gap-2.5" data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-placement="bottom-end" data-kt-dropdown-trigger="click">
                <button class="kt-btn kt-btn-icon kt-btn-ghost size-9 relative rounded-full hover:bg-primary/10" data-kt-dropdown-toggle="true"><i class="ki-filled ki-profile-circle text-xl"></i></button>
                <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
                    <div class="flex items-center gap-2 px-2.5 py-2"><div class="flex items-center justify-center size-9 rounded-full bg-primary/10"><i class="ki-filled ki-profile-circle text-primary"></i></div><div class="flex flex-col gap-1"><span class="text-sm text-foreground font-semibold leading-none">{{ auth()->user()?->name }}</span><span class="text-xs text-secondary-foreground font-medium leading-none">{{ auth()->user()?->email }}</span></div></div>
                    <div class="kt-dropdown-menu-separator"></div>
                    <div class="kt-dropdown-menu-link"><a href="{{ route('client.account.show') }}"><i class="ki-filled ki-setting-2"></i> Mi cuenta</a></div>
                    <form action="{{ route('client.logout') }}" method="POST" class="p-2">@csrf<button class="kt-btn kt-btn-outline justify-center w-full" type="submit">Cerrar sesión</button></form>
                </div>
            </div>
        </div>
    </header>
    <div class="border-y border-border bg-background" id="navbar"><div class="kt-container-fixed flex items-center justify-between h-[60px]"><div class="kt-menu kt-menu-default" data-kt-menu="true"><div class="kt-menu-item {{ request()->routeIs('client.dashboard') ? 'active' : '' }}"><a class="kt-menu-link" href="{{ route('client.dashboard') }}"><span class="kt-menu-icon"><i class="ki-filled ki-home-2"></i></span><span class="kt-menu-title">Inicio</span></a></div><div class="kt-menu-item {{ request()->routeIs('client.plans.*') ? 'active' : '' }}"><a class="kt-menu-link" href="{{ route('client.plans.index') }}"><span class="kt-menu-icon"><i class="ki-filled ki-handcart"></i></span><span class="kt-menu-title">Planes</span></a></div><div class="kt-menu-item {{ request()->routeIs('client.orders.*') ? 'active' : '' }}"><a class="kt-menu-link" href="{{ route('client.orders.index') }}"><span class="kt-menu-icon"><i class="ki-filled ki-bill"></i></span><span class="kt-menu-title">Contrataciones</span></a></div><div class="kt-menu-item {{ request()->routeIs('client.account.*') ? 'active' : '' }}"><a class="kt-menu-link" href="{{ route('client.account.show') }}"><span class="kt-menu-icon"><i class="ki-filled ki-profile-circle"></i></span><span class="kt-menu-title">Mi cuenta</span></a></div></div><a class="kt-btn kt-btn-primary" href="{{ route('client.host.show') }}"><i class="ki-filled ki-screen"></i> Administrar hosting</a></div></div>
    <main class="grow py-7.5 lg:py-12.5" id="content" role="content">@yield('content')</main>
    @include('layouts.partials.client.footer')
</div>
<script src="{{ asset('assets/js/core.bundle.js') }}"></script><script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>@stack('scripts')
</body>
</html>
