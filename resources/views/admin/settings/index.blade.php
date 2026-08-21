@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<div class="flex flex-col gap-6 max-w-xl">

    <div>
        <h1 class="text-xl font-semibold text-mono">Configuración del sistema</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Ajustes generales del panel visible para tus clientes.
        </p>
    </div>

    @if (session('status'))
        <div class="flex items-center gap-2 rounded-lg bg-success/10 border border-success/20 px-4 py-3 text-sm text-success">
            <i class="ki-filled ki-check-circle text-base shrink-0"></i>
            {{ session('status') }}
        </div>
    @endif

    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Nombre del panel</h3>
        </div>
        <div class="kt-card-content p-6">
            <form action="{{ route('admin.settings.update') }}" method="POST" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-1.5">
                    <label class="kt-form-label font-normal text-mono" for="app_name">
                        Nombre visible del panel
                    </label>
                    <input
                        id="app_name"
                        class="kt-input @error('app_name') border-danger @enderror"
                        type="text"
                        name="app_name"
                        value="{{ old('app_name', $appName) }}"
                        placeholder="XPanel"
                        maxlength="80"
                        required
                    />
                    @error('app_name')
                        <p class="text-xs text-danger">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-muted-foreground">
                        Este nombre aparece en los logins y cabeceras del panel de clientes.
                        El sistema interno siempre se llama <strong>XPanel</strong>.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check text-base"></i>
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
