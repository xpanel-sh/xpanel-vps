@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="grid gap-5 lg:gap-7.5 max-w-4xl">
        <div>
            <h1 class="font-medium text-lg text-mono">Nameservers</h1>
            <div class="flex items-center gap-1 text-sm">
                <span class="text-secondary-foreground">DNS</span>
                <span class="text-muted-foreground">/</span>
                <span class="text-mono">Nameservers</span>
            </div>
        </div>

        <form action="{{ route('admin.dns.nameservers.update') }}" method="POST" class="kt-card">
            @csrf
            @method('PUT')
            <div class="kt-card-header">
                <h3 class="kt-card-title">Configuracion DNS</h3>
            </div>
            <div class="kt-card-content grid gap-5">
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Proveedor principal</label>
                    <select name="provider" class="kt-select">
                        <option value="xpanel" @selected($settings->provider === 'xpanel')>XPanel NS propio</option>
                        <option value="cloudflare" @selected($settings->provider === 'cloudflare')>Cloudflare</option>
                        <option value="external" @selected($settings->provider === 'external')>Externo/manual</option>
                    </select>
                </div>
                @foreach(['ns1', 'ns2', 'ns3', 'ns4'] as $ns)
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">{{ strtoupper($ns) }}</label>
                        <input class="kt-input" type="text" name="{{ $ns }}" value="{{ old($ns, $settings->{$ns}) }}" placeholder="{{ $ns }}.xpanel.sh">
                    </div>
                @endforeach
                <label class="kt-label">
                    <input class="kt-switch" type="checkbox" name="is_active" value="1" @checked($settings->is_active)>
                    Mostrar estos NS a clientes
                </label>
                <div class="rounded-md border border-warning/30 bg-warning/10 p-4 text-sm text-warning">
                    Esto configura la capa del panel. Para DNS autoritativo real se conectara luego el agente con PowerDNS/CoreDNS o proveedor equivalente.
                </div>
                <div class="flex justify-end">
                    <button class="kt-btn kt-btn-primary">Guardar nameservers</button>
                </div>
            </div>
        </form>
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
