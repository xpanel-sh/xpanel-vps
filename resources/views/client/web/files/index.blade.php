@extends('layouts.client')

@php
    $tenant = request()->attributes->get('tenant');
@endphp

@section('content')
    <div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
        <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="flex flex-col gap-3">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="kt-badge kt-badge-outline kt-badge-primary">Archivos</span>
                                        <span class="text-xs text-secondary-foreground uppercase">{{ $site->domain }}</span>
                                    </div>
                                    <h1 class="text-2xl font-semibold text-mono">Administrador de archivos</h1>
                                    <p class="mt-1 text-sm text-secondary-foreground">
                                        Elige si quieres trabajar solo con este dominio o abrir la raiz completa de tus sitios.
                                    </p>
                                </div>

                                <a class="kt-btn kt-btn-outline" href="{{ route('client.websites.show', ['domain' => $site->domain]) }}">
                                    <i class="ki-filled ki-left"></i>
                                    Volver al panel
                                </a>
                            </div>
                        </div>

                        <div class="grid gap-5 xl:grid-cols-2">
                            <a class="kt-card group transition hover:border-primary hover:shadow-lg" href="{{ route('client.website.file-manager.ikode', ['domain' => $site->domain]) }}">
                                <div class="kt-card-content p-7">
                                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                                        <div class="flex size-20 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                            <i class="ki-filled ki-files text-3xl"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h2 class="text-xl font-semibold text-mono group-hover:text-primary">
                                                Acceder a los archivos de {{ $site->domain }}
                                            </h2>
                                            <p class="mt-2 text-sm text-secondary-foreground">
                                                Accede a archivos solo de este sitio web especifico desde el editor ikode.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>

                            <a class="kt-card group transition hover:border-primary hover:shadow-lg" href="{{ route('client.website.file-manager.root') }}">
                                <div class="kt-card-content p-7">
                                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                                        <div class="flex size-20 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                            <i class="ki-filled ki-folder text-3xl"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h2 class="text-xl font-semibold text-mono group-hover:text-primary">
                                                Acceder a todos los archivos de {{ $tenant->name ?? 'XPanel Hosting' }}
                                            </h2>
                                            <p class="mt-2 text-sm text-secondary-foreground">
                                                Accede a todos los archivos de tus sitios web en un solo lugar.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
