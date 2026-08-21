@extends('layouts.client')

@section('content')
@php
    $panelNs = $nameservers ? $nameservers->records() : [];
    $isXpanelNs  = $dnsMode === 'xpanel_ns';
    $isCloudflare = $dnsMode === 'cloudflare';
    $isARecord    = $dnsMode === 'a_record';
@endphp
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">

            {{-- ── Breadcrumb ──────────────────────────────────────────────── --}}
            <div class="pb-4">
                <div class="kt-container-fluid flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                        <a href="{{ route('client.domains.index') }}" class="hover:text-mono transition">Dominios</a>
                        <i class="ki-filled ki-right text-xs"></i>
                        <a href="{{ route('client.dns.index') }}" class="hover:text-mono transition">DNS</a>
                        <i class="ki-filled ki-right text-xs"></i>
                        <span class="font-semibold text-mono">{{ $domain }}</span>
                    </div>
                    <a href="{{ route('client.dns.index') }}" class="kt-btn kt-btn-ghost kt-btn-sm gap-1">
                        <i class="ki-filled ki-arrow-left text-sm"></i>
                        Cambiar dominio
                    </a>
                </div>
            </div>

            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- ── Flash / Errors ──────────────────────────────────── --}}
                    @if(session('success'))
                        <div class="flex items-center gap-3 rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm font-medium text-success">
                            <i class="ki-filled ki-check-circle text-lg"></i>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if($errors->has('dns') || $errors->has('ssl'))
                        <div class="flex items-start gap-3 rounded-xl border border-warning/20 bg-warning/10 px-4 py-3 text-sm text-warning">
                            <i class="ki-filled ki-information-2 text-lg shrink-0 mt-0.5"></i>
                            {{ $errors->first('dns') ?: $errors->first('ssl') }}
                        </div>
                    @endif

                    @if(!$domainRecord)
                        <div class="kt-card">
                            <div class="flex flex-col items-center justify-center gap-5 py-16 text-center">
                                <i class="ki-filled ki-globe text-4xl text-secondary-foreground opacity-40"></i>
                                <div>
                                    <p class="font-semibold text-mono">Dominio no registrado</p>
                                    <p class="text-sm text-secondary-foreground mt-1">El dominio <strong>{{ $domain }}</strong> no está en tu portafolio.</p>
                                </div>
                                <a href="{{ route('client.domains.create') }}" class="kt-btn kt-btn-primary">
                                    <i class="ki-filled ki-plus"></i>
                                    Agregar dominio
                                </a>
                            </div>
                        </div>
                    @else

                    {{-- ── NS actuales detectados ───────────────────────────── --}}
                    @if(count($liveNs) > 0)
                        <div class="flex items-start gap-3 rounded-xl border border-border bg-muted/40 px-5 py-4">
                            <i class="ki-filled ki-globe text-lg text-secondary-foreground shrink-0 mt-0.5"></i>
                            <div class="text-sm">
                                <span class="font-semibold text-mono">NS actuales de {{ $domain }}:</span>
                                <span class="ml-2 text-secondary-foreground">{{ implode(' · ', $liveNs) }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- ── Selector de modo ─────────────────────────────────── --}}
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <div>
                                <h2 class="kt-card-title">Modo de gestión DNS</h2>
                                <p class="mt-0.5 text-xs text-secondary-foreground">Elige cómo está conectado este dominio a tu servidor.</p>
                            </div>
                        </div>
                        <div class="kt-card-content border-t border-border p-5">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                                {{-- Modo A: XPanel NS --}}
                                <form action="{{ route('client.websites.dns-zone-editor.mode', $domain) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="dns_mode" value="xpanel_ns">
                                    <button type="submit" class="w-full text-left rounded-xl border-2 p-4 transition hover:border-primary/40 hover:bg-primary/5 {{ $isXpanelNs ? 'border-primary bg-primary/5' : 'border-border' }}">
                                        <div class="flex items-start gap-3">
                                            <div class="flex size-9 items-center justify-center rounded-lg {{ $isXpanelNs ? 'bg-primary' : 'bg-muted' }} shrink-0">
                                                <i class="ki-filled ki-setting-2 text-sm {{ $isXpanelNs ? 'text-white' : 'text-secondary-foreground' }}"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-sm text-mono">XPanel NS</p>
                                                <p class="text-xs text-secondary-foreground mt-0.5">Tus NS apuntan aquí. XPanel gestiona los registros DNS directamente.</p>
                                            </div>
                                        </div>
                                    </button>
                                </form>

                                {{-- Modo B: Solo A record --}}
                                <form action="{{ route('client.websites.dns-zone-editor.mode', $domain) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="dns_mode" value="a_record">
                                    <button type="submit" class="w-full text-left rounded-xl border-2 p-4 transition hover:border-primary/40 hover:bg-primary/5 {{ $isARecord ? 'border-primary bg-primary/5' : 'border-border' }}">
                                        <div class="flex items-start gap-3">
                                            <div class="flex size-9 items-center justify-center rounded-lg {{ $isARecord ? 'bg-primary' : 'bg-muted' }} shrink-0">
                                                <i class="ki-filled ki-map-pin text-sm {{ $isARecord ? 'text-white' : 'text-secondary-foreground' }}"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-sm text-mono">Solo A record</p>
                                                <p class="text-xs text-secondary-foreground mt-0.5">Apunta un A record a la IP de este servidor en tu registrador o Cloudflare.</p>
                                            </div>
                                        </div>
                                    </button>
                                </form>

                                {{-- Modo C: Cloudflare API --}}
                                <form action="{{ route('client.websites.dns-zone-editor.mode', $domain) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="dns_mode" value="cloudflare">
                                    <button type="submit" class="w-full text-left rounded-xl border-2 p-4 transition hover:border-primary/40 hover:bg-primary/5 {{ $isCloudflare ? 'border-primary bg-primary/5' : 'border-border' }}">
                                        <div class="flex items-start gap-3">
                                            <div class="flex size-9 items-center justify-center rounded-lg {{ $isCloudflare ? 'bg-[#f48120]' : 'bg-muted' }} shrink-0">
                                                <i class="ki-filled ki-cloud text-sm {{ $isCloudflare ? 'text-white' : 'text-secondary-foreground' }}"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-sm text-mono">Cloudflare API</p>
                                                <p class="text-xs text-secondary-foreground mt-0.5">XPanel gestiona registros en Cloudflare vía API. Soporta SSL wildcard.</p>
                                            </div>
                                        </div>
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>

                    {{-- ══ CONTENIDO POR MODO ══════════════════════════════════ --}}

                    {{-- ── MODO: XPanel NS ──────────────────────────────────── --}}
                    @if($isXpanelNs)

                        <div class="flex items-start gap-3 rounded-xl border border-border bg-muted/60 px-5 py-4">
                            <i class="ki-filled ki-information-2 text-xl text-secondary-foreground shrink-0 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-semibold text-mono">Las actualizaciones de DNS pueden tardar hasta 24 horas en propagarse</p>
                                <p class="mt-0.5 text-xs text-secondary-foreground">Una vez realizados los cambios en los registros DNS, los cambios tardan hasta 24 horas en tener efecto a nivel global.</p>
                            </div>
                        </div>

                        @if(count($panelNs) > 0)
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h2 class="kt-card-title">Configura estos nameservers en tu registrador</h2>
                                    <span class="text-xs text-secondary-foreground">Copia y pega en GoDaddy, Namecheap, etc.</span>
                                </div>
                                <div class="kt-card-content border-t border-border p-4">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($panelNs as $ns)
                                            <code class="rounded-lg border border-border bg-muted px-3 py-2 text-xs font-mono text-mono select-all">{{ $ns }}</code>
                                        @endforeach
                                    </div>
                                    @if(count($liveNs) > 0 && count(array_intersect($liveNs, array_map('strtolower', $panelNs))) === 0)
                                        <p class="mt-3 text-xs text-warning flex items-center gap-1.5">
                                            <i class="ki-filled ki-information-2"></i>
                                            El dominio aún no apunta a estos NS. Actualiza tu registrador y espera la propagación.
                                        </p>
                                    @elseif(count($liveNs) > 0)
                                        <p class="mt-3 text-xs text-success flex items-center gap-1.5">
                                            <i class="ki-filled ki-check-circle"></i>
                                            El dominio ya apunta a los NS de XPanel.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Editor de registros (igual que antes) --}}
                        <div class="kt-card" x-data="dnsZoneEditor()">
                            <div class="kt-card-header">
                                <div>
                                    <h2 class="kt-card-title">Administrar registros DNS</h2>
                                    <p class="mt-0.5 text-xs text-secondary-foreground">Estos registros se sirven directamente desde los nameservers de XPanel.</p>
                                </div>
                            </div>
                            <form action="{{ route('client.websites.dns-zone-editor.store', $domain) }}" method="POST">
                                @csrf
                                <div class="border-t border-border">
                                    <div class="hidden lg:grid px-5 pt-4 pb-2" style="grid-template-columns: 140px 1fr 2fr 110px 44px">
                                        <span class="text-xs font-semibold text-secondary-foreground uppercase tracking-wide">Tipo</span>
                                        <span class="text-xs font-semibold text-secondary-foreground uppercase tracking-wide">Nombre</span>
                                        <span class="text-xs font-semibold text-secondary-foreground uppercase tracking-wide">Valor</span>
                                        <span class="text-xs font-semibold text-secondary-foreground uppercase tracking-wide">TTL</span>
                                        <span></span>
                                    </div>
                                    <template x-for="(row, idx) in rows" :key="idx">
                                        <div class="grid items-start gap-3 border-t border-border px-5 py-4" :style="'grid-template-columns: 140px 1fr 2fr 110px 44px'">
                                            <div>
                                                <label class="block text-xs text-secondary-foreground mb-1 lg:hidden">Tipo *</label>
                                                <select name="type[]" x-model="row.type" @change="onTypeChange(row)" class="kt-select w-full text-sm">
                                                    @foreach(['A','AAAA','CNAME','MX','TXT','NS','SRV','CAA'] as $t)
                                                        <option value="{{ $t }}">{{ $t }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-secondary-foreground mb-1 lg:hidden">Nombre *</label>
                                                <div class="kt-input">
                                                    <input name="name[]" x-model="row.name" type="text" :placeholder="row.namePlaceholder || '@'" class="text-sm">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-secondary-foreground mb-1 lg:hidden">Valor *</label>
                                                <div class="kt-input">
                                                    <input name="value[]" x-model="row.value" type="text" :placeholder="row.valuePlaceholder || ''" class="text-sm">
                                                </div>
                                                <p class="mt-1 text-xs text-secondary-foreground" x-show="row.hint" x-text="row.hint"></p>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-secondary-foreground mb-1 lg:hidden">TTL</label>
                                                <select name="ttl[]" x-model="row.ttl" class="kt-select w-full text-sm">
                                                    <option value="300">5 min</option>
                                                    <option value="1800">30 min</option>
                                                    <option value="3600" selected>1 hora</option>
                                                    <option value="14400">4 horas</option>
                                                    <option value="86400">24 horas</option>
                                                </select>
                                            </div>
                                            <div class="flex items-center justify-center pt-1">
                                                <button type="button" @click="removeRow(idx)" x-show="rows.length > 1"
                                                    class="flex size-9 items-center justify-center rounded-lg border border-border text-secondary-foreground hover:border-danger/40 hover:bg-danger/10 hover:text-danger transition">
                                                    <i class="ki-filled ki-cross text-sm"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex items-center justify-between border-t border-border px-5 py-3">
                                    <button type="button" @click="addRow" class="flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
                                        <i class="ki-filled ki-plus text-sm"></i>
                                        Añade más registros
                                    </button>
                                    <button type="submit" class="kt-btn kt-btn-primary">Añadir registro</button>
                                </div>
                            </form>
                        </div>

                        @include('client.web.advanced.partials.records-table', ['records' => $records, 'domain' => $domain])

                        {{-- SSL (HTTP-01) --}}
                        @include('client.web.advanced.partials.ssl-card', ['domain' => $domain, 'mode' => 'http', 'cfToken' => $cfToken])

                    {{-- ── MODO: Solo A record ───────────────────────────────── --}}
                    @elseif($isARecord)

                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h2 class="kt-card-title">Conecta tu dominio con un A record</h2>
                                <span class="text-xs text-secondary-foreground">Hazlo en tu registrador (GoDaddy, Namecheap…) o en Cloudflare</span>
                            </div>
                            <div class="kt-card-content border-t border-border p-5">
                                <p class="text-sm text-secondary-foreground mb-4">Crea un registro <strong>A</strong> apuntando tu dominio raíz (<code>@</code>) a la IP de este servidor:</p>
                                @if($serverIp)
                                    <div class="flex items-center gap-3 rounded-lg border border-border bg-muted px-4 py-3">
                                        <i class="ki-filled ki-map-pin text-secondary-foreground"></i>
                                        <code class="text-sm font-mono text-mono select-all">{{ $serverIp }}</code>
                                        <button onclick="navigator.clipboard.writeText('{{ $serverIp }}')" class="ml-auto kt-btn kt-btn-ghost kt-btn-sm">
                                            <i class="ki-filled ki-copy text-sm"></i>
                                        </button>
                                    </div>
                                @else
                                    <p class="text-sm text-warning">IP del servidor no configurada. Configura <code>XPANEL_SERVER_IP</code> en tu .env.</p>
                                @endif

                                <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="rounded-xl border border-border bg-muted/40 p-4 text-center">
                                        <span class="inline-flex size-10 items-center justify-center rounded-full bg-primary/10 mb-2"><span class="font-bold text-primary">1</span></span>
                                        <p class="text-sm font-semibold text-mono">Ve a tu registrador</p>
                                        <p class="text-xs text-secondary-foreground mt-1">GoDaddy, Namecheap, Google Domains…</p>
                                    </div>
                                    <div class="rounded-xl border border-border bg-muted/40 p-4 text-center">
                                        <span class="inline-flex size-10 items-center justify-center rounded-full bg-primary/10 mb-2"><span class="font-bold text-primary">2</span></span>
                                        <p class="text-sm font-semibold text-mono">Crea un A record</p>
                                        <p class="text-xs text-secondary-foreground mt-1">Host: <code>@</code> → apunta a la IP de arriba</p>
                                    </div>
                                    <div class="rounded-xl border border-border bg-muted/40 p-4 text-center">
                                        <span class="inline-flex size-10 items-center justify-center rounded-full bg-primary/10 mb-2"><span class="font-bold text-primary">3</span></span>
                                        <p class="text-sm font-semibold text-mono">Espera la propagación</p>
                                        <p class="text-xs text-secondary-foreground mt-1">Puede tardar hasta 48 horas</p>
                                    </div>
                                </div>

                                @if(count($liveNs) > 0)
                                    <div class="mt-4 text-xs text-secondary-foreground">
                                        NS actuales: <span class="font-mono">{{ implode(', ', $liveNs) }}</span>
                                        — los DNS records se gestionan en esa plataforma.
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- SSL (HTTP-01) en modo A record --}}
                        @include('client.web.advanced.partials.ssl-card', ['domain' => $domain, 'mode' => 'http', 'cfToken' => $cfToken])

                    {{-- ── MODO: Cloudflare API ──────────────────────────────── --}}
                    @elseif($isCloudflare)

                        {{-- Token configuration --}}
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h2 class="kt-card-title">Cloudflare API Token</h2>
                                <span class="text-xs text-secondary-foreground">Guardado en tu cuenta, aplica a todos tus dominios en Cloudflare</span>
                            </div>
                            <div class="kt-card-content border-t border-border p-5">
                                <form action="{{ route('client.dns.cf-token') }}" method="POST" class="flex items-end gap-3 max-w-lg">
                                    @csrf
                                    <div class="grow">
                                        <label class="block text-xs font-semibold text-mono mb-1">API Token de Cloudflare</label>
                                        <div class="kt-input">
                                            <input type="password" name="cloudflare_api_token"
                                                   value="{{ $cfToken }}"
                                                   placeholder="Tu token de Cloudflare..."
                                                   class="text-sm font-mono">
                                        </div>
                                        <p class="mt-1 text-xs text-secondary-foreground">
                                            Crea uno en <strong>cloudflare.com → My Profile → API Tokens</strong> con permisos: <em>Zone:DNS:Edit</em> y <em>Zone:Zone:Read</em>.
                                        </p>
                                    </div>
                                    <button type="submit" class="kt-btn kt-btn-primary shrink-0">Guardar</button>
                                </form>

                                @if($cfToken)
                                    <div class="mt-4 flex items-center gap-2 text-xs text-success">
                                        <i class="ki-filled ki-check-circle"></i>
                                        Token configurado.
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Editor de registros vía Cloudflare --}}
                        @if($cfToken)
                            <div class="kt-card" x-data="dnsZoneEditor()">
                                <div class="kt-card-header">
                                    <div>
                                        <h2 class="kt-card-title">Registros DNS en Cloudflare</h2>
                                        <p class="mt-0.5 text-xs text-secondary-foreground">Estos registros se crean directamente en tu zona de Cloudflare vía API.</p>
                                    </div>
                                </div>
                                <form action="{{ route('client.websites.dns-zone-editor.store', $domain) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="_cf_mode" value="1">
                                    <div class="border-t border-border">
                                        <div class="hidden lg:grid px-5 pt-4 pb-2" style="grid-template-columns: 140px 1fr 2fr 110px 120px 44px">
                                            <span class="text-xs font-semibold text-secondary-foreground uppercase tracking-wide">Tipo</span>
                                            <span class="text-xs font-semibold text-secondary-foreground uppercase tracking-wide">Nombre</span>
                                            <span class="text-xs font-semibold text-secondary-foreground uppercase tracking-wide">Valor</span>
                                            <span class="text-xs font-semibold text-secondary-foreground uppercase tracking-wide">TTL</span>
                                            <span class="text-xs font-semibold text-secondary-foreground uppercase tracking-wide">Proxied</span>
                                            <span></span>
                                        </div>
                                        <template x-for="(row, idx) in rows" :key="idx">
                                            <div class="grid items-start gap-3 border-t border-border px-5 py-4" :style="'grid-template-columns: 140px 1fr 2fr 110px 120px 44px'">
                                                <div>
                                                    <select name="type[]" x-model="row.type" @change="onTypeChange(row)" class="kt-select w-full text-sm">
                                                        @foreach(['A','AAAA','CNAME','MX','TXT','NS','SRV','CAA'] as $t)
                                                            <option value="{{ $t }}">{{ $t }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="kt-input">
                                                    <input name="name[]" x-model="row.name" type="text" :placeholder="row.namePlaceholder || '@'" class="text-sm">
                                                </div>
                                                <div>
                                                    <div class="kt-input">
                                                        <input name="value[]" x-model="row.value" type="text" :placeholder="row.valuePlaceholder || ''" class="text-sm">
                                                    </div>
                                                    <p class="mt-1 text-xs text-secondary-foreground" x-show="row.hint" x-text="row.hint"></p>
                                                </div>
                                                <div>
                                                    <select name="ttl[]" x-model="row.ttl" class="kt-select w-full text-sm">
                                                        <option value="1">Auto</option>
                                                        <option value="300">5 min</option>
                                                        <option value="3600" selected>1 hora</option>
                                                        <option value="86400">24 horas</option>
                                                    </select>
                                                </div>
                                                <div class="flex items-center gap-2 pt-1">
                                                    <input type="checkbox" name="proxied[]" :value="idx" class="kt-checkbox" x-model="row.proxied">
                                                    <span class="text-xs text-secondary-foreground">Proxied</span>
                                                </div>
                                                <div class="flex items-center justify-center pt-1">
                                                    <button type="button" @click="removeRow(idx)" x-show="rows.length > 1"
                                                        class="flex size-9 items-center justify-center rounded-lg border border-border text-secondary-foreground hover:border-danger/40 hover:bg-danger/10 hover:text-danger transition">
                                                        <i class="ki-filled ki-cross text-sm"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex items-center justify-between border-t border-border px-5 py-3">
                                        <button type="button" @click="addRow" class="flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
                                            <i class="ki-filled ki-plus text-sm"></i>
                                            Añade más registros
                                        </button>
                                        <button type="submit" class="kt-btn kt-btn-primary">Añadir en Cloudflare</button>
                                    </div>
                                </form>
                            </div>

                            @include('client.web.advanced.partials.records-table', ['records' => $records, 'domain' => $domain])

                            {{-- SSL wildcard via Cloudflare --}}
                            @include('client.web.advanced.partials.ssl-card', ['domain' => $domain, 'mode' => 'cloudflare', 'cfToken' => $cfToken])
                        @endif

                    @endif
                    {{-- ── fin modos ──────────────────────────────────────────── --}}

                    @endif {{-- domainRecord --}}

                </div>
            </div>
        </main>

        @include('layouts.partials.client.footer')
    </div>
</div>

@push('scripts')
<script>
function dnsZoneEditor() {
    const hints = {
        A:'Dirección IPv4. Ej: 192.168.1.1', AAAA:'Dirección IPv6.', CNAME:'Alias. Ej: alias.example.com.',
        MX:'Servidor de correo. Ej: mail.example.com.', TXT:'Texto. Ej: v=spf1 include:example.com ~all',
        NS:'Nameserver. Ej: ns1.example.com.', SRV:'Formato: prioridad peso puerto destino.', CAA:'Ej: 0 issue "letsencrypt.org"',
    };
    const placeholders = {
        A:'1.2.3.4', AAAA:'2001:db8::1', CNAME:'alias.example.com.', MX:'mail.example.com.',
        TXT:'v=spf1 ...', NS:'ns1.example.com.', SRV:'10 20 443 target.example.com.', CAA:'0 issue "letsencrypt.org"',
    };
    function makeRow() {
        return { type:'A', name:'', value:'', ttl:'3600', proxied:false, hint:hints['A'], valuePlaceholder:placeholders['A'], namePlaceholder:'@' };
    }
    return {
        rows: [makeRow()],
        addRow() { this.rows.push(makeRow()); },
        removeRow(idx) { if (this.rows.length > 1) this.rows.splice(idx, 1); },
        onTypeChange(row) { row.hint = hints[row.type]||''; row.valuePlaceholder = placeholders[row.type]||''; },
    };
}
</script>
@endpush
@endsection
