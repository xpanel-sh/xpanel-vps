@extends('layouts.client')

@section('content')
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">

            @include('client.domains.partials.tabs')

            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- ── Hero search ─────────────────────────────────────────────── --}}
                    <div class="kt-card overflow-hidden">
                        <div class="relative flex flex-col items-center justify-center gap-6 px-6 py-14 text-center"
                             style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a78bfa 100%);">
                            <div class="absolute inset-0 opacity-10"
                                 style="background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px), radial-gradient(circle at 80% 50%, white 1px, transparent 1px); background-size: 40px 40px;"></div>
                            <div class="relative">
                                <h1 class="text-3xl font-bold text-white">Busca un nombre de dominio</h1>
                                <p class="mt-2 text-sm text-white/70">Registra tu presencia en línea con el dominio perfecto.</p>
                            </div>

                            <form method="GET" action="{{ route('client.domains.search') }}"
                                  class="relative flex w-full max-w-xl items-center gap-0">
                                <label class="flex flex-1 items-center rounded-l-xl border-0 bg-white px-4 shadow-lg" style="height:52px">
                                    <i class="ki-filled ki-magnifier text-gray-400 me-2 text-lg shrink-0"></i>
                                    <input name="q" value="{{ $query }}" type="text"
                                           placeholder="ejemplo.com"
                                           class="w-full border-0 bg-transparent text-sm text-gray-800 outline-none placeholder-gray-400"
                                           autofocus>
                                </label>
                                <button type="submit"
                                        class="rounded-r-xl bg-indigo-700 px-6 text-sm font-semibold text-white hover:bg-indigo-800 transition"
                                        style="height:52px">
                                    Buscar
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- ── TLD showcase ─────────────────────────────────────────────── --}}
                    <div>
                        <h2 class="text-base font-semibold text-mono mb-4">Extensiones populares</h2>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                            @foreach([
                                ['ext' => '.com',    'color' => 'bg-indigo-500/10 border-indigo-500/20 text-indigo-600'],
                                ['ext' => '.net',    'color' => 'bg-blue-500/10 border-blue-500/20 text-blue-600'],
                                ['ext' => '.org',    'color' => 'bg-green-500/10 border-green-500/20 text-green-600'],
                                ['ext' => '.io',     'color' => 'bg-violet-500/10 border-violet-500/20 text-violet-600'],
                                ['ext' => '.es',     'color' => 'bg-red-500/10 border-red-500/20 text-red-600'],
                                ['ext' => '.online', 'color' => 'bg-amber-500/10 border-amber-500/20 text-amber-600'],
                            ] as $tld)
                                <div class="kt-card flex flex-col items-center gap-2 p-5 text-center hover:shadow-md transition-shadow cursor-pointer border {{ $tld['color'] }}">
                                    <span class="text-2xl font-black {{ $tld['color'] }}">{{ $tld['ext'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── Search results / info ─────────────────────────────────────── --}}
                    @if($query)
                        @php
                            $alreadyOwned = in_array(strtolower($query), $domains);
                        @endphp
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h2 class="kt-card-title">Resultados para "{{ $query }}"</h2>
                            </div>
                            <div class="kt-card-content border-t border-border p-5">
                                @if($alreadyOwned)
                                    <div class="flex items-center gap-4 rounded-xl border border-success/20 bg-success/10 px-5 py-4">
                                        <i class="ki-filled ki-check-circle text-2xl text-success shrink-0"></i>
                                        <div>
                                            <p class="font-semibold text-mono">{{ $query }}</p>
                                            <p class="text-sm text-secondary-foreground mt-0.5">Este dominio ya está en tu portafolio.</p>
                                        </div>
                                        <a href="{{ route('client.domains.index') }}" class="kt-btn kt-btn-outline ms-auto shrink-0">
                                            Ver portafolio
                                        </a>
                                    </div>
                                @else
                                    <div class="flex items-center gap-4 rounded-xl border border-border bg-muted/40 px-5 py-4">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted border border-border">
                                            <i class="ki-filled ki-globe text-lg text-secondary-foreground"></i>
                                        </span>
                                        <div class="min-w-0 grow">
                                            <p class="font-semibold text-mono truncate">{{ $query }}</p>
                                            <p class="text-xs text-secondary-foreground mt-0.5">
                                                XPanel no es un registrador de dominios. Para registrar este dominio usa un registrador externo (Namecheap, GoDaddy, IONOS…) y luego conéctalo aquí.
                                            </p>
                                        </div>
                                        <a href="{{ route('client.domains.create') }}" class="kt-btn kt-btn-primary shrink-0">
                                            <i class="ki-filled ki-plus"></i>
                                            Conectar dominio
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- ── How to connect ────────────────────────────────────────────── --}}
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h2 class="kt-card-title">¿Cómo conectar tu dominio?</h2>
                        </div>
                        <div class="kt-card-content border-t border-border p-5">
                            <ol class="space-y-5">
                                @foreach([
                                    ['icon' => 'ki-globe',      'title' => 'Registra tu dominio', 'desc' => 'Adquiere el dominio en cualquier registrador externo: Namecheap, GoDaddy, IONOS, Cloudflare, etc.'],
                                    ['icon' => 'ki-setting-2',  'title' => 'Apunta los nameservers', 'desc' => 'En el panel de tu registrador, cambia los nameservers para que apunten a XPanel. Los encontrarás en la pestaña DNS.'],
                                    ['icon' => 'ki-plus',       'title' => 'Agrega el dominio aquí', 'desc' => 'Ve a "Portafolio de dominios" y haz clic en Agregar dominio. El sistema lo detectará automáticamente.'],
                                ] as $i => $step)
                                    <li class="flex items-start gap-4">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-muted text-sm font-bold text-mono">{{ $i + 1 }}</span>
                                        <div>
                                            <p class="font-semibold text-sm text-mono">{{ $step['title'] }}</p>
                                            <p class="mt-0.5 text-xs text-secondary-foreground">{{ $step['desc'] }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>

                </div>
            </div>
        </main>

        @include('layouts.partials.client.footer')
    </div>
</div>
@endsection
