@extends('layouts.client-store')

@section('content')
<div class="kt-container-fixed max-w-[980px]">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-7.5"><div><h1 class="text-xl font-medium text-mono">Tu panel de hosting</h1><p class="text-sm text-secondary-foreground mt-1">Instancia independiente de XPanel Host para {{ $tenant->name }}.</p></div><a class="kt-btn kt-btn-outline" href="{{ route('client.orders.index') }}">Ver boletas</a></div>
    @php($instance = $tenant->hostInstance)
    <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5">
        <div class="kt-card lg:col-span-2"><div class="kt-card-header"><h3 class="kt-card-title">Estado de la instancia</h3>@if($instance)<span class="kt-badge kt-badge-outline {{ $instance->status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }}">{{ ucfirst($instance->status) }}</span>@endif</div><div class="kt-card-content p-7.5">
            @if(!$tenant->plan)
                <h2 class="text-xl font-medium text-mono">Aún no tienes un plan</h2><p class="text-sm text-secondary-foreground mt-3">Contrata un plan para crear tu entorno de hosting.</p><a class="kt-btn kt-btn-primary mt-6" href="{{ route('client.plans.index') }}">Ver planes</a>
            @elseif(!$instance)
                <h2 class="text-xl font-medium text-mono">Preparación pendiente</h2><p class="text-sm text-secondary-foreground mt-3">Tu plan está activo y conservas acceso. La creación de la instancia requiere revisión administrativa.</p>
            @elseif($instance->status === 'active')
                <h2 class="text-xl font-medium text-mono">Tu panel está disponible</h2><p class="text-sm text-secondary-foreground mt-3">XPanel Host funciona en un entorno aislado con almacenamiento, base de datos y PHP-FPM propios.</p><a class="kt-btn kt-btn-primary mt-6" href="{{ $instance->panelUrl() }}" target="_blank" rel="noopener"><i class="ki-filled ki-exit-right"></i> {{ $instance->ssl_status === 'active' ? 'Abrir XPanel Host' : 'Abrir acceso temporal' }}</a>
            @elseif($instance->status === 'error')
                <h2 class="text-xl font-medium text-mono">La instalación necesita revisión</h2><p class="text-sm text-secondary-foreground mt-3">Tu plan sigue activo. El administrador revisará la configuración del servidor sin afectar la boleta.</p>
            @else
                <h2 class="text-xl font-medium text-mono">Estamos preparando tu hosting</h2><p class="text-sm text-secondary-foreground mt-3">La instancia fue registrada y quedará disponible al finalizar la configuración del servidor.</p>
            @endif
        </div></div>
        <div class="kt-card content-start"><div class="kt-card-header"><h3 class="kt-card-title">Acceso</h3></div><div class="kt-card-content p-7.5 grid gap-4 text-sm">
            <div><div class="text-secondary-foreground">Dominio del panel</div><div class="text-mono font-medium mt-1">{{ $instance?->panel_domain ?? 'Pendiente' }}</div></div>
            @if($instance?->fallbackUrl())<div><div class="text-secondary-foreground">Acceso temporal</div><a class="kt-link break-all mt-1" href="{{ $instance->fallbackUrl() }}" target="_blank" rel="noopener">{{ $instance->fallbackUrl() }}</a><p class="text-xs text-secondary-foreground mt-2">Funciona antes de configurar DNS. El navegador puede advertir sobre el certificado temporal.</p></div>@endif
            @if($instance)<div><div class="text-secondary-foreground">DNS requerido</div><div class="text-mono font-medium mt-1">{{ $instance->panel_domain }} → {{ config('xpanel.server_ip') ?: 'IP del servidor' }}</div><p class="text-xs text-secondary-foreground mt-2">Apuntar solo {{ $tenant->domain }} no crea automáticamente el subdominio del panel.</p></div><div><div class="text-secondary-foreground">SSL</div><span class="kt-badge kt-badge-outline {{ $instance->ssl_status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }} mt-1">{{ $instance->ssl_status === 'active' ? 'Activo' : 'Esperando DNS' }}</span></div>@endif
            <div><div class="text-secondary-foreground">Usuario</div><div class="text-mono font-medium mt-1">{{ $tenant->user?->email }}</div></div>
            @if($instance?->initial_password)<div><div class="text-secondary-foreground">Clave inicial</div><div class="kt-input mt-1 select-all">{{ $instance->initial_password }}</div><p class="text-xs text-secondary-foreground mt-2">Cámbiala al entrar al panel.</p></div>@endif
        </div></div>
    </div>
</div>
@endsection
