@csrf

@if($errors->any())
<div class="kt-card border-destructive/30 mb-5">
    <div class="kt-card-content p-4 text-sm text-destructive">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

@php
    $existingParams = json_encode($template->parameters ?? []);
@endphp

<div x-data="{
    params: {{ $existingParams }},
    newOption: '',
    addParam() {
        this.params.push({ key: '', label: '', type: 'text', required: false, default: '', placeholder: '', description: '', options: [], min: '', max: '' });
    },
    removeParam(i) { this.params.splice(i, 1); },
    moveUp(i) {
        if (i === 0) return;
        [this.params[i-1], this.params[i]] = [this.params[i], this.params[i-1]];
    },
    moveDown(i) {
        if (i >= this.params.length - 1) return;
        [this.params[i], this.params[i+1]] = [this.params[i+1], this.params[i]];
    },
    addOption(i, opt) {
        if (!opt.trim()) return;
        if (!this.params[i].options) this.params[i].options = [];
        this.params[i].options.push(opt.trim());
    },
    removeOption(i, j) { this.params[i].options.splice(j, 1); }
}">
    {{-- Hidden field: params JSON --}}
    <input type="hidden" name="parameters" :value="JSON.stringify(params)">

    <div class="flex grow gap-5 lg:gap-7.5">

        {{-- Columna izquierda: info general + YAML --}}
        <div class="flex flex-col gap-5 grow min-w-0">

            {{-- Info general --}}
            <div class="kt-card">
                <div class="kt-card-header min-h-12">
                    <h3 class="kt-card-title">Información general</h3>
                </div>
                <div class="kt-card-content p-5 grid sm:grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-mono">Nombre de la app</label>
                        <input class="kt-input" type="text" name="name"
                            value="{{ old('name', $template->name) }}"
                            required maxlength="100" placeholder="Coder.com, PostgreSQL, Redis…">
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-mono">Slug <span class="text-xs text-secondary-foreground font-normal">(único)</span></label>
                        <input class="kt-input font-mono" type="text" name="slug"
                            value="{{ old('slug', $template->slug) }}"
                            required maxlength="80" pattern="[a-z0-9-]+"
                            placeholder="coder-com">
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-mono">Categoría</label>
                        <input class="kt-input" type="text" name="category"
                            value="{{ old('category', $template->category ?? 'other') }}"
                            required placeholder="database, dev-tools, cms, other…">
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-mono">
                            URL del ícono
                            <span class="text-xs text-secondary-foreground font-normal">(opcional)</span>
                        </label>
                        <input class="kt-input" type="text" name="icon"
                            value="{{ old('icon', $template->icon) }}"
                            placeholder="https://…/icon.png">
                    </div>
                    <div class="grid gap-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-mono">Descripción <span class="text-xs text-secondary-foreground font-normal">(opcional)</span></label>
                        <textarea class="kt-input" name="description" rows="2" maxlength="500"
                            placeholder="Editor de código en el navegador, perfecto para desarrollo remoto.">{{ old('description', $template->description) }}</textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_public" name="is_public" value="1"
                            class="rounded"
                            {{ old('is_public', $template->is_public ?? true) ? 'checked' : '' }}>
                        <label for="is_public" class="text-sm font-medium text-mono cursor-pointer">Visible para clientes</label>
                    </div>
                </div>
            </div>

            {{-- Editor YAML --}}
            <div class="kt-card">
                <div class="kt-card-header min-h-12">
                    <h3 class="kt-card-title">docker-compose.yml</h3>
                </div>
                <div class="p-5">
                    <div class="rounded-lg bg-muted/50 border border-border px-3 py-2 mb-3 text-xs text-secondary-foreground">
                        Variables de sistema disponibles siempre:
                        <code class="bg-muted px-1 rounded">{{TENANT_CODE}}</code>
                        <code class="bg-muted px-1 rounded">{{SLUG}}</code>
                        <code class="bg-muted px-1 rounded">{{CONTAINER_NAME}}</code>
                        <code class="bg-muted px-1 rounded">{{DOMAIN}}</code>
                        — más las variables de los parámetros que definas abajo.
                    </div>
                    <textarea
                        name="compose_template"
                        class="kt-input font-mono text-xs w-full"
                        rows="28"
                        required
                        placeholder="services:
  myapp:
    image: myimage:latest
    restart: unless-stopped
    mem_limit: {{MEMORY}}m
    environment:
      - PASSWORD={{MY_PASSWORD}}
    volumes:
      - /home/xpanel/clients/{{TENANT_CODE}}/sites/{{WORK_DOMAIN}}/www:/var/www
    labels:
      - traefik.enable=true
      - traefik.http.routers.{{SLUG}}.rule=Host(`{{DOMAIN}}`)">{{ old('compose_template', $template->compose_template) }}</textarea>
                </div>
            </div>

        </div>

        {{-- Columna derecha: builder de parámetros --}}
        <div class="w-full lg:w-[400px] shrink-0 flex flex-col gap-5">
            <div class="kt-card">
                <div class="kt-card-header min-h-12 flex items-center justify-between">
                    <h3 class="kt-card-title">Parámetros del cliente</h3>
                    <button type="button" class="kt-btn kt-btn-primary kt-btn-sm" @click="addParam()">
                        <i class="ki-filled ki-plus"></i> Añadir
                    </button>
                </div>
                <div class="p-4">
                    <p class="text-xs text-secondary-foreground mb-4">
                        Define qué campos pedirás al cliente cuando instale esta app. Cada campo genera una variable <code class="bg-muted px-1 rounded">{{KEY}}</code> para usar en el compose.
                    </p>

                    {{-- Lista de parámetros --}}
                    <div class="grid gap-3">
                        <template x-for="(param, i) in params" :key="i">
                            <div class="rounded-xl border border-border bg-muted/20 overflow-hidden">
                                {{-- Cabecera del parámetro --}}
                                <div class="flex items-center gap-2 px-3 py-2 bg-muted/40 border-b border-border">
                                    <div class="flex flex-col gap-0.5 shrink-0">
                                        <button type="button" class="text-secondary-foreground hover:text-mono leading-none" @click="moveUp(i)" title="Subir">
                                            <i class="ki-filled ki-up text-xs"></i>
                                        </button>
                                        <button type="button" class="text-secondary-foreground hover:text-mono leading-none" @click="moveDown(i)" title="Bajar">
                                            <i class="ki-filled ki-down text-xs"></i>
                                        </button>
                                    </div>
                                    <span class="text-xs font-mono font-semibold text-mono truncate grow" x-text="param.key || 'SIN_KEY'"></span>
                                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs shrink-0" x-text="param.type"></span>
                                    <button type="button" class="text-destructive hover:text-destructive/80 shrink-0" @click="removeParam(i)">
                                        <i class="ki-filled ki-trash text-xs"></i>
                                    </button>
                                </div>

                                {{-- Campos de configuración --}}
                                <div class="p-3 grid gap-2.5">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="grid gap-1">
                                            <label class="text-xs font-medium text-secondary-foreground">Variable (KEY)</label>
                                            <input class="kt-input kt-input-sm font-mono uppercase" type="text"
                                                x-model="param.key"
                                                placeholder="MY_PASSWORD"
                                                @input="param.key = param.key.toUpperCase().replace(/[^A-Z0-9_]/g, '_')">
                                        </div>
                                        <div class="grid gap-1">
                                            <label class="text-xs font-medium text-secondary-foreground">Tipo</label>
                                            <select class="kt-input kt-input-sm" x-model="param.type">
                                                <option value="text">Texto</option>
                                                <option value="password">Contraseña</option>
                                                <option value="number">Número</option>
                                                <option value="port">Puerto</option>
                                                <option value="email">Email</option>
                                                <option value="select">Selector</option>
                                                <option value="domain">Dominio del cliente</option>
                                                <option value="disk">Espacio en disco (plan)</option>
                                                <option value="memory">Memoria RAM (MB)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid gap-1">
                                        <label class="text-xs font-medium text-secondary-foreground">Etiqueta (visible para el cliente)</label>
                                        <input class="kt-input kt-input-sm" type="text" x-model="param.label" placeholder="Contraseña de acceso">
                                    </div>

                                    <div class="grid gap-1">
                                        <label class="text-xs font-medium text-secondary-foreground">Descripción <span class="text-secondary-foreground/60">(ayuda al cliente)</span></label>
                                        <input class="kt-input kt-input-sm" type="text" x-model="param.description" placeholder="Mínimo 8 caracteres…">
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="grid gap-1">
                                            <label class="text-xs font-medium text-secondary-foreground">Valor por defecto</label>
                                            <input class="kt-input kt-input-sm" type="text" x-model="param.default" placeholder="opcional">
                                        </div>
                                        <div class="grid gap-1">
                                            <label class="text-xs font-medium text-secondary-foreground">Placeholder</label>
                                            <input class="kt-input kt-input-sm" type="text" x-model="param.placeholder" placeholder="opcional">
                                        </div>
                                    </div>

                                    {{-- Min/Max para number/disk/memory/port --}}
                                    <div class="grid grid-cols-2 gap-2" x-show="['number','port','disk','memory'].includes(param.type)">
                                        <div class="grid gap-1">
                                            <label class="text-xs font-medium text-secondary-foreground">Mín</label>
                                            <input class="kt-input kt-input-sm" type="number" x-model="param.min" placeholder="—">
                                        </div>
                                        <div class="grid gap-1">
                                            <label class="text-xs font-medium text-secondary-foreground">Máx <span x-show="param.type === 'disk'" class="text-secondary-foreground/60">(MB)</span></label>
                                            <input class="kt-input kt-input-sm" type="number" x-model="param.max" placeholder="plan →">
                                        </div>
                                    </div>

                                    {{-- Opciones para select --}}
                                    <div x-show="param.type === 'select'" class="grid gap-1.5">
                                        <label class="text-xs font-medium text-secondary-foreground">Opciones del selector</label>
                                        <div class="flex flex-wrap gap-1.5 mb-1">
                                            <template x-for="(opt, j) in param.options ?? []" :key="j">
                                                <span class="inline-flex items-center gap-1 bg-muted rounded px-2 py-0.5 text-xs font-mono">
                                                    <span x-text="opt"></span>
                                                    <button type="button" class="text-destructive leading-none" @click="removeOption(i, j)">×</button>
                                                </span>
                                            </template>
                                        </div>
                                        <div class="flex gap-1">
                                            <input class="kt-input kt-input-sm grow" type="text"
                                                :id="'opt-input-' + i"
                                                placeholder="Nueva opción…"
                                                @keydown.enter.prevent="addOption(i, $el.value); $el.value = ''">
                                            <button type="button" class="kt-btn kt-btn-outline kt-btn-sm shrink-0"
                                                @click="const inp = document.getElementById('opt-input-' + i); addOption(i, inp.value); inp.value = ''">
                                                +
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Required checkbox --}}
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" class="rounded" x-model="param.required">
                                        <span class="text-xs font-medium text-secondary-foreground">Campo obligatorio</span>
                                    </label>
                                </div>
                            </div>
                        </template>

                        <div x-show="params.length === 0" class="text-center py-6 text-sm text-secondary-foreground">
                            Sin parámetros. Pulsa "Añadir" para definir los campos que pedirás al cliente.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex gap-3">
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check"></i>
                    Guardar template
                </button>
                <a href="{{ route('admin.docker.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
            </div>
        </div>

    </div>
</div>
