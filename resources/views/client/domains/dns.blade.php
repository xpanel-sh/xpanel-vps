@extends('layouts.client')

@section('content')
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">

            @include('client.domains.partials.tabs')

            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    <div>
                        <h1 class="text-2xl font-semibold text-mono">Editor de zonas DNS</h1>
                        <p class="mt-1 text-sm text-secondary-foreground">Elige un dominio de tu cuenta para gestionar sus registros DNS.</p>
                    </div>

                    @if($domains->isEmpty())
                        <div class="kt-card">
                            <div class="flex flex-col items-center justify-center gap-5 py-20 text-center">
                                <span class="flex size-20 items-center justify-center rounded-full bg-muted">
                                    <i class="ki-filled ki-setting-2 text-4xl text-secondary-foreground opacity-50"></i>
                                </span>
                                <div>
                                    <h2 class="text-lg font-semibold text-mono">Sin dominios registrados</h2>
                                    <p class="mt-1 text-sm text-secondary-foreground">Agrega un dominio primero para poder gestionar sus registros DNS.</p>
                                </div>
                                <a href="{{ route('client.domains.create') }}" class="kt-btn kt-btn-primary">
                                    <i class="ki-filled ki-plus"></i>
                                    Agregar dominio
                                </a>
                            </div>
                        </div>
                    @else
                        {{-- ── Domain picker card ───────────────────────────────────── --}}
                        <div class="kt-card" x-data="{ open: false }" @click.outside="open = false">
                            <div class="kt-card-content p-6">
                                <p class="text-sm font-semibold text-mono mb-5">Elige un dominio para gestionar</p>
                                <p class="text-xs text-secondary-foreground mb-5 -mt-3">Te llevaremos directamente a sus registros DNS.</p>

                                {{-- Dropdown --}}
                                <div class="relative max-w-sm">
                                    <button type="button"
                                            class="flex w-full items-center justify-between gap-3 rounded-xl border border-border bg-background px-4 py-3 text-sm hover:border-primary/50 transition"
                                            @click="open = !open">
                                        <span class="flex items-center gap-2 text-secondary-foreground">
                                            <i class="ki-filled ki-globe"></i>
                                            Selecciona un dominio
                                        </span>
                                        <i class="ki-filled ki-down text-secondary-foreground text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
                                    </button>

                                    <div x-show="open" x-cloak x-transition
                                         class="absolute left-0 top-full z-20 mt-1 w-full rounded-xl border border-border bg-background shadow-xl overflow-hidden">
                                        @foreach($domains as $dom)
                                            <a href="{{ route('client.websites.dns-zone-editor', $dom->domain) }}"
                                               class="flex items-center gap-3 px-4 py-3.5 text-sm text-mono hover:bg-muted transition border-b border-border last:border-b-0">
                                                <i class="ki-filled ki-globe text-secondary-foreground shrink-0"></i>
                                                <span class="grow">{{ $dom->domain }}</span>
                                                <i class="ki-filled ki-right text-secondary-foreground text-xs"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Recent domains chips --}}
                                <div class="mt-5 flex flex-wrap items-center gap-2">
                                    <span class="text-xs text-secondary-foreground">O salta a uno reciente:</span>
                                    @foreach($domains->take(6) as $dom)
                                        <a href="{{ route('client.websites.dns-zone-editor', $dom->domain) }}"
                                           class="rounded-full border border-border bg-muted px-3 py-1.5 text-xs font-medium text-mono hover:border-primary/40 hover:bg-primary/5 hover:text-primary transition">
                                            {{ $dom->domain }}
                                        </a>
                                    @endforeach
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
