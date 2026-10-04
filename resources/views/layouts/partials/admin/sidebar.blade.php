@php
    $sideItems = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard'], 'icon' => 'ki-element-11'],
        ['label' => 'Clientes', 'route' => 'admin.clients.index', 'match' => ['admin.clients.*'], 'icon' => 'ki-people'],
        ['label' => 'Instancias Host', 'route' => 'admin.instances.index', 'match' => ['admin.instances.*'], 'icon' => 'ki-abstract-26'],
        ['label' => 'Sitios', 'route' => 'admin.sites.index', 'match' => ['admin.sites.*', 'admin.files.*'], 'icon' => 'ki-screen'],
        ['label' => 'Dominios', 'route' => 'admin.domains.index', 'match' => ['admin.domains.*', 'admin.dns.*'], 'icon' => 'ki-click'],
        ['label' => 'Planes', 'route' => 'admin.plans.index', 'match' => ['admin.plans.*'], 'icon' => 'ki-dollar'],
        ['label' => 'Contrataciones', 'route' => 'admin.orders.index', 'match' => ['admin.orders.*'], 'icon' => 'ki-bill'],
        ['label' => 'Paginas', 'route' => 'admin.pages.index', 'match' => ['admin.pages.*'], 'icon' => 'ki-document'],
        ['label' => 'Servidores', 'route' => 'admin.servers.index', 'match' => ['admin.servers.*'], 'icon' => 'ki-setting-3'],
        ['label' => 'Software', 'route' => 'admin.software-packages.index', 'match' => ['admin.software-packages.*'], 'icon' => 'ki-abstract-26'],
        ...(config('xpanel.docker.enabled', false)
            ? [['label' => 'Docker Apps', 'route' => 'admin.docker.index', 'match' => ['admin.docker.*'], 'icon' => 'ki-cube-3']]
            : []),
        ['label' => 'Backups / HA', 'disabled' => true, 'match' => [], 'icon' => 'ki-shield-tick'],
        ['label' => 'Operaciones', 'route' => 'admin.daemon.operations', 'match' => ['admin.daemon.*'], 'icon' => 'ki-pulse'],
        ['label' => 'Settings', 'route' => 'admin.settings.index', 'match' => ['admin.settings.*'], 'icon' => 'ki-setting-2'],
    ];

    $sideBaseClass = 'kt-btn kt-btn-ghost kt-btn-icon rounded-full size-10 border border-transparent text-secondary-foreground hover:bg-background hover:[&_i]:text-primary hover:border-input';
    $sideActiveClass = 'bg-background [&_i]:text-primary border-input active';
    $sideDisabledClass = 'opacity-45 cursor-not-allowed hover:bg-transparent hover:border-transparent hover:[&_i]:text-secondary-foreground';
@endphp

<!-- Sidebar -->
<div class="fixed w-(--sidebar-width) lg:top-(--header-height) top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 group py-3 lg:py-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
     data-kt-drawer="true"
     data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
     id="sidebar">
    <div class="flex grow shrink-0" id="sidebar_content">
        <div class="kt-scrollable-y-auto grow gap-2.5 shrink-0 flex items-center flex-col max-h-[calc(100dvh-10px)] lg:max-h-[calc(100dvh-70px)] pt-2">
            @foreach($sideItems as $item)
                @php
                    $active = collect($item['match'])->contains(fn ($pattern) => request()->routeIs($pattern));
                @endphp

                @if($item['disabled'] ?? false)
                    <button class="{{ $sideBaseClass }} {{ $sideDisabledClass }}"
                            data-kt-tooltip=""
                            data-kt-tooltip-placement="right"
                            type="button"
                            aria-disabled="true">
                        <span class="kt-menu-icon">
                            <i class="ki-filled {{ $item['icon'] }} text-lg"></i>
                        </span>
                        <span class="kt-tooltip" data-kt-tooltip-content="true">
                            {{ $item['label'] }}
                        </span>
                    </button>
                @else
                    <a class="{{ $sideBaseClass }} {{ $active ? $sideActiveClass : '' }}"
                       data-kt-tooltip=""
                       data-kt-tooltip-placement="right"
                       href="{{ route($item['route']) }}">
                    <span class="kt-menu-icon">
                        <i class="ki-filled {{ $item['icon'] }} text-lg"></i>
                    </span>
                        <span class="kt-tooltip" data-kt-tooltip-content="true">
                            {{ $item['label'] }}
                        </span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>
<!-- End of Sidebar -->
