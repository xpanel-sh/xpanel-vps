@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl border-x border-b border-input bg-background mx-5 mb-5 lg:ms-(--sidebar-width) lg:mt-(--navbar-height)">
    <div class="flex grow flex-col kt-scrollable-y-auto p-5 lg:p-8">
        <main class="mx-auto flex w-full max-w-7xl grow flex-col gap-5" role="content">
            @if(session('success'))<div class="kt-alert kt-alert-success"><i class="ki-filled ki-check-circle"></i>{{ session('success') }}</div>@endif
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div><div class="text-sm text-secondary-foreground">Administración / Infraestructura</div><h1 class="mt-1 text-2xl font-semibold text-mono">Servidores conectados</h1><p class="mt-1 text-sm text-secondary-foreground">Nodos donde se ejecutan los servicios y agentes de XPanel.</p></div>
                <a class="kt-btn kt-btn-primary" href="{{ route('admin.servers.create') }}"><i class="ki-filled ki-plus"></i>Agregar servidor</a>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach([['Total', $summary['total']], ['Activos', $summary['active']], ['Inactivos', $summary['inactive']]] as [$label, $value])
                    <div class="kt-card"><div class="kt-card-content p-4"><div class="text-xs text-secondary-foreground">{{ $label }}</div><div class="mt-1 text-2xl font-semibold text-mono">{{ $value }}</div></div></div>
                @endforeach
            </div>
            <div class="kt-card">
                <div class="kt-card-header"><div><h2 class="kt-card-title">Inventario de nodos</h2><p class="mt-1 text-xs text-secondary-foreground">Estado y dirección de los servidores registrados.</p></div></div>
                <div class="overflow-x-auto"><table class="kt-table min-w-[760px]"><thead><tr><th>Servidor</th><th>Dirección</th><th>Puerto</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead><tbody>
                    @forelse($nodes as $node)
                        <tr><td><div class="font-medium text-mono">{{ $node->name }}</div><div class="text-xs text-secondary-foreground">Nodo #{{ $node->id }}</div></td><td class="font-mono">{{ $node->ip_address }}</td><td class="font-mono">{{ $node->port }}</td><td><span class="kt-badge kt-badge-outline {{ $node->is_active ? 'kt-badge-success' : 'kt-badge-destructive' }}">{{ $node->is_active ? 'Activo' : 'Inactivo' }}</span></td><td class="text-right"><form action="{{ route('admin.servers.toggle', $node) }}" method="POST">@csrf<button class="kt-btn kt-btn-sm kt-btn-outline">{{ $node->is_active ? 'Desactivar' : 'Activar' }}</button></form></td></tr>
                    @empty<tr><td colspan="5" class="py-14 text-center"><i class="ki-filled ki-abstract-26 text-3xl text-muted-foreground"></i><p class="mt-3 text-sm font-medium text-mono">No hay servidores conectados.</p><p class="mt-1 text-xs text-secondary-foreground">Registra un nodo cuando quieras ampliar la infraestructura.</p></td></tr>@endforelse
                </tbody></table></div>
            </div>
            <div>{{ $nodes->links() }}</div>
        </main>
        @include('layouts.partials.admin.footer')
    </div>
</div>
@endsection
