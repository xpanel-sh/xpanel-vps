@extends('layouts.client-store')

@section('content')
<div>
    <div>
        <main class="grow" role="content">
            <div class="kt-container-fixed">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-7.5">
                    <div><h1 class="text-xl font-medium text-mono">Planes de hosting</h1><p class="text-sm text-secondary-foreground mt-1">Cada contratación crea una cuenta XPanel Host independiente para {{ $tenant->name }}.</p></div>
                    <span class="kt-badge kt-badge-outline">{{ $tenant->hostingAccounts()->count() }} hosting(s)</span>
                </div>
                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5">
                    @foreach($plans as $plan)
                        @php($selected = request('selected') === $plan->slug)
                        <div class="kt-card {{ $selected ? 'border-primary bg-muted/40' : '' }}">
                            <div class="kt-card-content flex flex-col p-7.5">
                                <div class="flex items-center justify-between gap-3"><h3 class="text-lg text-mono font-medium">{{ $plan->name }}</h3>@if($selected)<span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-primary">Seleccionado</span>@endif</div>
                                <div class="text-secondary-foreground text-sm min-h-12 mt-2">{{ $plan->description ?: 'Recursos administrados para tus proyectos web.' }}</div>
                                <div class="flex items-end gap-1.5 py-5"><div class="text-3xl text-mono font-semibold leading-none">${{ number_format((float) $plan->monthly_price, 2) }}</div><div class="text-secondary-foreground text-xs">por mes</div></div>
                                <div class="grid gap-3 grow text-sm">
                                    <div class="flex justify-between border-b border-border pb-3"><span class="text-secondary-foreground">Sitios web</span><span class="text-mono font-medium">{{ $plan->max_sites ?: 'Ilimitados' }}</span></div>
                                    <div class="flex justify-between border-b border-border pb-3"><span class="text-secondary-foreground">Bases de datos</span><span class="text-mono font-medium">{{ $plan->max_databases ?: 'Ilimitadas' }}</span></div>
                                    <div class="flex justify-between border-b border-border pb-3"><span class="text-secondary-foreground">Almacenamiento</span><span class="text-mono font-medium">{{ $plan->storage_mb >= 1024 ? number_format($plan->storage_mb / 1024, 0).' GB' : $plan->storage_mb.' MB' }}</span></div>
                                    <div class="flex justify-between"><span class="text-secondary-foreground">Transferencia (aviso, sin corte)</span><span class="text-mono font-medium">{{ $plan->bandwidth_gb > 0 ? $plan->bandwidth_gb.' GB/mes' : 'Sin umbral definido' }}</span></div>
                                </div>
                                <div class="grid gap-1.5 mt-5 text-xs text-secondary-foreground"><div>Duración: {{ $plan->billing_period_months }} mes(es)</div><div>Plazo para pagar: {{ $plan->payment_due_days }} días</div></div>
                                <form method="POST" action="{{ route('client.plans.contract', $plan) }}" class="mt-6">@csrf<button class="kt-btn kt-btn-primary justify-center w-full">Contratar nuevo hosting</button></form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
