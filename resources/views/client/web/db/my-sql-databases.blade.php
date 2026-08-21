@extends('layouts.client')

@php
    // xpanel-php:*-apache tiene socat que proxea localhost → xpanel-db:3306.
    // serversideup/php Nginx y otros stacks no tienen ese proxy → deben usar xpanel-db.
    $dbHost = ($site->project_type === 'php' && $site->web_server !== 'nginx')
        ? 'localhost'
        : 'xpanel-db';

    $dbPrefix        = ($site->tenant?->code ?: 'X000000') . '_';
    $nameSuffix      = old('name_suffix', str_starts_with((string) old('name'), $dbPrefix) ? substr((string) old('name'), strlen($dbPrefix)) : '');
    $usernameSuffix  = old('username_suffix', str_starts_with((string) old('username'), $dbPrefix) ? substr((string) old('username'), strlen($dbPrefix)) : '');
    $suffixMaxLength = max(1, 32 - strlen($dbPrefix));
    $allPrivileges   = ['SELECT','INSERT','UPDATE','DELETE','CREATE','DROP','INDEX','ALTER','REFERENCES'];

    // if ($databases->isEmpty()) {
    //     $fakeUser1 = (object)['id'=>null,'username'=>$dbPrefix.'usuario1','status'=>'active','privileges'=>$allPrivileges];
    //     $fakeUser2 = (object)['id'=>null,'username'=>$dbPrefix.'shop_user','status'=>'active','privileges'=>['SELECT','INSERT']];
    //     $db1 = \App\Models\ManagedDatabase::make(['name'=>$dbPrefix.'mi_proyecto','engine'=>'mysql','status'=>'active','created_at'=>now()]);
    //     $db1->setRelation('dbUsers', collect([$fakeUser1]));
    //     $db2 = \App\Models\ManagedDatabase::make(['name'=>$dbPrefix.'tienda','engine'=>'mysql','status'=>'active','created_at'=>now()->subDays(3)]);
    //     $db2->setRelation('dbUsers', collect([$fakeUser2]));
    //     $databases = collect([$db1, $db2]);
    // }
@endphp

@section('content')
{{-- x-data cubre todo: tabla, colapsos y los tres modales --}}
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5"
    x-data="{
        /* ── colapso de cada tarjeta DB (clave: índice de loop) ── */
        expanded: {},
        isOpen(k)    { return this.expanded[k] === true; },
        toggleOpen(k){ this.expanded[k] = !this.isOpen(k); },

        /* ── modal Permisos ── */
        allPrivs: @js($allPrivileges),
        permDbId: null, permUserId: null, permDbName: '', permUsername: '',
        permSelected: @js($allPrivileges),
        get allChecked() { return this.permSelected.length === this.allPrivs.length; },
        openPerms(dbId, dbName, userId, username, privs) {
            this.permDbId = dbId; this.permDbName = dbName;
            this.permUserId = userId; this.permUsername = username;
            this.permSelected = (Array.isArray(privs) && privs.length) ? [...privs] : [...this.allPrivs];
            this.$nextTick(() => document.getElementById('_perm_trigger').click());
        },
        toggleAll() { this.permSelected = this.allChecked ? [] : [...this.allPrivs]; },

        /* ── modal Cambiar contraseña ── */
        passDbId: null, passUserId: null, passUsername: '',
        openPass(dbId, userId, username) {
            this.passDbId = dbId; this.passUserId = userId; this.passUsername = username;
        },

        /* ── modal Agregar usuario ── */
        addDbId: null, addDbName: '',
        openAddUser(dbId, dbName) { this.addDbId = dbId; this.addDbName = dbName; }
    }">

    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- ── Cabecera de página ── --}}
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-mono">Bases de datos MySQL</h1>
                            <p class="mt-1 text-sm text-secondary-foreground">Administra bases de datos y usuarios MySQL para {{ $site->domain }}.</p>
                        </div>
                        <a class="kt-btn kt-btn-outline" href="{{ route('client.websites.show', ['domain' => $site->domain]) }}">
                            Panel del sitio
                        </a>
                    </div>

                    {{-- ── Mensajes flash ── --}}
                    @if(session('success'))
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-300">
                            {{ session('success') }}
                        </div>
                    @endif
                    @error('db_user')
                        <div class="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">{{ $message }}</div>
                    @enderror

                    {{-- ── Formulario crear DB ── --}}
                    <form action="{{ route('client.databases.store') }}" method="POST" class="kt-card overflow-hidden">
                        @csrf
                        <input type="hidden" name="site_id" value="{{ $site->id }}">
                        <input type="hidden" name="engine" value="mysql">

                        <div class="kt-card-header min-h-14">
                            <h2 class="kt-card-title flex items-center gap-2 text-base">
                                <i class="ki-filled ki-plus text-secondary-foreground"></i>
                                Crear nueva base de datos MySQL
                            </h2>
                        </div>

                        <div class="kt-card-content grid gap-5 p-5">
                            @if($errors->hasAny(['name','username','password']))
                                <div class="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div class="grid sm:grid-cols-3 gap-4">
                                <div class="grid gap-1.5">
                                    <label class="text-sm font-medium text-mono" for="name_suffix">Nombre de la base</label>
                                    <div class="kt-input-group">
                                        <span class="kt-input-addon text-xs">{{ $dbPrefix }}</span>
                                        <input id="name_suffix" class="kt-input" type="text" name="name_suffix"
                                            value="{{ $nameSuffix }}" required pattern="[A-Za-z0-9_]+"
                                            maxlength="{{ $suffixMaxLength }}" placeholder="mi_base">
                                    </div>
                                </div>
                                <div class="grid gap-1.5">
                                    <label class="text-sm font-medium text-mono" for="username_suffix">Usuario inicial</label>
                                    <div class="kt-input-group">
                                        <span class="kt-input-addon text-xs">{{ $dbPrefix }}</span>
                                        <input id="username_suffix" class="kt-input" type="text" name="username_suffix"
                                            value="{{ $usernameSuffix }}" required pattern="[A-Za-z0-9_]+"
                                            maxlength="{{ $suffixMaxLength }}" placeholder="usuario1">
                                    </div>
                                </div>
                                <div class="grid gap-1.5">
                                    <label class="text-sm font-medium text-mono" for="db_password">Contraseña</label>
                                    <div class="flex items-center rounded-xl border border-input bg-background">
                                        <input id="db_password" class="kt-input border-0" type="password" name="password"
                                            required minlength="16" maxlength="128" autocomplete="new-password" placeholder="Min. 16 caracteres">
                                        <button class="kt-btn kt-btn-icon kt-btn-ghost me-1" type="button"
                                            onclick="const i=document.getElementById('db_password');i.type=i.type==='password'?'text':'password'">
                                            <i class="ki-filled ki-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <button class="kt-btn kt-btn-primary" type="submit">
                                    <i class="ki-filled ki-check"></i>
                                    Crear base de datos
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- ── Lista de bases de datos ── --}}
                    <section class="grid gap-4">
                        <h2 class="text-lg font-semibold text-mono">Bases de datos</h2>

                        @foreach($databases as $database)
                        @php
                            $isDemo  = !$database->exists;
                            $loopKey = (string) $loop->index;
                            $dbUsers = $database->dbUsers ?? collect();
                            $statusClass = str_contains((string) $database->status, 'error')
                                ? 'kt-badge-danger'
                                : ($database->status === 'provisioning' ? 'kt-badge-warning' : 'kt-badge-success');
                        @endphp

                        <div class="kt-card overflow-visible">

                            {{-- ── Header de la DB ── --}}
                            <div class="kt-card-header min-h-14 flex items-center justify-between gap-3 px-5">
                                {{-- Izquierda: nombre y badges --}}
                                <div class="flex items-center gap-3 min-w-0">
                                    <i class="ki-filled ki-data text-xl text-secondary-foreground shrink-0"></i>
                                    <div class="min-w-0">
                                        <span class="font-semibold text-mono truncate">{{ $database->name }}</span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs text-secondary-foreground uppercase">{{ $database->engine }}</span>
                                            <span class="kt-badge kt-badge-outline {{ $statusClass }} text-xs">{{ $database->status }}</span>
                                            @if($isDemo)
                                                <span class="kt-badge kt-badge-outline kt-badge-warning text-xs">Ejemplo</span>
                                            @endif
                                            <span class="text-xs text-secondary-foreground font-mono">Host: <strong class="text-mono">{{ $dbHost }}</strong>:3306</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Derecha: acciones + toggle colapso --}}
                                <div class="flex items-center gap-2 shrink-0">
                                    @if(!$isDemo)
                                        <a class="kt-btn kt-btn-outline kt-btn-sm"
                                            href="{{ route('client.databases.phpmyadmin', $database) }}"
                                            target="_blank" rel="noopener">
                                            phpMyAdmin
                                        </a>
                                    @else
                                        <button type="button" class="kt-btn kt-btn-outline kt-btn-sm opacity-50 cursor-not-allowed" disabled>
                                            phpMyAdmin
                                        </button>
                                    @endif

                                    {{-- Agregar usuario --}}
                                    @if(!$isDemo)
                                        <button type="button"
                                            class="kt-btn kt-btn-sm kt-btn-primary"
                                            @click="openAddUser({{ $database->id }}, '{{ $database->name }}')"
                                            data-kt-modal-toggle="#add_user_modal">
                                            <i class="ki-filled ki-plus text-xs"></i>
                                            Agregar usuario
                                        </button>
                                    @else
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-primary opacity-50 cursor-not-allowed" disabled>
                                            <i class="ki-filled ki-plus text-xs"></i>
                                            Agregar usuario
                                        </button>
                                    @endif

                                    {{-- Toggle colapso --}}
                                    <button type="button"
                                        class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost"
                                        @click="toggleOpen('{{ $loopKey }}')"
                                        :title="isOpen('{{ $loopKey }}') ? 'Colapsar' : 'Expandir'">
                                        <i class="ki-filled text-sm transition-transform duration-200"
                                            :class="isOpen('{{ $loopKey }}') ? 'ki-up' : 'ki-down'"></i>
                                    </button>

                                    {{-- Eliminar DB --}}
                                    @if(!$isDemo)
                                        <form action="{{ route('client.databases.destroy', $database) }}" method="POST"
                                            onsubmit="return confirm('Eliminar {{ addslashes($database->name) }} y TODOS sus datos. Esto no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="kt-btn kt-btn-icon kt-btn-sm "
                                                title="Eliminar base de datos">
                                                <i class="ki-filled ki-trash text-xs"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button"
                                            class="kt-btn kt-btn-icon kt-btn-sm text-destructive border border-destructive/30 opacity-50 cursor-not-allowed" disabled
                                            title="Eliminar base de datos">
                                            <i class="ki-filled ki-trash text-xs"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- ── Cuerpo colapsable: tabla de usuarios ── --}}
                            <div x-show="isOpen('{{ $loopKey }}')" class="border-t border-border">

                                @if($dbUsers->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead class="border-b border-border bg-muted/30 text-xs font-semibold uppercase text-secondary-foreground">
                                            <tr>
                                                <th class="px-5 py-2.5">Usuario</th>
                                                <th class="px-5 py-2.5">Estado</th>
                                                <th class="px-5 py-2.5">Privilegios</th>
                                                <th class="px-5 py-2.5 text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-border">
                                            @foreach($dbUsers as $dbUser)
                                            @php
                                                $privs     = is_array($dbUser->privileges) ? $dbUser->privileges : $allPrivileges;
                                                $privLabel = count($privs) === count($allPrivileges)
                                                    ? 'Todos'
                                                    : implode(', ', array_slice($privs, 0, 3)) . (count($privs) > 3 ? '…' : '');
                                                $realUser  = !$isDemo && !empty($dbUser->id);
                                                $disabledCls = $realUser ? '' : 'opacity-50 cursor-not-allowed';
                                            @endphp
                                            <tr class="align-middle">
                                                <td class="px-5 py-3 font-mono text-sm text-mono">{{ $dbUser->username }}</td>
                                                <td class="px-5 py-3">
                                                    <span class="kt-badge kt-badge-outline {{ ($dbUser->status ?? 'active') === 'active' ? 'kt-badge-success' : 'kt-badge-secondary' }} text-xs">
                                                        {{ $dbUser->status ?? 'active' }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3 text-xs text-secondary-foreground font-mono">{{ $privLabel }}</td>
                                                <td class="px-5 py-3">
                                                    <div class="flex items-center justify-end gap-2">
                                                        {{-- Permisos --}}
                                                        <button type="button"
                                                            class="kt-btn kt-btn-outline kt-btn-sm {{ $disabledCls }}"
                                                            @if($realUser)
                                                                @click="openPerms({{ $database->id }}, '{{ $database->name }}', {{ $dbUser->id }}, '{{ $dbUser->username }}', @js($privs))"
                                                            @else
                                                                disabled
                                                            @endif>
                                                            <i class="ki-filled ki-shield-tick text-xs"></i>
                                                            Permisos
                                                        </button>

                                                        {{-- Contraseña --}}
                                                        <button type="button"
                                                            class="kt-btn kt-btn-outline kt-btn-sm {{ $disabledCls }}"
                                                            @if($realUser)
                                                                @click="openPass({{ $database->id }}, {{ $dbUser->id }}, '{{ $dbUser->username }}')"
                                                                data-kt-modal-toggle="#pass_modal"
                                                            @else
                                                                disabled
                                                            @endif>
                                                            <i class="ki-filled ki-key text-xs"></i>
                                                            Contraseña
                                                        </button>

                                                        {{-- Eliminar usuario --}}
                                                        @if($realUser)
                                                            <form action="{{ route('client.databases.users.destroy', [$database, $dbUser]) }}" method="POST"
                                                                onsubmit="return confirm('Eliminar usuario {{ addslashes($dbUser->username) }}?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="kt-btn kt-btn-icon kt-btn-sm "
                                                                    title="Eliminar usuario">
                                                                    <i class="ki-filled ki-trash text-xs"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <button type="button"
                                                                class="kt-btn kt-btn-icon kt-btn-sm  opacity-50 cursor-not-allowed" disabled
                                                                title="Eliminar usuario">
                                                                <i class="ki-filled ki-trash text-xs"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                    <div class="px-5 py-5 text-sm text-secondary-foreground text-center">
                                        Sin usuarios. Usa <strong>Agregar usuario</strong> para añadir uno.
                                    </div>
                                @endif

                            </div>{{-- /colapsable --}}

                        </div>{{-- /kt-card --}}
                        @endforeach
                    </section>

                </div>
            </div>
        </main>

        @include('layouts.partials.client.footer')
    </div>

    {{-- Trigger oculto: Alpine lo clickea desde $nextTick en openPerms --}}
    <button id="_perm_trigger" data-kt-modal-toggle="#perm_modal" hidden tabindex="-1" aria-hidden="true"></button>

    {{-- ════════════════════════════════════
        Modal: Permisos
    ════════════════════════════════════ --}}
    <div class="kt-modal" data-kt-modal="true" id="perm_modal">
    <div class="kt-modal-content max-w-[500px] top-[5%] max-h-[90vh] flex flex-col">
        <div class="kt-modal-header py-4 px-5 shrink-0">
            <i class="ki-filled ki-shield-tick text-muted-foreground text-xl"></i>
            <div class="flex flex-col grow ms-2">
                <span class="text-base font-semibold text-mono">Permisos MySQL</span>
                <span class="text-xs text-secondary-foreground" x-text="permDbName + ' → ' + permUsername"></span>
            </div>
            <button type="button" class="kt-modal-close" aria-label="Cerrar" data-kt-modal-dismiss="#perm_modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        <div class="kt-modal-body p-5 overflow-y-auto">
            <form method="POST" :action="'/client/databases/' + permDbId + '/users/' + permUserId + '/permissions'">
                @csrf
                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-border bg-muted/50 px-3 py-2.5 mb-3 hover:bg-muted transition-colors"
                    :class="allChecked ? 'border-primary/40 bg-primary/10' : ''">
                    <input type="checkbox" class="kt-checkbox" :checked="allChecked" @click.prevent="toggleAll()">
                    <span class="text-sm font-semibold text-mono">Seleccionar todos</span>
                    <span class="ms-auto text-xs text-secondary-foreground" x-text="permSelected.length + ' / ' + allPrivs.length"></span>
                </label>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    @foreach($allPrivileges as $priv)
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-border bg-accent/40 px-3 py-2.5 hover:bg-accent transition-colors"
                        :class="permSelected.includes('{{ $priv }}') ? 'border-primary/40 bg-primary/10' : ''">
                        <input type="checkbox" name="privileges[]" value="{{ $priv }}" class="kt-checkbox"
                            :checked="permSelected.includes('{{ $priv }}')"
                            @change="permSelected = $event.target.checked
                                ? [...permSelected, '{{ $priv }}']
                                : permSelected.filter(p => p !== '{{ $priv }}')">
                        <span class="text-sm font-mono text-mono">{{ $priv }}</span>
                    </label>
                    @endforeach
                </div>
                {{-- Aviso cuando no hay permisos: el usuario no podrá ejecutar queries --}}
                <div x-show="permSelected.length === 0"
                    class="rounded-lg border border-amber-400/30 bg-amber-400/10 px-3 py-2 mb-3 text-xs text-amber-600 dark:text-amber-400 flex items-center gap-2">
                    <i class="ki-filled ki-information-2 shrink-0"></i>
                    Sin permisos: el usuario podrá conectarse pero no ejecutar ninguna consulta.
                </div>
                <div class="flex gap-3 justify-end border-t border-border pt-4">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="#perm_modal">Cancelar</button>
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check"></i>
                        Guardar permisos
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>

    {{-- ════════════════════════════════════
        Modal: Cambiar contraseña de usuario
    ════════════════════════════════════ --}}
    <div class="kt-modal" data-kt-modal="true" id="pass_modal">
    <div class="kt-modal-content max-w-[400px] top-[10%] max-h-[90vh] flex flex-col">
        <div class="kt-modal-header py-4 px-5 shrink-0">
            <i class="ki-filled ki-key text-muted-foreground text-xl"></i>
            <div class="flex flex-col grow ms-2">
                <span class="text-base font-semibold text-mono">Cambiar contraseña</span>
                <span class="text-xs text-secondary-foreground" x-text="passUsername"></span>
            </div>
            <button type="button" class="kt-modal-close" aria-label="Cerrar" data-kt-modal-dismiss="#pass_modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        <div class="kt-modal-body p-5 overflow-y-auto">
            <form method="POST" :action="'/client/databases/' + passDbId + '/users/' + passUserId + '/password'">
                @csrf
                <div class="grid gap-4 mb-5">
                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-mono">Nueva contraseña</label>
                        <div class="flex items-center rounded-xl border border-input bg-background">
                            <input id="pass_modal_input" class="kt-input border-0" type="password" name="password"
                                required minlength="8" maxlength="128" placeholder="Min. 8 caracteres">
                            <button class="kt-btn kt-btn-icon kt-btn-ghost me-1" type="button"
                                onclick="const i=document.getElementById('pass_modal_input');i.type=i.type==='password'?'text':'password'">
                                <i class="ki-filled ki-eye"></i>
                            </button>
                        </div>
                        <p class="text-xs text-secondary-foreground">XPanel no almacena contraseñas en texto plano.</p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end border-t border-border pt-4">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="#pass_modal">Cancelar</button>
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check"></i>
                        Actualizar contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>

    {{-- ════════════════════════════════════
        Modal: Agregar usuario a la DB
    ════════════════════════════════════ --}}
    <div class="kt-modal" data-kt-modal="true" id="add_user_modal">
    <div class="kt-modal-content max-w-[420px] top-[10%] max-h-[90vh] flex flex-col">
        <div class="kt-modal-header py-4 px-5 shrink-0">
            <i class="ki-filled ki-user-plus text-muted-foreground text-xl"></i>
            <div class="flex flex-col grow ms-2">
                <span class="text-base font-semibold text-mono">Agregar usuario</span>
                <span class="text-xs text-secondary-foreground" x-text="addDbName"></span>
            </div>
            <button type="button" class="kt-modal-close" aria-label="Cerrar" data-kt-modal-dismiss="#add_user_modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        <div class="kt-modal-body p-5 overflow-y-auto">
            <form method="POST" :action="'/client/databases/' + addDbId + '/users'">
                @csrf
                <div class="grid gap-4 mb-5">
                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-mono">Nombre de usuario</label>
                        <div class="kt-input-group">
                            <span class="kt-input-addon text-xs">{{ $dbPrefix }}</span>
                            <input class="kt-input" type="text" name="username_suffix"
                                required pattern="[A-Za-z0-9_]+" maxlength="{{ $suffixMaxLength }}"
                                placeholder="nuevo_usuario" autocomplete="off">
                        </div>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-mono">Contraseña</label>
                        <div class="flex items-center rounded-xl border border-input bg-background">
                            <input id="add_user_pass" class="kt-input border-0" type="password" name="password"
                                required minlength="8" maxlength="128" placeholder="Min. 8 caracteres"
                                autocomplete="new-password">
                            <button class="kt-btn kt-btn-icon kt-btn-ghost me-1" type="button"
                                onclick="const i=document.getElementById('add_user_pass');i.type=i.type==='password'?'text':'password'">
                                <i class="ki-filled ki-eye"></i>
                            </button>
                        </div>
                        <p class="text-xs text-secondary-foreground">El usuario tendrá todos los privilegios sobre esta base.</p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end border-t border-border pt-4">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="#add_user_modal">Cancelar</button>
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check"></i>
                        Crear usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>{{-- /x-data --}}
@endsection
