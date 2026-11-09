@extends('layouts.client-store')

@section('content')
@php($instance = $hostingAccount->hostInstance)
<div class="kt-container-fixed max-w-[980px]">
    @if(session('success'))<div class="kt-alert kt-alert-success mb-5">{{ session('success') }}</div>@endif
    @if(session('info'))<div class="kt-alert kt-alert-info mb-5">{{ session('info') }}</div>@endif
    @if($errors->any())<div class="kt-alert kt-alert-danger mb-5">{{ $errors->first() }}</div>@endif
    <div class="flex flex-wrap items-center justify-between gap-3 mb-7.5"><div><a class="kt-link text-xs" href="{{ route('client.host.show') }}">← Mis hostings</a><h1 class="text-xl font-medium text-mono mt-2">{{ $hostingAccount->name }}</h1><p class="text-sm text-secondary-foreground mt-1">{{ $hostingAccount->plan?->name ?? 'Plan no asignado' }} · instancia de hosting</p></div><a class="kt-btn kt-btn-outline" href="{{ route('client.orders.index') }}">Ver boletas</a></div>
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
            <div><div class="text-secondary-foreground">1 · Dominio propio @if($hostingAccount->custom_panel_domain)<span class="kt-badge kt-badge-outline ms-1">{{ $hostingAccount->custom_domain_status }}</span>@endif</div>@if($hostingAccount->custom_panel_domain)<a class="kt-link break-all mt-1" href="https://{{ $hostingAccount->custom_panel_domain }}" target="_blank" rel="noopener">https://{{ $hostingAccount->custom_panel_domain }}</a>@else<div class="text-mono mt-1">No configurado</div>@endif</div>
            <div><div class="text-secondary-foreground">2 · Dirección técnica @if($instance)<span class="kt-badge kt-badge-outline ms-1">{{ $instance->ssl_status === 'active' ? 'SSL activo' : 'SSL pendiente' }}</span>@endif</div>@if($instance)<a class="kt-link break-all mt-1" href="https://{{ $instance->panel_domain }}" target="_blank" rel="noopener">https://{{ $instance->panel_domain }}</a>@else<div>Pendiente</div>@endif</div>
            @if($instance?->fallbackUrl())<div><div class="text-secondary-foreground">3 · Acceso por IP y puerto</div><a class="kt-link break-all mt-1" href="{{ $instance->fallbackUrl() }}" target="_blank" rel="noopener">{{ $instance->fallbackUrl() }}</a></div>@endif
            <div><div class="text-secondary-foreground">Versión Host</div><div class="text-mono font-medium mt-1">{{ $instance?->version ?? 'Pendiente' }}</div></div>
        </div></div>
    </div>
    @if($hostingAccount->initial_site_domain)<section class="kt-card mt-5"><div class="kt-card-content p-5 flex flex-wrap items-center justify-between gap-3"><div><strong class="text-sm">Primer sitio: {{ $hostingAccount->initial_site_domain }}</strong><p class="text-xs text-secondary-foreground mt-1">{{ match($hostingAccount->initial_site_status) { 'active' => 'Sitio creado. Configura DNS y SSL desde Host.', 'error' => 'No se pudo terminar el sitio; el hosting sigue disponible.', default => 'Preparando el sitio.' } }}</p>@if($hostingAccount->initial_site_error)<p class="text-xs text-destructive mt-1">{{ $hostingAccount->initial_site_error }}</p>@endif</div>@if($hostingAccount->initial_site_status === 'error')<form method="post" action="{{ route('client.host.initial-site.retry', $hostingAccount) }}">@csrf<button class="kt-btn kt-btn-outline" type="submit">Reintentar sitio</button></form>@endif</div></section>@endif
    <div class="kt-card mt-5 lg:mt-7.5"><div class="kt-card-header"><h3 class="kt-card-title">Dominio personalizado del panel</h3><span class="kt-badge kt-badge-outline {{ $hostingAccount->custom_domain_status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }}">{{ str_replace('_', ' ', ucfirst($hostingAccount->custom_domain_status)) }}</span></div><div class="kt-card-content p-7.5">
        <form method="POST" action="{{ route('client.host.domain', $hostingAccount) }}" class="flex flex-col md:flex-row md:items-end gap-3">@csrf @method('PUT')<label class="grow max-w-xl"><span class="text-sm font-medium text-mono">Dirección propia</span><input class="kt-input mt-2" name="custom_panel_domain" value="{{ old('custom_panel_domain', $hostingAccount->custom_panel_domain) }}" placeholder="panel.empresa.com"></label><button class="kt-btn kt-btn-primary" type="submit">Guardar dominio</button></form>
        <p class="text-xs text-secondary-foreground mt-3">Crea un registro A hacia {{ config('xpanel.server_ip') ?: 'la IP de este servidor' }} o un CNAME hacia {{ $instance?->panel_domain ?? 'la dirección incluida' }}. La dirección incluida nunca se elimina.</p>
        @if($hostingAccount->custom_domain_last_error)<p class="text-sm text-danger mt-3">{{ $hostingAccount->custom_domain_last_error }}</p>@endif
    </div></div>
    <div class="grid gap-5 mt-5 lg:mt-7.5 lg:grid-cols-2">
        <section class="kt-card"><div class="kt-card-header"><div><h3 class="kt-card-title">Cambiar plan</h3><p class="mt-1 text-xs text-secondary-foreground">Conserva esta instancia y aplica sus nuevos límites.</p></div></div><div class="kt-card-content p-7.5">
            <form method="post" action="{{ route('client.host.plan', $hostingAccount) }}" class="grid gap-4">@csrf
                <label class="grid gap-2"><span class="text-sm font-medium text-mono">Plan disponible</span><select class="kt-select" name="plan_id" required>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected($hostingAccount->hosting_plan_id === $plan->id)>{{ $plan->name }} · {{ $plan->max_sites }} sitios · {{ number_format($plan->storage_mb / 1024, 1) }} GB · ${{ number_format((float)$plan->monthly_price, 2) }}</option>@endforeach</select></label>
                <p class="text-xs text-secondary-foreground">Una bajada se rechazará si el consumo actual no cabe en el nuevo plan.</p><div><button class="kt-btn kt-btn-primary" type="submit">Aplicar nuevo plan</button></div>
            </form>
        </div></section>
        <section class="kt-card"><div class="kt-card-header"><div><h3 class="kt-card-title">Ciclo del servicio</h3><p class="mt-1 text-xs text-secondary-foreground">Cancelación reversible durante el periodo de retención.</p></div></div><div class="kt-card-content p-7.5">
            @if($hostingAccount->status === 'active')
                <p class="text-sm text-secondary-foreground">El hosting seguirá operativo hasta terminar el periodo contratado. Después se suspenderá sin borrar sus datos.</p>
                <form class="mt-5" method="post" action="{{ route('client.host.cancel', $hostingAccount) }}" onsubmit="return confirm('¿Programar la cancelación de este hosting?')">@csrf<button class="kt-btn kt-btn-outline text-destructive" type="submit">Programar cancelación</button></form>
            @elseif(in_array($hostingAccount->status, ['cancellation_pending','suspended'], true))
                <div class="grid gap-2 text-sm"><div><span class="text-secondary-foreground">Estado:</span> {{ $hostingAccount->status === 'suspended' ? 'Suspendido' : 'Cancelación programada' }}</div>@if($hostingAccount->suspend_at)<div><span class="text-secondary-foreground">Suspensión:</span> {{ $hostingAccount->suspend_at->format('d/m/Y H:i') }}</div>@endif @if($hostingAccount->purge_after)<div><span class="text-secondary-foreground">Retención hasta:</span> {{ $hostingAccount->purge_after->format('d/m/Y H:i') }}</div>@endif</div>
                <form class="mt-5" method="post" action="{{ route('client.host.restore', $hostingAccount) }}">@csrf<button class="kt-btn kt-btn-primary" type="submit">Conservar y restaurar hosting</button></form>
            @elseif($hostingAccount->status === 'purge_pending')<div class="kt-alert kt-alert-warning"><i class="ki-filled ki-information-2"></i><div><strong>Terminó el periodo de retención.</strong><p class="mt-1 text-xs">La cuenta sigue suspendida y ningún dato se elimina automáticamente. Contacta al administrador para revisar una recuperación o depuración definitiva.</p></div></div>
            @else<p class="text-sm text-secondary-foreground">El servicio está {{ $hostingAccount->status }}.</p>@endif
        </div></section>
    </div>
</div>
@endsection
