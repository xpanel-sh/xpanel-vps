@csrf

@if($errors->any())
    <div class="kt-card border-destructive/30">
        <div class="kt-card-content p-4 text-sm text-destructive">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="flex grow gap-5 lg:gap-7.5">
    <div class="hidden lg:block w-[230px] shrink-0">
        <div class="w-[230px]">
            <div class="flex flex-col grow relative before:absolute before:left-[11px] before:top-0 before:bottom-0 before:border-l before:border-border">
                <a class="flex items-center rounded-lg pl-2.5 pr-2.5 py-2.5 gap-1.5 active border border-transparent text-sm text-foreground hover:text-primary hover:font-medium" href="#plan_basic">
                    <span class="flex w-1.5 relative before:absolute before:top-0 before:size-1.5 before:rounded-full before:-translate-x-2/4 before:-translate-y-2/4 before:bg-primary"></span>
                    Datos del plan
                </a>
                <a class="flex items-center rounded-lg pl-2.5 pr-2.5 py-2.5 gap-1.5 border border-transparent text-sm text-foreground hover:text-primary hover:font-medium" href="#plan_limits">
                    <span class="flex w-1.5 relative before:absolute before:top-0 before:size-1.5 before:rounded-full before:-translate-x-2/4 before:-translate-y-2/4"></span>
                    Limites
                </a>
                <a class="flex items-center rounded-lg pl-2.5 pr-2.5 py-2.5 gap-1.5 border border-transparent text-sm text-foreground hover:text-primary hover:font-medium" href="#plan_publish">
                    <span class="flex w-1.5 relative before:absolute before:top-0 before:size-1.5 before:rounded-full before:-translate-x-2/4 before:-translate-y-2/4"></span>
                    Publicacion
                </a>
            </div>
        </div>
    </div>

    <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header" id="plan_basic">
                <h3 class="kt-card-title">Datos del plan</h3>
            </div>
            <div class="kt-card-content grid gap-5">
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Nombre</label>
                    <input class="kt-input" type="text" name="name" value="{{ old('name', $plan->name) }}" required placeholder="Starter">
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Slug</label>
                    <div class="grow grid gap-1.5">
                        <input class="kt-input" type="text" name="slug" value="{{ old('slug', $plan->slug) }}" placeholder="starter">
                        <p class="kt-form-description">Si lo dejas vacio, se genera desde el nombre.</p>
                    </div>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Descripcion</label>
                    <textarea class="kt-textarea" name="description" rows="4" placeholder="Plan base para sitios pequenos...">{{ old('description', $plan->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header" id="plan_limits">
                <h3 class="kt-card-title">Limites del paquete</h3>
            </div>
            <div class="kt-card-content grid gap-5">
                @foreach([
                    ['max_sites', 'Sitios web', $plan->max_sites ?? 1],
                    ['max_databases', 'Bases de datos', $plan->max_databases ?? 1],
                    ['storage_mb', 'Almacenamiento MB', $plan->storage_mb ?? 1024],
                    ['bandwidth_gb', 'Transferencia GB', $plan->bandwidth_gb ?? 10],
                    ['email_accounts', 'Cuentas de correo', $plan->email_accounts ?? 0],
                    ['memory_mb', 'Memoria máxima MB', $plan->memory_mb ?? 512],
                    ['swap_mb', 'Swap máximo MB', $plan->swap_mb ?? 0],
                    ['cpu_percent', 'CPU % (100 = 1 núcleo)', $plan->cpu_percent ?? 100],
                    ['tasks_max', 'Procesos máximos', $plan->tasks_max ?? 256],
                    ['monthly_price', 'Precio mensual', $plan->monthly_price ?? 0],
                    ['billing_period_months', 'Duración contratada (meses)', $plan->billing_period_months ?? 1],
                    ['payment_due_days', 'Días disponibles para pagar', $plan->payment_due_days ?? 30],
                ] as [$name, $label, $value])
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label max-w-56">{{ $label }}</label>
                        <input class="kt-input" type="number" name="{{ $name }}" value="{{ old($name, $value) }}" min="{{ in_array($name, ['billing_period_months', 'payment_due_days']) ? 1 : 0 }}" step="{{ $name === 'monthly_price' ? '0.01' : '1' }}" required>
                    </div>
                @endforeach
                <p class="kt-form-description">RAM, swap, CPU y procesos se aplican a la slice systemd de la instancia. El cliente podrá contratar sin pasarela y la boleta conservará el plazo configurado.</p>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header" id="plan_publish">
                <h3 class="kt-card-title">Publicacion</h3>
            </div>
            <div class="kt-card-content grid gap-5">
                <label class="kt-label">
                    <input class="kt-switch" type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->exists ? $plan->is_active : true))>
                    Plan activo para nuevas asignaciones
                </label>
                <div class="flex justify-end gap-2.5">
                    <a href="{{ route('admin.plans.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
                    <button class="kt-btn kt-btn-primary">Guardar plan</button>
                </div>
            </div>
        </div>
    </div>
</div>
