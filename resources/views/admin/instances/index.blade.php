@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl border-x border-b border-input bg-background mx-5 mb-5 lg:ms-(--sidebar-width) lg:mt-(--navbar-height)">
    <div class="flex grow flex-col kt-scrollable-y-auto p-5 lg:p-8" id="scrollable_content">
        <main class="mx-auto flex w-full max-w-7xl grow flex-col gap-5" role="content">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <div class="text-sm text-secondary-foreground">Administración / Hostings</div>
                    <h1 class="mt-1 text-2xl font-semibold text-mono">Instancias XPanel Host</h1>
                    <p class="mt-1 text-sm text-secondary-foreground">Cada cuenta conserva su panel, archivos y procesos dentro de su entorno.</p>
                </div>
                <span class="kt-badge kt-badge-outline">{{ $instances->total() }} {{ $instances->total() === 1 ? 'instancia' : 'instancias' }}</span>
            </div>

            <div class="kt-card">
                <div class="kt-card-header flex-wrap gap-2">
                    <div>
                        <h2 class="kt-card-title">Hostings del servidor</h2>
                        <p class="text-xs text-secondary-foreground">Abre el panel del hosting o gestiona su cliente desde aquí.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b border-border bg-muted/30 text-xs text-secondary-foreground">
                            <tr>
                                <th class="px-5 py-3 font-medium">Cliente y panel</th>
                                <th class="px-5 py-3 font-medium">Versión</th>
                                <th class="px-5 py-3 font-medium">PHP</th>
                                <th class="px-5 py-3 font-medium">Estado</th>
                                <th class="px-5 py-3 text-right font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($instances as $instance)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-mono">{{ $instance->tenant->name }}</div>
                                        <div class="mt-1 truncate text-xs text-secondary-foreground">{{ $instance->hostingAccount?->custom_panel_domain ?: $instance->panel_domain }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="text-mono">{{ $instance->version ?: 'Sin versión' }}</div>
                                        <div class="mt-1 text-xs text-secondary-foreground">{{ $instance->update_channel }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-mono">{{ $instance->php_version }}</td>
                                    <td class="px-5 py-4">
                                        <span class="kt-badge kt-badge-outline {{ $instance->status === 'active' ? 'kt-badge-success' : ($instance->status === 'suspended' ? 'kt-badge-destructive' : 'kt-badge-secondary') }}">
                                            {{ match($instance->status) { 'active' => 'Activo', 'suspended' => 'Suspendido', 'staged' => 'Preparando', default => ucfirst($instance->status) } }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            @if($instance->status === 'active')
                                                <a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('admin.instances.access', $instance) }}" target="_blank" rel="noopener noreferrer" aria-label="Abrir panel de {{ $instance->tenant->name }} en otra pestaña">
                                                    <i class="ki-filled ki-exit-right-corner"></i>Abrir panel
                                                </a>
                                            @endif
                                            <a class="kt-btn kt-btn-sm kt-btn-ghost" href="{{ route('admin.clients.show', $instance->tenant) }}">Gestionar</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-secondary-foreground">Aún no hay hostings. Crea una cuenta desde Clientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div>{{ $instances->links() }}</div>
        </main>
        @include('layouts.partials.admin.footer')
    </div>
</div>
@endsection
