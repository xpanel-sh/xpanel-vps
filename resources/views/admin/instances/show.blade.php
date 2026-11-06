@extends('layouts.admin')

@section('content')
@php
    $account = $instance->hostingAccount;
    $plan = $account?->plan;
    $formatBytes = static function (int $bytes): string {
        if ($bytes <= 0) return '0 MB';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return number_format($bytes / (1024 ** $power), $power >= 3 ? 1 : 0).' '.$units[$power];
    };
    $percent = static fn (int $used, int $limit): int => $limit > 0 ? min(100, (int) round(($used / $limit) * 100)) : 0;
    $diskPercent = $percent((int) $metrics['disk_bytes'], (int) $metrics['storage_limit_bytes']);
    $memoryPercent = $percent((int) $metrics['memory_bytes'], (int) $metrics['memory_limit_bytes']);
    $tasksPercent = $percent((int) $metrics['tasks'], (int) $metrics['tasks_limit']);
@endphp
<div class="flex grow rounded-b-xl border-x border-b border-input bg-background mx-5 mb-5 lg:ms-(--sidebar-width) lg:mt-(--navbar-height)">
    <div class="flex grow flex-col kt-scrollable-y-auto p-5 lg:p-8">
        <main class="mx-auto flex w-full max-w-7xl grow flex-col gap-5" role="content">
            @if(session('success'))<div class="kt-alert kt-alert-success"><i class="ki-filled ki-check-circle"></i>{{ session('success') }}</div>@endif
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <a class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" href="{{ route('admin.instances.index') }}" aria-label="Volver"><i class="ki-filled ki-left"></i></a>
                    <div class="min-w-0">
                        <div class="text-sm text-secondary-foreground">Hosting / {{ $instance->tenant->name }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <h1 class="truncate text-2xl font-semibold text-mono">{{ $account?->name ?? 'Instancia Host' }}</h1>
                            <span class="kt-badge kt-badge-outline {{ $instance->status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }}">{{ $instance->status === 'active' ? 'Operativo' : ucfirst($instance->status) }}</span>
                        </div>
                        <p class="mt-1 truncate text-sm text-secondary-foreground">{{ $account?->custom_panel_domain ?: $instance->panel_domain }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a class="kt-btn kt-btn-outline" href="{{ route('admin.clients.show', $instance->tenant) }}"><i class="ki-filled ki-user"></i>Cliente</a>
                    @if($account && in_array($account->status, ['cancellation_pending', 'suspended', 'suspended_overdue'], true) && (!$account->purge_after || $account->purge_after->isFuture()))
                        <form method="post" action="{{ route('admin.instances.restore', $instance) }}">@csrf<button class="kt-btn kt-btn-outline" type="submit"><i class="ki-filled ki-arrows-circle"></i>Restaurar hosting</button></form>
                    @endif
                    @if($instance->status === 'active')<a class="kt-btn kt-btn-primary" href="{{ route('admin.instances.access', $instance) }}" target="_blank" rel="noopener"><i class="ki-filled ki-exit-right-corner"></i>Abrir panel</a>@endif
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach([
                    ['Disco', $formatBytes((int) $metrics['disk_bytes']).' / '.$formatBytes((int) $metrics['storage_limit_bytes']), $diskPercent, 'ki-folder'],
                    ['Memoria', $formatBytes((int) $metrics['memory_bytes']).' / '.$formatBytes((int) $metrics['memory_limit_bytes']), $memoryPercent, 'ki-chart-line-up'],
                    ['Procesos', ((int) $metrics['tasks']).' / '.((int) $metrics['tasks_limit']), $tasksPercent, 'ki-pulse'],
                    ['Sitios', $instance->brokerResources->count().' / '.($plan?->max_sites ?? '—'), null, 'ki-screen'],
                ] as [$label, $value, $usage, $icon])
                    <div class="kt-card"><div class="kt-card-content p-5">
                        <div class="flex items-center justify-between"><span class="text-sm text-secondary-foreground">{{ $label }}</span><i class="ki-filled {{ $icon }} text-primary"></i></div>
                        <div class="mt-2 text-xl font-semibold text-mono">{{ $value }}</div>
                        @if($usage !== null)<div class="mt-3 h-1.5 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full {{ $usage >= 90 ? 'bg-destructive' : ($usage >= 75 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ $usage }}%"></div></div>@endif
                    </div></div>
                @endforeach
            </div>

            @if($metrics['error'])<div class="kt-alert kt-alert-warning"><i class="ki-filled ki-information-2"></i>No se pudieron leer todas las métricas en vivo. Los límites del plan siguen aplicados.</div>@endif

            <div class="grid gap-5 xl:grid-cols-3">
                <section class="kt-card xl:col-span-2">
                    <div class="kt-card-header"><div><h2 class="kt-card-title">Dominios y sitios</h2><p class="mt-1 text-xs text-secondary-foreground">Sitios informados por esta instancia Host.</p></div><a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('admin.domains.index') }}">Ver inventario</a></div>
                    <div class="overflow-x-auto"><table class="kt-table min-w-[560px]"><thead><tr><th>Dominio</th><th>Origen</th><th>Estado</th><th></th></tr></thead><tbody>
                        @forelse($instance->brokerResources as $resource)<tr><td class="font-medium text-mono">{{ $resource->name }}</td><td>{{ $account?->name }}</td><td><span class="kt-badge kt-badge-outline kt-badge-success">Activo</span></td><td class="text-right"><a class="kt-btn kt-btn-sm kt-btn-ghost" href="https://{{ $resource->name }}" target="_blank" rel="noopener">Abrir <i class="ki-filled ki-exit-up"></i></a></td></tr>
                        @empty<tr><td colspan="4" class="py-10 text-center text-secondary-foreground">Este hosting todavía no ha registrado sitios.</td></tr>@endforelse
                    </tbody></table></div>
                </section>
                <aside class="kt-card">
                    <div class="kt-card-header"><h2 class="kt-card-title">Plan y límites</h2></div>
                    <div class="kt-card-content grid gap-4 p-5 text-sm">
                        @foreach([
                            ['Plan', $plan?->name ?? 'Sin plan'],
                            ['CPU', ($metrics['cpu_limit_percent'] ?? 0).'%'],
                            ['Bases de datos', $plan?->max_databases ?? '—'],
                            ['Transferencia', $plan?->bandwidth_gb ? $plan->bandwidth_gb.' GB/mes' : 'Sin definir'],
                            ['Correos', $plan?->email_accounts ?? '—'],
                            ['Versión Host', $instance->version ?: 'No detectada'],
                        ] as [$label, $value])<div class="flex items-center justify-between gap-3 border-b border-border pb-3 last:border-0 last:pb-0"><span class="text-secondary-foreground">{{ $label }}</span><strong class="text-right text-mono">{{ $value }}</strong></div>@endforeach
                    </div>
                    @if($account)<form method="post" action="{{ route('admin.instances.plan', $instance) }}" class="grid gap-3 border-t border-border p-5">@csrf<label class="grid gap-1"><span class="text-xs text-secondary-foreground">Cambiar plan</span><select class="kt-select" name="plan_id">@foreach($plans as $availablePlan)<option value="{{ $availablePlan->id }}" @selected($availablePlan->id === $account->hosting_plan_id)>{{ $availablePlan->name }}</option>@endforeach</select></label><button class="kt-btn kt-btn-outline justify-center" type="submit">Validar y aplicar</button></form>@endif
                </aside>
            </div>

            <section class="kt-card">
                <div class="kt-card-header"><div><h2 class="kt-card-title">Historial de pagos</h2><p class="mt-1 text-xs text-secondary-foreground">Boletas vinculadas exclusivamente a este hosting.</p></div><a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('admin.orders.index') }}">Todos los pagos</a></div>
                <div class="overflow-x-auto"><table class="kt-table min-w-[720px]"><thead><tr><th>Boleta</th><th>Plan</th><th>Total</th><th>Vencimiento</th><th>Pago</th><th></th></tr></thead><tbody>
                    @forelse($orders as $order)<tr><td class="font-medium text-mono">{{ $order->number }}</td><td>{{ $order->plan?->name }}</td><td>{{ $order->currency }} {{ number_format((float) $order->amount, 2) }}</td><td>{{ $order->payment_due_at?->format('d/m/Y') ?? '—' }}</td><td><span class="kt-badge kt-badge-outline {{ $order->payment_status === 'paid' ? 'kt-badge-success' : 'kt-badge-warning' }}">{{ $order->payment_status === 'paid' ? 'Pagado' : 'Pendiente' }}</span></td><td class="text-right"><a class="kt-btn kt-btn-sm kt-btn-ghost" href="{{ route('admin.orders.show', $order) }}">Detalle</a></td></tr>
                    @empty<tr><td colspan="6" class="py-10 text-center text-secondary-foreground">No hay boletas vinculadas a este hosting.</td></tr>@endforelse
                </tbody></table></div>
            </section>

            <section class="kt-card">
                <div class="kt-card-header"><h2 class="kt-card-title">Actividad técnica reciente</h2></div>
                <div class="overflow-x-auto"><table class="kt-table min-w-[680px]"><thead><tr><th>Fecha</th><th>Acción</th><th>Estado</th><th>Resultado</th></tr></thead><tbody>
                    @forelse($instance->brokerOperations as $operation)<tr><td class="text-secondary-foreground">{{ $operation->created_at->format('d/m/Y H:i') }}</td><td class="font-medium text-mono">{{ $operation->action }}</td><td><span class="kt-badge kt-badge-outline {{ $operation->status === 'completed' ? 'kt-badge-success' : ($operation->status === 'failed' ? 'kt-badge-destructive' : 'kt-badge-warning') }}">{{ ucfirst($operation->status) }}</span></td><td class="max-w-md truncate text-secondary-foreground" title="{{ $operation->error ?: $operation->output }}">{{ Str::limit($operation->error ?: $operation->output, 100) ?: '—' }}</td></tr>
                    @empty<tr><td colspan="4" class="py-10 text-center text-secondary-foreground">No hay actividad registrada.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        </main>
        @include('layouts.partials.admin.footer')
    </div>
</div>
@endsection
