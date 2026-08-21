@extends('layouts.client')

@section('content')
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5"
     x-data="{
         selectedTemplate: @js($preselectedId ? $templates->firstWhere('id', $preselectedId) : null),
         templates: @js($templates->keyBy('id')),
         params: {},
         selectTemplate(id) {
             this.selectedTemplate = this.templates[id] ?? null;
             this.params = {};
         }
     }">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- Cabecera --}}
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-mono">Instalar una app</h1>
                            <p class="mt-1 text-sm text-secondary-foreground">Elige una app del catálogo y configúrala en segundos.</p>
                        </div>
                        <a href="{{ route('client.docker.index') }}" class="kt-btn kt-btn-outline">
                            Volver
                        </a>
                    </div>

                    @if($errors->any())
                        <div class="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    {{-- ── CATÁLOGO de apps ── --}}
                    <div x-show="!selectedTemplate">
                        @if($templates->isEmpty())
                            <div class="kt-card p-10 text-center">
                                <i class="ki-filled ki-cube-3 text-4xl text-secondary-foreground mb-3 block"></i>
                                <p class="text-secondary-foreground">No hay apps disponibles todavía.</p>
                                <p class="text-sm text-secondary-foreground mt-1">Contacta con soporte si necesitas una app específica.</p>
                            </div>
                        @else
                            @foreach($templates->groupBy('category') as $category => $group)
                            <div class="mb-6">
                                <p class="text-xs font-semibold text-secondary-foreground uppercase tracking-wider mb-3">{{ ucfirst($category) }}</p>
                                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($group as $tmpl)
                                    <button type="button"
                                        class="kt-card p-4 text-left border-2 border-transparent hover:border-primary/40 hover:bg-primary/5 transition-all"
                                        @click="selectTemplate({{ $tmpl->id }})">
                                        <div class="flex items-center gap-3">
                                            @if($tmpl->icon)
                                                <img src="{{ $tmpl->icon }}" alt="{{ $tmpl->name }}" class="w-10 h-10 rounded-lg object-cover shrink-0">
                                            @else
                                                <div class="w-10 h-10 rounded-lg bg-muted flex items-center justify-center shrink-0">
                                                    <i class="ki-filled ki-cube-3 text-secondary-foreground text-lg"></i>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="font-semibold text-sm text-mono">{{ $tmpl->name }}</p>
                                                @if($tmpl->description)
                                                    <p class="text-xs text-secondary-foreground truncate mt-0.5">{{ $tmpl->description }}</p>
                                                @endif
                                            </div>
                                            <i class="ki-filled ki-arrow-right text-secondary-foreground ms-auto shrink-0"></i>
                                        </div>
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>

                    {{-- ── FORMULARIO de configuración ── --}}
                    <div x-show="selectedTemplate" x-cloak>
                        <form action="{{ route('client.docker.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="template_id" :value="selectedTemplate?.id">

                            <div class="grid gap-5">
                                {{-- Nav de vuelta --}}
                                <div class="flex items-center gap-3">
                                    <button type="button" class="text-secondary-foreground hover:text-mono" @click="selectedTemplate = null">
                                        <i class="ki-filled ki-arrow-left text-sm"></i>
                                    </button>
                                    <div>
                                        <h2 class="text-base font-semibold text-mono" x-text="selectedTemplate?.name"></h2>
                                        <p class="text-xs text-secondary-foreground" x-text="selectedTemplate?.description ?? ''"></p>
                                    </div>
                                </div>

                                {{-- Datos básicos --}}
                                <div class="kt-card">
                                    <div class="kt-card-header min-h-12">
                                        <h3 class="kt-card-title text-sm">Información de la instancia</h3>
                                    </div>
                                    <div class="p-5 grid sm:grid-cols-2 gap-4">
                                        <div class="grid gap-1.5">
                                            <label class="text-sm font-medium text-mono">Nombre visible</label>
                                            <input class="kt-input" type="text" name="name"
                                                value="{{ old('name') }}" required maxlength="60"
                                                placeholder="Mi PostgreSQL">
                                        </div>
                                        <div class="grid gap-1.5">
                                            <label class="text-sm font-medium text-mono">
                                                Identificador
                                                <span class="text-xs text-secondary-foreground font-normal">(slug)</span>
                                            </label>
                                            <input class="kt-input font-mono" type="text" name="slug"
                                                value="{{ old('slug') }}" required
                                                pattern="[a-z0-9][a-z0-9-]{0,48}[a-z0-9]"
                                                placeholder="mi-postgres">
                                            <p class="text-xs text-secondary-foreground">Minúsculas, números y guiones.</p>
                                        </div>
                                        <div class="grid gap-1.5 sm:col-span-2">
                                            <label class="text-sm font-medium text-mono">
                                                Dominio público
                                                <span class="text-xs text-secondary-foreground font-normal">(opcional)</span>
                                            </label>
                                            <input class="kt-input" type="text" name="domain"
                                                value="{{ old('domain') }}" placeholder="app.midominio.com">
                                            <p class="text-xs text-secondary-foreground">Si la app necesita URL pública, indica aquí el dominio o subdominio.</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Parámetros definidos por el admin --}}
                                <div x-show="selectedTemplate?.parameters?.length" class="kt-card">
                                    <div class="kt-card-header min-h-12">
                                        <h3 class="kt-card-title text-sm">Configuración de la app</h3>
                                    </div>
                                    <div class="p-5 grid sm:grid-cols-2 gap-4">
                                        <template x-for="param in selectedTemplate?.parameters ?? []" :key="param.key">
                                            <div class="grid gap-1.5">
                                                <label class="text-sm font-medium text-mono" x-text="param.label"></label>

                                                {{-- Dominio del cliente --}}
                                                <template x-if="param.type === 'domain'">
                                                    <select :name="'params[' + param.key + ']'" :required="param.required ?? false" class="kt-input">
                                                        <option value="">Selecciona un dominio…</option>
                                                        @foreach($tenantDomains as $dom)
                                                        <option value="{{ $dom }}">{{ $dom }}</option>
                                                        @endforeach
                                                    </select>
                                                </template>

                                                {{-- Selector con opciones del admin --}}
                                                <template x-if="param.type === 'select'">
                                                    <select :name="'params[' + param.key + ']'" :required="param.required ?? false" class="kt-input">
                                                        <template x-for="opt in param.options ?? []" :key="opt">
                                                            <option :value="opt" :selected="opt === (param.default ?? '')" x-text="opt"></option>
                                                        </template>
                                                    </select>
                                                </template>

                                                {{-- Espacio en disco (límite del plan) --}}
                                                <template x-if="param.type === 'disk'">
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <input type="number"
                                                                :name="'params[' + param.key + ']'"
                                                                :min="param.min || 1"
                                                                :max="param.max || {{ $planStorageMb }}"
                                                                :value="param.default || ''"
                                                                :placeholder="param.placeholder || ''"
                                                                :required="param.required ?? false"
                                                                class="kt-input w-36">
                                                            <span class="text-sm text-secondary-foreground">MB</span>
                                                        </div>
                                                        <p class="text-xs text-secondary-foreground mt-1">Máximo disponible: {{ number_format($planStorageMb) }} MB</p>
                                                    </div>
                                                </template>

                                                {{-- Memoria RAM --}}
                                                <template x-if="param.type === 'memory'">
                                                    <div class="flex items-center gap-2">
                                                        <input type="number"
                                                            :name="'params[' + param.key + ']'"
                                                            :min="param.min || 64"
                                                            :max="param.max || 4096"
                                                            :value="param.default || ''"
                                                            :placeholder="param.placeholder || '512'"
                                                            :required="param.required ?? false"
                                                            class="kt-input w-36">
                                                        <span class="text-sm text-secondary-foreground">MB</span>
                                                    </div>
                                                </template>

                                                {{-- Puerto --}}
                                                <template x-if="param.type === 'port'">
                                                    <input type="number" min="1" max="65535"
                                                        :name="'params[' + param.key + ']'"
                                                        :value="param.default || ''"
                                                        :placeholder="param.placeholder || '8080'"
                                                        :required="param.required ?? false"
                                                        class="kt-input">
                                                </template>

                                                {{-- Default: text, password, email, number --}}
                                                <template x-if="!['domain','select','disk','memory','port'].includes(param.type)">
                                                    <input
                                                        :type="param.type === 'password' ? 'password' : param.type === 'email' ? 'email' : param.type === 'number' ? 'number' : 'text'"
                                                        :name="'params[' + param.key + ']'"
                                                        :min="param.min || undefined"
                                                        :max="param.max || undefined"
                                                        :placeholder="param.placeholder ?? (param.default ?? '')"
                                                        :required="param.required ?? false"
                                                        class="kt-input">
                                                </template>

                                                <p x-show="param.description" class="text-xs text-secondary-foreground" x-text="param.description ?? ''"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex gap-3">
                                    <button type="submit" class="kt-btn kt-btn-primary">
                                        <i class="ki-filled ki-check"></i>
                                        Instalar app
                                    </button>
                                    <button type="button" class="kt-btn kt-btn-outline" @click="selectedTemplate = null">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </main>
        @include('layouts.partials.client.footer')
    </div>
</div>
@endsection
