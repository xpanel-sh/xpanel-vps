{{-- Expects: $pkg (native package state), $installed, $active, $enabled, $isDefault --}}
<div class="flex min-w-0 flex-col justify-between gap-4 rounded-xl border border-border bg-background p-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <p class="font-semibold text-sm text-mono">{{ $pkg['label'] }}</p>
            <p class="mt-1 truncate text-xs text-secondary-foreground">{{ !empty($pkg['version']) ? 'Versión '.$pkg['version'] : ($pkg['category'] === 'webserver' ? 'Motor web' : 'Intérprete PHP') }}</p>
        </div>
        @if($installed)
            <span class="kt-badge {{ $active ? 'kt-badge-success' : 'kt-badge-outline kt-badge-secondary' }} text-xs">
                {{ $active ? 'Activo' : 'Instalado' }}
            </span>
        @elseif($pkg['installable'] ?? false)
            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">No instalado</span>
        @endif
    </div>

    <div class="flex items-center justify-between gap-2 border-t border-border pt-3">
        @if($installed && !$active && $pkg['slug'] !== 'apache')
            <span class="text-xs text-secondary-foreground">Inicia el servicio para ofrecerlo</span>
        @elseif($installed)
            <form action="{{ route('admin.software-packages.toggle') }}" method="POST">
                @csrf
                <input type="hidden" name="slug" value="{{ $pkg['slug'] }}">
                <button type="submit" class="kt-btn kt-btn-sm {{ $enabled ? 'kt-btn-outline' : 'kt-btn-primary' }}"
                        @if($isDefault && $enabled) disabled title="Siempre disponible por defecto" @endif>
                    {{ $enabled ? 'Quitar de clientes' : 'Ofrecer a clientes' }}
                </button>
            </form>
        @elseif($pkg['installable'] ?? false)
            <form action="{{ route('admin.software-packages.install') }}" method="POST"
                  onsubmit="return confirm('Instalar {{ addslashes($pkg['label']) }} en el servidor? Esto puede tardar uno o dos minutos.')">
                @csrf
                <input type="hidden" name="slug" value="{{ $pkg['slug'] }}">
                <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">
                    <i class="ki-filled ki-down text-xs"></i> Instalar
                </button>
            </form>
        @else
            <span class="text-xs text-secondary-foreground">No disponible en APT</span>
        @endif
    </div>
</div>
