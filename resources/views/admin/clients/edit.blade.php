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
                <h1 class="font-medium text-lg text-mono">Editar cliente</h1>
                <div class="flex items-center gap-1 text-sm">
                    <a class="text-secondary-foreground hover:text-primary" href="{{ route('admin.clients.show', $tenant) }}">{{ $tenant->name }}</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-mono">Editar</span>
                </div>
            </div>
            <a href="{{ route('admin.clients.show', $tenant) }}" class="kt-btn kt-btn-outline kt-btn-sm">Volver</a>
        </div>

        <form action="{{ route('admin.clients.update', $tenant) }}" method="POST" class="grid gap-5 lg:gap-7.5">
            @csrf
            @method('PUT')

            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Cliente</h3></div>
                <div class="kt-card-content grid gap-5">
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Nombre / Empresa</label>
                        <input class="kt-input" type="text" name="company_name" value="{{ old('company_name', $tenant->name) }}" required>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Dominio principal</label>
                        <input class="kt-input" type="text" name="domain" value="{{ old('domain', $tenant->domain) }}" required>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Estado</label>
                        <select name="status" class="kt-select">
                            <option value="active" @selected(old('status', $tenant->status) === 'active')>Activo</option>
                            <option value="suspended" @selected(old('status', $tenant->status) === 'suspended')>Suspendido</option>
                        </select>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Plan</label>
                        <select name="plan_id" class="kt-select">
                            <option value="">Sin plan</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('plan_id', $tenant->plan_id) == $plan->id)>
                                    {{ $plan->name }} - {{ $plan->max_sites }} sitios / {{ $plan->max_databases }} DB
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Usuario principal</h3></div>
                <div class="kt-card-content grid gap-5">
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Nombre</label>
                        <input class="kt-input" type="text" name="owner_name" value="{{ old('owner_name', $tenant->user?->name) }}" required>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Email</label>
                        <input class="kt-input" type="email" name="owner_email" value="{{ old('owner_email', $tenant->user?->email) }}" required>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Nueva contrasena</label>
                        <input class="kt-input" type="text" name="owner_password" value="{{ old('owner_password') }}" placeholder="Dejar vacio para mantener la actual">
                    </div>
                    <div class="flex justify-end gap-2.5">
                        <a href="{{ route('admin.clients.show', $tenant) }}" class="kt-btn kt-btn-outline">Cancelar</a>
                        <button class="kt-btn kt-btn-primary">Guardar cambios</button>
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
