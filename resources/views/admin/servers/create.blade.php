@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="grid gap-5 lg:gap-7.5 max-w-4xl">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="font-medium text-lg text-mono">Agregar servidor conectado</h1>
                <div class="flex items-center gap-1 text-sm">
                    <a class="text-secondary-foreground hover:text-primary" href="{{ route('admin.servers.index') }}">Servidores</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-mono">Nuevo</span>
                </div>
            </div>
            <a href="{{ route('admin.servers.index') }}" class="kt-btn kt-btn-outline kt-btn-sm">Volver</a>
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

        <form action="{{ route('admin.servers.store') }}" method="POST" class="kt-card">
            @csrf

            <div class="kt-card-header">
                <h3 class="kt-card-title">Conexion del agente</h3>
            </div>
            <div class="kt-card-content grid gap-5">
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Nombre</label>
                    <input class="kt-input" type="text" name="name" value="{{ old('name') }}" required placeholder="Servidor LATAM 1">
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">IP</label>
                    <input class="kt-input" type="text" name="ip_address" value="{{ old('ip_address') }}" required placeholder="10.0.0.10">
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Puerto del agente</label>
                    <input class="kt-input" type="number" name="port" value="{{ old('port', 7070) }}" required>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Token</label>
                    <div class="grow grid gap-1.5">
                        <input class="kt-input" type="text" name="auth_token" value="{{ old('auth_token') }}" placeholder="Si lo dejas vacio se genera uno">
                        <p class="kt-form-description">El token se usa para conectar el panel con el daemon del servidor.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2.5">
                    <a href="{{ route('admin.servers.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
                    <button type="submit" class="kt-btn kt-btn-primary">Agregar servidor</button>
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
