@extends('layouts.client')

@php
    $plan = $tenant?->plan;
    $planName = $plan?->name ?? 'Cloud Startup';
    $planExpiresAt = now()->addDays(7);
    $planExpirationWarningDays = 20;
    $isExpiringSoon = now()->diffInDays($planExpiresAt, false) <= $planExpirationWarningDays;
    $deletingStatuses = ['deleting', 'delete_pending', 'delete_error'];
@endphp

@section('content')
    <div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
        <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h1 class="text-2xl font-semibold text-mono">Sitios web</h1>
                                <p class="mt-1 text-sm text-secondary-foreground">Administra tus sitios, dominios y accesos principales.</p>
                            </div>

                            <a href="{{ route('client.websites.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                Anadir sitio web
                            </a>
                        </div>

                        <div class="kt-card">
                            <div class="kt-card-content p-5">
                                <form method="GET" action="{{ route('client.websites.index') }}" class="flex flex-col gap-4 md:flex-row md:items-center">
                                    <label class="kt-input flex-1">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input name="search" value="{{ $search }}" type="text" placeholder="Busca por dominio, email o nombre">
                                    </label>

                                    <div class="flex items-center gap-2">
                                        <button class="kt-btn kt-btn-icon kt-btn-outline" type="submit" title="Buscar">
                                            <i class="ki-filled ki-magnifier"></i>
                                        </button>
                                        <a class="kt-btn kt-btn-icon kt-btn-outline" href="{{ route('client.websites.index') }}" title="Limpiar">
                                            <i class="ki-filled ki-arrows-circle"></i>
                                        </a>
                                        <button class="kt-btn kt-btn-icon kt-btn-outline" type="button" title="Etiquetas">
                                            <i class="ki-filled ki-tag"></i>
                                        </button>
                                        <button class="kt-btn kt-btn-icon kt-btn-outline" type="button" title="Favoritos">
                                            <i class="ki-filled ki-star"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @if($isExpiringSoon)
                                <div class="mx-5 flex flex-col gap-3 rounded-xl border border-warning/20 bg-warning/10 px-4 py-3 md:flex-row md:items-center md:justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-warning/15 text-warning">
                                            <i class="ki-filled ki-information-2"></i>
                                        </span>
                                        <span class="text-sm font-medium text-mono">
                                            El plan de hosting caducara el {{ $planExpiresAt->format('Y-m-d') }}
                                        </span>
                                    </div>
                                    <a href="#" class="kt-btn kt-btn-warning kt-btn-sm">Renovar</a>
                                </div>
                            @endif

                        <div class="kt-card overflow-visible">
                            <div class="kt-card-header min-h-16">
                                <div>
                                    <h2 class="kt-card-title">{{ $planName }}</h2>
                                    <p class="text-xs text-secondary-foreground">Plan activo para tus sitios web</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('client.websites.create') }}" class="kt-btn kt-btn-outline">
                                        <i class="ki-filled ki-exit-down"></i>
                                        Migrar sitio web
                                    </a>
                                    <a href="{{ route('client.websites.create') }}" class="kt-btn kt-btn-primary">
                                        <i class="ki-filled ki-plus"></i>
                                        Anadir sitio web
                                    </a>
                                </div>
                            </div>

                            <div class="border-t border-border">
                                @forelse($sites as $site)
                                    @php
                                        $status = $site->status ?? 'active';
                                        $isDeleting = in_array($status, $deletingStatuses, true) || str_contains($status, 'delete');
                                        $isProvisioning = $status === 'provisioning' || $status === 'creating';
                                    @endphp

                                    <div class="flex flex-col gap-4 border-b border-border px-5 py-5 last:border-b-0 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-3">
                                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-border bg-muted">
                                                    <i class="ki-filled ki-code text-lg text-mono"></i>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="flex min-w-0 items-center gap-2">
                                                        <a class="truncate text-sm font-semibold text-mono hover:text-primary" href="{{ route('client.websites.show', ['domain' => $site->domain]) }}">
                                                            {{ $site->domain }}
                                                        </a>
                                                        <a class="shrink-0 text-secondary-foreground hover:text-primary" href="http://{{ $site->domain }}" target="_blank" rel="noopener" title="Abrir sitio">
                                                            <i class="ki-filled ki-exit-right-corner text-sm"></i>
                                                        </a>
                                                    </div>
                                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-secondary-foreground">
                                                        <span>{{ strtoupper($site->project_type ?? 'web') }}</span>
                                                        <span class="text-muted-foreground">/</span>
                                                        <span>{{ $site->web_server ?? 'apache' }}</span>
                                                        @if($site->php_version)
                                                            <span class="text-muted-foreground">/</span>
                                                            <span>PHP {{ $site->php_version }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            @if($isDeleting)
                                                <div class="ms-12 mt-3 inline-flex items-center gap-2 rounded-xl bg-muted px-3 py-2 text-xs font-medium text-secondary-foreground">
                                                    <i class="ki-filled ki-time text-base"></i>
                                                    Se esta eliminando el sitio web
                                                </div>
                                            @elseif($isProvisioning)
                                                <div class="ms-12 mt-3 inline-flex items-center gap-2 rounded-xl bg-warning/10 px-3 py-2 text-xs font-medium text-warning"
                                                     data-site-provisioning-badge="{{ $site->id }}">
                                                    <i class="ki-filled ki-loading text-base"></i>
                                                    Sitio en aprovisionamiento
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('client.websites.show', ['domain' => $site->domain]) }}"
                                                class="kt-btn kt-btn-outline kt-btn-sm {{ $isDeleting ? 'disabled pointer-events-none opacity-50' : '' }}">
                                                Panel
                                            </a>

                                            <details class="relative">
                                                <summary class="kt-btn kt-btn-icon kt-btn-ghost kt-btn-sm list-none cursor-pointer" title="Opciones">
                                                    <i class="ki-filled ki-dots-vertical"></i>
                                                </summary>
                                                <div class="absolute end-0 z-20 mt-2 w-56 rounded-xl border border-border bg-background p-1.5 shadow-lg">
                                                    <a class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-mono hover:bg-muted" href="{{ route('client.websites.show', ['domain' => $site->domain]) }}">
                                                        <i class="ki-filled ki-information-2 text-secondary-foreground"></i>
                                                        Detalles del sitio
                                                    </a>
                                                    <a class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-mono hover:bg-muted" href="{{ route('client.website.file-manager.entry', ['domain' => $site->domain]) }}">
                                                        <i class="ki-filled ki-folder text-secondary-foreground"></i>
                                                        Archivos
                                                    </a>
                                                    <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-mono hover:bg-muted" type="button">
                                                        <i class="ki-filled ki-star text-secondary-foreground"></i>
                                                        Anadir a favoritos
                                                    </button>
                                                    <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-mono hover:bg-muted" type="button">
                                                        <i class="ki-filled ki-tag text-secondary-foreground"></i>
                                                        Etiquetas
                                                    </button>
                                                    <form action="{{ route('client.sites.destroy', $site) }}" method="POST" onsubmit="return confirm('Se eliminara {{ $site->domain }} y todos sus datos. Esta accion no se puede deshacer.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-destructive hover:bg-destructive/10" type="submit">
                                                            <i class="ki-filled ki-trash"></i>
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </details>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-5 py-16 text-center">
                                        <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-muted">
                                            <i class="ki-filled ki-screen text-xl text-secondary-foreground"></i>
                                        </div>
                                        <h3 class="mt-4 text-base font-semibold text-mono">No tienes sitios web registrados</h3>
                                        <p class="mt-1 text-sm text-secondary-foreground">Crea tu primer sitio para empezar a gestionar archivos, dominios y recursos.</p>
                                        <a href="{{ route('client.websites.create') }}" class="kt-btn kt-btn-primary mt-5">
                                            <i class="ki-filled ki-plus"></i>
                                            Crear primer sitio
                                        </a>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection

@push('scripts')
@php
    $provisioningSites = $sites->filter(fn($s) => in_array($s->status ?? '', ['provisioning', 'creating']))->values();
@endphp
@if($provisioningSites->isNotEmpty())
<script>
(function () {
    const pending = new Map([
        @foreach($provisioningSites as $s)
        [{{ $s->id }}, { url: '{{ route('client.sites.status', $s) }}', domain: '{{ $s->domain }}' }],
        @endforeach
    ]);

    function badgeEl(siteId) {
        return document.querySelector('[data-site-provisioning-badge="{{ $s->id }}"]'.replace('{{ $s->id }}', siteId));
    }

    function updateBadge(siteId, siteStatus) {
        const el = badgeEl(siteId);
        if (!el) return;
        if (siteStatus === 'active') {
            el.innerHTML = '<i class="ki-filled ki-check-circle text-base"></i> Activo';
            el.className = el.className.replace('bg-warning/10', 'bg-success/10').replace('text-warning', 'text-success');
        } else if (siteStatus === 'provision_error') {
            el.innerHTML = '<i class="ki-filled ki-cross-circle text-base"></i> Error al provisionar';
            el.className = el.className.replace('bg-warning/10', 'bg-destructive/10').replace('text-warning', 'text-destructive');
        }
    }

    async function poll() {
        if (pending.size === 0) return;

        for (const [siteId, info] of pending) {
            try {
                const res = await fetch(info.url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) continue;
                const data = await res.json();
                const status = data.site_status ?? '';
                if (status === 'active' || status === 'provision_error') {
                    updateBadge(siteId, status);
                    pending.delete(siteId);
                }
            } catch (_) {}
        }

        if (pending.size > 0) {
            setTimeout(poll, 4000);
        }
    }

    setTimeout(poll, 4000);
})();
</script>
@endif
@endpush
