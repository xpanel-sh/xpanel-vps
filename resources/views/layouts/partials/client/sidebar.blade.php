                @php
                    $secondaryDomain = $selectedSiteDomain ?? null;
                    $activeSection = request()->route('section');
                    $activePage = trim((string) request()->route('page'), '/');
                    $activePath = request()->routeIs('client.websites.module')
                        ? trim((string) $activeSection . '/' . $activePage, '/')
                        : null;

                    $siteModuleUrl = function (?string $path = null) use ($secondaryDomain) {
                        if (!$secondaryDomain) {
                            return null;
                        }

                        if (!$path) {
                            return route('client.websites.show', ['domain' => $secondaryDomain]);
                        }

                        $parts = explode('/', $path, 2);

                        return route('client.websites.module', [
                            'domain' => $secondaryDomain,
                            'section' => $parts[0],
                            'page' => $parts[1] ?? null,
                        ]);
                    };

                    $secondaryMenu = [
                        [
                            'label' => 'Panel',
                            'icon' => 'ki-element-11',
                            'url' => $siteModuleUrl(),
                            'active' => request()->routeIs('client.websites.show'),
                        ],
                        [
                            'label' => 'Plan de hosting',
                            'icon' => 'ki-external-drive',
                            'children' => [
                                ['label' => 'Detalles del plan', 'path' => 'order/details'],
                                ['label' => 'Uso de recursos', 'path' => 'order/order-usage'],
                                ['label' => 'Renovar', 'disabled' => true],
                                ['label' => 'Mejorar', 'path' => 'order/upgrade'],
                            ],
                        ],
                        [
                            'label' => 'Rendimiento',
                            'icon' => 'ki-chart-line-up',
                            'children' => [
                                ['label' => 'Solucionador con IA', 'path' => 'performance/ai-troubleshooter'],
                                ['label' => 'Page Speed', 'path' => 'performance/page-speed'],
                                ['label' => 'CDN', 'path' => 'performance/cdn'],
                            ],
                        ],
                        [
                            'label' => 'Analisis',
                            'icon' => 'ki-chart-simple',
                            'path' => 'analytics',
                        ],
                        [
                            'label' => 'Seguridad',
                            'icon' => 'ki-shield-tick',
                            'children' => [
                                ['label' => 'Escaner de malware', 'path' => 'hosting-security/malware-scanner'],
                                ['label' => 'SSL', 'path' => 'hosting-security/ssl'],
                            ],
                        ],
                        [
                            'label' => 'Dominios',
                            'icon' => 'ki-click',
                            'children' => [
                                ['label' => 'Subdominios', 'path' => 'domains/subdomains'],
                                ['label' => 'Dominios aparcados', 'path' => 'domains/parked-domains'],
                                ['label' => 'Redirecciones', 'path' => 'domains/redirects'],
                            ],
                        ],
                        [
                            'label' => 'Sitio web',
                            'icon' => 'ki-screen',
                            'children' => [
                                ['label' => 'Instalar WordPress', 'path' => 'website/wordpress'],
                                ['label' => 'Instalador automatico', 'path' => 'website/auto-installer'],
                                ['label' => 'Migrar sitio web', 'path' => 'website/migration'],
                                ['label' => 'Paginas de error', 'path' => 'website/error-pages'],
                                ['label' => 'Creador de sitios', 'path' => 'website/builder'],
                            ],
                        ],
                        [
                            'label' => 'Archivos',
                            'icon' => 'ki-folder',
                            'children' => [
                                [
                                    'label' => 'Gestor de archivos',
                                    'path' => 'files/file-manager',
                                    'url' => $secondaryDomain ? route('client.website.file-manager.entry', ['domain' => $secondaryDomain]) : null,
                                    'active' => request()->routeIs('client.files.*', 'client.website.file-manager.*'),
                                ],
                                ['label' => 'Backups', 'path' => 'files/backups'],
                                ['label' => 'Cuentas FTP', 'path' => 'files/ftp-accounts'],
                            ],
                        ],
                        [
                            'label' => 'Bases de datos',
                            'icon' => 'ki-data',
                            'children' => [
                                ['label' => 'Administracion', 'path' => 'databases/my-sql-databases'],
                                ['label' => 'phpMyAdmin', 'path' => 'databases/php-my-admin'],
                                ['label' => 'MySQL remoto', 'path' => 'databases/remote-my-sql'],
                            ],
                        ],
                        [
                            'label' => 'Avanzado',
                            'icon' => 'ki-setting-2',
                            'children' => [
                                ['label' => 'Acceso SSH', 'path' => 'advanced/ssh-access'],
                                ['label' => 'Configuracion PHP', 'path' => 'advanced/php-configuration'],
                                [
                                    'label' => 'Editor DNS',
                                    'url'    => $secondaryDomain ? route('client.websites.dns-zone-editor', $secondaryDomain) : null,
                                    'active' => request()->routeIs('client.websites.dns-zone-editor*'),
                                ],
                                ['label' => 'Cron Jobs', 'path' => 'advanced/cron-jobs'],
                                ['label' => 'PHP info', 'path' => 'advanced/php-info'],
                                ['label' => 'Administrador de cache', 'path' => 'advanced/cache-manager'],
                                ['label' => 'Git', 'path' => 'advanced/git'],
                                ['label' => 'Directorios protegidos', 'path' => 'advanced/password-protect-directories'],
                                ['label' => 'Administrador de IP', 'path' => 'advanced/ip-manager'],
                                ['label' => 'Proteccion Hotlink', 'path' => 'advanced/hotlink-protection'],
                                ['label' => 'Indice de carpetas', 'path' => 'advanced/folder-index-manager'],
                                ['label' => 'Corregir propietarios', 'path' => 'advanced/fix-file-ownership'],
                                ['label' => 'Registro de actividad', 'path' => 'advanced/activity-log'],
                                
                            ],
                        ],
                    ];

                    $isMenuPathActive = fn (?string $path) => $path && $activePath === $path;
                    $childUrl = fn (array $child) => $child['url'] ?? $siteModuleUrl($child['path'] ?? null);
                    $childIsDisabled = fn (array $child) => ($child['disabled'] ?? false) || (!$secondaryDomain && !isset($child['url']));
                @endphp
  
    <!-- Sidebar -->
            <div class="fixed top-0 bottom-0 z-20 hidden lg:flex items-stretch shrink-0 w-(--sidebar-width) bg-muted [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
                data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start flex flex-row top-0 bottom-0"
                id="sidebar">
                <!-- Sidebar Primary -->
                <div class="flex flex-col items-stretch shrink-0 gap-5 py-5 w-[90px] @if($secondaryDomain) border-e border-input @endIf"
                    id="sidebar_primary">
                    <div class="hidden lg:flex items-center justify-center shrink-0" id="sidebar_primary_header">
                        <a href="{{ route('client.dashboard') }}">
                            <img class="dark:hidden min-h-[30px]"
                                src="{{ asset('assets/media/app/mini-logo-gray.svg') }}" />
                            <img class="hidden dark:block min-h-[30px]"
                                src="{{ asset('assets/media/app/mini-logo-gray-dark.svg') }}" />
                        </a>
                    </div>
                    <div class="flex grow shrink-0" id="sidebar_primary_content">
                        <div class="kt-scrollable-y-hover grow gap-2.5 shrink-0 flex ps-3 flex-col"
                            data-kt-scrollable="true"
                            data-kt-scrollable-dependencies="#sidebar_primary_header,#sidebar_primary_footer"
                            data-kt-scrollable-height="auto" data-kt-scrollable-offset="80px"
                            data-kt-scrollable-wrappers="#sidebar_primary_content">
                            <div class="kt-menu-item {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                                <a class="kt-menu-link rounded-[9px] border border-transparent kt-menu-item-active:border-border kt-menu-item-active:bg-background kt-menu-link-hover:bg-background kt-menu-link-hover:border-border w-[62px] h-[60px] flex flex-col justify-center items-center gap-1 p-2"
                                    href="{{ route('client.dashboard') }}">
                                    <span
                                        class="kt-menu-icon kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground">
                                        <i class="ki-filled ki-chart-line-star text-xl">
                                        </i>
                                    </span>
                                    <span
                                        class="kt-menu-title text-xs kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground font-medium">
                                        Inicio
                                    </span>
                                </a>
                            </div>
                            <div class="kt-menu-item {{ request()->routeIs('client.plans.*') ? 'active' : '' }}">
                                <a class="kt-menu-link rounded-[9px] border border-transparent kt-menu-item-active:border-border kt-menu-item-active:bg-background kt-menu-link-hover:bg-background kt-menu-link-hover:border-border w-[62px] h-[60px] flex flex-col justify-center items-center gap-1 p-2" href="{{ route('client.plans.index') }}">
                                    <span class="kt-menu-icon kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground"><i class="ki-filled ki-handcart text-xl"></i></span>
                                    <span class="kt-menu-title text-xs kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground font-medium">Planes</span>
                                </a>
                            </div>
                            <div class="kt-menu-item {{ request()->routeIs('client.websites.*', 'client.sites.*', 'client.files.*') ? 'active' : '' }}">
                                <a class="kt-menu-link rounded-[9px] border border-transparent kt-menu-item-active:border-border kt-menu-item-active:bg-background kt-menu-link-hover:bg-background kt-menu-link-hover:border-border w-[62px] h-[60px] flex flex-col justify-center items-center gap-1 p-2"
                                    href="{{ route('client.websites.index') }}">
                                    <span
                                        class="kt-menu-icon kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground">
                                        <i class="ki-filled ki-screen text-xl">
                                        </i>
                                    </span>
                                    <span
                                        class="kt-menu-title text-xs kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground font-medium">
                                        Websites
                                    </span>
                                </a>
                            </div>
                            <div class="kt-menu-item {{ request()->routeIs('client.domains.*', 'client.dns.*') ? 'active' : '' }}">
                                <a class="kt-menu-link rounded-[9px] border border-transparent kt-menu-item-active:border-border kt-menu-item-active:bg-background kt-menu-link-hover:bg-background kt-menu-link-hover:border-border w-[62px] h-[60px] flex flex-col justify-center items-center gap-1 p-2"
                                    href="{{ route('client.domains.index') }}">
                                    <span
                                        class="kt-menu-icon kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground">
                                        <i class="ki-filled ki-click text-xl">
                                        </i>
                                    </span>
                                    <span
                                        class="kt-menu-title text-xs kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground font-medium">
                                        Dominios
                                    </span>
                                </a>
                            </div>
                            <div class="kt-menu-item {{ request()->routeIs('client.mail.*') ? 'active' : '' }}">
                                <a class="kt-menu-link rounded-[9px] border border-transparent kt-menu-item-active:border-border kt-menu-item-active:bg-background kt-menu-link-hover:bg-background kt-menu-link-hover:border-border w-[62px] h-[60px] flex flex-col justify-center items-center gap-1 p-2"
                                    href="{{ route('client.mail.index') }}">
                                    <span
                                        class="kt-menu-icon kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground">
                                        <i class="ki-filled ki-sms text-xl">
                                        </i>
                                    </span>
                                    <span
                                        class="kt-menu-title text-xs kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground font-medium">
                                        Correos
                                    </span>
                                </a>
                            </div>
                            <div class="kt-menu-item {{ request()->routeIs('client.builder.*') ? 'active' : '' }}">
                                <a class="kt-menu-link rounded-[9px] border border-transparent kt-menu-item-active:border-border kt-menu-item-active:bg-background kt-menu-link-hover:bg-background kt-menu-link-hover:border-border w-[62px] h-[60px] flex flex-col justify-center items-center gap-1 p-2"
                                    href="{{ route('client.builder.index') }}">
                                    <span
                                        class="kt-menu-icon kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground">
                                        <i class="ki-filled ki-color-swatch text-xl">
                                        </i>
                                    </span>
                                    <span
                                        class="kt-menu-title text-xs kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground font-medium">
                                        Builder
                                    </span>
                                </a>
                            </div>
                            @if(config('xpanel.docker.enabled', false))
                            <div class="kt-menu-item {{ request()->routeIs('client.docker.*') ? 'active' : '' }}">
                                <a class="kt-menu-link rounded-[9px] border border-transparent kt-menu-item-active:border-border kt-menu-item-active:bg-background kt-menu-link-hover:bg-background kt-menu-link-hover:border-border w-[62px] h-[60px] flex flex-col justify-center items-center gap-1 p-2"
                                    href="{{ route('client.docker.index') }}">
                                    <span
                                        class="kt-menu-icon kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground">
                                        <i class="ki-filled ki-cube-3 text-xl">
                                        </i>
                                    </span>
                                    <span
                                        class="kt-menu-title text-xs kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground font-medium">
                                        Apps
                                    </span>
                                </a>
                            </div>
                            @endif
                            <div class="kt-menu-item {{ request()->routeIs('client.vps.*') ? 'active' : '' }}">
                                <a class="kt-menu-link rounded-[9px] border border-transparent kt-menu-item-active:border-border kt-menu-item-active:bg-background kt-menu-link-hover:bg-background kt-menu-link-hover:border-border w-[62px] h-[60px] flex flex-col justify-center items-center gap-1 p-2"
                                    href="{{ route('client.vps.index') }}">
                                    <span
                                        class="kt-menu-icon kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground">
                                        <i class="ki-filled ki-external-drive text-xl">
                                        </i>
                                    </span>
                                    <span
                                        class="kt-menu-title text-xs kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground font-medium">
                                        VPS
                                    </span>
                                </a>
                            </div>

                        </div>
                    </div>
                    <div class="flex flex-col gap-5 items-center shrink-0" id="sidebar_primary_footer">
                        <div class="flex flex-col gap-1.5">
                            <!-- Chat -->
                            <button class="kt-btn kt-btn-ghost kt-btn-icon size-9 hover:bg-background hover:[&amp;_i]:text-primary">
                                <i class="ki-filled ki-grid text-lg"></i>
                            </button>

                            <!-- End of Chat -->
                            <!-- Apps -->
                            <div data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px"
                                data-kt-dropdown-offset-rtl="-10px, 10px" data-kt-dropdown-placement="bottom-start"
                                data-kt-dropdown-placement-rtl="bottom-end">
                                <button
                                    class="kt-btn kt-btn-ghost kt-btn-icon size-9 hover:bg-background hover:[&amp;_i]:text-primary kt-dropdown-open:bg-background kt-dropdown-open:[&amp;_i]:text-primary"
                                    data-kt-dropdown-toggle="true">
                                    <i class="ki-filled ki-element-11 text-lg">
                                    </i>
                                </button>
                                <div class="kt-dropdown-menu p-0 w-screen max-w-[320px]"
                                    data-kt-dropdown-menu="true">
                                    <div
                                        class="flex items-center justify-between gap-2.5 text-xs text-secondary-foreground font-medium px-5 py-3 border-b border-b-border">
                                        <span>
                                            Apps
                                        </span>
                                        <span>
                                            Enabled
                                        </span>
                                    </div>
                                    <div
                                        class="flex flex-col kt-scrollable-y-auto max-h-[400px] divide-y divide-border">
                                        <div class="flex items-center justify-between flex-wrap gap-2 px-5 py-3.5">
                                            <div class="flex items-center flex-wrap gap-2">
                                                <div
                                                    class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-border size-10">
                                                    <img alt="" class="size-6"
                                                        src="/metronic/tailwind/dist/assets/media/brand-logos/jira.svg">
                                                    </img>
                                                </div>
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-semibold text-mono hover:text-primary"
                                                        href="#">
                                                        Jira
                                                    </a>
                                                    <span class="text-xs font-medium text-secondary-foreground">
                                                        Project management
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 lg:gap-5">
                                                <input class="kt-switch" type="checkbox" value="1" />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between flex-wrap gap-2 px-5 py-3.5">
                                            <div class="flex items-center flex-wrap gap-2">
                                                <div
                                                    class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-border size-10">
                                                    <img alt="" class="size-6"
                                                        src="/metronic/tailwind/dist/assets/media/brand-logos/inferno.svg">
                                                    </img>
                                                </div>
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-semibold text-mono hover:text-primary"
                                                        href="#">
                                                        Inferno
                                                    </a>
                                                    <span class="text-xs font-medium text-secondary-foreground">
                                                        Ensures healthcare app
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 lg:gap-5">
                                                <input checked="" class="kt-switch" type="checkbox"
                                                    value="1" />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between flex-wrap gap-2 px-5 py-3.5">
                                            <div class="flex items-center flex-wrap gap-2">
                                                <div
                                                    class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-border size-10">
                                                    <img alt="" class="size-6"
                                                        src="/metronic/tailwind/dist/assets/media/brand-logos/evernote.svg">
                                                    </img>
                                                </div>
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-semibold text-mono hover:text-primary"
                                                        href="#">
                                                        Evernote
                                                    </a>
                                                    <span class="text-xs font-medium text-secondary-foreground">
                                                        Notes management app
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 lg:gap-5">
                                                <input checked="" class="kt-switch" type="checkbox"
                                                    value="1" />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between flex-wrap gap-2 px-5 py-3.5">
                                            <div class="flex items-center flex-wrap gap-2">
                                                <div
                                                    class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-border size-10">
                                                    <img alt="" class="size-6"
                                                        src="/metronic/tailwind/dist/assets/media/brand-logos/gitlab.svg" />
                                                </div>
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-semibold text-mono hover:text-primary"
                                                        href="#">
                                                        Gitlab
                                                    </a>
                                                    <span class="text-xs font-medium text-secondary-foreground">
                                                        DevOps platform
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 lg:gap-5">
                                                <input class="kt-switch" type="checkbox" value="1" />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between flex-wrap gap-2 px-5 py-3.5">
                                            <div class="flex items-center flex-wrap gap-2">
                                                <div
                                                    class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-border size-10">
                                                    <img alt="" class="size-6"
                                                        src="/metronic/tailwind/dist/assets/media/brand-logos/google-webdev.svg" />
                                                </div>
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-semibold text-mono hover:text-primary"
                                                        href="#">
                                                        Google webdev
                                                    </a>
                                                    <span class="text-xs font-medium text-secondary-foreground">
                                                        Building web expierences
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 lg:gap-5">
                                                <input checked="" class="kt-switch" type="checkbox"
                                                    value="1" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid p-5 border-t border-t-border">
                                        <a class="kt-btn kt-btn-outline justify-center"
                                            href="/metronic/tailwind/demo4/account/integrations">
                                            Go to Apps
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- End of Apps -->
                        </div>

                    </div>
                </div>
                <!-- End of Sidebar Primary -->
                <!-- Sidebar Secondary -->

                @if($secondaryDomain)
                <div class="flex items-stretch grow shrink-0 justify-center ps-1.5 my-5 me-1.5"
                    id="sidebar_secondary" style="margin-top: 3.5rem; padding-top: 5px;">
                    <div class="kt-scrollable-y-auto grow" data-kt-scrollable="true"
                        data-kt-scrollable-height="auto" data-kt-scrollable-offset="0px"
                        data-kt-scrollable-wrappers="#sidebar_secondary">
                        <div class="flex flex-col gap-2 max-w-[180px]">

                            <div class="px-2 pb-2 hidden" >
                                <div class="flex">
                                    <label class="kt-input">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input data-kt-datatable-search="#team_crew_table" placeholder="Search users" type="text" value="">
                                    </label>
                                </div>
                            </div>

                            <div class="kt-menu flex flex-col grow gap-1" data-kt-menu="true"
                                data-kt-menu-accordion-expand-all="false" id="sidebar_menu">
                                @foreach($secondaryMenu as $item)
                                    @php
                                        $children = $item['children'] ?? [];
                                        $hasChildren = count($children) > 0;
                                        $itemUrl = $item['url'] ?? $siteModuleUrl($item['path'] ?? null);
                                        $itemDisabled = ($item['disabled'] ?? false) || (!$secondaryDomain && !$itemUrl);
                                        $itemActive = ($item['active'] ?? false) || $isMenuPathActive($item['path'] ?? null);

                                        foreach ($children as $child) {
                                            $itemActive = $itemActive || ($child['active'] ?? false) || $isMenuPathActive($child['path'] ?? null);
                                        }
                                    @endphp

                                    @if($hasChildren)
                                        <div class="kt-menu-item {{ $itemActive ? 'here show' : '' }} kt-menu-item-accordion"
                                            data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                                            <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] {{ $itemDisabled ? 'opacity-50 pointer-events-none' : '' }}"
                                                tabindex="0">
                                                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                                    <i class="ki-filled {{ $item['icon'] }} text-lg"></i>
                                                </span>
                                                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                                    {{ $item['label'] }}
                                                </span>
                                                <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                                    <span class="inline-flex kt-menu-item-show:hidden">
                                                        <i class="ki-filled ki-plus text-[11px]"></i>
                                                    </span>
                                                    <span class="hidden kt-menu-item-show:inline-flex">
                                                        <i class="ki-filled ki-minus text-[11px]"></i>
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="kt-menu-accordion gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border">
                                                @foreach($children as $child)
                                                    @php
                                                        $active = ($child['active'] ?? false) || $isMenuPathActive($child['path'] ?? null);
                                                        $disabled = $childIsDisabled($child);
                                                        $url = $childUrl($child);
                                                    @endphp

                                                    <div class="kt-menu-item {{ $active ? 'active' : '' }}">
                                                        @if($disabled)
                                                            <button class="kt-menu-link border border-transparent items-center grow gap-[14px] ps-[10px] pe-[10px] py-[8px] opacity-50 cursor-not-allowed"
                                                                type="button" aria-disabled="true">
                                                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2"></span>
                                                                <span class="kt-menu-title text-2sm font-normal text-secondary-foreground">
                                                                    {{ $child['label'] }}
                                                                </span>
                                                            </button>
                                                        @else
                                                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                                                href="{{ $url }}" tabindex="0">
                                                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary"></span>
                                                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                                                    {{ $child['label'] }}
                                                                </span>
                                                            </a>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="kt-menu-item {{ $itemActive ? 'active' : '' }}">
                                            @if($itemDisabled)
                                                <button class="kt-menu-label border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] opacity-50 cursor-not-allowed"
                                                    type="button" aria-disabled="true">
                                                    <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                                        <i class="ki-filled {{ $item['icon'] }} text-lg"></i>
                                                    </span>
                                                    <span class="kt-menu-title text-sm font-medium text-secondary-foreground">
                                                        {{ $item['label'] }}
                                                    </span>
                                                </button>
                                            @else
                                                <a class="kt-menu-link flex items-center grow border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg"
                                                    href="{{ $itemUrl }}" tabindex="0">
                                                    <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                                        <i class="ki-filled {{ $item['icon'] }} text-lg"></i>
                                                    </span>
                                                    <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                                        {{ $item['label'] }}
                                                    </span>
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <!-- End of Menu -->
                        </div>
                    </div>
                </div>
                <!-- End of Sidebar Secondary-->
                @endif
            </div>
            <!-- End of Sidebar -->
