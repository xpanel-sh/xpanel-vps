@extends('layouts.client')

@section('content')
@php
    $phpOptions = $site->php_options ?? [];
    $currentVersion = $site->php_version ?? '8.2';
    $isPhp = $site->project_type === 'php';
    $isNative = \App\Services\SiteProvisioner::isNativeType($site->project_type);
    $currentEngine = $site->web_server ?? 'nginx';
    $engineLabels = ['nginx' => 'Nginx', 'apache' => 'Apache', 'openlitespeed' => 'OpenLiteSpeed'];
    $availableEngines = $isNative ? app(\App\Services\SiteProvisioner::class)->availableEngines() : [];
    $versions = ['8.1', '8.2', '8.3', '8.4'];
    $extensions = [
        ['name' => 'pdo_mysql',  'label' => 'PDO MySQL',   'desc' => 'Conexiones MySQL via PDO'],
        ['name' => 'mysqli',     'label' => 'MySQLi',       'desc' => 'Extensión MySQL mejorada'],
        ['name' => 'gd',         'label' => 'GD',           'desc' => 'Procesamiento de imágenes'],
        ['name' => 'mbstring',   'label' => 'Multibyte',    'desc' => 'Cadenas multibyte (UTF-8)'],
        ['name' => 'intl',       'label' => 'Intl',         'desc' => 'Internacionalización'],
        ['name' => 'zip',        'label' => 'Zip',          'desc' => 'Comprimir y descomprimir'],
        ['name' => 'opcache',    'label' => 'OPcache',      'desc' => 'Caché de bytecode PHP'],
        ['name' => 'bcmath',     'label' => 'BCMath',       'desc' => 'Precisión matemática arbitraria'],
        ['name' => 'curl',       'label' => 'cURL',         'desc' => 'Peticiones HTTP'],
        ['name' => 'json',       'label' => 'JSON',         'desc' => 'Codificación/decodificación JSON'],
        ['name' => 'xml',        'label' => 'XML',          'desc' => 'Procesamiento XML'],
        ['name' => 'openssl',    'label' => 'OpenSSL',      'desc' => 'Cifrado y SSL'],
    ];
@endphp

<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5"
     x-data="{ tab: '{{ session('php_tab', $isPhp ? 'version' : 'engine') }}' }">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- Header --}}
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="kt-badge kt-badge-outline kt-badge-primary">Avanzado</span>
                                <span class="text-xs text-secondary-foreground uppercase tracking-wide">{{ $site->domain }}</span>
                            </div>
                            <h1 class="mt-2 text-2xl font-semibold text-mono">Configuración PHP</h1>
                            <p class="mt-1 max-w-2xl text-sm text-secondary-foreground">
                                Cambiar la versión o las opciones de PHP reiniciará el sitio. Verifica compatibilidad antes de actualizar.
                            </p>
                        </div>
                        @if($isNative)
                        <div class="flex items-center gap-2 shrink-0">
                            @if($isPhp)
                                <span class="kt-badge kt-badge-success">PHP {{ $currentVersion }}</span>
                            @endif
                            <span class="kt-badge kt-badge-outline">{{ $engineLabels[$currentEngine] ?? ucfirst($currentEngine) }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Alertas --}}
                    @if(session('success'))
                        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if(!$isNative)
                    {{-- Sitio no nativo (node/python siguen usando contenedor Docker) --}}
                    <div class="kt-card">
                        <div class="kt-card-content p-8 text-center">
                            <i class="ki-filled ki-code text-4xl text-secondary-foreground mb-3"></i>
                            <h3 class="text-lg font-semibold mb-1">No disponible</h3>
                            <p class="text-sm text-secondary-foreground">
                                La configuración de PHP/motor web solo aplica a sitios PHP o estáticos.<br>
                                Este sitio es de tipo <span class="font-mono">{{ $site->project_type }}</span>.
                            </p>
                        </div>
                    </div>
                    @else
                    {{-- Card con tabs --}}
                    <div class="kt-card">
                        {{-- Tab headers --}}
                        <div class="border-b border-border">
                            <div class="flex overflow-x-auto">
                                <button @click="tab='engine'"
                                        :class="tab==='engine'
                                            ? 'border-b-2 border-primary text-primary'
                                            : 'border-b-2 border-transparent text-secondary-foreground hover:text-foreground'"
                                        class="px-6 py-4 text-sm font-medium whitespace-nowrap transition-colors">
                                    Motor web
                                </button>
                                @if($isPhp)
                                <button @click="tab='version'"
                                        :class="tab==='version'
                                            ? 'border-b-2 border-primary text-primary'
                                            : 'border-b-2 border-transparent text-secondary-foreground hover:text-foreground'"
                                        class="px-6 py-4 text-sm font-medium whitespace-nowrap transition-colors">
                                    Versión PHP
                                </button>
                                <button @click="tab='extensions'"
                                        :class="tab==='extensions'
                                            ? 'border-b-2 border-primary text-primary'
                                            : 'border-b-2 border-transparent text-secondary-foreground hover:text-foreground'"
                                        class="px-6 py-4 text-sm font-medium whitespace-nowrap transition-colors">
                                    Extensiones PHP
                                </button>
                                <button @click="tab='options'"
                                        :class="tab==='options'
                                            ? 'border-b-2 border-primary text-primary'
                                            : 'border-b-2 border-transparent text-secondary-foreground hover:text-foreground'"
                                        class="px-6 py-4 text-sm font-medium whitespace-nowrap transition-colors">
                                    Opciones de PHP
                                </button>
                                @endif
                            </div>
                        </div>

                        <div class="p-6">

                            {{-- ── Tab: Motor web ── --}}
                            <div x-show="tab==='engine'" x-cloak>
                                <div class="max-w-lg">
                                    <h3 class="text-base font-semibold mb-1">Motor web</h3>
                                    <p class="text-sm text-secondary-foreground mb-6">
                                        Este sitio se sirve de forma nativa (sin Docker). Elige qué motor lo maneja —
                                        puedes cambiarlo en cualquier momento sin perder tus archivos.
                                    </p>

                                    <form method="POST"
                                          action="{{ route('client.websites.engine', $site->domain) }}">
                                        @csrf

                                        <div class="space-y-3 mb-8">
                                            @forelse($availableEngines as $engine)
                                            <label class="flex items-center gap-3 cursor-pointer rounded-xl border px-4 py-3 transition-colors
                                                          {{ $currentEngine === $engine ? 'border-primary bg-primary/5' : 'border-border hover:bg-accent' }}">
                                                <input type="radio" name="web_server" value="{{ $engine }}"
                                                       {{ $currentEngine === $engine ? 'checked' : '' }}
                                                       class="accent-primary">
                                                <span class="font-medium">{{ $engineLabels[$engine] ?? ucfirst($engine) }}</span>
                                                @if($currentEngine === $engine)
                                                    <span class="ml-auto text-xs text-primary font-semibold">Activo</span>
                                                @endif
                                            </label>
                                            @empty
                                            <p class="text-sm text-secondary-foreground">
                                                No hay motores web disponibles en este servidor. Contacta al administrador.
                                            </p>
                                            @endforelse
                                        </div>

                                        <div class="rounded-lg border border-amber-500/20 bg-amber-500/5 px-4 py-3 text-xs text-amber-300 mb-6">
                                            ⚠ Cambiar el motor web reconfigura el vhost del sitio, sin downtime.
                                        </div>

                                        <button type="submit" class="kt-btn kt-btn-primary" @disabled(empty($availableEngines))>
                                            Cambiar motor
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- ── Tab: Versión ── --}}
                            <div x-show="tab==='version'" x-cloak>
                                <div class="max-w-lg">
                                    <h3 class="text-base font-semibold mb-1">Versión</h3>
                                    <p class="text-sm text-secondary-foreground mb-6">
                                        Elige qué versión de PHP quieres activar para este sitio.
                                    </p>

                                    <form method="POST"
                                          action="{{ route('client.websites.php-version', $site->domain) }}">
                                        @csrf

                                        <div class="space-y-3 mb-8">
                                            @foreach($versions as $v)
                                            <label class="flex items-center gap-3 cursor-pointer rounded-xl border px-4 py-3 transition-colors
                                                          {{ $currentVersion === $v ? 'border-primary bg-primary/5' : 'border-border hover:bg-accent' }}">
                                                <input type="radio" name="php_version" value="{{ $v }}"
                                                       {{ $currentVersion === $v ? 'checked' : '' }}
                                                       class="accent-primary">
                                                <span class="font-medium">PHP {{ $v }}</span>
                                                @if($currentVersion === $v)
                                                    <span class="ml-auto text-xs text-primary font-semibold">Activo</span>
                                                @endif
                                            </label>
                                            @endforeach
                                        </div>

                                        <div class="rounded-lg border border-amber-500/20 bg-amber-500/5 px-4 py-3 text-xs text-amber-300 mb-6">
                                            ⚠ Cambiar la versión de PHP reiniciará el contenedor del sitio (1-2 min).
                                        </div>

                                        <button type="submit"
                                                class="kt-btn kt-btn-primary">
                                            Actualizar
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- ── Tab: Extensiones ── --}}
                            <div x-show="tab==='extensions'" x-cloak>
                                <div class="mb-6">
                                    <h3 class="text-base font-semibold mb-1">Extensiones instaladas</h3>
                                    <p class="text-sm text-secondary-foreground">
                                        Todas las extensiones siguientes están pre-instaladas en los sitios PHP de XPanel.
                                    </p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($extensions as $ext)
                                    <div class="flex items-center gap-3 rounded-xl border border-border bg-accent/30 px-4 py-3">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                                            <i class="ki-filled ki-check text-sm text-emerald-400"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold font-mono">{{ $ext['label'] }}</p>
                                            <p class="text-xs text-secondary-foreground truncate">{{ $ext['desc'] }}</p>
                                        </div>
                                        <span class="ml-auto shrink-0 rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-400">
                                            Activo
                                        </span>
                                    </div>
                                    @endforeach
                                </div>

                                <p class="mt-5 text-xs text-secondary-foreground">
                                    Las extensiones se gestionan a nivel de imagen Docker. Están disponibles para todos los sitios PHP.
                                </p>
                            </div>

                            {{-- ── Tab: Opciones ── --}}
                            <div x-show="tab==='options'" x-cloak>
                                <div class="max-w-lg">
                                    <h3 class="text-base font-semibold mb-1">Opciones de PHP</h3>
                                    <p class="text-sm text-secondary-foreground mb-6">
                                        Estos valores se escriben en <code class="font-mono text-xs">php.ini</code> del sitio.
                                        Guardar reiniciará el contenedor.
                                    </p>

                                    <form method="POST"
                                          action="{{ route('client.websites.php-options', $site->domain) }}">
                                        @csrf

                                        @php
                                            $opt = fn(string $key, string $default) => $phpOptions[$key] ?? $default;
                                        @endphp

                                        <div class="space-y-5 mb-8">

                                            <div class="grid grid-cols-2 gap-4 items-center">
                                                <div>
                                                    <label class="text-sm font-medium">memory_limit</label>
                                                    <p class="text-xs text-secondary-foreground">Memoria máxima por script</p>
                                                </div>
                                                <select name="memory_limit" class="kt-select w-full">
                                                    @foreach(['64M','128M','256M','512M','1024M'] as $v)
                                                        <option value="{{ $v }}" {{ $opt('memory_limit','128M') === $v ? 'selected' : '' }}>{{ $v }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4 items-center">
                                                <div>
                                                    <label class="text-sm font-medium">upload_max_filesize</label>
                                                    <p class="text-xs text-secondary-foreground">Tamaño máximo de archivo subido</p>
                                                </div>
                                                <select name="upload_max_filesize" class="kt-select w-full">
                                                    @foreach(['8M','32M','64M','128M','256M'] as $v)
                                                        <option value="{{ $v }}" {{ $opt('upload_max_filesize','64M') === $v ? 'selected' : '' }}>{{ $v }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4 items-center">
                                                <div>
                                                    <label class="text-sm font-medium">post_max_size</label>
                                                    <p class="text-xs text-secondary-foreground">Tamaño máximo del body POST</p>
                                                </div>
                                                <select name="post_max_size" class="kt-select w-full">
                                                    @foreach(['8M','32M','64M','128M','256M'] as $v)
                                                        <option value="{{ $v }}" {{ $opt('post_max_size','64M') === $v ? 'selected' : '' }}>{{ $v }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4 items-center">
                                                <div>
                                                    <label class="text-sm font-medium">max_execution_time</label>
                                                    <p class="text-xs text-secondary-foreground">Tiempo máximo de ejecución (seg)</p>
                                                </div>
                                                <select name="max_execution_time" class="kt-select w-full">
                                                    @foreach(['30','60','120','300'] as $v)
                                                        <option value="{{ $v }}" {{ $opt('max_execution_time','60') === $v ? 'selected' : '' }}>{{ $v }}s</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4 items-center">
                                                <div>
                                                    <label class="text-sm font-medium">max_input_time</label>
                                                    <p class="text-xs text-secondary-foreground">Tiempo máximo leyendo datos (seg)</p>
                                                </div>
                                                <select name="max_input_time" class="kt-select w-full">
                                                    @foreach(['30','60','120','300'] as $v)
                                                        <option value="{{ $v }}" {{ $opt('max_input_time','60') === $v ? 'selected' : '' }}>{{ $v }}s</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                        </div>

                                        <div class="rounded-lg border border-amber-500/20 bg-amber-500/5 px-4 py-3 text-xs text-amber-300 mb-6">
                                            ⚠ Guardar reiniciará el contenedor del sitio para aplicar los cambios.
                                        </div>

                                        <button type="submit" class="kt-btn kt-btn-primary">
                                            Guardar cambios
                                        </button>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </main>

        @include('layouts.partials.client.footer')
    </div>
</div>
@endsection
