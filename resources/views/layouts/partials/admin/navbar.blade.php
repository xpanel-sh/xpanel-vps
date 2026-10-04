@php
    $navSections = [
        'dashboard' => [
            'match' => ['admin.dashboard'],
            'title' => 'Inicio',
            'items' => [
                ['label' => 'Resumen', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard']],
                ['label' => 'Clientes', 'route' => 'admin.clients.index', 'match' => ['admin.clients.*']],
                ['label' => 'Sitios', 'route' => 'admin.sites.index', 'match' => ['admin.sites.*']],
                ['label' => 'Operaciones', 'route' => 'admin.daemon.operations', 'match' => ['admin.daemon.*']],
            ],
            'actions' => [
                ['label' => 'Nuevo cliente', 'route' => 'admin.clients.create', 'icon' => 'ki-plus'],
            ],
        ],
        'clients' => [
            'match' => ['admin.clients.*'],
            'title' => 'Clientes',
            'items' => [
                ['label' => 'Portafolio', 'route' => 'admin.clients.index', 'match' => ['admin.clients.index']],
                [
                    'label' => 'Gestion',
                    'match' => ['admin.clients.create', 'admin.clients.edit', 'admin.clients.show'],
                    'children' => [
                        ['label' => 'Nuevo cliente', 'route' => 'admin.clients.create', 'match' => ['admin.clients.create']],
                        ['label' => 'Listado', 'route' => 'admin.clients.index', 'match' => ['admin.clients.index']],
                        ['label' => 'Roles y permisos', 'disabled' => true, 'match' => []],
                    ],
                ],
                [
                    'label' => 'Hosting',
                    'match' => ['admin.plans.*', 'admin.sites.*'],
                    'children' => [
                        ['label' => 'Planes', 'route' => 'admin.plans.index', 'match' => ['admin.plans.*']],
                        ['label' => 'Sitios', 'route' => 'admin.sites.index', 'match' => ['admin.sites.*']],
                    ],
                ],
            ],
            'actions' => [
                ['label' => 'Crear', 'route' => 'admin.clients.create', 'icon' => 'ki-plus'],
            ],
        ],
        'plans' => [
            'match' => ['admin.plans.*', 'admin.orders.*'],
            'title' => 'Planes',
            'items' => [
                ['label' => 'Paquetes', 'route' => 'admin.plans.index', 'match' => ['admin.plans.index']],
                ['label' => 'Contrataciones', 'route' => 'admin.orders.index', 'match' => ['admin.orders.*']],
                [
                    'label' => 'Gestion',
                    'match' => ['admin.plans.create', 'admin.plans.edit'],
                    'children' => [
                        ['label' => 'Nuevo plan', 'route' => 'admin.plans.create', 'match' => ['admin.plans.create']],
                        ['label' => 'Listado', 'route' => 'admin.plans.index', 'match' => ['admin.plans.index']],
                    ],
                ],
                [
                    'label' => 'Uso',
                    'match' => ['admin.clients.*'],
                    'children' => [
                        ['label' => 'Clientes', 'route' => 'admin.clients.index', 'match' => ['admin.clients.*']],
                        ['label' => 'Ingresos por plan', 'disabled' => true, 'match' => []],
                    ],
                ],
            ],
            'actions' => [
                ['label' => 'Crear', 'route' => 'admin.plans.create', 'icon' => 'ki-plus'],
            ],
        ],
        'servers' => [
            'match' => ['admin.servers.*'],
            'title' => 'Servidores',
            'items' => [
                ['label' => 'Nodos', 'route' => 'admin.servers.index', 'match' => ['admin.servers.index']],
                [
                    'label' => 'Gestion',
                    'match' => ['admin.servers.create'],
                    'children' => [
                        ['label' => 'Nuevo nodo', 'route' => 'admin.servers.create', 'match' => ['admin.servers.create']],
                        ['label' => 'Listado', 'route' => 'admin.servers.index', 'match' => ['admin.servers.index']],
                    ],
                ],
                [
                    'label' => 'Operaciones',
                    'match' => ['admin.daemon.*'],
                    'children' => [
                        ['label' => 'Operaciones', 'route' => 'admin.daemon.operations', 'match' => ['admin.daemon.*']],
                        ['label' => 'Health checks', 'disabled' => true, 'match' => []],
                    ],
                ],
                [
                    'label' => 'Alta disponibilidad',
                    'match' => [],
                    'children' => [
                        ['label' => 'Replicas', 'disabled' => true, 'match' => []],
                        ['label' => 'Failover IP', 'disabled' => true, 'match' => []],
                    ],
                ],
            ],
            'actions' => [
                ['label' => 'Agregar nodo', 'route' => 'admin.servers.create', 'icon' => 'ki-plus'],
            ],
        ],
        'sites' => [
            'match' => ['admin.sites.*', 'admin.files.*'],
            'title' => 'Sitios',
            'items' => [
                ['label' => 'Todos los sitios', 'route' => 'admin.sites.index', 'match' => ['admin.sites.*']],
                [
                    'label' => 'Herramientas',
                    'match' => ['admin.files.*'],
                    'children' => [
                        ['label' => 'Administrador de archivos', 'route' => 'admin.files.index', 'match' => ['admin.files.*']],
                        ['label' => 'Backups', 'disabled' => true, 'match' => []],
                        ['label' => 'Migraciones', 'disabled' => true, 'match' => []],
                    ],
                ],
                [
                    'label' => 'Sitio',
                    'match' => [],
                    'children' => [
                        ['label' => 'SSL', 'disabled' => true, 'match' => []],
                        ['label' => 'Logs', 'disabled' => true, 'match' => []],
                        ['label' => 'Recursos', 'disabled' => true, 'match' => []],
                    ],
                ],
            ],
        ],
        'dns' => [
            'match' => ['admin.domains.*', 'admin.dns.*'],
            'title' => 'Dominios',
            'items' => [
                ['label' => 'Portafolio', 'route' => 'admin.domains.index', 'match' => ['admin.domains.*']],
                [
                    'label' => 'DNS',
                    'match' => ['admin.dns.*'],
                    'children' => [
                        ['label' => 'Nameservers', 'route' => 'admin.dns.nameservers', 'match' => ['admin.dns.*']],
                        ['label' => 'Zonas DNS', 'disabled' => true, 'match' => []],
                    ],
                ],
                ['label' => 'Transferencias', 'disabled' => true, 'match' => []],
            ],
        ],
        'daemon' => [
            'match' => ['admin.daemon.*'],
            'title' => 'Operaciones',
            'items' => [
                ['label' => 'Operaciones', 'route' => 'admin.daemon.operations', 'match' => ['admin.daemon.*']],
                ['label' => 'Servidores', 'route' => 'admin.servers.index', 'match' => ['admin.servers.*']],
                ['label' => 'Runtime', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard']],
            ],
        ],
        'settings' => [
            'match' => ['admin.settings.*', 'admin.pages.*'],
            'title' => 'Configuracion',
            'items' => [
                ['label' => 'General', 'route' => 'admin.settings.index', 'match' => ['admin.settings.*']],
                ['label' => 'Paginas publicas', 'route' => 'admin.pages.index', 'match' => ['admin.pages.*']],
                ['label' => 'DNS', 'route' => 'admin.dns.nameservers', 'match' => ['admin.dns.*']],
                ['label' => 'Branding', 'disabled' => true, 'match' => []],
            ],
        ],
    ];

    $section = collect($navSections)->first(fn ($section) => collect($section['match'])->contains(fn ($pattern) => request()->routeIs($pattern)))
        ?? $navSections['dashboard'];

    $itemClass = 'kt-menu-link gap-2.5 h-full border-b-2 border-b-transparent';
    $itemActiveClass = 'border-b-mono text-mono font-medium';
    $itemIdleClass = 'text-foreground hover:text-mono';

    $isNavActive = fn (array $patterns) => collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern));
@endphp

<!-- Navbar -->
<div class="flex items-stretch lg:fixed z-5 top-(--header-height) start-(--sidebar-width) end-5 h-(--navbar-height) mx-5 lg:mx-0 bg-muted" id="navbar">
    <div class="rounded-t-xl border border-input border-b-input bg-background flex items-stretch grow">
        <div class="kt-container-fluid flex justify-between items-stretch gap-5">
            <div class="grid items-stretch min-w-0">
                <div class="kt-scrollable-x-auto flex items-stretch">
                    <div class="kt-menu gap-5 lg:gap-7.5" data-kt-menu="true">
                         
                        <div class="flex items-center pe-2">
                            <span class="text-sm font-semibold text-mono text-nowrap">{{ $section['title'] }}</span>
                        </div>

                        @foreach($section['items'] as $item)
                            @php
                                $active = $isNavActive($item['match'] ?? []);
                                $disabled = $item['disabled'] ?? false;
                            @endphp

                            @if(isset($item['children']))
                                <div class="kt-menu-item border-b-2 border-b-transparent {{ $active ? 'here' : '' }}"
                                     data-kt-menu-item-placement="bottom-start"
                                     data-kt-menu-item-placement-rtl="bottom-end"
                                     data-kt-menu-item-toggle="dropdown"
                                     data-kt-menu-item-trigger="click|lg:hover">
                                    <button class="{{ $itemClass }} {{ $active ? $itemActiveClass : $itemIdleClass }}" type="button">
                                        <span class="kt-menu-title text-nowrap text-sm">
                                            {{ $item['label'] }}
                                        </span>
                                        <span class="kt-menu-arrow">
                                            <i class="ki-filled ki-down text-xs text-muted-foreground"></i>
                                        </span>
                                    </button>

                                    <div class="kt-menu-dropdown kt-menu-default py-2 min-w-[210px]">
                                        @foreach($item['children'] as $child)
                                            @php
                                                $childActive = $isNavActive($child['match'] ?? []);
                                                $childDisabled = $child['disabled'] ?? false;
                                            @endphp

                                            <div class="kt-menu-item {{ $childActive ? 'active' : '' }}">
                                                @if($childDisabled)
                                                    <button class="kt-menu-link opacity-50 cursor-not-allowed" type="button" aria-disabled="true">
                                                        <span class="kt-menu-title">
                                                            {{ $child['label'] }}
                                                        </span>
                                                    </button>
                                                @else
                                                    <a class="kt-menu-link" href="{{ route($child['route']) }}" tabindex="0">
                                                        <span class="kt-menu-title">
                                                            {{ $child['label'] }}
                                                        </span>
                                                        @if($childActive)
                                                            <span class="kt-menu-icon ms-auto">
                                                                <i class="ki-filled ki-check text-primary"></i>
                                                            </span>
                                                        @endif
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="kt-menu-item border-b-2 border-b-transparent {{ $active ? 'here' : '' }}">
                                    @if($disabled)
                                        <button class="{{ $itemClass }} opacity-50 cursor-not-allowed text-foreground" type="button" aria-disabled="true">
                                            <span class="kt-menu-title text-nowrap text-sm">
                                                {{ $item['label'] }}
                                            </span>
                                        </button>
                                    @else
                                        <a class="{{ $itemClass }} {{ $active ? $itemActiveClass : $itemIdleClass }}"
                                           href="{{ route($item['route']) }}"
                                           tabindex="0">
                                            <span class="kt-menu-title text-nowrap text-sm">
                                                {{ $item['label'] }}
                                            </span>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @yield('navbar_actions')

                @foreach($section['actions'] ?? [] as $action)
                    <a class="kt-btn kt-btn-primary flex-nowrap" href="{{ route($action['route']) }}">
                        <i class="ki-filled {{ $action['icon'] }}"></i>
                        <span class="hidden sm:inline text-nowrap">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
<!-- End of Navbar -->
