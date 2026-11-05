@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl border-x border-b border-input bg-background mx-5 mb-5 lg:ms-(--sidebar-width) lg:mt-(--navbar-height)">
    <div class="flex grow flex-col kt-scrollable-y-auto p-5 lg:p-8" id="scrollable_content">
        <main class="mx-auto flex w-full max-w-7xl grow flex-col gap-5" role="content">
            <div>
                <div class="text-sm text-secondary-foreground">Administración / Hosting</div>
                <h1 class="mt-1 text-2xl font-semibold text-mono">Dominios y sitios</h1>
                <p class="mt-1 text-sm text-secondary-foreground">Inventario único de dominios registrados y sitios alojados en todas las instancias.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach([['Total', $summary['total']], ['En instancias Host', $summary['managed']], ['Sitios nativos', $summary['native']], ['Solo registrados', $summary['registered']]] as [$label, $value])
                    <div class="kt-card"><div class="kt-card-content p-4"><div class="text-xs text-secondary-foreground">{{ $label }}</div><div class="mt-1 text-2xl font-semibold text-mono">{{ $value }}</div></div></div>
                @endforeach
            </div>

            <div class="kt-card">
                <div class="kt-card-header flex-wrap gap-3">
                    <div><h2 class="kt-card-title">Inventario global</h2><p class="text-xs text-secondary-foreground">Cada dominio aparece una sola vez, priorizando su hosting real.</p></div>
                    <form class="flex w-full gap-2 sm:w-auto" method="GET">
                        <label class="kt-input kt-input-sm w-full sm:w-72"><i class="ki-filled ki-magnifier"></i><input name="search" value="{{ request('search') }}" placeholder="Dominio, cliente o hosting..."></label>
                        <button class="kt-btn kt-btn-sm kt-btn-outline">Buscar</button>
                        @if(request()->filled('search'))<a class="kt-btn kt-btn-sm kt-btn-ghost" href="{{ route('admin.domains.index') }}">Limpiar</a>@endif
                    </form>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto">
                    <table class="kt-table min-w-[980px] align-middle text-sm">
                        <thead><tr><th>Dominio</th><th>Cliente</th><th>Hosting / origen</th><th>DNS</th><th>SSL</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            @forelse($domains as $row)
                                <tr>
                                    <td><div class="font-medium text-mono">{{ $row['domain'] }}</div><div class="text-xs text-secondary-foreground">{{ $row['kind'] }}</div></td>
                                    <td>@if($row['tenant'])<a class="kt-link" href="{{ route('admin.clients.show', $row['tenant']) }}">{{ $row['tenant']->name }}</a>@else<span class="text-secondary-foreground">Sin cliente</span>@endif</td>
                                    <td><div class="font-medium">{{ $row['hosting'] }}</div><div class="text-xs text-secondary-foreground">{{ match($row['source']) { 'managed' => 'XPanel Host', 'native' => 'VPS nativo', default => 'Portafolio DNS' } }}</div></td>
                                    <td><span class="kt-badge kt-badge-outline">{{ $row['dns'] ?: 'Pendiente' }}</span></td>
                                    <td><span class="kt-badge kt-badge-outline">{{ $row['ssl'] ?: 'Pendiente' }}</span></td>
                                    <td><span class="kt-badge kt-badge-outline {{ $row['status'] === 'active' ? 'kt-badge-success' : ($row['status'] === 'suspended' ? 'kt-badge-destructive' : 'kt-badge-secondary') }}">{{ ucfirst($row['status']) }}</span></td>
                                    <td class="text-right">
                                        @if($row['instance'])
                                            <a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('admin.instances.show', $row['instance']) }}">Ver hosting</a>
                                        @elseif($row['site'])
                                            <a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('admin.files.index', $row['domain']) }}">Archivos</a>
                                        @elseif($row['tenant'])
                                            <a class="kt-btn kt-btn-sm kt-btn-ghost" href="{{ route('admin.clients.show', $row['tenant']) }}">Ver cliente</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-12 text-center text-secondary-foreground">No hay dominios o sitios registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div>{{ $domains->links() }}</div>
        </main>
        @include('layouts.partials.admin.footer')
    </div>
</div>
@endsection
