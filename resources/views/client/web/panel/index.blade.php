@extends('layouts.client')

@php
    $tenant = $site->tenant;
    $plan = $tenant?->plan;
    $planName = $plan?->name ?? 'Cloud Startup Hosting';
    $status = $site->status ?? 'active';
    $statusClass = str_contains($status, 'error')
        ? 'kt-badge-danger'
        : ($status === 'provisioning' || $status === 'creating' ? 'kt-badge-warning' : 'kt-badge-success');

    $limitText = fn ($value) => $value && (int) $value > 0 ? number_format((int) $value) : 'Ilimitado';
    $percent = function ($used, $limit) {
        if (!$limit || !is_numeric($limit) || (float) $limit <= 0) {
            return 0;
        }

        return min(100, round(((float) $used / (float) $limit) * 100));
    };

    $siteUsage = $percent($stats['tenant_sites'] ?? 0, $plan?->max_sites);
    $dbUsage = $percent($stats['tenant_databases'] ?? 0, $plan?->max_databases);
    $emailUsage = $percent($stats['tenant_emails'] ?? 0, $plan?->email_accounts);
    $resourceRing = max(4, min(100, round(($siteUsage + $dbUsage + $emailUsage) / 3)));

    $planExpiresAt = now()->addDays(7);
    $planExpirationWarningDays = 20;
    $isExpiringSoon = now()->diffInDays($planExpiresAt, false) <= $planExpirationWarningDays;

    $essentials = [
        [
            'label' => 'Base de datos',
            'description' => 'Administrar base de datos',
            'icon' => 'ki-data',
            'url' => route('client.websites.module', ['domain' => $site->domain, 'section' => 'databases', 'page' => 'my-sql-databases']),
            'button' => 'Administrar',
        ],
        [
            'label' => 'Copias de seguridad',
            'description' => 'Diariamente',
            'icon' => 'ki-time',
            'url' => route('client.websites.module', ['domain' => $site->domain, 'section' => 'files', 'page' => 'backups']),
        ],
        [
            'label' => 'Administrador de archivos',
            'description' => 'Edita tus archivos',
            'icon' => 'ki-folder',
            'url' => route('client.website.file-manager.entry', ['domain' => $site->domain]),
            'button' => 'Abrir',
            'external' => true,
        ],
        [
            'label' => 'Cache',
            'description' => 'Ver los cambios mas recientes',
            'icon' => 'ki-eraser',
            'url' => route('client.websites.module', ['domain' => $site->domain, 'section' => 'advanced', 'page' => 'cache-manager']),
            'actions' => [
                ['label' => 'Borrar cache', 'url' => route('client.websites.module', ['domain' => $site->domain, 'section' => 'advanced', 'page' => 'cache-manager'])],
                ['label' => 'Sin vista previa de cache', 'url' => route('client.websites.module', ['domain' => $site->domain, 'section' => 'advanced', 'page' => 'cache-manager']), 'external' => true],
            ],
        ],
        [
            'label' => 'Plan de hosting',
            'description' => $planName,
            'icon' => 'ki-server',
            'url' => route('client.websites.module', ['domain' => $site->domain, 'section' => 'order', 'page' => 'details']),
        ],
    ];

    $tips = [
        [
            'title' => 'Tu plan de hosting caduca en ' . now()->diffInDays($planExpiresAt, false) . ' dias',
            'description' => 'Renueva el plan antes de que caduque',
            'icon' => 'ki-information-2',
            'actions' => [
                ['label' => 'Renovar', 'style' => 'kt-btn-outline'],
                ['label' => 'Mejorar', 'style' => 'kt-btn-primary'],
            ],
        ],
        [
            'title' => 'Gestiona todos los servicios en un solo lugar',
            'description' => 'Transfiere tu dominio ' . $site->domain . ' a XPanel',
            'icon' => 'ki-route',
            'actions' => [
                ['label' => 'Transferir', 'style' => 'kt-btn-outline'],
            ],
        ],
    ];

    $securityBadges = [
        ['label' => 'Malware pendiente', 'ok' => false],
        ['label' => ($stats['ssl_active'] ?? false) ? 'SSL activo' : 'SSL pendiente', 'ok' => $stats['ssl_active'] ?? false],
        ['label' => 'CDN pendiente', 'ok' => false],
    ];
@endphp

@section('content')
    <div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
        <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <h1 class="text-2xl font-semibold text-mono">Panel</h1>
                            <a class="kt-btn kt-btn-outline" href="{{ route('client.websites.module', ['domain' => $site->domain, 'section' => 'order', 'page' => 'upgrade']) }}">
                                Mejorar plan
                            </a>
                        </div>

                        <section class="kt-card overflow-hidden">
                            <div class="kt-card-content p-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex min-w-0 items-center gap-4">
                                        <div class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-muted">
                                            <i class="ki-filled ki-code text-2xl text-mono"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <h2 class="truncate text-lg font-semibold text-mono">{{ $site->domain }}</h2>
                                                <a class="shrink-0 text-secondary-foreground hover:text-primary" href="http://{{ $site->domain }}" target="_blank" rel="noopener">
                                                    <i class="ki-filled ki-exit-right-corner"></i>
                                                </a>
                                            </div>
                                            <p class="mt-1 text-sm text-secondary-foreground">Creado: {{ $site->created_at?->format('Y-m-d') ?? 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <span class="kt-badge kt-badge-outline {{ $statusClass }}">{{ $status }}</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-border p-5 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a class="kt-btn kt-btn-outline" href="{{ route('client.websites.module', ['domain' => $site->domain, 'section' => 'domains', 'page' => 'subdomains']) }}">
                                        <i class="ki-filled ki-click"></i>
                                        Administrar dominio
                                    </a>
                                    <a class="kt-btn kt-btn-outline" href="{{ route('client.mail.index') }}">
                                        <i class="ki-filled ki-sms"></i>
                                        Administrar email
                                    </a>
                                    <form method="POST" action="{{ route('client.sites.restart', $site) }}">
                                        @csrf
                                        <button type="submit" class="kt-btn kt-btn-outline"
                                                onclick="return confirm('¿Reiniciar el sitio {{ $site->domain }}?')">
                                            <i class="ki-filled ki-arrows-circle"></i>
                                            Reiniciar sitio
                                        </button>
                                    </form>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach($securityBadges as $badge)
                                        <span class="kt-badge kt-badge-outline {{ $badge['ok'] ? 'kt-badge-success' : 'kt-badge-warning' }}">
                                            <i class="ki-filled {{ $badge['ok'] ? 'ki-check-circle' : 'ki-information-2' }}"></i>
                                            {{ $badge['label'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </section>

                        <div class="grid gap-5 xl:grid-cols-2">
                            <section class="kt-card overflow-hidden">
                                <div class="kt-card-header">
                                    <h3 class="kt-card-title">Esenciales</h3>
                                </div>

                                <div class="divide-y divide-border">
                                    @foreach($essentials as $item)
                                        <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                                            <a class="flex min-w-0 items-center gap-4" href="{{ $item['url'] }}">
                                                <i class="ki-filled {{ $item['icon'] }} text-2xl text-secondary-foreground"></i>
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-semibold text-mono">{{ $item['label'] }}</span>
                                                    <span class="mt-1 block text-sm text-secondary-foreground">{{ $item['description'] }}</span>
                                                </span>
                                            </a>

                                            @if(!empty($item['actions']))
                                                <div class="flex flex-wrap gap-2 sm:justify-end">
                                                    @foreach($item['actions'] as $action)
                                                        <a class="kt-btn kt-btn-outline kt-btn-sm" href="{{ $action['url'] }}">
                                                            {{ $action['label'] }}
                                                            @if(!empty($action['external']))
                                                                <i class="ki-filled ki-exit-right-corner"></i>
                                                            @endif
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @elseif(!empty($item['button']))
                                                <a class="kt-btn kt-btn-outline kt-btn-sm" href="{{ $item['url'] }}">
                                                    {{ $item['button'] }}
                                                    @if(!empty($item['external']))
                                                        <i class="ki-filled ki-exit-right-corner"></i>
                                                    @endif
                                                </a>
                                            @else
                                                <i class="ki-filled ki-right text-secondary-foreground"></i>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>

                            <div class="grid gap-5">
                                <section class="kt-card">
                                    <div class="kt-card-header">
                                        <h3 class="kt-card-title">Rendimiento</h3>
                                        <a class="kt-btn kt-btn-outline kt-btn-sm" href="{{ route('client.websites.module', ['domain' => $site->domain, 'section' => 'performance', 'page' => 'page-speed']) }}">
                                            Ejecutar prueba de velocidad
                                        </a>
                                    </div>
                                    <div class="kt-card-content p-5">
                                        <div class="grid gap-5 md:grid-cols-2">
                                            @foreach([['Escritorio', 'ki-screen'], ['Movil', 'ki-phone']] as [$label, $icon])
                                                <div class="flex items-center gap-4 border-border md:border-e md:last:border-e-0">
                                                    <div class="flex size-20 shrink-0 items-center justify-center rounded-full border border-dashed border-input">
                                                        <i class="ki-filled {{ $icon }} text-xl text-secondary-foreground"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-semibold text-mono">{{ $label }}</div>
                                                        <div class="mt-1 text-sm text-secondary-foreground">No escaneado todavia</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </section>

                                <section class="kt-card">
                                    <div class="kt-card-header">
                                        <h3 class="kt-card-title">Planificar el uso de recursos</h3>
                                        <a class="kt-btn kt-btn-outline kt-btn-sm" href="{{ route('client.websites.module', ['domain' => $site->domain, 'section' => 'order', 'page' => 'order-usage']) }}">Ver detalles</a>
                                    </div>
                                    <div class="kt-card-content p-5">
                                        <div class="grid gap-5 md:grid-cols-[auto_minmax(0,1fr)_minmax(0,1fr)] md:items-center">
                                            <div class="flex size-24 items-center justify-center rounded-full"
                                                style="background: conic-gradient(var(--color-primary, #6d5dfc) {{ $resourceRing }}%, hsl(var(--muted)) 0);">
                                                <div class="flex size-16 items-center justify-center rounded-full bg-background">
                                                    <span class="text-sm font-semibold text-mono">{{ $resourceRing }}%</span>
                                                </div>
                                            </div>

                                            <div class="grid gap-2 text-sm">
                                                <div>
                                                    <div class="flex items-center justify-between gap-3">
                                                        <span class="text-secondary-foreground">Sitios web</span>
                                                        <span class="font-semibold text-mono">{{ $stats['tenant_sites'] ?? 0 }} / {{ $limitText($plan?->max_sites) }}</span>
                                                    </div>
                                                    <div class="mt-1 h-1 rounded-full bg-muted">
                                                        <div class="h-1 rounded-full bg-primary" style="width: {{ $siteUsage }}%"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="flex items-center justify-between gap-3">
                                                        <span class="text-secondary-foreground">Bases de datos</span>
                                                        <span class="font-semibold text-mono">{{ $stats['tenant_databases'] ?? 0 }} / {{ $limitText($plan?->max_databases) }}</span>
                                                    </div>
                                                    <div class="mt-1 h-1 rounded-full bg-muted">
                                                        <div class="h-1 rounded-full bg-success" style="width: {{ $dbUsage }}%"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="flex items-center justify-between gap-3">
                                                        <span class="text-secondary-foreground">Correos</span>
                                                        <span class="font-semibold text-mono">{{ $stats['tenant_emails'] ?? 0 }} / {{ $limitText($plan?->email_accounts) }}</span>
                                                    </div>
                                                    <div class="mt-1 h-1 rounded-full bg-muted">
                                                        <div class="h-1 rounded-full bg-info" style="width: {{ $emailUsage }}%"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid gap-3 border-t border-border pt-4 text-sm md:border-s md:border-t-0 md:ps-5 md:pt-0">
                                                <div>
                                                    <div class="text-secondary-foreground">Uso del disco</div>
                                                    <div class="font-semibold text-mono">No medido / {{ $plan?->storage_mb ? number_format($plan->storage_mb / 1024, 1) . ' GB' : 'Ilimitado' }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-secondary-foreground">CPU</div>
                                                    <div class="font-semibold text-mono">Sin datos</div>
                                                </div>
                                                <div>
                                                    <div class="text-secondary-foreground">Memoria</div>
                                                    <div class="font-semibold text-mono">Sin datos</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>

                        <section class="kt-card">
                            <div class="kt-card-header">
                                <h3 class="kt-card-title">Consejos para mejorar</h3>
                            </div>
                            <div class="kt-card-content p-5">
                                <div class="grid gap-4">
                                    @foreach($tips as $tip)
                                        <div class="flex flex-col gap-4 rounded-xl border border-border p-4 lg:flex-row lg:items-center lg:justify-between">
                                            <div class="flex min-w-0 items-center gap-4">
                                                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                                    <i class="ki-filled {{ $tip['icon'] }} text-xl"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-semibold text-mono">{{ $tip['title'] }}</div>
                                                    <div class="mt-1 text-sm text-secondary-foreground">{{ $tip['description'] }}</div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2 lg:justify-end">
                                                @foreach($tip['actions'] as $action)
                                                    <a class="kt-btn {{ $action['style'] }} kt-btn-sm" href="{{ route('client.websites.module', ['domain' => $site->domain, 'section' => 'order', 'page' => 'upgrade']) }}">
                                                        {{ $action['label'] }}
                                                    </a>
                                                @endforeach
                                                <button class="kt-btn kt-btn-icon kt-btn-ghost kt-btn-sm" type="button" title="Ocultar">
                                                    <i class="ki-filled ki-cross"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
