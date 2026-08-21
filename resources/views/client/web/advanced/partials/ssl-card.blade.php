{{-- SSL issue card partial --}}
@php $isCloudflareMode = ($mode === 'cloudflare'); @endphp
<div class="kt-card">
    <div class="kt-card-content p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-base font-semibold text-mono flex items-center gap-2">
                    <i class="ki-filled ki-shield-tick text-success"></i>
                    Certificado SSL
                    @if($isCloudflareMode)
                        <span class="kt-badge kt-badge-success text-xs">Wildcard disponible</span>
                    @endif
                </h2>
                <p class="mt-1 text-sm text-secondary-foreground">
                    @if($isCloudflareMode)
                        Emite un certificado <strong>wildcard</strong> (<code>*.{{ $domain }}</code>) vía Cloudflare DNS-01. Cubre todos tus subdominios.
                    @else
                        Emite un certificado HTTP-01 para <strong>{{ $domain }}</strong>. Requiere que el dominio ya apunte a este servidor.
                    @endif
                </p>
            </div>

            <form action="{{ route('client.websites.ssl.issue', $domain) }}" method="POST">
                @csrf
                <input type="hidden" name="mode" value="{{ $mode }}">
                @if($isCloudflareMode && !$cfToken)
                    <button type="button" class="kt-btn kt-btn-outline opacity-50 cursor-not-allowed" disabled>
                        <i class="ki-filled ki-shield-tick"></i>
                        Configura tu CF Token primero
                    </button>
                @else
                    <button type="submit" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-shield-tick"></i>
                        Emitir / Renovar SSL
                    </button>
                @endif
            </form>
        </div>
    </div>
</div>
