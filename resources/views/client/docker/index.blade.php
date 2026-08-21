@extends('layouts.client')

@section('content')
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5"
     x-data="{ tab: '{{ request('tab', 'installed') }}' }">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- Cabecera --}}
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-mono">Docker Apps</h1>
                            <p class="mt-1 text-sm text-secondary-foreground">Instala y gestiona apps en tu plan de hosting.</p>
                        </div>
                        <a href="{{ route('client.docker.create') }}" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            Instalar app
                        </a>
                    </div>

                    {{-- Flash --}}
                    @if(session('success'))
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-300">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Tabs --}}
                    <div class="kt-card">
                        <div class="border-b border-border px-0">
                            <nav class="flex">
                                <button type="button"
                                    class="px-5 py-3.5 text-sm font-medium border-b-2 transition-colors flex items-center gap-2"
                                    :class="tab === 'installed' ? 'border-primary text-primary' : 'border-transparent text-secondary-foreground hover:text-mono'"
                                    @click="tab = 'installed'">
                                    <i class="ki-filled ki-setting-2 text-sm"></i>
                                    Mis apps
                                    @if($instances->count())
                                        <span class="rounded-full bg-primary/10 text-primary text-xs px-1.5 py-0.5 font-semibold">{{ $instances->count() }}</span>
                                    @endif
                                </button>
                                <button type="button"
                                    class="px-5 py-3.5 text-sm font-medium border-b-2 transition-colors flex items-center gap-2"
                                    :class="tab === 'store' ? 'border-primary text-primary' : 'border-transparent text-secondary-foreground hover:text-mono'"
                                    @click="tab = 'store'">
                                    <i class="ki-filled ki-cube-3 text-sm"></i>
                                    Tienda de apps
                                    @if($templates->count())
                                        <span class="rounded-full bg-muted text-secondary-foreground text-xs px-1.5 py-0.5 font-semibold">{{ $templates->count() }}</span>
                                    @endif
                                </button>
                            </nav>
                        </div>

                        {{-- ── TAB: MIS APPS ── --}}
                        <div x-show="tab === 'installed'" class="p-5">
                            @if($instances->isEmpty())
                                <div class="text-center py-10">
                                    <i class="ki-filled ki-cube-3 text-4xl text-secondary-foreground mb-3 block"></i>
                                    <p class="text-sm text-secondary-foreground">Aún no tienes apps instaladas.</p>
                                    <button type="button" class="kt-btn kt-btn-primary mt-4 inline-flex" @click="tab = 'store'">
                                        <i class="ki-filled ki-cube-3"></i> Ver tienda de apps
                                    </button>
                                </div>
                            @else
                                <div class="grid gap-3">
                                    @foreach($instances as $instance)
                                    @php
                                        $statusClass = match($instance->status) {
                                            'running'      => 'kt-badge-success',
                                            'stopped'      => 'kt-badge-secondary',
                                            'error'        => 'kt-badge-danger',
                                            'provisioning' => 'kt-badge-warning',
                                            'partial'      => 'kt-badge-warning',
                                            default        => 'kt-badge-secondary',
                                        };
                                    @endphp
                                    <div class="rounded-xl border border-border bg-muted/10 flex items-center justify-between gap-3 px-4 py-3 hover:bg-muted/20 transition-colors">
                                        <div class="flex items-center gap-3 min-w-0">
                                            @if($instance->template?->icon)
                                                <img src="{{ $instance->template->icon }}" alt="{{ $instance->template->name }}" class="w-9 h-9 rounded-lg object-cover shrink-0">
                                            @else
                                                <div class="w-9 h-9 rounded-lg bg-muted flex items-center justify-center shrink-0">
                                                    <i class="ki-filled ki-cube-3 text-secondary-foreground text-base"></i>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-semibold text-mono text-sm">{{ $instance->name }}</span>
                                                    <span class="kt-badge kt-badge-outline {{ $statusClass }} text-xs">{{ $instance->status }}</span>
                                                    @if($instance->template)
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">{{ $instance->template->name }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-3 mt-0.5">
                                                    <span class="text-xs text-secondary-foreground font-mono">{{ $instance->slug }}</span>
                                                    @if($instance->domain)
                                                        <a href="https://{{ $instance->domain }}" target="_blank" rel="noopener"
                                                           class="text-xs text-primary hover:underline">
                                                            {{ $instance->domain }} <i class="ki-filled ki-exit-up text-[10px]"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ route('client.docker.show', $instance) }}"
                                               class="kt-btn kt-btn-outline kt-btn-sm">
                                                Gestionar
                                            </a>

                                            @if($instance->status === 'running' || $instance->status === 'partial')
                                                <form action="{{ route('client.docker.stop', $instance) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline" title="Detener">
                                                        <i class="ki-filled ki-stop text-xs"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('client.docker.start', $instance) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline" title="Arrancar">
                                                        <i class="ki-filled ki-triangle text-xs"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <form action="{{ route('client.docker.destroy', $instance) }}" method="POST"
                                                  onsubmit="return confirm('¿Eliminar {{ addslashes($instance->name) }}? Se borrarán el contenedor y todos sus datos.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="kt-btn kt-btn-icon kt-btn-sm" title="Eliminar">
                                                    <i class="ki-filled ki-trash text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- ── TAB: TIENDA ── --}}
                        <div x-show="tab === 'store'" x-cloak class="p-5">
                            @if($templates->isEmpty())
                                <div class="text-center py-10">
                                    <i class="ki-filled ki-cube-3 text-4xl text-secondary-foreground mb-3 block"></i>
                                    <p class="text-sm text-secondary-foreground">No hay apps disponibles todavía.</p>
                                </div>
                            @else
                                @foreach($templates->groupBy('category') as $category => $group)
                                <div class="mb-6 last:mb-0">
                                    <p class="text-xs font-semibold text-secondary-foreground uppercase tracking-wider mb-3">{{ ucfirst($category) }}</p>
                                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($group as $tmpl)
                                        <div class="kt-card p-4 flex flex-col gap-3">
                                            <div class="flex items-start gap-3">
                                                @if($tmpl->icon)
                                                    <img src="{{ $tmpl->icon }}" alt="{{ $tmpl->name }}" class="w-10 h-10 rounded-lg object-cover shrink-0 mt-0.5">
                                                @else
                                                    <div class="w-10 h-10 rounded-lg bg-muted flex items-center justify-center shrink-0 mt-0.5">
                                                        <i class="ki-filled ki-cube-3 text-secondary-foreground text-lg"></i>
                                                    </div>
                                                @endif
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-sm text-mono">{{ $tmpl->name }}</p>
                                                    @if($tmpl->description)
                                                        <p class="text-xs text-secondary-foreground mt-0.5 line-clamp-2">{{ $tmpl->description }}</p>
                                                    @endif
                                                    @php $paramCount = count($tmpl->parameters ?? []); @endphp
                                                    @if($paramCount)
                                                        <p class="text-xs text-secondary-foreground/70 mt-1">{{ $paramCount }} parámetro{{ $paramCount !== 1 ? 's' : '' }} a configurar</p>
                                                    @endif
                                                </div>
                                            </div>
                                            <a href="{{ route('client.docker.create') }}?app={{ $tmpl->id }}"
                                               class="kt-btn kt-btn-primary kt-btn-sm w-full justify-center">
                                                <i class="ki-filled ki-plus text-xs"></i>
                                                Instalar
                                            </a>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>

                    </div>

                </div>
            </div>
        </main>
        @include('layouts.partials.client.footer')
    </div>
</div>
@endsection
