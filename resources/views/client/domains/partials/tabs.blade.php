@php
    $currentRoute = Route::currentRouteName();
    $isDnsActive  = in_array($currentRoute, ['client.dns.index', 'client.websites.dns-zone-editor', 'client.websites.dns-zone-editor.store', 'client.websites.dns-zone-editor.destroy']);
@endphp
<div class="pb-5">
    <div class="kt-container-fluid flex items-center justify-between flex-wrap gap-3">
        <div class="kt-scrollable-x-auto">
            <div class="kt-menu gap-5 lg:gap-7.5" data-kt-menu="true">

                <div class="kt-menu-item py-3.5 border-b-2 {{ $currentRoute === 'client.domains.index' ? 'border-mono' : 'border-transparent' }}">
                    <a class="kt-menu-link gap-2.5" href="{{ route('client.domains.index') }}">
                        <span class="kt-menu-title text-nowrap text-sm {{ $currentRoute === 'client.domains.index' ? 'font-semibold text-mono' : 'font-medium text-secondary-foreground hover:text-mono' }}">
                            Portafolio de dominios
                        </span>
                    </a>
                </div>

                <div class="kt-menu-item py-3.5 border-b-2 {{ $currentRoute === 'client.domains.search' ? 'border-mono' : 'border-transparent' }}">
                    <a class="kt-menu-link gap-2.5" href="{{ route('client.domains.search') }}">
                        <span class="kt-menu-title text-nowrap text-sm {{ $currentRoute === 'client.domains.search' ? 'font-semibold text-mono' : 'font-medium text-secondary-foreground hover:text-mono' }}">
                            Búsqueda de dominios
                        </span>
                    </a>
                </div>

                <div class="kt-menu-item py-3.5 border-b-2 {{ $currentRoute === 'client.domains.transfers' ? 'border-mono' : 'border-transparent' }}">
                    <a class="kt-menu-link gap-2.5" href="{{ route('client.domains.transfers') }}">
                        <span class="kt-menu-title text-nowrap text-sm {{ $currentRoute === 'client.domains.transfers' ? 'font-semibold text-mono' : 'font-medium text-secondary-foreground hover:text-mono' }}">
                            Transferencias
                        </span>
                    </a>
                </div>

                <div class="kt-menu-item py-3.5 border-b-2 {{ $isDnsActive ? 'border-mono' : 'border-transparent' }}">
                    <a class="kt-menu-link gap-2.5" href="{{ route('client.dns.index') }}">
                        <span class="kt-menu-title text-nowrap text-sm {{ $isDnsActive ? 'font-semibold text-mono' : 'font-medium text-secondary-foreground hover:text-mono' }}">
                            DNS
                        </span>
                    </a>
                </div>

            </div>
        </div>

        <a href="{{ route('client.domains.create') }}" class="kt-btn kt-btn-outline">
            <i class="ki-filled ki-plus"></i>
            Agregar dominio
        </a>
    </div>
</div>
