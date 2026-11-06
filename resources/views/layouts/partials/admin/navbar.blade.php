@php
    // Cada barra pertenece a un único módulo. La navegación entre módulos vive
    // en el menú lateral para evitar pestañas que saquen al usuario de contexto.
    $navSections = [
        'dashboard' => [
            'match' => ['admin.dashboard'], 'title' => 'Inicio',
            'items' => [['label' => 'Resumen', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard']]],
            'actions' => [['label' => 'Nuevo cliente', 'route' => 'admin.clients.create', 'icon' => 'ki-plus']],
        ],
        'clients' => [
            'match' => ['admin.clients.*'], 'title' => 'Clientes',
            'items' => [
                ['label' => 'Directorio', 'route' => 'admin.clients.index', 'match' => ['admin.clients.index', 'admin.clients.show', 'admin.clients.edit']],
                ['label' => 'Nuevo cliente', 'route' => 'admin.clients.create', 'match' => ['admin.clients.create']],
            ],
        ],
        'hostings' => [
            'match' => ['admin.instances.*'], 'title' => 'Hostings',
            'items' => [['label' => 'Instancias', 'route' => 'admin.instances.index', 'match' => ['admin.instances.*']]],
        ],
        'domains' => [
            'match' => ['admin.domains.*', 'admin.sites.*', 'admin.dns.*'], 'title' => 'Dominios',
            'items' => [
                ['label' => 'Inventario', 'route' => 'admin.domains.index', 'match' => ['admin.domains.*', 'admin.sites.*']],
                ['label' => 'Nameservers', 'route' => 'admin.dns.nameservers', 'match' => ['admin.dns.*']],
            ],
        ],
        'plans' => [
            'match' => ['admin.plans.*'], 'title' => 'Planes',
            'items' => [
                ['label' => 'Planes', 'route' => 'admin.plans.index', 'match' => ['admin.plans.index', 'admin.plans.edit']],
                ['label' => 'Nuevo plan', 'route' => 'admin.plans.create', 'match' => ['admin.plans.create']],
            ],
        ],
        'payments' => [
            'match' => ['admin.orders.*'], 'title' => 'Pagos',
            'items' => [['label' => 'Historial', 'route' => 'admin.orders.index', 'match' => ['admin.orders.*']]],
        ],
        'pages' => [
            'match' => ['admin.pages.*'], 'title' => 'Páginas públicas',
            'items' => [['label' => 'Contenido', 'route' => 'admin.pages.index', 'match' => ['admin.pages.*']]],
        ],
        'servers' => [
            'match' => ['admin.servers.*'], 'title' => 'Servidores',
            'items' => [
                ['label' => 'Nodos', 'route' => 'admin.servers.index', 'match' => ['admin.servers.index']],
                ['label' => 'Agregar nodo', 'route' => 'admin.servers.create', 'match' => ['admin.servers.create']],
            ],
        ],
        'software' => [
            'match' => ['admin.software-packages.*'], 'title' => 'Software',
            'items' => [['label' => 'Paquetes', 'route' => 'admin.software-packages.index', 'match' => ['admin.software-packages.*']]],
        ],
        'files' => [
            'match' => ['admin.files.*'], 'title' => 'Archivos',
            'items' => [['label' => 'Administrador', 'route' => 'admin.files.index', 'match' => ['admin.files.*']]],
        ],
        'settings' => [
            'match' => ['admin.settings.*', 'admin.daemon.*'], 'title' => 'Ajustes',
            'items' => [
                ['label' => 'General', 'route' => 'admin.settings.index', 'match' => ['admin.settings.index']],
                ['label' => 'Operaciones', 'route' => 'admin.settings.operations', 'match' => ['admin.settings.operations', 'admin.daemon.*']],
            ],
        ],
    ];

    $section = collect($navSections)->first(fn ($candidate) => collect($candidate['match'])->contains(fn ($pattern) => request()->routeIs($pattern)))
        ?? $navSections['dashboard'];
    $isNavActive = fn (array $patterns) => collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern));
@endphp

<div class="flex items-stretch lg:fixed z-5 top-(--header-height) start-(--sidebar-width) end-5 h-(--navbar-height) mx-5 lg:mx-0 bg-muted" id="navbar">
    <div class="rounded-t-xl border border-input border-b-input bg-background flex items-stretch grow">
        <div class="kt-container-fluid flex justify-between items-stretch gap-5">
            <div class="grid min-w-0 items-stretch">
                <div class="kt-scrollable-x-auto flex items-stretch">
                    <div class="kt-menu gap-5 lg:gap-7.5" data-kt-menu="true">
                        <div class="flex items-center pe-2"><span class="text-sm font-semibold text-mono text-nowrap">{{ $section['title'] }}</span></div>
                        @foreach($section['items'] as $item)
                            @php($active = $isNavActive($item['match']))
                            <div class="kt-menu-item border-b-2 border-b-transparent {{ $active ? 'here' : '' }}">
                                <a class="kt-menu-link gap-2.5 h-full border-b-2 {{ $active ? 'border-b-mono text-mono font-medium' : 'border-b-transparent text-foreground hover:text-mono' }}" href="{{ route($item['route']) }}">
                                    <span class="kt-menu-title text-nowrap text-sm">{{ $item['label'] }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @yield('navbar_actions')
                @foreach($section['actions'] ?? [] as $action)
                    <a class="kt-btn kt-btn-primary flex-nowrap" href="{{ route($action['route']) }}"><i class="ki-filled {{ $action['icon'] }}"></i><span class="hidden sm:inline text-nowrap">{{ $action['label'] }}</span></a>
                @endforeach
            </div>
        </div>
    </div>
</div>
