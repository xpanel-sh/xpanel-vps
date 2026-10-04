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
                    <div>
                        <h3 class="kt-card-title">Cliente</h3>
                        <p class="kt-form-description mt-1">Aquí solo registras al cliente comercial. Cada hosting tendrá después su propio plan y administrador.</p>
                    </div>
                </div>
                <div class="kt-card-content grid gap-5">
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Empresa</label>
                        <input class="kt-input" type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Mi Cliente S.L." required>
                    </div>
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Dominio de referencia</label>
                        <input class="kt-input" type="text" name="domain" value="{{ old('domain') }}" placeholder="cliente.com" required>
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
