@extends('layouts.client-store')

@section('content')
@php($instance = $hostingAccount->hostInstance)
<div class="kt-container-fixed max-w-[980px]">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-7.5"><div><a class="kt-link text-xs" href="{{ route('client.host.show') }}">← Mis hostings</a><h1 class="text-xl font-medium text-mono mt-2">{{ $hostingAccount->name }}</h1><p class="text-sm text-secondary-foreground mt-1">{{ $hostingAccount->plan?->name ?? 'Plan no asignado' }} · instancia independiente</p></div><a class="kt-btn kt-btn-outline" href="{{ route('client.orders.index') }}">Ver boletas</a></div>
    <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5">
        <div class="kt-card lg:col-span-2"><div class="kt-card-header"><h3 class="kt-card-title">Estado del hosting</h3>@if($instance)<span class="kt-badge kt-badge-outline {{ $instance->status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }}">{{ ucfirst($instance->status) }}</span>@endif</div><div class="kt-card-content p-7.5">
            @if(!$instance)
                <h2 class="text-xl font-medium text-mono">Preparación pendiente</h2><p class="text-sm text-secondary-foreground mt-3">La cuenta existe, pero su instancia requiere revisión administrativa.</p>
            @elseif($instance->status === 'active')
                <h2 class="text-xl font-medium text-mono">Tu panel está disponible</h2><p class="text-sm text-secondary-foreground mt-3">Entrarás con tu sesión de Cloud; no necesitas otra contraseña.</p>
                <form method="POST" action="{{ route('client.host.access', $hostingAccount) }}" class="mt-6">@csrf<button class="kt-btn kt-btn-primary" type="submit"><i class="ki-filled ki-exit-right"></i> Administrar hosting</button></form>
            @elseif($instance->status === 'error')
                <h2 class="text-xl font-medium text-mono">La instalación necesita revisión</h2><p class="text-sm text-secondary-foreground mt-3">Tu servicio permanece registrado mientras el administrador revisa el servidor.</p>
            @else
                <h2 class="text-xl font-medium text-mono">Estamos preparando tu hosting</h2><p class="text-sm text-secondary-foreground mt-3">La instancia quedará disponible al finalizar el aprovisionamiento.</p>
            @endif
        </div></div>
        <div class="kt-card content-start"><div class="kt-card-header"><h3 class="kt-card-title">Acceso</h3></div><div class="kt-card-content p-7.5 grid gap-4 text-sm">
            <div><div class="text-secondary-foreground">Dirección incluida</div><div class="text-mono font-medium mt-1 break-all">{{ $instance?->panel_domain ?? 'Pendiente' }}</div></div>
            <div><div class="text-secondary-foreground">Dominio personalizado</div><div class="text-mono font-medium mt-1 break-all">{{ $hostingAccount->custom_panel_domain ?: 'No configurado' }}</div><p class="text-xs text-secondary-foreground mt-2">Podrás usar, por ejemplo, panel.tudominio.com sin perder la dirección incluida.</p></div>
            @if($instance?->fallbackUrl())<div><div class="text-secondary-foreground">Acceso de emergencia</div><a class="kt-link break-all mt-1" href="{{ $instance->fallbackUrl() }}" target="_blank" rel="noopener">{{ $instance->fallbackUrl() }}</a></div>@endif
            <div><div class="text-secondary-foreground">Versión Host</div><div class="text-mono font-medium mt-1">{{ $instance?->version ?? 'Pendiente' }}</div></div>
        </div></div>
    </div>
    <div class="kt-card mt-5 lg:mt-7.5"><div class="kt-card-header"><h3 class="kt-card-title">Dominio personalizado del panel</h3><span class="kt-badge kt-badge-outline {{ $hostingAccount->custom_domain_status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }}">{{ str_replace('_', ' ', ucfirst($hostingAccount->custom_domain_status)) }}</span></div><div class="kt-card-content p-7.5">
        <form method="POST" action="{{ route('client.host.domain', $hostingAccount) }}" class="flex flex-col md:flex-row md:items-end gap-3">@csrf @method('PUT')<label class="grow max-w-xl"><span class="text-sm font-medium text-mono">Dirección propia</span><input class="kt-input mt-2" name="custom_panel_domain" value="{{ old('custom_panel_domain', $hostingAccount->custom_panel_domain) }}" placeholder="panel.empresa.com"></label><button class="kt-btn kt-btn-primary" type="submit">Guardar dominio</button></form>
        <p class="text-xs text-secondary-foreground mt-3">Crea un registro A hacia {{ config('xpanel.server_ip') ?: 'la IP de este servidor' }} o un CNAME hacia {{ $instance?->panel_domain ?? 'la dirección incluida' }}. La dirección incluida nunca se elimina.</p>
        @if($hostingAccount->custom_domain_last_error)<p class="text-sm text-danger mt-3">{{ $hostingAccount->custom_domain_last_error }}</p>@endif
    </div></div>
</div>
@endsection
