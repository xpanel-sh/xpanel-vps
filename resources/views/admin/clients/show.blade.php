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
                <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-400 hover:text-white">Volver a clientes</a>
                <h1 class="mt-3 text-3xl font-black">{{ $tenant->name }}</h1>
                <p class="mt-2 text-gray-400">{{ $tenant->domain }} · {{ $tenant->user?->email }}</p>
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
                <div class="mt-2 truncate text-lg font-black">{{ $tenant->user?->name ?? 'Sin usuario' }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Instancia XPanel Host</h2>
                    @if($tenant->hostInstance)
                        <p class="mt-2 text-sm text-gray-400">Entorno aislado: usuario Linux, base de datos, storage y PHP-FPM propios.</p>
                        <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                            <div><dt class="text-gray-500">Dominio</dt><dd class="font-semibold">{{ $tenant->hostInstance->panel_domain }}</dd></div>
                            <div><dt class="text-gray-500">Estado</dt><dd class="font-semibold uppercase">{{ $tenant->hostInstance->status }}</dd></div>
                            <div><dt class="text-gray-500">Versión</dt><dd class="font-semibold">{{ $tenant->hostInstance->version }} / {{ $tenant->hostInstance->update_channel }}</dd></div>
                            <div><dt class="text-gray-500">PHP</dt><dd class="font-semibold">{{ $tenant->hostInstance->php_version }}</dd></div>
                            <div><dt class="text-gray-500">Acceso temporal</dt><dd class="font-semibold">{{ $tenant->hostInstance->fallbackUrl() ?? 'Sin IP pública configurada' }}</dd></div>
                            <div><dt class="text-gray-500">SSL</dt><dd class="font-semibold uppercase">{{ $tenant->hostInstance->ssl_status }}</dd></div>
                        </dl>
                        @if($tenant->hostInstance->last_error)
                            <p class="mt-4 rounded-xl border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-200">{{ $tenant->hostInstance->last_error }}</p>
                        @endif
                    @else
                        <p class="mt-2 max-w-2xl text-sm text-gray-400">Crea el panel Host independiente de este cliente usando la versión compartida instalada en el VPS.</p>
                    @endif
                </div>

                @if($tenant->hostInstance)
                    <div class="flex flex-wrap gap-3">
                        @if($tenant->hostInstance->status === 'active')
                            <a href="{{ $tenant->hostInstance->panelUrl() }}" target="_blank" rel="noopener" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-black">Abrir Host</a>
                        @endif
                        <form action="{{ route('admin.instances.apply', $tenant->hostInstance) }}" method="POST" class="flex flex-wrap gap-2">
                            @csrf
                            @if(!$tenant->hostInstance->provisioned_at)
                                <input name="owner_password" type="password" minlength="16" placeholder="Clave inicial (16+)" class="rounded-xl border border-white/10 bg-black px-4 py-3 text-sm" {{ config('xpanel.native_hosting.apply_system_changes') ? 'required' : '' }}>
                            @endif
                            <button class="rounded-xl border border-white/10 px-5 py-3 text-sm font-bold hover:bg-white/10">Aplicar configuración</button>
                        </form>
                        @if($tenant->hostInstance->status === 'active' && $tenant->hostInstance->ssl_status !== 'active')
                            <form action="{{ route('admin.instances.retry-ssl', $tenant->hostInstance) }}" method="POST">@csrf<button class="rounded-xl border border-white/10 px-5 py-3 text-sm font-bold hover:bg-white/10">Reintentar SSL</button></form>
                        @endif
                    </div>
                @else
                    <form action="{{ route('admin.clients.instances.store', $tenant) }}" method="POST" class="grid w-full max-w-md gap-3">
                        @csrf
                        <label class="text-xs font-bold uppercase tracking-widest text-gray-500">Dominio del panel</label>
                        <input name="panel_domain" value="{{ old('panel_domain', 'panel.'.$tenant->domain) }}" required class="rounded-xl border border-white/10 bg-black px-4 py-3 text-sm">
                        <label class="text-xs font-bold uppercase tracking-widest text-gray-500">Clave inicial del dueño</label>
                        <input name="owner_password" type="password" minlength="16" placeholder="Mínimo 16 caracteres" class="rounded-xl border border-white/10 bg-black px-4 py-3 text-sm" {{ config('xpanel.native_hosting.apply_system_changes') ? 'required' : '' }}>
                        <button class="rounded-xl bg-primary px-5 py-3 text-sm font-bold text-primary-foreground">Crear instancia</button>
                    </form>
                @endif
            </div>
        </div>

        @if($tenant->hostInstance && $tenant->hostInstance->brokerOperations->isNotEmpty())
            <div class="rounded-2xl border border-white/10 bg-white/[0.03]">
                <div class="border-b border-white/10 p-6"><h2 class="text-xl font-bold">Operaciones privilegiadas recientes</h2><p class="mt-1 text-sm text-gray-500">Auditoría del broker de esta instancia.</p></div>
                <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left"><thead class="text-xs uppercase tracking-widest text-gray-500"><tr><th class="px-6 py-4">Acción</th><th class="px-6 py-4">Estado</th><th class="px-6 py-4">Solicitud</th><th class="px-6 py-4">Fecha</th></tr></thead><tbody class="divide-y divide-white/10">@foreach($tenant->hostInstance->brokerOperations as $operation)<tr><td class="px-6 py-4 font-semibold">{{ $operation->action }}</td><td class="px-6 py-4"><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">{{ strtoupper($operation->status) }}</span></td><td class="px-6 py-4 font-mono text-xs text-gray-400">{{ $operation->request_id }}</td><td class="px-6 py-4 text-sm text-gray-400">{{ $operation->created_at }}</td></tr>@endforeach</tbody></table></div>
            </div>
        @endif

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
