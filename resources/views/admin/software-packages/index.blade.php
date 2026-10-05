@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
    <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&_.kt-container-fluid]:pe-4" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="mx-auto grid w-full max-w-7xl gap-5 lg:gap-7.5">

                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <div class="text-sm text-secondary-foreground">Servidor / Software</div>
                            <h1 class="mt-1 text-2xl font-semibold text-mono">Software del servidor</h1>
                            <p class="mt-1 text-sm text-secondary-foreground">Instala los paquetes una vez y decide cuáles pueden usar los hostings.</p>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-300">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-600 dark:text-red-300">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    @php
                        $renderCard = function ($pkg) {
                            $installed = $pkg['installed'] ?? false;
                            $active = $pkg['service_active'] ?? false;
                            $enabled = $pkg['enabled_for_clients'] ?? false;
                            $isDefault = $pkg['default'] ?? false;
                            return compact('pkg', 'installed', 'active', 'enabled', 'isDefault');
                        };
                    @endphp

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="kt-card"><div class="kt-card-content flex items-center gap-3 p-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="ki-filled ki-global text-xl"></i></span>
                            <div><div class="text-sm font-semibold text-mono">Entrada pública</div><p class="text-xs text-secondary-foreground">Nginx · puertos 80 y 443</p></div>
                        </div></div>
                        <div class="kt-card"><div class="kt-card-content flex items-center gap-3 p-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="ki-filled ki-code text-xl"></i></span>
                            <div><div class="text-sm font-semibold text-mono">Procesos del hosting</div><p class="text-xs text-secondary-foreground">PHP-FPM y aplicaciones separados</p></div>
                        </div></div>
                        <div class="kt-card"><div class="kt-card-content flex items-center gap-3 p-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="ki-filled ki-setting-2 text-xl"></i></span>
                            <div><div class="text-sm font-semibold text-mono">Apache opcional</div><p class="text-xs text-secondary-foreground">Servicio interno por hosting</p></div>
                        </div></div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header">
                            <div><h2 class="kt-card-title">Motores web</h2><p class="text-xs text-secondary-foreground">Apache se instala aquí; cada hosting habilitado usa su propio servicio interno.</p></div>
                        </div>
                        <div class="kt-card-content grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($webservers as $pkg)
                                @include('admin.software-packages._card', $renderCard($pkg))
                            @endforeach
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header">
                            <div><h2 class="kt-card-title">Versiones de PHP</h2><p class="text-xs text-secondary-foreground">Los procesos PHP-FPM se ejecutan dentro del hosting correspondiente.</p></div>
                        </div>
                        <div class="kt-card-content grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach($phpVersions as $pkg)
                                @include('admin.software-packages._card', $renderCard($pkg))
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </main>
        @include('layouts.partials.admin.footer')
    </div>
</div>
@endsection
