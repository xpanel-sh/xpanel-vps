@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="grid gap-5 lg:gap-7.5 max-w-4xl">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="font-medium text-lg text-mono">Agregar dominio</h1>
                <div class="flex items-center gap-1 text-sm">
                    <a class="text-secondary-foreground hover:text-primary" href="{{ route('client.domains.index') }}">Dominios</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-mono">Nuevo</span>
                </div>
            </div>
            <a href="{{ route('client.domains.index') }}" class="kt-btn kt-btn-outline kt-btn-sm">Volver</a>
        </div>

        <form action="{{ route('client.domains.store') }}" method="POST" class="kt-card">
            @csrf
            <div class="kt-card-header">
                <h3 class="kt-card-title">Dominio</h3>
            </div>
            <div class="kt-card-content grid gap-5">
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Dominio</label>
                    <div class="grow grid gap-1.5">
                        <input class="kt-input" type="text" name="domain" value="{{ old('domain') }}" required placeholder="example.com">
                        <p class="kt-form-description">Sin http:// ni https://.</p>
                    </div>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Tipo</label>
                    <select name="type" class="kt-select">
                        <option value="primary" @selected(old('type') === 'primary')>Principal</option>
                        <option value="alias" @selected(old('type') === 'alias')>Alias</option>
                        <option value="subdomain" @selected(old('type') === 'subdomain')>Subdominio</option>
                    </select>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Sitio asociado</label>
                    <select name="site_id" class="kt-select">
                        <option value="">Sin asociar todavia</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2.5">
                    <a href="{{ route('client.domains.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
                    <button class="kt-btn kt-btn-primary">Guardar dominio</button>
                </div>
            </div>
        </form>
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
