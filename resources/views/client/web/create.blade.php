@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="grid gap-5 lg:gap-7.5">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="font-medium text-lg text-mono">Nuevo sitio web</h1>
                <div class="flex items-center gap-1 text-sm">
                    <a class="text-secondary-foreground hover:text-primary" href="{{ route('client.websites.index') }}">Sitios</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-mono">Nuevo</span>
                </div>
            </div>
            <a href="{{ route('client.websites.index') }}" class="kt-btn kt-btn-outline kt-btn-sm">Volver</a>
        </div>

        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Proyecto</h3>
            </div>
            <form action="{{ route('client.sites.store') }}" method="POST">
                @csrf

                <div class="kt-card-content grid gap-5">
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label for="domain" class="kt-form-label max-w-56">Dominio</label>
                        <div class="grow grid gap-1.5">
                            <div class="kt-input">
                                <input class="grow" type="text" name="domain" id="domain" placeholder="ejemplo.com" required>
                                <span class="text-xs text-secondary-foreground">HTTPS</span>
                            </div>
                            <p class="kt-form-description">No incluyas http:// o https://.</p>
                        </div>
                    </div>

                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">Tipo de proyecto</label>
                        <div class="grow grid grid-cols-2 gap-3">
                        <label class="cursor-pointer relative">
                            <input type="radio" name="project_type" value="php" class="peer sr-only" checked
                                onchange="toggleOptions('php')">
                            <div
                                class="p-4 border border-border rounded-lg hover:border-primary peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition text-center h-full flex flex-col items-center justify-center gap-1">
                                <i class="ki-filled ki-code text-xl"></i>
                                <span class="block font-medium text-sm">PHP</span>
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="project_type" value="static" class="peer sr-only"
                                onchange="toggleOptions('static')">
                            <div
                                class="p-4 border border-border rounded-lg hover:border-primary peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition text-center h-full flex flex-col items-center justify-center gap-1">
                                <i class="ki-filled ki-html text-xl"></i>
                                <span class="block font-medium text-sm">Static</span>
                            </div>
                        </label>
                        </div>
                        <p class="kt-form-description w-full">
                            Node.js, Python y stacks Compose se despliegan desde el módulo Docker cuando el administrador lo habilita.
                        </p>
                    </div>

                    <div id="engine-options">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label max-w-56">Servidor web</label>
                            <div class="grow grid grid-cols-1 gap-3 md:grid-cols-2">
                                <select name="web_server" class="kt-select">
                                    @foreach($availableEngines as $engine)
                                        <option value="{{ $engine }}" {{ $engine === 'nginx' ? 'selected' : '' }}>
                                            {{ ['nginx' => 'Nginx', 'apache' => 'Apache', 'openlitespeed' => 'OpenLiteSpeed'][$engine] ?? ucfirst($engine) }}
                                        </option>
                                    @endforeach
                                </select>
                                <select name="php_version" id="php-version-select" class="kt-select">
                                    @foreach($availablePhpVersions as $version)
                                        <option value="{{ $version }}" {{ $version === '8.3' ? 'selected' : '' }}>
                                            PHP {{ $version }}{{ $version === '8.3' ? ' (Recomendado)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="kt-form-description w-full">
                                El sitio se sirve nativamente con el motor elegido (sin Docker).
                            </p>
                        </div>
                    </div>
                </div>

                <script>
                    function toggleOptions(type) {
                        const engineOptions = document.getElementById('engine-options');
                        const phpVersion = document.getElementById('php-version-select');
                        if (type === 'php' || type === 'static') {
                            engineOptions.classList.remove('hidden');
                        } else {
                            engineOptions.classList.add('hidden');
                        }
                        phpVersion.style.display = (type === 'php') ? '' : 'none';
                    }
                </script>

                <div class="kt-card-footer justify-end gap-2.5">
                    <a href="{{ route('client.websites.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
                    <button type="submit" class="kt-btn kt-btn-primary">Crear sitio web</button>
                </div>
            </form>
        </div>
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
