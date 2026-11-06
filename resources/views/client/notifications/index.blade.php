@extends('layouts.client-store')

@section('content')
<div class="kt-container-fixed max-w-[1000px]">
    <div class="mb-7.5 flex flex-wrap items-center justify-between gap-3">
        <div><div class="text-sm text-secondary-foreground">Mi cuenta</div><h1 class="mt-1 text-2xl font-semibold text-mono">Notificaciones</h1><p class="mt-1 text-sm text-secondary-foreground">Eventos reales de tus hostings, planes y pagos.</p></div>
        @if($unreadCount > 0)<form method="post" action="{{ route('client.notifications.read-all') }}">@csrf<button class="kt-btn kt-btn-outline" type="submit"><i class="ki-filled ki-check"></i> Marcar todas como leídas</button></form>@endif
    </div>
    @if(session('success'))<div class="kt-alert kt-alert-success mb-5">{{ session('success') }}</div>@endif
    <div class="kt-card"><div class="kt-card-header"><h2 class="kt-card-title">Historial</h2><span class="kt-badge kt-badge-outline {{ $unreadCount ? 'kt-badge-primary' : 'kt-badge-success' }}">{{ $unreadCount }} sin leer</span></div>
        <div class="divide-y divide-border">
            @forelse($notifications as $notification)
                <form method="post" action="{{ route('client.notifications.read', $notification) }}" class="flex items-start gap-4 p-5 {{ $notification->read_at ? '' : 'bg-primary/5' }}">@csrf
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-muted text-secondary-foreground' : 'bg-primary/10 text-primary' }}"><i class="ki-filled {{ str_contains($notification->type, 'payment') ? 'ki-dollar' : (str_contains($notification->type, 'suspend') ? 'ki-lock' : 'ki-information-2') }}"></i></div>
                    <div class="min-w-0 grow"><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-mono">{{ $notification->title }}</h3>@if(!$notification->read_at)<span class="kt-badge kt-badge-sm kt-badge-primary">Nueva</span>@endif</div><p class="mt-1 text-sm leading-6 text-secondary-foreground">{{ $notification->message }}</p><div class="mt-2 text-xs text-muted-foreground">{{ $notification->created_at->format('d/m/Y H:i') }}</div></div>
                    <button class="kt-btn kt-btn-sm kt-btn-ghost shrink-0" type="submit">{{ $notification->action_url ? 'Ver detalle' : 'Leída' }} <i class="ki-filled ki-right"></i></button>
                </form>
            @empty<div class="p-12 text-center text-secondary-foreground"><i class="ki-filled ki-notification-status text-3xl"></i><p class="mt-3">Todavía no hay notificaciones.</p></div>@endforelse
        </div>
    </div>
    <div class="mt-5">{{ $notifications->links() }}</div>
</div>
@endsection
