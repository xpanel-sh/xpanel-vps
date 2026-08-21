@extends('layouts.client')

@section('content')
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">

            @include('client.domains.partials.tabs')

            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- ── Header ─────────────────────────────────────────────────── --}}
                    <div>
                        <h1 class="text-2xl font-semibold text-mono">Transferir dominio</h1>
                        <p class="mt-1 text-sm text-secondary-foreground">Transfiere un dominio existente para gestionarlo desde XPanel.</p>
                    </div>

                    {{-- ── Transfer form card ───────────────────────────────────────── --}}
                    <div class="kt-card">
                        <div class="flex flex-col items-center gap-6 px-6 py-12 text-center">
                            <span class="flex size-20 items-center justify-center rounded-full border border-border bg-muted">
                                <i class="ki-filled ki-arrows-circle text-4xl text-secondary-foreground"></i>
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold text-mono">Conecta tu dominio a XPanel</h2>
                                <p class="mt-1 text-sm text-secondary-foreground max-w-md mx-auto">
                                    XPanel no actúa como registrador. Para "transferir" tu dominio, simplemente apunta sus nameservers a XPanel y agrégalo a tu portafolio.
                                </p>
                            </div>

                            <form method="GET" action="{{ route('client.domains.search') }}"
                                  class="flex w-full max-w-md items-center gap-0">
                                <label class="kt-input flex-1 rounded-r-none border-r-0" style="height:44px">
                                    <i class="ki-filled ki-globe text-secondary-foreground"></i>
                                    <input name="q" type="text" placeholder="Escribe el dominio que quieres conectar"
                                           class="w-full border-0 bg-transparent text-sm outline-none">
                                </label>
                                <button type="submit"
                                        class="kt-btn kt-btn-primary rounded-l-none"
                                        style="height:44px; border-radius: 0 10px 10px 0">
                                    Conectar
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- ── Checklist ─────────────────────────────────────────────────── --}}
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h2 class="kt-card-title">Prepara tu dominio para la conexión</h2>
                        </div>
                        <div class="kt-card-content border-t border-border">
                            <div class="grid gap-0 divide-y divide-border">
                                @foreach([
                                    [
                                        'icon'  => 'ki-check-circle',
                                        'color' => 'text-success',
                                        'title' => 'El dominio se puede conectar',
                                        'desc'  => 'No hay requisito de tiempo mínimo para conectar un dominio a XPanel como nameserver secundario.',
                                    ],
                                    [
                                        'icon'  => 'ki-shield-tick',
                                        'color' => 'text-primary',
                                        'title' => 'Acceso al panel de tu registrador',
                                        'desc'  => 'Necesitas poder modificar los nameservers en el panel de control del registrador actual (GoDaddy, Namecheap, IONOS, Cloudflare, etc.).',
                                    ],
                                    [
                                        'icon'  => 'ki-key',
                                        'color' => 'text-warning',
                                        'title' => 'Nameservers de XPanel',
                                        'desc'  => 'Copia los nameservers desde la pestaña DNS y pégalos en tu registrador. Los cambios DNS pueden tardar hasta 48h en propagarse.',
                                    ],
                                ] as $item)
                                    <div class="flex items-start gap-4 px-5 py-5">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full border border-border bg-muted">
                                            <i class="ki-filled {{ $item['icon'] }} text-xl {{ $item['color'] }}"></i>
                                        </span>
                                        <div>
                                            <p class="font-semibold text-sm text-mono">{{ $item['title'] }}</p>
                                            <p class="mt-1 text-xs text-secondary-foreground leading-relaxed">{{ $item['desc'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- ── Domains in portfolio ──────────────────────────────────────── --}}
                    @if($domains->isNotEmpty())
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h2 class="kt-card-title">Dominios en tu portafolio</h2>
                                <span class="kt-badge kt-badge-outline">{{ $domains->count() }}</span>
                            </div>
                            <div class="border-t border-border overflow-x-auto">
                                <table class="w-full min-w-[500px]">
                                    <thead>
                                        <tr class="border-b border-border bg-muted/40">
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Dominio</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Tipo</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-secondary-foreground">DNS</th>
                                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($domains as $dom)
                                            @php
                                                $dnsOk    = $dom->dns_status === 'managed';
                                                $dnsError = str_contains($dom->dns_status ?? '', 'error');
                                            @endphp
                                            <tr class="border-b border-border last:border-b-0 hover:bg-muted/30 transition-colors">
                                                <td class="px-5 py-3.5">
                                                    <div class="flex items-center gap-2">
                                                        <i class="ki-filled ki-globe text-secondary-foreground text-sm"></i>
                                                        <span class="text-sm font-medium text-mono">{{ $dom->domain }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-3.5">
                                                    <span class="kt-badge kt-badge-outline text-xs capitalize">{{ $dom->type ?? 'primary' }}</span>
                                                </td>
                                                <td class="px-5 py-3.5">
                                                    <span class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-0.5 text-xs font-medium
                                                        {{ $dnsError ? 'border-danger/25 bg-danger/10 text-danger' : ($dnsOk ? 'border-success/25 bg-success/10 text-success' : 'border-warning/25 bg-warning/10 text-warning') }}">
                                                        <i class="ki-filled ki-{{ $dnsError ? 'cross-circle' : ($dnsOk ? 'check-circle' : 'information-2') }} text-sm"></i>
                                                        {{ ucfirst($dom->dns_status ?? 'pending') }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3.5 text-right">
                                                    <a href="{{ route('client.dns.index') }}?domain={{ $dom->id }}"
                                                       class="kt-btn kt-btn-outline kt-btn-sm">
                                                        <i class="ki-filled ki-setting-2"></i>
                                                        Gestionar DNS
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </main>

        @include('layouts.partials.client.footer')
    </div>
</div>
@endsection
