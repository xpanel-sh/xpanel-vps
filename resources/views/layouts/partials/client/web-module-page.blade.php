@php
    $cards = $cards ?? [];
    $metrics = $metrics ?? [];
    $actions = $actions ?? [];
@endphp

<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="kt-badge kt-badge-outline kt-badge-primary">{{ $sectionLabel ?? 'Sitio web' }}</span>
                                <span class="text-xs text-secondary-foreground uppercase">{{ $site->domain }}</span>
                            </div>
                            <h1 class="mt-2 text-2xl font-semibold text-mono truncate">{{ $title ?? ($activeModule['label'] ?? 'Modulo') }}</h1>
                            <p class="mt-1 max-w-3xl text-sm text-secondary-foreground">
                                {{ $description ?? 'Vista preparada para gestionar este recurso del sitio seleccionado.' }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($actions as $action)
                                <a class="kt-btn {{ $action['style'] ?? 'kt-btn-outline' }}" href="{{ $action['url'] ?? '#' }}">
                                    @isset($action['icon'])
                                        <i class="ki-filled {{ $action['icon'] }}"></i>
                                    @endisset
                                    {{ $action['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if($metrics)
                        <div class="grid gap-5 md:grid-cols-3">
                            @foreach($metrics as $metric)
                                <div class="kt-card">
                                    <div class="kt-card-content p-5">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <div class="text-sm text-secondary-foreground">{{ $metric['label'] }}</div>
                                                <div class="mt-2 text-2xl font-semibold text-mono">{{ $metric['value'] }}</div>
                                            </div>
                                            <div class="flex size-10 items-center justify-center rounded-lg bg-accent">
                                                <i class="ki-filled {{ $metric['icon'] ?? 'ki-chart-simple' }} text-lg text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid gap-5 lg:grid-cols-2">
                        @foreach($cards as $card)
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h3 class="kt-card-title">{{ $card['title'] }}</h3>
                                </div>
                                <div class="kt-card-content p-5">
                                    <p class="text-sm text-secondary-foreground">{{ $card['body'] }}</p>
                                    @if(!empty($card['items']))
                                        <div class="mt-4 grid gap-3">
                                            @foreach($card['items'] as $item)
                                                <div class="flex items-center justify-between gap-3 rounded-lg border border-border px-3 py-2">
                                                    <span class="text-sm text-mono">{{ $item['label'] }}</span>
                                                    <span class="text-xs text-secondary-foreground">{{ $item['value'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>

        @include('layouts.partials.client.footer')
    </div>
</div>
