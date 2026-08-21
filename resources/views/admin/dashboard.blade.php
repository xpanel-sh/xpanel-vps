@extends('layouts.admin')

@php
    $resources = $runtime['resources'] ?? [];
    $system = $runtime['system'] ?? $runtime['server'] ?? [];
    $memory = $system['memory'] ?? $runtime['memory'] ?? [];
    $disk = $system['disk'] ?? $runtime['disk'] ?? [];
    $cpuPercent = $system['cpu_percent'] ?? $system['cpu'] ?? $runtime['cpu_percent'] ?? $runtime['cpu'] ?? null;
    $memoryPercent = $memory['percent'] ?? $memory['used_percent'] ?? $runtime['memory_percent'] ?? null;
    $diskPercent = $disk['percent'] ?? $disk['used_percent'] ?? $runtime['disk_percent'] ?? null;
    $formatPercent = fn ($value) => is_numeric($value) ? number_format((float) $value, 1) . '%' : '-';
    $statusOnline = empty($runtimeError);
    $totalMonthly = collect($planStats ?? [])->sum('monthly');
@endphp

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                            <div class="lg:col-span-2">
                                <div class="kt-card h-full">
                                    <div class="kt-card-content flex flex-col place-content-center gap-5">
                                        <div class="flex justify-center">
                                            <img alt="image" class="dark:hidden max-h-[180px]" src="{{ asset('assets/media/illustrations/32.svg') }}" />
                                            <img alt="image" class="light:hidden max-h-[180px]" src="{{ asset('assets/media/illustrations/32-dark.svg') }}" />
                                        </div>
                                        <div class="flex flex-col gap-4">
                                            <div class="flex flex-col gap-3 text-center">
                                                <div class="flex justify-center">
                                                    <span class="kt-badge kt-badge-sm {{ $statusOnline ? 'kt-badge-success' : 'kt-badge-destructive' }} kt-badge-outline">
                                                        {{ $statusOnline ? 'Daemon conectado' : 'Daemon sin respuesta' }}
                                                    </span>
                                                </div>
                                                <h2 class="text-xl font-semibold text-mono">
                                                    Centro de control XPanel
                                                </h2>
                                                <p class="text-sm font-medium text-secondary-foreground">
                                                    Gestiona clientes, sitios, planes y servidores conectados desde el panel global.
                                                    <br />
                                                    El monitoreo del servidor se actualiza en tiempo real cuando el daemon responde.
                                                </p>
                                            </div>
                                            <div class="flex justify-center gap-2">
                                                <a class="kt-btn kt-btn-mono" href="{{ route('admin.clients.create') }}">
                                                    Crear cliente
                                                </a>
                                                <a class="kt-btn kt-btn-outline" href="{{ route('admin.servers.index') }}">
                                                    Ver servidores
                                                </a>
                                            </div>
                                            @if($runtimeError)
                                                <div class="mx-auto max-w-xl rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-center text-sm text-destructive">
                                                    Runtime no disponible: {{ $runtimeError }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-1">
                                <div class="kt-card h-full">
                                    <div class="kt-card-header">
                                        <h3 class="kt-card-title">Highlights</h3>
                                        <a class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" href="{{ route('admin.plans.index') }}">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </a>
                                    </div>
                                    <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-sm font-normal text-secondary-foreground">
                                                Ingreso mensual estimado
                                            </span>
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-3xl font-semibold text-mono">
                                                    ${{ number_format($totalMonthly, 2) }}
                                                </span>
                                                <span class="kt-badge kt-badge-outline kt-badge-primary kt-badge-sm">
                                                    {{ $planCount }} planes
                                                </span>
                                            </div>
                                        </div>

                                        <div id="plans-revenue-chart" class="h-[145px]"></div>

                                        <div class="border-b border-input"></div>

                                        <div class="grid gap-3">
                                            @forelse($planStats as $plan)
                                                <div class="flex items-center justify-between flex-wrap gap-2">
                                                    <div class="flex items-center gap-1.5">
                                                        <i class="ki-filled ki-dollar text-base text-muted-foreground"></i>
                                                        <span class="text-sm font-normal text-mono">{{ $plan['name'] }}</span>
                                                    </div>
                                                    <div class="flex items-center text-sm font-medium text-foreground gap-6">
                                                        <span class="lg:text-right">{{ $plan['tenants'] }} clientes</span>
                                                        <span class="lg:text-right">${{ number_format($plan['monthly'], 2) }}</span>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="text-sm text-secondary-foreground">
                                                    Aun no hay clientes asociados a planes.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 lg:gap-7.5">
                            <div class="kt-card">
                                <div class="kt-card-content p-5">
                                    <div class="text-sm text-secondary-foreground">Clientes</div>
                                    <div class="mt-2 text-3xl font-semibold text-mono">{{ $clientCount }}</div>
                                    <div class="mt-1 text-xs text-secondary-foreground">Tenants registrados</div>
                                </div>
                            </div>
                            <div class="kt-card">
                                <div class="kt-card-content p-5">
                                    <div class="text-sm text-secondary-foreground">Sitios</div>
                                    <div class="mt-2 text-3xl font-semibold text-mono">{{ $siteCount }}</div>
                                    <div class="mt-1 text-xs text-secondary-foreground">Webs administradas</div>
                                </div>
                            </div>
                            <div class="kt-card">
                                <div class="kt-card-content p-5">
                                    <div class="text-sm text-secondary-foreground">Servidores</div>
                                    <div class="mt-2 text-3xl font-semibold text-mono">{{ $activeNodeCount }}/{{ $nodeCount }}</div>
                                    <div class="mt-1 text-xs text-secondary-foreground">Activos / totales</div>
                                </div>
                            </div>
                            <div class="kt-card">
                                <div class="kt-card-content p-5">
                                    <div class="text-sm text-secondary-foreground">Operaciones daemon</div>
                                    <div class="mt-2 text-3xl font-semibold text-mono" data-resource-value="operations">{{ $resources['operations'] ?? 0 }}</div>
                                    <div class="mt-1 text-xs text-secondary-foreground">Eventos registrados</div>
                                </div>
                            </div>
                        </div>

                        <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                            <div class="kt-card lg:col-span-2">
                                <div class="kt-card-header">
                                    <h3 class="kt-card-title">Monitoreo en tiempo real</h3>
                                    <span class="text-xs text-secondary-foreground" id="runtime-updated">Esperando datos</span>
                                </div>
                                <div class="kt-card-content p-5">
                                    <div id="server-runtime-chart" class="h-[320px]"></div>
                                </div>
                            </div>

                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h3 class="kt-card-title">Control del servidor</h3>
                                </div>
                                <div class="kt-card-content grid gap-4 p-5">
                                    @foreach([
                                        ['key' => 'cpu', 'label' => 'CPU', 'value' => $cpuPercent, 'color' => 'bg-primary'],
                                        ['key' => 'memory', 'label' => 'Memoria', 'value' => $memoryPercent, 'color' => 'bg-violet-500'],
                                        ['key' => 'disk', 'label' => 'Disco', 'value' => $diskPercent, 'color' => 'bg-green-500'],
                                    ] as $metric)
                                        <div class="grid gap-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-secondary-foreground">{{ $metric['label'] }}</span>
                                                <span class="text-sm font-semibold text-mono" data-runtime-value="{{ $metric['key'] }}">{{ $formatPercent($metric['value']) }}</span>
                                            </div>
                                            <div class="h-2 rounded-xs bg-muted">
                                                <div class="h-2 rounded-xs {{ $metric['color'] }}" data-runtime-bar="{{ $metric['key'] }}" style="width: {{ is_numeric($metric['value']) ? min(100, max(0, $metric['value'])) : 0 }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="border-b border-input"></div>

                                    <div class="grid gap-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-secondary-foreground">Daemon</span>
                                            <span class="kt-badge kt-badge-outline {{ $statusOnline ? 'kt-badge-success' : 'kt-badge-destructive' }}" id="daemon-status">
                                                {{ $statusOnline ? 'Online' : 'Offline' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-secondary-foreground">Bases</span>
                                            <span class="text-sm font-semibold text-mono" data-resource-value="databases">{{ $resources['databases'] ?? 0 }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-secondary-foreground">DNS records</span>
                                            <span class="text-sm font-semibold text-mono" data-resource-value="dns_records">{{ $resources['dns_records'] ?? 0 }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-secondary-foreground">Correos</span>
                                            <span class="text-sm font-semibold text-mono" data-resource-value="mail_accounts">{{ $resources['mail_accounts'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                            <div class="lg:col-span-2">
                                <div class="kt-card kt-card-grid h-full min-w-full">
                                    <div class="kt-card-header">
                                        <h3 class="kt-card-title">Webs recientes</h3>
                                        <a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('admin.sites.index') }}">Ver todo</a>
                                    </div>
                                    <div class="kt-card-table">
                                        <div class="kt-scrollable-x-auto">
                                            <table class="kt-table kt-table-border table-fixed">
                                                <thead>
                                                    <tr>
                                                        <th class="w-[260px]">Dominio</th>
                                                        <th class="w-[180px]">Cliente</th>
                                                        <th class="w-[140px]">Tipo</th>
                                                        <th class="w-[140px]">Web server</th>
                                                        <th class="w-[120px]">Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($recentSites as $site)
                                                        <tr>
                                                            <td>
                                                                <div class="flex flex-col gap-1">
                                                                    <span class="font-medium text-sm text-mono">{{ $site->domain }}</span>
                                                                    <span class="text-xs text-secondary-foreground">{{ $site->created_at?->diffForHumans() }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="text-sm text-foreground">{{ $site->tenant?->name ?? '-' }}</td>
                                                            <td class="text-sm text-foreground">{{ strtoupper($site->project_type ?? '-') }}</td>
                                                            <td class="text-sm text-foreground">{{ $site->web_server ?? '-' }}</td>
                                                            <td>
                                                                <span class="kt-badge kt-badge-sm kt-badge-outline {{ $site->status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }}">
                                                                    {{ $site->status ?? 'pending' }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="py-8 text-center text-sm text-secondary-foreground">
                                                                Aun no hay sitios creados.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h3 class="kt-card-title">Acciones rapidas</h3>
                                </div>
                                <div class="kt-card-content grid gap-3 p-5">
                                    <a class="kt-btn kt-btn-outline justify-start" href="{{ route('admin.clients.create') }}">
                                        <i class="ki-filled ki-plus"></i>
                                        Crear cliente
                                    </a>
                                    <a class="kt-btn kt-btn-outline justify-start" href="{{ route('admin.plans.create') }}">
                                        <i class="ki-filled ki-dollar"></i>
                                        Crear plan
                                    </a>
                                    <a class="kt-btn kt-btn-outline justify-start" href="{{ route('admin.servers.create') }}">
                                        <i class="ki-filled ki-setting-3"></i>
                                        Agregar servidor
                                    </a>
                                    <a class="kt-btn kt-btn-outline justify-start" href="{{ route('admin.daemon.operations') }}">
                                        <i class="ki-filled ki-pulse"></i>
                                        Operaciones daemon
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (() => {
        const runtimeUrl = @json(route('admin.dashboard.runtime'));
        const initialRuntime = @json($runtime);
        const planStats = @json($planStats);
        const maxPoints = 24;

        const pickNumber = (source, paths) => {
            for (const path of paths) {
                const value = path.split('.').reduce((carry, key) => carry && carry[key] !== undefined ? carry[key] : undefined, source);
                if (value !== undefined && value !== null && value !== '' && !Number.isNaN(Number(value))) {
                    return Math.max(0, Math.min(100, Number(value)));
                }
            }
            return null;
        };

        const metricFromRuntime = (runtime) => ({
            cpu: pickNumber(runtime, ['system.cpu_percent', 'system.cpu', 'server.cpu_percent', 'cpu_percent', 'cpu']),
            memory: pickNumber(runtime, ['system.memory.percent', 'system.memory.used_percent', 'memory.percent', 'memory.used_percent', 'memory_percent']),
            disk: pickNumber(runtime, ['system.disk.percent', 'system.disk.used_percent', 'disk.percent', 'disk.used_percent', 'disk_percent']),
        });

        const labels = [];
        const series = { cpu: [], memory: [], disk: [] };

        const pushPoint = (metrics) => {
            const now = new Date();
            labels.push(now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
            ['cpu', 'memory', 'disk'].forEach((key) => {
                series[key].push(metrics[key] ?? 0);
                if (series[key].length > maxPoints) series[key].shift();
            });
            if (labels.length > maxPoints) labels.shift();
        };

        const updateText = (metrics) => {
            ['cpu', 'memory', 'disk'].forEach((key) => {
                const value = metrics[key];
                document.querySelectorAll(`[data-runtime-value="${key}"]`).forEach((el) => {
                    el.textContent = value === null ? '-' : `${value.toFixed(1)}%`;
                });
                document.querySelectorAll(`[data-runtime-bar="${key}"]`).forEach((el) => {
                    el.style.width = `${value ?? 0}%`;
                });
            });
        };

        const updateResources = (runtime) => {
            const resources = runtime.resources || {};
            Object.keys(resources).forEach((key) => {
                document.querySelectorAll(`[data-resource-value="${key}"]`).forEach((el) => {
                    el.textContent = resources[key] ?? 0;
                });
            });
        };

        const baseMetrics = metricFromRuntime(initialRuntime);
        pushPoint(baseMetrics);
        updateText(baseMetrics);
        updateResources(initialRuntime);

        let runtimeChart = null;
        if (window.ApexCharts && document.querySelector('#server-runtime-chart')) {
            runtimeChart = new ApexCharts(document.querySelector('#server-runtime-chart'), {
                chart: { type: 'area', height: 320, toolbar: { show: false }, animations: { enabled: true, easing: 'linear', dynamicAnimation: { speed: 400 } } },
                series: [
                    { name: 'CPU', data: series.cpu },
                    { name: 'Memoria', data: series.memory },
                    { name: 'Disco', data: series.disk },
                ],
                xaxis: { categories: labels },
                yaxis: { min: 0, max: 100, labels: { formatter: (value) => `${Math.round(value)}%` } },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.22, opacityTo: 0.02 } },
                colors: ['#2563eb', '#8b5cf6', '#22c55e'],
                dataLabels: { enabled: false },
                legend: { position: 'top' },
                tooltip: { y: { formatter: (value) => `${Number(value).toFixed(1)}%` } },
            });

            runtimeChart.render();
        } else {
            const holder = document.querySelector('#server-runtime-chart');
            if (holder) holder.innerHTML = '<div class="h-full grid place-items-center text-sm text-secondary-foreground">Graficos no disponibles, metricas textuales activas.</div>';
        }

        const revenueChartEl = document.querySelector('#plans-revenue-chart');
        if (window.ApexCharts && revenueChartEl) {
            new ApexCharts(revenueChartEl, {
                chart: { type: 'bar', height: 145, toolbar: { show: false }, sparkline: { enabled: true } },
                series: [{ name: 'Mensual', data: planStats.map((plan) => Number(plan.monthly || 0)) }],
                labels: planStats.map((plan) => plan.name),
                colors: ['#2563eb'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: (value) => `$${Number(value).toFixed(2)}` } },
            }).render();
        }

        const markStatus = (online) => {
            const status = document.getElementById('daemon-status');
            if (!status) return;
            status.textContent = online ? 'Online' : 'Offline';
            status.classList.toggle('kt-badge-success', online);
            status.classList.toggle('kt-badge-destructive', !online);
        };

        const refresh = async () => {
            try {
                const response = await fetch(runtimeUrl, { headers: { Accept: 'application/json' } });
                const payload = await response.json();
                if (!response.ok || !payload.ok) throw new Error(payload.message || 'Daemon unavailable');

                const runtime = payload.runtime || {};
                const metrics = metricFromRuntime(runtime);
                pushPoint(metrics);
                updateText(metrics);
                updateResources(runtime);
                markStatus(true);

                if (runtimeChart) {
                    runtimeChart.updateOptions({ xaxis: { categories: labels } }, false, false);
                    runtimeChart.updateSeries([
                        { name: 'CPU', data: series.cpu },
                        { name: 'Memoria', data: series.memory },
                        { name: 'Disco', data: series.disk },
                    ], false);
                }

                const updated = document.getElementById('runtime-updated');
                if (updated) updated.textContent = `Actualizado ${new Date().toLocaleTimeString()}`;
            } catch (error) {
                markStatus(false);
                const updated = document.getElementById('runtime-updated');
                if (updated) updated.textContent = 'Daemon sin respuesta';
            }
        };

        setInterval(refresh, 5000);
        refresh();
    })();
</script>
@endpush
