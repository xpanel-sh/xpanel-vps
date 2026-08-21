{{-- Expects: $pkg (native package state), $installed, $active, $enabled, $isDefault --}}
<div class="rounded-xl border border-border p-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <p class="font-semibold text-sm text-mono">{{ $pkg['label'] }}</p>
            <p class="text-xs text-secondary-foreground font-mono">{{ $pkg['slug'] }}@if(!empty($pkg['version'])) · {{ $pkg['version'] }}@endif</p>
        </div>
        @if($installed)
            <span class="kt-badge {{ $active ? 'kt-badge-success' : 'kt-badge-outline kt-badge-secondary' }} text-xs">
                {{ $active ? 'Activo' : 'Instalado' }}
            </span>
        @elseif($pkg['installable'] ?? false)
            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">No instalado</span>
        @endif
    </div>

    <div class="mt-3 flex items-center justify-between gap-2">
        @if($installed)
            <form action="{{ route('admin.software-packages.toggle') }}" method="POST">
                @csrf
                <input type="hidden" name="slug" value="{{ $pkg['slug'] }}">
                <input type="hidden" name="category" value="{{ $pkg['category'] }}">
                <button type="submit" class="kt-btn kt-btn-sm {{ $enabled ? 'kt-btn-outline' : 'kt-btn-primary' }}"
                        @if($isDefault && $enabled) disabled title="Siempre disponible por defecto" @endif>
                    {{ $enabled ? 'Quitar de clientes' : 'Ofrecer a clientes' }}
                </button>
            </form>
        @else
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
