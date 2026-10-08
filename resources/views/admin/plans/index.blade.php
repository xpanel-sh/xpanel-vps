@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Admin Global</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Planes de Hosting</h1>
                <p class="mt-2 text-gray-400">Define límites base para clientes: sitios, bases de datos, almacenamiento y correo.</p>
                <p class="mt-2 text-sm text-amber-300">RAM, CPU, procesos, sitios, bases y buzones se aplican en Host administrado. Almacenamiento e inodos aún no son cuotas de disco; transferencia no tiene corte automático.</p>
            </div>
            <a href="{{ route('admin.plans.create') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-black transition hover:bg-gray-200">
                Crear plan
            </a>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            @forelse($plans as $plan)
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black">{{ $plan->name }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $plan->slug }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $plan->is_active ? 'bg-emerald-500/10 text-emerald-300' : 'bg-red-500/10 text-red-300' }}">
                            {{ $plan->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    <div class="mt-5 text-3xl font-black">
                        @if(!$plan->is_active && (float) $plan->monthly_price === 0.0)
                            <span class="text-lg">Precio pendiente</span>
                        @else
                            ${{ $plan->monthly_price }}
                            <span class="text-sm font-medium text-gray-500">/ mes</span>
                        @endif
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">Sitios</dt>
                            <dd class="mt-1 font-bold">{{ $plan->max_sites }}</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">DB</dt>
                            <dd class="mt-1 font-bold">{{ $plan->max_databases }}</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">Storage</dt>
                            <dd class="mt-1 font-bold">{{ number_format($plan->storage_mb / 1024, 1) }} GB</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">Cuentas asignadas</dt>
                            <dd class="mt-1 font-bold">{{ $plan->hosting_accounts_count }}</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">RAM / CPU</dt>
                            <dd class="mt-1 font-bold">{{ $plan->memory_mb }} MB / {{ $plan->cpu_percent }}%</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">Procesos</dt>
                            <dd class="mt-1 font-bold">{{ $plan->tasks_max }}</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">Duración</dt>
                            <dd class="mt-1 font-bold">{{ $plan->billing_period_months }} mes(es)</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">Plazo de pago</dt>
                            <dd class="mt-1 font-bold">{{ $plan->payment_due_days }} días</dd>
                        </div>
                    </dl>

                    @if($plan->description)
                        <p class="mt-4 text-sm text-gray-400">{{ $plan->description }}</p>
                    @endif

                    <div class="mt-6 flex gap-2">
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="flex-1 rounded-xl border border-white/10 px-4 py-2 text-center text-sm font-bold text-white hover:bg-white/10">
                            Editar
                        </a>
                        <form action="{{ route('admin.plans.toggle', $plan) }}" method="POST">
                            @csrf
                            <button class="rounded-xl border border-white/10 px-4 py-2 text-sm font-bold text-gray-300 hover:bg-white/10">
                                {{ $plan->is_active ? 'Pausar' : 'Activar' }}
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-white/10 p-10 text-center text-gray-500 lg:col-span-3">
                    Todavía no hay planes. Crea el primer paquete para empezar a organizar clientes.
                </div>
            @endforelse
        </div>

        {{ $plans->links() }}
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
