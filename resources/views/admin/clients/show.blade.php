@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
                        @if(session('success'))
                            <div class="kt-alert kt-alert-success"><i class="ki-filled ki-check-circle"></i>{{ session('success') }}</div>
                        @endif

                        @if(session('warning'))
                            <div class="kt-alert kt-alert-warning"><i class="ki-filled ki-information-2"></i>{{ session('warning') }}</div>
                        @endif

                        @if(session('info'))
                            <div class="kt-alert kt-alert-primary"><i class="ki-filled ki-information-2"></i>{{ session('info') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="kt-alert kt-alert-destructive">
                                <i class="ki-filled ki-information-2"></i>
                                <div class="grid gap-1">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <a href="{{ route('admin.clients.index') }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost mt-0.5" aria-label="Volver a clientes"><i class="ki-filled ki-left"></i></a>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h1 class="truncate text-2xl font-semibold text-mono">{{ $tenant->name }}</h1>
                                        <span class="kt-badge kt-badge-sm kt-badge-outline {{ $tenant->status === 'active' ? 'kt-badge-success' : 'kt-badge-destructive' }}">{{ $tenant->status === 'active' ? 'Activo' : 'Suspendido' }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-secondary-foreground">
                                        {{ $tenant->domain }} <span class="mx-1">·</span> {{ $tenant->access_ready ? $tenant->user?->email : 'Acceso pendiente del primer hosting' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.clients.edit', $tenant) }}" class="kt-btn kt-btn-outline"><i class="ki-filled ki-pencil"></i>Editar cliente</a>
                                <form action="{{ route('admin.clients.toggle-status', $tenant) }}" method="POST">
                                    @csrf
                                    <button class="kt-btn {{ $tenant->status === 'active' ? 'kt-btn-destructive kt-btn-outline' : 'kt-btn-success' }}">
                                        <i class="ki-filled {{ $tenant->status === 'active' ? 'ki-cross-circle' : 'ki-check-circle' }}"></i>{{ $tenant->status === 'active' ? 'Suspender' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-5">
                            @foreach([
                                ['Hostings', $tenant->hostingAccounts->count()],
                                ['Sitios', $tenant->sites->count()],
                                ['Instancias activas', $tenant->hostingAccounts->filter(fn ($account) => $account->hostInstance?->status === 'active')->count()],
                                ['Acceso general', $tenant->access_ready ? $tenant->user?->name : 'Pendiente'],
                            ] as [$label, $value])
                                <div class="kt-card"><div class="kt-card-content p-4 lg:p-5"><div class="text-sm text-secondary-foreground">{{ $label }}</div><div class="mt-1 truncate text-2xl font-semibold text-mono">{{ $value }}</div></div></div>
                            @endforeach
                        </div>

                        <div class="kt-card">
                            <div class="kt-card-header flex-wrap gap-3 py-4">
                                <div><h2 class="kt-card-title">Cuentas de hosting</h2><p class="mt-1 text-xs text-secondary-foreground">Cada hosting tiene su propio plan, panel y administrador.</p></div>
                                <span class="kt-badge kt-badge-outline">{{ $tenant->hostingAccounts->count() }} en total</span>
                            </div>
                            <div class="kt-card-content p-5">
                                <div class="grid gap-4 xl:grid-cols-2">
                                    @forelse($tenant->hostingAccounts as $account)
                                        @php($instance = $account->hostInstance)
                                        <article class="rounded-xl border border-border bg-background p-5">
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h3 class="truncate font-semibold text-mono">{{ $account->name }}</h3>
                                                        <span class="kt-badge kt-badge-sm kt-badge-outline {{ $instance?->status === 'active' ? 'kt-badge-success' : ($instance?->status === 'error' ? 'kt-badge-destructive' : 'kt-badge-warning') }}">
                                                            {{ match($instance?->status) { 'active' => 'Operativo', 'error' => 'Con error', 'staged' => 'Preparado', 'suspended' => 'Suspendido', default => 'Pendiente' } }}
                                                        </span>
                                                    </div>
                                                    <div class="mt-1 truncate text-xs text-secondary-foreground">{{ $instance?->panel_domain ?? 'Dominio técnico pendiente' }}</div>
                                                </div>
                                                <span class="kt-badge kt-badge-primary kt-badge-outline">{{ $account->plan?->name ?? 'Sin plan' }}</span>
                                            </div>

                                            <div class="mt-5 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                                                <div class="min-w-0"><div class="text-xs text-secondary-foreground">Administrador</div><div class="mt-0.5 truncate font-medium text-mono">{{ $account->admin_name ?: 'No definido' }}</div></div>
                                                <div class="min-w-0"><div class="text-xs text-secondary-foreground">Correo</div><div class="mt-0.5 truncate font-medium text-mono" title="{{ $account->admin_email }}">{{ $account->admin_email ?: 'No definido' }}</div></div>
                                                <div><div class="text-xs text-secondary-foreground">Versión</div><div class="mt-0.5 font-medium text-mono">{{ $instance?->version ?? 'Pendiente' }}</div></div>
                                                <div><div class="text-xs text-secondary-foreground">SSL</div><div class="mt-0.5 font-medium text-mono">{{ match($instance?->ssl_status) { 'active' => 'Activo', 'waiting_dns' => 'Esperando DNS', 'error' => 'Con error', default => 'Pendiente' } }}</div></div>
                                            </div>

                                            @if($instance?->last_error)
                                                <div class="mt-4 rounded-lg border border-destructive/20 bg-destructive/10 px-3 py-2 text-xs text-destructive">{{ Str::limit($instance->last_error, 180) }}</div>
                                            @endif

                                            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-border pt-4">
                                                @if($instance?->status === 'active')
                                                    <a href="{{ route('admin.instances.access', $instance) }}" target="_blank" rel="noopener" class="kt-btn kt-btn-primary kt-btn-sm"><i class="ki-filled ki-exit-right-corner"></i>Abrir panel</a>
                                                @endif
                                                @if($instance && $instance->ssl_status !== 'active')
                                                    <form action="{{ route('admin.instances.retry-ssl', $instance) }}" method="POST">@csrf<button class="kt-btn kt-btn-outline kt-btn-sm"><i class="ki-filled ki-shield-tick"></i>Reintentar SSL</button></form>
                                                @endif
                                                @if($instance)
                                                    <form action="{{ route('admin.instances.update', $instance) }}" method="POST" onsubmit="return confirm('Se migrará esta instancia a la release actual de XPanel Host. ¿Continuar?')">
                                                        @csrf
                                                        <button class="kt-btn kt-btn-outline kt-btn-sm" title="Actualiza únicamente esta instancia a la release preparada por XPanel VPS"><i class="ki-filled ki-update-file"></i>Actualizar Host</button>
                                                    </form>
                                                    <form action="{{ route('admin.instances.apply', $instance) }}" method="POST" class="ms-auto" onsubmit="return confirm('Esto volverá a generar y aplicar la configuración técnica de esta instancia. ¿Continuar?')">
                                                        @csrf
                                                        <button class="kt-btn kt-btn-ghost kt-btn-sm" title="Regenera PHP-FPM, Nginx, entorno y límites de la instancia"><i class="ki-filled ki-arrows-circle"></i>Reparar configuración</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </article>
                                    @empty
                                        <div class="col-span-full rounded-xl border border-dashed border-border px-5 py-10 text-center">
                                            <i class="ki-filled ki-cloud-add text-3xl text-muted-foreground"></i><p class="mt-3 text-sm font-medium text-mono">Este cliente todavía no tiene hostings.</p><p class="mt-1 text-xs text-secondary-foreground">Crea el primero con el formulario inferior.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="kt-card">
                            <div class="kt-card-header"><div><h2 class="kt-card-title">Crear hosting</h2><p class="mt-1 text-xs text-secondary-foreground">El primer administrador también habilita el acceso general del cliente.</p></div></div>
                            <form action="{{ route('admin.clients.instances.store', $tenant) }}" method="POST" class="kt-card-content grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                                @csrf
                                <div class="grid gap-1.5"><label for="hosting_name" class="kt-form-label">Nombre del hosting</label><input id="hosting_name" name="name" value="{{ old('name') }}" placeholder="Hosting principal" class="kt-input"></div>
                                <div class="grid gap-1.5"><label for="hosting_plan" class="kt-form-label">Plan</label><select id="hosting_plan" name="plan_id" class="kt-select" required><option value="">Selecciona un plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->name }} · {{ number_format($plan->storage_mb / 1024, 1) }} GB</option>@endforeach</select></div>
                                <div class="grid gap-1.5"><label for="panel_domain" class="kt-form-label">Dominio técnico <span class="text-secondary-foreground">(opcional)</span></label><input id="panel_domain" name="panel_domain" value="{{ old('panel_domain') }}" placeholder="Se genera automáticamente" class="kt-input"></div>
                                <div class="grid gap-1.5"><label for="admin_name" class="kt-form-label">Administrador</label><input id="admin_name" name="admin_name" value="{{ old('admin_name', $tenant->access_ready ? $tenant->user?->name : '') }}" placeholder="Nombre completo" class="kt-input" required></div>
                                <div class="grid gap-1.5"><label for="admin_email" class="kt-form-label">Correo de acceso</label><input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email', $tenant->access_ready ? $tenant->user?->email : '') }}" placeholder="admin@cliente.com" class="kt-input" required></div>
                                <div class="grid gap-1.5">
                                    <label for="host_admin_password" class="kt-form-label">Contraseña inicial</label>
                                    <div class="kt-input flex items-center pe-2"><input id="host_admin_password" name="admin_password" type="text" value="{{ old('admin_password', Str::password(20)) }}" minlength="16" maxlength="128" class="min-w-0 grow border-0 bg-transparent outline-none" required><button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" onclick="document.getElementById('host_admin_password').value = crypto.randomUUID().replaceAll('-', '').slice(0, 20) + 'Aa1!'">Generar</button></div>
                                </div>
                                <div class="flex justify-end border-t border-border pt-4 md:col-span-2 xl:col-span-3"><button class="kt-btn kt-btn-primary"><i class="ki-filled ki-plus"></i>Crear hosting</button></div>
                            </form>
                        </div>

                        @if($tenant->sites->isNotEmpty())
                            <div class="kt-card kt-card-grid">
                                <div class="kt-card-header"><h2 class="kt-card-title">Sitios registrados</h2><span class="kt-badge kt-badge-outline">{{ $tenant->sites->count() }}</span></div>
                                <div class="kt-card-table"><div class="kt-scrollable-x-auto"><table class="kt-table kt-table-border min-w-[640px]"><thead><tr><th>Dominio</th><th>Tipo</th><th>PHP</th><th>Estado</th></tr></thead><tbody>
                                    @foreach($tenant->sites as $site)<tr><td class="font-medium text-mono">{{ $site->domain }}</td><td>{{ strtoupper($site->project_type) }}</td><td>{{ $site->php_version }}</td><td><span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-success">{{ strtoupper($site->status ?? 'active') }}</span></td></tr>@endforeach
                                </tbody></table></div></div>
                            </div>
                        @endif
                    </div>
                </div>
            </main>
            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
