@extends('layouts.client-store')

@section('content')
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card"><div class="kt-card-content flex flex-wrap md:flex-nowrap items-center justify-between gap-7.5 px-7.5 py-7.5 lg:px-10">
            <div class="flex flex-col items-start gap-3"><span class="kt-badge kt-badge-sm {{ $tenant->status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }} kt-badge-outline">Cuenta {{ $tenant->status }}</span><h1 class="text-2xl font-semibold text-mono">Hola, {{ auth()->user()?->name }}</h1><p class="text-sm text-secondary-foreground leading-6 max-w-[620px]">Desde Cloud administras contrataciones, boletas y todas las cuentas de hosting de {{ $tenant->name }}.</p><div class="flex flex-wrap gap-2"><a class="kt-btn kt-btn-primary" href="{{ route('client.host.show') }}"><i class="ki-filled ki-screen"></i> Mis hostings</a><a class="kt-btn kt-btn-outline" href="{{ route('client.plans.index') }}">Contratar hosting</a></div></div>
            <img alt="XPanel Cloud" class="dark:hidden max-h-[170px]" src="{{ asset('assets/media/illustrations/32.svg') }}"><img alt="XPanel Cloud" class="light:hidden max-h-[170px]" src="{{ asset('assets/media/illustrations/32-dark.svg') }}">
        </div></div>
        <div class="grid sm:grid-cols-3 gap-5">
            <a class="kt-card hover:border-primary" href="{{ route('client.host.show') }}"><div class="kt-card-content p-5"><div class="text-sm text-secondary-foreground">Hostings contratados</div><div class="text-3xl font-semibold text-mono mt-2">{{ $hostingAccounts->count() }}</div></div></a>
            <a class="kt-card hover:border-primary" href="{{ route('client.host.show') }}"><div class="kt-card-content p-5"><div class="text-sm text-secondary-foreground">Servicios activos</div><div class="text-3xl font-semibold text-mono mt-2">{{ $activeHostingCount }}</div></div></a>
            <a class="kt-card hover:border-primary" href="{{ route('client.orders.index') }}"><div class="kt-card-content p-5"><div class="text-sm text-secondary-foreground">Boletas pendientes</div><div class="text-3xl font-semibold text-mono mt-2">{{ $pendingInvoiceCount }}</div></div></a>
        </div>
        <div class="kt-card"><div class="kt-card-header"><h2 class="kt-card-title">Hostings recientes</h2><a class="kt-link" href="{{ route('client.host.show') }}">Ver todos</a></div><div class="kt-card-table kt-scrollable-x-auto"><table class="kt-table align-middle"><thead><tr><th>Hosting</th><th>Plan</th><th>Panel</th><th>Estado</th><th></th></tr></thead><tbody>
            @forelse($hostingAccounts->take(5) as $account)
                <tr><td class="text-mono font-medium">{{ $account->name }}</td><td>{{ $account->plan?->name ?? 'Sin plan' }}</td><td class="text-secondary-foreground">{{ $account->custom_panel_domain ?: ($account->hostInstance?->panel_domain ?? 'Preparando') }}</td><td><span class="kt-badge kt-badge-sm kt-badge-outline {{ $account->status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }}">{{ ucfirst($account->status) }}</span></td><td class="text-end"><a class="kt-btn kt-btn-sm kt-btn-ghost" href="{{ route('client.host.account', $account) }}">Administrar</a></td></tr>
            @empty
                <tr><td colspan="5" class="text-center py-8 text-secondary-foreground">Todavía no tienes una cuenta de hosting.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
