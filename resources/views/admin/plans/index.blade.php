@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl border-x border-b border-input bg-background mx-5 mb-5 lg:ms-(--sidebar-width) lg:mt-(--navbar-height)">
    <div class="flex grow flex-col kt-scrollable-y-auto p-5 lg:p-8">
        <main class="mx-auto flex w-full max-w-7xl grow flex-col gap-5" role="content">
            @if(session('success'))<div class="kt-alert kt-alert-success"><i class="ki-filled ki-check-circle"></i>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="kt-alert kt-alert-destructive"><i class="ki-filled ki-information-2"></i>{{ $errors->first() }}</div>@endif

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="text-sm text-secondary-foreground">Administración / Planes</div>
                    <h1 class="mt-1 text-2xl font-semibold text-mono">Planes de hosting</h1>
                    <p class="mt-1 text-sm text-secondary-foreground">Define capacidad, recursos y condiciones comerciales para cada hosting.</p>
                </div>
                <a class="kt-btn kt-btn-primary" href="{{ route('admin.plans.create') }}"><i class="ki-filled ki-plus"></i>Crear plan</a>
            </div>

            <div class="kt-alert kt-alert-warning">
                <i class="ki-filled ki-information-2"></i>
                <div><strong>Límites administrados.</strong> RAM, CPU, procesos, sitios, bases y buzones se aplican por hosting. La transferencia excedida reduce la velocidad y las cuotas de archivos requieren soporte Linux activo.</div>
            </div>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @forelse($plans as $plan)
                    <article class="kt-card overflow-hidden">
                        <div class="kt-card-header items-start gap-3 py-5">
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold text-mono">{{ $plan->name }}</h2>
                                <div class="mt-1 text-xs text-secondary-foreground">{{ $plan->slug }}</div>
                            </div>
                            <span class="kt-badge kt-badge-outline {{ $plan->is_active ? 'kt-badge-success' : 'kt-badge-secondary' }}">{{ $plan->is_active ? 'Activo' : 'Borrador' }}</span>
                        </div>
                        <div class="kt-card-content p-5">
                            <div class="flex items-end justify-between gap-3 border-b border-border pb-5">
                                <div>
                                    <div class="text-xs text-secondary-foreground">Precio mensual</div>
                                    @if(!$plan->is_active && (float) $plan->monthly_price === 0.0)
                                        <div class="mt-1 text-xl font-semibold text-mono">Por definir</div>
                                    @else
                                        <div class="mt-1 text-2xl font-semibold text-mono">${{ number_format((float) $plan->monthly_price, 2) }} <span class="text-xs font-normal text-secondary-foreground">/ mes</span></div>
                                    @endif
                                </div>
                                <div class="text-right"><div class="text-xs text-secondary-foreground">Asignados</div><div class="mt-1 text-xl font-semibold text-mono">{{ $plan->hosting_accounts_count }}</div></div>
                            </div>

                            <dl class="mt-5 grid grid-cols-2 gap-x-5 gap-y-4 text-sm">
                                @foreach([
                                    ['Sitios', $plan->max_sites],
                                    ['Bases de datos', $plan->max_databases],
                                    ['Almacenamiento', number_format($plan->storage_mb / 1024, 1).' GB'],
                                    ['Transferencia', $plan->bandwidth_gb.' GB'],
                                    ['RAM', $plan->memory_mb.' MB'],
                                    ['CPU', $plan->cpu_percent.'%'],
                                    ['Procesos', $plan->tasks_max],
                                    ['Correos', $plan->email_accounts],
                                ] as [$label, $value])
                                    <div><dt class="text-xs text-secondary-foreground">{{ $label }}</dt><dd class="mt-1 font-medium text-mono">{{ $value }}</dd></div>
                                @endforeach
                            </dl>

                            <p class="mt-5 min-h-10 text-sm leading-5 text-secondary-foreground">{{ $plan->description ?: 'Sin descripción comercial todavía.' }}</p>
                            <div class="mt-5 flex items-center gap-2 border-t border-border pt-4">
                                <a class="kt-btn kt-btn-sm kt-btn-outline grow" href="{{ route('admin.plans.edit', $plan) }}"><i class="ki-filled ki-pencil"></i>Editar</a>
                                @if(!$plan->is_active && (float) $plan->monthly_price <= 0)
                                    <a class="kt-btn kt-btn-sm kt-btn-primary" href="{{ route('admin.plans.edit', $plan) }}">Configurar precio</a>
                                @else
                                    <form action="{{ route('admin.plans.toggle', $plan) }}" method="POST">@csrf<button class="kt-btn kt-btn-sm {{ $plan->is_active ? 'kt-btn-ghost' : 'kt-btn-primary' }}">{{ $plan->is_active ? 'Pausar' : 'Activar' }}</button></form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="kt-card md:col-span-2 xl:col-span-3"><div class="kt-card-content py-14 text-center"><i class="ki-filled ki-dollar text-3xl text-muted-foreground"></i><p class="mt-3 font-medium text-mono">Todavía no hay planes.</p><a class="kt-btn kt-btn-primary kt-btn-sm mt-4" href="{{ route('admin.plans.create') }}">Crear el primero</a></div></div>
                @endforelse
            </div>
            <div>{{ $plans->links() }}</div>
        </main>
        @include('layouts.partials.admin.footer')
    </div>
</div>
@endsection
