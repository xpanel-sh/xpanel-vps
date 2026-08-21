@extends('layouts.client-store')

@section('content')
<div>
    <div>
        <main class="grow" role="content">
            <div class="kt-container-fixed">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-7.5">
                    <div><h1 class="text-xl font-medium text-mono">Información de la cuenta</h1><p class="text-sm text-secondary-foreground mt-1">Datos del propietario, servicio contratado y consumo.</p></div>
                    <a class="kt-btn kt-btn-outline" href="{{ route('client.plans.index') }}"><i class="ki-filled ki-handcart"></i> Ver planes</a>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 lg:gap-7.5">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="kt-card min-w-full">
                            <div class="kt-card-header"><h3 class="kt-card-title">Información personal</h3><span class="kt-badge kt-badge-sm {{ $tenant->status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }} kt-badge-outline">{{ ucfirst($tenant->status) }}</span></div>
                            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                                <table class="kt-table align-middle text-sm text-muted-foreground">
                                    <tr><td class="py-3 min-w-36 text-secondary-foreground font-normal">Nombre</td><td class="py-3 text-foreground font-normal">{{ $tenant->user?->name }}</td></tr>
                                    <tr><td class="py-3 text-secondary-foreground font-normal">Correo</td><td class="py-3"><a class="text-foreground hover:text-primary" href="mailto:{{ $tenant->user?->email }}">{{ $tenant->user?->email }}</a></td></tr>
                                    <tr><td class="py-3 text-secondary-foreground font-normal">Empresa</td><td class="py-3 text-foreground font-normal">{{ $tenant->name }}</td></tr>
                                    <tr><td class="py-3 text-secondary-foreground font-normal">Dominio</td><td class="py-3 text-foreground font-normal">{{ $tenant->domain }}</td></tr>
                                    <tr><td class="py-3 text-secondary-foreground font-normal">Código de cliente</td><td class="py-3"><span class="kt-badge kt-badge-outline">{{ $tenant->code }}</span></td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="kt-card min-w-full">
                            <div class="kt-card-header"><h3 class="kt-card-title">Uso de la cuenta</h3></div>
                            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                                <table class="kt-table align-middle text-sm text-muted-foreground">
                                    <tr><td class="py-3 min-w-36 text-secondary-foreground">Sitios</td><td class="py-3 text-foreground">{{ $usage['sites'] }} / {{ $tenant->plan?->max_sites ?? 'Sin límite definido' }}</td></tr>
                                    <tr><td class="py-3 text-secondary-foreground">Bases de datos</td><td class="py-3 text-foreground">{{ $usage['databases'] }} / {{ $tenant->plan?->max_databases ?? 'Sin límite definido' }}</td></tr>
                                    <tr><td class="py-3 text-secondary-foreground">Dominios</td><td class="py-3 text-foreground">{{ $usage['domains'] }}</td></tr>
                                    <tr><td class="py-3 text-secondary-foreground">Correos</td><td class="py-3 text-foreground">{{ $usage['emails'] }} / {{ $tenant->plan?->email_accounts ?? 'Sin límite definido' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="grid gap-5 lg:gap-7.5 content-start">
                        <div class="kt-card">
                            <div class="kt-card-header"><h3 class="kt-card-title">Plan contratado</h3>@if($tenant->plan)<span class="kt-badge kt-badge-success kt-badge-outline">Actual</span>@endif</div>
                            <div class="kt-card-content p-7.5">
                                <h2 class="text-2xl text-mono font-semibold">{{ $tenant->plan?->name ?? 'Sin plan asignado' }}</h2>
                                <p class="text-sm text-secondary-foreground mt-3">{{ $tenant->plan?->description ?? 'El administrador todavía no asignó un plan a esta cuenta.' }}</p>
                                @if($tenant->plan)
                                    <div class="flex items-end gap-1.5 py-6"><div class="text-3xl text-mono font-semibold leading-none">${{ number_format((float) $tenant->plan->monthly_price, 2) }}</div><div class="text-secondary-foreground text-xs">por mes</div></div>
                                    <div class="grid gap-3 text-sm"><div class="flex justify-between"><span class="text-secondary-foreground">Almacenamiento</span><span class="text-mono font-medium">{{ number_format($tenant->plan->storage_mb / 1024, 1) }} GB</span></div><div class="flex justify-between"><span class="text-secondary-foreground">Transferencia</span><span class="text-mono font-medium">{{ $tenant->plan->bandwidth_gb }} GB</span></div></div>
                                @endif
                                <a class="kt-btn kt-btn-primary justify-center w-full mt-7" href="{{ route('client.plans.index') }}">Comparar planes</a>
                            </div>
                        </div>
                        <div class="kt-card"><div class="kt-card-content px-10 py-7.5"><div class="flex flex-wrap md:flex-nowrap items-center gap-6"><div class="flex flex-col items-start gap-3"><h2 class="text-xl font-medium text-mono">¿Necesitas ayuda?</h2><p class="text-sm text-foreground leading-5.5">Contacta al equipo de soporte para revisar tu cuenta, plan o renovación.</p><a class="kt-link kt-link-underlined kt-link-dashed" href="mailto:{{ \App\Support\PublicPageRegistry::content('home')['support_email'] }}">Contactar soporte</a></div><img alt="Soporte" class="dark:hidden max-h-[130px]" src="{{ asset('assets/media/illustrations/4.svg') }}"><img alt="Soporte" class="light:hidden max-h-[130px]" src="{{ asset('assets/media/illustrations/4-dark.svg') }}"></div></div></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
