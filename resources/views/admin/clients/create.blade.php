@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="grid gap-5 lg:gap-7.5">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="font-medium text-lg text-mono">Nuevo cliente</h1>
                <div class="flex items-center gap-1 text-sm">
                    <a class="text-secondary-foreground hover:text-primary" href="{{ route('admin.clients.index') }}">Clientes</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-mono">Nuevo</span>
                </div>
            </div>
            <a href="{{ route('admin.clients.index') }}" class="kt-btn kt-btn-outline kt-btn-sm">Volver</a>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-lg text-red-400">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.clients.store') }}" method="POST" class="grid gap-5 lg:gap-7.5">
            @csrf

            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Cliente</h3>
                </div>
                <div class="kt-card-content grid gap-5">
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Empresa</label>
                        <input class="kt-input" type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Mi Cliente S.L." required>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Dominio del panel</label>
                        <input class="kt-input" type="text" name="domain" value="{{ old('domain') }}" placeholder="cliente.com" required>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Plan</label>
                        <select name="plan_id" class="kt-select">
                            <option value="">Sin plan por ahora</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                    {{ $plan->name }} - {{ $plan->max_sites }} sitios / {{ $plan->max_databases }} DB / {{ number_format($plan->storage_mb / 1024, 1) }} GB
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Usuario principal</h3>
                </div>
                <div class="kt-card-content grid gap-5">
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Nombre</label>
                        <input class="kt-input" type="text" name="owner_name" value="{{ old('owner_name') }}" placeholder="Juan Perez" required>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Email</label>
                        <input class="kt-input" type="email" name="owner_email" value="{{ old('owner_email') }}" placeholder="email@cliente.com" required>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Contrasena temporal</label>
                        <div class="grow grid gap-1.5">
                            <div class="kt-input">
                                <input class="grow" type="text" name="owner_password" id="pass" value="{{ Str::random(12) }}" required>
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline -me-2" onclick="document.getElementById('pass').value = Math.random().toString(36).slice(-12)">Generar</button>
                            </div>
                            <p class="kt-form-description">Copia esta contrasena para entregarla al cliente.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2.5">
                        <a href="{{ route('admin.clients.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
                        <button type="submit" class="kt-btn kt-btn-primary">Crear cliente</button>
                    </div>
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
