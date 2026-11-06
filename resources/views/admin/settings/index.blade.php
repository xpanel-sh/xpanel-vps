@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<div class="flex flex-col gap-6 max-w-3xl">

    <div>
        <h1 class="text-xl font-semibold text-mono">Configuración del sistema</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Ajustes generales del panel visible para tus clientes.
        </p>
    </div>

    @if (session('status'))
        <div class="flex items-center gap-2 rounded-lg bg-success/10 border border-success/20 px-4 py-3 text-sm text-success">
            <i class="ki-filled ki-check-circle text-base shrink-0"></i>
            {{ session('status') }}
        </div>
    @endif

    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Identidad y acceso</h3>
        </div>
        <div class="kt-card-content p-6">
            <form action="{{ route('admin.settings.update') }}" method="POST" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-1.5">
                    <label class="kt-form-label font-normal text-mono" for="app_name">
                        Nombre visible del panel
                    </label>
                    <input
                        id="app_name"
                        class="kt-input @error('app_name') border-danger @enderror"
                        type="text"
                        name="app_name"
                        value="{{ old('app_name', $appName) }}"
                        placeholder="XPanel"
                        maxlength="80"
                        required
                    />
                    @error('app_name')
                        <p class="text-xs text-danger">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-muted-foreground">
                        Este nombre aparece en los logins y cabeceras del panel de clientes.
                        El sistema interno siempre se llama <strong>XPanel</strong>.
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="kt-form-label font-normal text-mono" for="host_name">Nombre inicial de los hostings</label>
                    <input id="host_name" class="kt-input @error('host_name') border-danger @enderror" name="host_name" value="{{ old('host_name', $hostName) }}" maxlength="80" required>
                    @error('host_name')<p class="text-xs text-danger">{{ $message }}</p>@enderror
                    <p class="text-xs text-muted-foreground">Se entrega como identidad inicial a cada Host nuevo. Después, el propietario podrá personalizar su propia instancia.</p>
                </div>

                <div class="grid gap-4 border-t border-border pt-5 md:grid-cols-2">
                    <div class="flex flex-col gap-1.5">
                        <label class="kt-form-label font-normal text-mono" for="panel_domain">
                            Dominio principal de Cloud
                        </label>
                        <input
                            id="panel_domain"
                            class="kt-input @error('panel_domain') border-danger @enderror"
                            type="text"
                            name="panel_domain"
                            value="{{ old('panel_domain', $panelDomain) }}"
                            placeholder="cloud.empresa.com"
                            maxlength="253"
                            autocomplete="off"
                        />
                        @error('panel_domain')
                            <p class="text-xs text-danger">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-muted-foreground">
                            Crea registros A para el dominio y <strong>*.dominio</strong> hacia {{ config('xpanel.server_ip') }}. XPanel validará ambos y configurará Nginx, SSL y las futuras direcciones de hosting.
                            Si ya existen instancias, sus direcciones técnicas se migrarán automáticamente conservando el acceso por IP y puerto.
                        </p>
                    </div>

                    <div class="rounded-xl border border-border bg-muted/30 p-4">
                        <div class="text-xs text-muted-foreground">Acceso de recuperación permanente</div>
                        <a class="mt-2 block break-all text-sm font-medium text-primary hover:underline" href="{{ $recoveryUrl }}" target="_blank" rel="noopener">
                            {{ $recoveryUrl }}
                        </a>
                        <div class="mt-3 text-xs text-muted-foreground">
                            Estado del dominio: <span class="font-medium text-mono">{{ match($panelDomainStatus) { 'active' => 'Activo', 'staged' => 'Preparado', default => 'Sin configurar' } }}</span>
                        </div>
                    </div>
                </div>

                @php($telemetrySelected = old('telemetry_categories', $telemetryPreferences['categories']))
                <div class="grid gap-4 border-t border-border pt-5 md:grid-cols-2">
                    <div class="md:col-span-2"><h3 class="font-semibold text-mono">Métricas anónimas para xpanel.sh</h3><p class="mt-1 text-xs text-secondary-foreground">Ayudan a conocer instalaciones activas y compatibilidad de servidores. Nunca incluyen IP, dominios, correos, nombres ni archivos.</p></div>
                    <label class="flex items-start gap-3 rounded-xl border border-border p-4 md:col-span-2"><input type="hidden" name="telemetry_enabled" value="0"><input class="kt-switch mt-0.5" type="checkbox" name="telemetry_enabled" value="1" @checked(old('telemetry_enabled', $telemetryPreferences['enabled']))><span><strong class="block text-sm text-mono">Enviar métricas anónimas</strong><span class="mt-1 block text-xs text-secondary-foreground">Se envía como máximo dos veces al día a set.xpanel.sh. Desactivarlo no limita el panel ni sus actualizaciones.</span></span></label>
                    @foreach([
                        'version' => ['Producto y versión', 'Revisión instalada y versión de PHP.'],
                        'deployment' => ['Tipo de instalación', 'VPS, Host independiente o instancia administrada.'],
                        'health' => ['Estado general', 'Tiempo encendido y carga media del sistema.'],
                        'os' => ['Sistema Linux', 'Distribución, versión, kernel y arquitectura.'],
                        'compute' => ['CPU y memoria', 'Modelo de CPU, núcleos lógicos y memoria total/disponible.'],
                        'storage' => ['Disco', 'Capacidad y espacio libre del sistema raíz.'],
                        'gpu' => ['GPU', 'Cantidad e identificadores de fabricante/dispositivo, si existen.'],
                        'workload' => ['Uso de XPanel', 'Cantidad total y activa de instancias Host administradas.'],
                    ] as $key => [$title, $description])
                        <label class="flex items-start gap-3 rounded-xl border border-border p-4"><input class="kt-checkbox mt-0.5" type="checkbox" name="telemetry_categories[]" value="{{ $key }}" @checked(in_array($key, $telemetrySelected, true))><span><strong class="block text-sm text-mono">{{ $title }}</strong><span class="mt-1 block text-xs text-secondary-foreground">{{ $description }}</span></span></label>
                    @endforeach
                </div>

                <div class="grid gap-4 border-t border-border pt-5 md:grid-cols-2">
                    <label class="grid gap-1.5"><span class="kt-form-label">Días de gracia</span><input class="kt-input" type="number" min="0" max="90" name="service_grace_days" value="{{ old('service_grace_days', $graceDays) }}" required><span class="text-xs text-muted-foreground">Tiempo adicional antes de suspender un servicio vencido.</span></label>
                    <label class="grid gap-1.5"><span class="kt-form-label">Retención después de cancelar</span><input class="kt-input" type="number" min="0" max="365" name="cancelled_data_retention_days" value="{{ old('cancelled_data_retention_days', $retentionDays) }}" required><span class="text-xs text-muted-foreground">Días durante los que una cuenta suspendida puede restaurarse.</span></label>
                    <div class="rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm md:col-span-2"><strong class="text-mono">El borrado definitivo siempre requiere confirmación administrativa.</strong><p class="mt-1 text-xs text-secondary-foreground">Al terminar la retención la cuenta cambia a «Pendiente de depuración», pero ninguna tarea automática destruye archivos, bases o correos.</p></div>
                    <label class="flex items-start gap-3 rounded-xl border border-border p-4 md:col-span-2"><input type="hidden" name="notification_email_enabled" value="0"><input class="kt-switch mt-0.5" type="checkbox" name="notification_email_enabled" value="1" @checked(old('notification_email_enabled', $notificationEmailEnabled))><span><strong class="block text-sm text-mono">Enviar también por correo</strong><span class="mt-1 block text-xs text-secondary-foreground">Replica por correo los avisos de pagos, planes, cancelaciones y suspensiones. Si SMTP falla, el evento permanece en el centro interno y la operación continúa.</span></span></label>
                </div>

                <div class="grid gap-4 border-t border-border pt-5 md:grid-cols-2">
                    <div class="md:col-span-2"><h3 class="font-semibold text-mono">Pago manual verificable</h3><p class="mt-1 text-xs text-secondary-foreground">Se muestra en las boletas pendientes mientras no exista una pasarela automática.</p></div>
                    <label class="grid gap-1.5"><span class="kt-form-label">Método</span><input class="kt-input" name="payment_method_name" maxlength="100" value="{{ old('payment_method_name', $paymentMethodName) }}" required></label>
                    <label class="grid gap-1.5 md:col-span-2"><span class="kt-form-label">Instrucciones para el cliente</span><textarea class="kt-textarea min-h-28" name="payment_instructions" maxlength="2000" required>{{ old('payment_instructions', $paymentInstructions) }}</textarea></label>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check text-base"></i>
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
