@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="space-y-6">
        @if($errors->any())
            <div class="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-400 hover:text-white">Volver a clientes</a>
                <h1 class="mt-3 text-3xl font-black">{{ $tenant->name }}</h1>
                <p class="mt-2 text-gray-400">{{ $tenant->domain }} · {{ $tenant->access_ready ? $tenant->user?->email : 'Acceso pendiente del primer hosting' }}</p>
            </div>
            <form action="{{ route('admin.clients.toggle-status', $tenant) }}" method="POST">
                @csrf
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.clients.edit', $tenant) }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-black transition hover:bg-gray-200">
                        Editar cliente
                    </a>
                    <button class="rounded-xl border border-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        {{ $tenant->status === 'active' ? 'Suspender cliente' : 'Activar cliente' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-black p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Estado</div>
                <div class="mt-2 text-2xl font-black {{ $tenant->status === 'active' ? 'text-emerald-300' : 'text-red-300' }}">
                    {{ ucfirst($tenant->status) }}
                </div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Plan</div>
                <div class="mt-2 text-2xl font-black">{{ $tenant->plan?->name ?? 'Sin plan' }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Sitios</div>
                <div class="mt-2 text-2xl font-black">{{ $tenant->sites->count() }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Dueño</div>
                <div class="mt-2 truncate text-lg font-black">{{ $tenant->access_ready ? $tenant->user?->name : 'Pendiente' }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <div class="flex flex-wrap items-start justify-between gap-5"><div><h2 class="text-xl font-bold">Cuentas de hosting</h2><p class="mt-2 text-sm text-gray-400">Cada cuenta posee plan, recursos e instancia XPanel Host independientes.</p></div><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">{{ $tenant->hostingAccounts->count() }} HOSTING(S)</span></div>
            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                @forelse($tenant->hostingAccounts as $account)
                    @php($instance = $account->hostInstance)
                    <div class="rounded-xl border border-white/10 p-5"><div class="flex items-center justify-between gap-3"><h3 class="font-bold">{{ $account->name }}</h3><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">{{ strtoupper($account->status) }}</span></div><dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2"><div><dt class="text-gray-500">Plan</dt><dd>{{ $account->plan?->name ?? 'Sin plan' }}</dd></div><div><dt class="text-gray-500">Dominio</dt><dd class="break-all">{{ $account->custom_panel_domain ?: ($instance?->panel_domain ?? 'Pendiente') }}</dd></div><div><dt class="text-gray-500">Versión</dt><dd>{{ $instance?->version ?? 'Pendiente' }}</dd></div><div><dt class="text-gray-500">PHP</dt><dd>{{ $instance?->php_version ?? '—' }}</dd></div></dl>@if($instance)<div class="mt-4 flex flex-wrap gap-2">@if($instance->status === 'active')<a href="{{ route('admin.instances.access', $instance) }}" target="_blank" rel="noopener" class="rounded-lg bg-white px-3 py-2 text-xs font-bold text-black">Abrir Host</a>@endif<form action="{{ route('admin.instances.apply', $instance) }}" method="POST">@csrf<button class="rounded-lg border border-white/10 px-3 py-2 text-xs font-bold">Aplicar</button></form>@if($instance->ssl_status !== 'active')<form action="{{ route('admin.instances.retry-ssl', $instance) }}" method="POST">@csrf<button class="rounded-lg border border-white/10 px-3 py-2 text-xs font-bold">Reintentar SSL</button></form>@endif</div>@endif</div>
                @empty
                    <p class="text-sm text-gray-500">Este cliente todavía no tiene hostings contratados.</p>
                @endforelse
            </div>
            <form action="{{ route('admin.clients.instances.store', $tenant) }}" method="POST" class="mt-6 grid gap-3 border-t border-white/10 pt-6 md:grid-cols-2 xl:grid-cols-3">
                @csrf
                <div class="md:col-span-2 xl:col-span-3">
                    <h3 class="font-bold">Crear hosting</h3>
                    <p class="mt-1 text-xs text-gray-400">Cada hosting tendrá su propia cuenta administradora. El primer administrador también habilita el acceso general del cliente.</p>
                </div>
                <input name="name" value="{{ old('name') }}" placeholder="Nombre del hosting" class="rounded-xl border border-white/10 bg-black px-4 py-3 text-sm">
                <select name="plan_id" class="rounded-xl border border-white/10 bg-black px-4 py-3 text-sm" required>
                    <option value="">Selecciona un plan</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->name }} · {{ number_format($plan->storage_mb / 1024, 1) }} GB</option>
                    @endforeach
                </select>
                <input name="panel_domain" placeholder="Dominio técnico opcional" class="rounded-xl border border-white/10 bg-black px-4 py-3 text-sm">
                <input name="admin_name" value="{{ old('admin_name', $tenant->access_ready ? $tenant->user?->name : '') }}" placeholder="Nombre del administrador" class="rounded-xl border border-white/10 bg-black px-4 py-3 text-sm" required>
                <input name="admin_email" type="email" value="{{ old('admin_email', $tenant->access_ready ? $tenant->user?->email : '') }}" placeholder="Correo del administrador" class="rounded-xl border border-white/10 bg-black px-4 py-3 text-sm" required>
                <div class="flex rounded-xl border border-white/10 bg-black px-4 py-1 text-sm">
                    <input id="host_admin_password" name="admin_password" type="text" value="{{ old('admin_password', Str::password(20)) }}" minlength="16" maxlength="128" class="min-w-0 grow bg-transparent py-2 outline-none" required>
                    <button type="button" class="text-xs font-bold text-primary" onclick="document.getElementById('host_admin_password').value = crypto.randomUUID().replaceAll('-', '').slice(0, 20) + 'Aa1!'">Generar</button>
                </div>
                <button class="rounded-xl bg-primary px-5 py-3 text-sm font-bold text-primary-foreground md:col-start-2 xl:col-start-3">Crear hosting</button>
            </form>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03]">
            <div class="border-b border-white/10 p-6">
                <h2 class="text-xl font-bold">Sitios del cliente</h2>
                <p class="mt-1 text-sm text-gray-500">Vista rápida de los proyectos asignados a esta cuenta.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Dominio</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">PHP</th>
                            <th class="px-6 py-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($tenant->sites as $site)
                            <tr>
                                <td class="px-6 py-4 font-semibold text-white">{{ $site->domain }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ strtoupper($site->project_type) }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $site->php_version }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-300">
                                        {{ strtoupper($site->status ?? 'active') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    Este cliente aún no tiene sitios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
