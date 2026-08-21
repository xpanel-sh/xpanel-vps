@extends('layouts.client-store')

@php
    $plan = $tenant?->plan;
    $status = $tenant->status ?? 'active';
    $limits = [
        ['Sitios web', $siteCount, $plan?->max_sites, 'ki-screen', route('client.host.show')],
        ['Dominios', $domainCount, null, 'ki-click', route('client.domains.index')],
        ['Bases de datos', $databaseCount, $plan?->max_databases, 'ki-data', route('client.databases.index')],
        ['Correos', $emailCount, $plan?->email_accounts, 'ki-sms', route('client.mail.index')],
    ];
@endphp

@section('content')
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
            <div class="kt-card lg:col-span-2"><div class="kt-card-content flex flex-wrap md:flex-nowrap items-center justify-between gap-7.5 px-7.5 py-7.5 lg:px-10">
                <div class="flex flex-col items-start gap-3"><span class="kt-badge kt-badge-sm {{ $status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }} kt-badge-outline">Servicio {{ $status }}</span><h1 class="text-2xl font-semibold text-mono">Hola, {{ auth()->user()?->name }}</h1><p class="text-sm text-secondary-foreground leading-6 max-w-[520px]">Administra los servicios de {{ $tenant->name }}, consulta el plan contratado y entra a tu instancia independiente de XPanel Host.</p><div class="flex flex-wrap gap-2"><a class="kt-btn kt-btn-primary" href="{{ route('client.host.show') }}"><i class="ki-filled ki-screen"></i> Administrar hosting</a><a class="kt-btn kt-btn-outline" href="{{ route('client.plans.index') }}">Ver planes</a></div></div>
                <img alt="Panel cliente" class="dark:hidden max-h-[170px]" src="{{ asset('assets/media/illustrations/32.svg') }}"><img alt="Panel cliente" class="light:hidden max-h-[170px]" src="{{ asset('assets/media/illustrations/32-dark.svg') }}">
            </div></div>
            <div class="kt-card"><div class="kt-card-header"><h3 class="kt-card-title">Plan actual</h3></div><div class="kt-card-content flex flex-col p-7.5"><h2 class="text-2xl font-semibold text-mono">{{ $plan?->name ?? 'Sin plan' }}</h2><p class="text-sm text-secondary-foreground mt-2 grow">{{ $plan?->description ?? 'Tu cuenta aún no tiene un plan asignado.' }}</p>@if($plan)<div class="flex items-end gap-1.5 py-5"><div class="text-3xl text-mono font-semibold">${{ number_format((float) $plan->monthly_price, 2) }}</div><div class="text-xs text-secondary-foreground pb-1">por mes</div></div>@endif<a class="kt-btn kt-btn-outline justify-center w-full" href="{{ route('client.account.show') }}">Detalles de cuenta</a></div></div>
        </div>
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-5 lg:gap-7.5">
            @foreach($limits as [$label,$used,$limit,$icon,$url])
                <a class="kt-card hover:border-primary" href="{{ $url }}"><div class="kt-card-content flex items-center justify-between gap-3 p-5"><div><div class="text-sm text-secondary-foreground">{{ $label }}</div><div class="text-3xl font-semibold text-mono mt-2">{{ $used }}</div><div class="text-xs text-secondary-foreground mt-1">{{ $limit ? 'Límite: '.$limit : 'En tu cuenta' }}</div></div><div class="flex items-center justify-center size-12 rounded-full bg-primary/10"><i class="ki-filled {{ $icon }} text-xl text-primary"></i></div></div></a>
            @endforeach
        </div>
        <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="kt-card lg:col-span-2"><div class="kt-card-header"><h3 class="kt-card-title">Sitios recientes</h3><a class="kt-link" href="{{ route('client.websites.index') }}">Ver todos</a></div><div class="kt-card-table kt-scrollable-x-auto"><table class="kt-table align-middle"><thead><tr><th>Dominio</th><th>Tipo</th><th>Estado</th><th></th></tr></thead><tbody>@forelse($sites as $site)<tr><td class="text-mono font-medium">{{ $site->domain }}</td><td class="text-secondary-foreground">{{ strtoupper($site->project_type) }}</td><td><span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-success">{{ $site->status ?? 'active' }}</span></td><td class="text-end"><a class="kt-btn kt-btn-sm kt-btn-ghost" href="{{ route('client.websites.show', $site->domain) }}">Administrar</a></td></tr>@empty<tr><td colspan="4" class="text-center py-8 text-secondary-foreground">Todavía no tienes sitios creados.</td></tr>@endforelse</tbody></table></div></div>
            <div class="kt-card"><div class="kt-card-header"><h3 class="kt-card-title">Acciones rápidas</h3></div><div class="kt-card-content grid gap-3 p-5"><a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.websites.create') }}"><i class="ki-filled ki-plus"></i> Crear sitio</a><a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.domains.create') }}"><i class="ki-filled ki-click"></i> Agregar dominio</a><a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.databases.create') }}"><i class="ki-filled ki-data"></i> Crear base de datos</a><a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.mail.create') }}"><i class="ki-filled ki-sms"></i> Crear correo</a></div></div>
        </div>
    </div>
</div>
@endsection
