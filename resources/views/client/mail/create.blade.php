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
                <h1 class="font-medium text-lg text-mono">Crear correo</h1>
                <div class="flex items-center gap-1 text-sm">
                    <a class="text-secondary-foreground hover:text-primary" href="{{ route('client.mail.index') }}">Correos</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-mono">Nuevo</span>
                </div>
            </div>
            <a href="{{ route('client.mail.index') }}" class="kt-btn kt-btn-outline kt-btn-sm">Volver</a>
        </div>

        <form action="{{ route('client.mail.store') }}" method="POST" class="kt-card">
            @csrf
            <div class="kt-card-header">
                <h3 class="kt-card-title">Cuenta de correo</h3>
            </div>
            <div class="kt-card-content grid gap-5">
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Usuario</label>
                    <input class="kt-input" type="text" name="local_part" value="{{ old('local_part') }}" required placeholder="ventas">
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Dominio</label>
                    <select name="domain_id" class="kt-select" required>
                        <option value="">Seleccionar dominio</option>
                        @foreach($domains as $domain)
                            <option value="{{ $domain->id }}" @selected(old('domain_id') == $domain->id)>@{{ $domain->domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Contrasena</label>
                    <div class="grow grid gap-1.5">
                        <input class="kt-input" type="password" name="password" required minlength="12" autocomplete="new-password" placeholder="Minimo 12 caracteres">
                        <p class="kt-form-description">XPanel no mostrara esta contrasena despues de crearla.</p>
                    </div>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Cuota MB</label>
                    <input class="kt-input" type="number" name="quota_mb" value="{{ old('quota_mb', 1024) }}" min="128" max="102400" required>
                </div>
                <div class="rounded-md border border-warning/30 bg-warning/10 p-4 text-sm text-warning">
                    La cuenta queda registrada en XPanel y el agente actualiza los artefactos reales del servicio de correo.
                </div>
                <div class="flex justify-end gap-2.5">
                    <a href="{{ route('client.mail.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
                    <button class="kt-btn kt-btn-primary">Crear correo</button>
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
