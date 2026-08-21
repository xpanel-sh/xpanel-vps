@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
    <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&_.kt-container-fluid]:pe-4" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <h1 class="font-medium text-lg text-mono">Paquetes de software</h1>
                            <p class="text-sm text-secondary-foreground mt-0.5">
                                Motores web y versiones de PHP instalables en el servidor. Los sitios de los clientes
                                se sirven con estos paquetes de forma nativa (sin Docker por sitio).
                            </p>
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

                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Servidores web</h3>
                        </div>
                        <div class="kt-card-content grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($webservers as $pkg)
                                @include('admin.software-packages._card', $renderCard($pkg))
                            @endforeach
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Versiones de PHP</h3>
                        </div>
                        <div class="kt-card-content grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
