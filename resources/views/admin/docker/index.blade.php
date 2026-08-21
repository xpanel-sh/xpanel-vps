@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
    <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&_.kt-container-fluid]:pe-4" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <h1 class="font-medium text-lg text-mono">Apps Docker</h1>
                            <p class="text-sm text-secondary-foreground mt-0.5">Templates de apps que los clientes pueden instalar.</p>
                        </div>
                        <a href="{{ route('admin.docker.create') }}" class="kt-btn kt-btn-primary kt-btn-sm">
                            <i class="ki-filled ki-plus"></i>
                            Nuevo template
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-300">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($templates->isEmpty())
                        <div class="kt-card p-10 text-center">
                            <i class="ki-filled ki-cube-3 text-4xl text-secondary-foreground mb-3 block"></i>
                            <p class="text-secondary-foreground">Aún no has creado ningún template de app.</p>
                        </div>
                    @else
                        <div class="kt-card overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="border-b border-border bg-muted/30 text-xs font-semibold uppercase text-secondary-foreground">
                                    <tr>
                                        <th class="px-5 py-3">App</th>
                                        <th class="px-5 py-3">Categoría</th>
                                        <th class="px-5 py-3">Parámetros</th>
                                        <th class="px-5 py-3">Instancias</th>
                                        <th class="px-5 py-3">Visible</th>
                                        <th class="px-5 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach($templates as $tmpl)
                                    <tr class="hover:bg-muted/20 transition-colors">
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-3">
                                                @if($tmpl->icon)
                                                    <img src="{{ $tmpl->icon }}" alt="{{ $tmpl->name }}" class="w-8 h-8 rounded object-cover shrink-0">
                                                @else
                                                    <div class="w-8 h-8 rounded bg-muted flex items-center justify-center shrink-0">
                                                        <i class="ki-filled ki-cube-3 text-secondary-foreground text-sm"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <p class="font-semibold text-sm text-mono">{{ $tmpl->name }}</p>
                                                    <p class="text-xs text-secondary-foreground font-mono">{{ $tmpl->slug }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">{{ $tmpl->category }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-secondary-foreground">
                                            {{ count($tmpl->parameters ?? []) }} campo{{ count($tmpl->parameters ?? []) !== 1 ? 's' : '' }}
                                        </td>
                                        <td class="px-5 py-3 text-sm text-secondary-foreground">
                                            {{ $tmpl->instances_count }}
                                        </td>
                                        <td class="px-5 py-3">
                                            @if($tmpl->is_public)
                                                <span class="kt-badge kt-badge-outline kt-badge-success text-xs">Sí</span>
                                            @else
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">No</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-2 justify-end">
                                                <a href="{{ route('admin.docker.edit', $tmpl) }}"
                                                   class="kt-btn kt-btn-outline kt-btn-sm">
                                                    <i class="ki-filled ki-pencil text-xs"></i> Editar
                                                </a>
                                                <form action="{{ route('admin.docker.destroy', $tmpl) }}" method="POST"
                                                      onsubmit="return confirm('¿Eliminar {{ addslashes($tmpl->name) }}? Las instancias existentes quedarán huérfanas.')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="kt-btn kt-btn-icon kt-btn-sm" title="Eliminar">
                                                        <i class="ki-filled ki-trash text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>
            </div>
        </main>
        @include('layouts.partials.admin.footer')
    </div>
</div>
@endsection
