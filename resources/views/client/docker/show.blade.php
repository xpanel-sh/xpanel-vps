@extends('layouts.client')

@php
    $statusClass = match($dockerInstance->status) {
        'running'      => 'kt-badge-success',
        'stopped'      => 'kt-badge-secondary',
        'error'        => 'kt-badge-danger',
        'provisioning' => 'kt-badge-warning',
        'partial'      => 'kt-badge-warning',
        default        => 'kt-badge-secondary',
    };
@endphp

@section('content')
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5"
     x-data="{ tab: 'params', logsContent: '', logsLoading: false }"
     x-init="$watch('tab', v => { if (v === 'logs') loadLogs() })">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- Cabecera --}}
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-2xl font-semibold text-mono">{{ $dockerInstance->name }}</h1>
                                <span class="kt-badge kt-badge-outline {{ $statusClass }}">{{ $dockerInstance->status }}</span>
                                @if($dockerInstance->template)
                                    <span class="kt-badge kt-badge-outline kt-badge-info text-xs">{{ $dockerInstance->template->name }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-secondary-foreground font-mono">{{ $dockerInstance->slug }}</p>
                            @if($dockerInstance->domain)
                                <a href="https://{{ $dockerInstance->domain }}" target="_blank" rel="noopener"
                                   class="text-xs text-primary hover:underline mt-0.5 inline-block">
                                    {{ $dockerInstance->domain }} <i class="ki-filled ki-exit-up text-xs"></i>
                                </a>
                            @endif
                        </div>

                        {{-- Acciones --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            @if(in_array($dockerInstance->status, ['running', 'partial']))
                                <form action="{{ route('client.docker.stop', $dockerInstance) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">
                                        <i class="ki-filled ki-stop text-xs"></i> Detener
                                    </button>
                                </form>
                                <form action="{{ route('client.docker.restart', $dockerInstance) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">
                                        <i class="ki-filled ki-arrows-circle text-xs"></i> Reiniciar
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('client.docker.start', $dockerInstance) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">
                                        <i class="ki-filled ki-triangle text-xs"></i> Arrancar
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('client.docker.index') }}" class="kt-btn kt-btn-outline kt-btn-sm">
                                Volver
                            </a>

                            <form action="{{ route('client.docker.destroy', $dockerInstance) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar {{ addslashes($dockerInstance->name) }}? Esta acción borrará el contenedor y todos sus datos y no se puede deshacer.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="kt-btn kt-btn-icon kt-btn-sm" title="Eliminar">
                                    <i class="ki-filled ki-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Flash --}}
                    @if(session('success'))
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-300">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Contenedores activos --}}
                    @if(!empty($services))
                    <div class="kt-card">
                        <div class="kt-card-header min-h-12">
                            <h2 class="kt-card-title text-base">Contenedores</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="border-b border-border bg-muted/30 text-xs font-semibold uppercase text-secondary-foreground">
                                    <tr>
                                        <th class="px-5 py-2.5">Servicio</th>
                                        <th class="px-5 py-2.5">Estado</th>
                                        <th class="px-5 py-2.5">Health</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach($services as $svc)
                                    @php $svcClass = str_contains($svc['state'] ?? '', 'running') ? 'kt-badge-success' : 'kt-badge-secondary'; @endphp
                                    <tr>
                                        <td class="px-5 py-3 font-mono text-sm">{{ $svc['name'] ?? '-' }}</td>
                                        <td class="px-5 py-3">
                                            <span class="kt-badge kt-badge-outline {{ $svcClass }} text-xs">{{ $svc['state'] ?? '-' }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-xs text-secondary-foreground">{{ $svc['health'] ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Tabs --}}
                    <div class="kt-card">
                        <div class="kt-card-header min-h-12 gap-0 border-b border-border px-0">
                            <nav class="flex">
                                <button type="button"
                                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"
                                    :class="tab === 'params' ? 'border-primary text-primary' : 'border-transparent text-secondary-foreground hover:text-mono'"
                                    @click="tab = 'params'">
                                    <i class="ki-filled ki-setting-2 text-sm me-1.5"></i>Parámetros
                                </button>
                                <button type="button"
                                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"
                                    :class="tab === 'logs' ? 'border-primary text-primary' : 'border-transparent text-secondary-foreground hover:text-mono'"
                                    @click="tab = 'logs'">
                                    <i class="ki-filled ki-abstract-36 text-sm me-1.5"></i>Logs
                                </button>
                            </nav>
                        </div>

                        {{-- Tab Parámetros --}}
                        <div x-show="tab === 'params'" class="p-5">
                            @if($dockerInstance->env_values && count($dockerInstance->env_values))
                                <div class="grid sm:grid-cols-2 gap-4">
                                    @foreach($dockerInstance->env_values as $key => $value)
                                    <div>
                                        <p class="text-xs text-secondary-foreground font-mono uppercase tracking-wider mb-1">{{ $key }}</p>
                                        <p class="text-sm text-mono font-medium break-all">
                                            @if(str_contains(strtolower($key), 'password') || str_contains(strtolower($key), 'secret') || str_contains(strtolower($key), 'token'))
                                                <span class="text-secondary-foreground">••••••••</span>
                                            @else
                                                {{ $value ?: '—' }}
                                            @endif
                                        </p>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-secondary-foreground">Esta app no tiene parámetros configurables.</p>
                            @endif
                        </div>

                        {{-- Tab Logs --}}
                        <div x-show="tab === 'logs'" class="p-5">
                            <div class="flex items-center gap-3 mb-3">
                                <button type="button" class="kt-btn kt-btn-outline kt-btn-sm" @click="loadLogs()">
                                    <i class="ki-filled ki-arrows-circle text-xs"></i> Actualizar
                                </button>
                                <span x-show="logsLoading" class="text-xs text-secondary-foreground">Cargando…</span>
                            </div>
                            <pre x-text="logsContent || 'Sin logs disponibles.'"
                                 class="bg-muted rounded-lg p-4 text-xs font-mono overflow-x-auto whitespace-pre-wrap max-h-[500px] overflow-y-auto"></pre>
                        </div>
                    </div>

                </div>
            </div>
        </main>
        @include('layouts.partials.client.footer')
    </div>
</div>

<script>
function loadLogs() {
    const el = document.querySelector('[x-data]');
    if (!el) return;
    const comp = Alpine.$data(el);
    comp.logsLoading = true;
    fetch('{{ route('client.docker.logs', $dockerInstance) }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        comp.logsContent = data.logs || 'Sin logs.';
        comp.logsLoading = false;
    })
    .catch(() => {
        comp.logsContent = 'Error al obtener logs.';
        comp.logsLoading = false;
    });
}
</script>
@endsection
