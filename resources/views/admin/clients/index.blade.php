@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl border-x border-b border-input bg-background mx-5 mb-5 lg:ms-(--sidebar-width) lg:mt-(--navbar-height)">
    <div class="flex grow flex-col kt-scrollable-y-auto p-5 lg:p-8" id="scrollable_content">
        <main class="mx-auto flex w-full max-w-7xl grow flex-col gap-5" role="content" x-data="{ search: '' }">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <div class="text-sm text-secondary-foreground">Administración / Clientes</div>
                    <h1 class="mt-1 text-2xl font-semibold text-mono">Clientes</h1>
                    <p class="mt-1 text-sm text-secondary-foreground">Personas o empresas que pueden tener una o varias cuentas de hosting.</p>
                </div>
                <a class="kt-btn kt-btn-primary" href="{{ route('admin.clients.create') }}"><i class="ki-filled ki-plus"></i>Nuevo cliente</a>
            </div>

            <div class="kt-card">
                <div class="kt-card-header flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="kt-card-title">Directorio de clientes</h2>
                        <p class="text-xs text-secondary-foreground">El dominio pertenece a un sitio o hosting, no a la ficha comercial del cliente.</p>
                    </div>
                    <label class="kt-input kt-input-sm w-64 max-w-full shrink-0"><i class="ki-filled ki-magnifier"></i><input x-model="search" type="search" placeholder="Buscar cliente..."></label>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto">
                    <table class="kt-table min-w-[820px] align-middle text-sm">
                        <thead><tr><th>Cliente</th><th>Acceso principal</th><th>Hostings</th><th>Sitios</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            @forelse($tenants as $tenant)
                                <tr x-show="!search || @js(strtolower($tenant->name.' '.($tenant->access_ready ? ($tenant->user?->name.' '.$tenant->user?->email) : 'pendiente'))).includes(search.toLowerCase())">
                                    <td><div class="font-medium text-mono">{{ $tenant->name }}</div><div class="text-xs text-secondary-foreground">{{ $tenant->code ?: 'Cliente #'.$tenant->id }}</div></td>
                                    <td>
                                        @if($tenant->access_ready)
                                            <div class="font-medium">{{ $tenant->user?->name }}</div><div class="text-xs text-secondary-foreground">{{ $tenant->user?->email }}</div>
                                        @else
                                            <span class="text-secondary-foreground">Se creará con el primer hosting</span>
                                        @endif
                                    </td>
                                    <td><span class="kt-badge kt-badge-outline">{{ $tenant->hosting_accounts_count }}</span></td>
                                    <td><span class="kt-badge kt-badge-outline">{{ $tenant->sites_count + $tenant->managed_sites_count }}</span></td>
                                    <td><span class="kt-badge kt-badge-outline {{ $tenant->status === 'active' ? 'kt-badge-success' : 'kt-badge-destructive' }}">{{ $tenant->status === 'active' ? 'Activo' : 'Suspendido' }}</span></td>
                                    <td class="text-right"><a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('admin.clients.show', $tenant) }}">Ver cliente<i class="ki-filled ki-right"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-12 text-center text-secondary-foreground">Todavía no hay clientes registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div>{{ $tenants->links() }}</div>
        </main>
        @include('layouts.partials.admin.footer')
    </div>
</div>
@endsection
