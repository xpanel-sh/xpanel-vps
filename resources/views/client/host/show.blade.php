@extends('layouts.client-store')

@section('content')
<div class="kt-container-fixed max-w-[1180px]">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-7.5">
        <div><h1 class="text-xl font-medium text-mono">Mis hostings</h1><p class="text-sm text-secondary-foreground mt-1">Cada contratación tiene recursos, versión y panel XPanel Host independientes.</p></div>
        <a class="kt-btn kt-btn-primary" href="{{ route('client.plans.index') }}"><i class="ki-filled ki-plus"></i> Contratar hosting</a>
    </div>
    @if($accounts->isEmpty())
        <div class="kt-card"><div class="kt-card-content p-10 text-center"><h2 class="text-lg font-medium text-mono">Aún no tienes hostings</h2><p class="text-sm text-secondary-foreground mt-2">Elige un plan para crear tu primera cuenta de alojamiento.</p><a class="kt-btn kt-btn-primary mt-5" href="{{ route('client.plans.index') }}">Ver planes</a></div></div>
    @else
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($accounts as $account)
                @php($instance = $account->hostInstance)
                <article class="kt-card">
                    <div class="kt-card-header"><h2 class="kt-card-title">{{ $account->name }}</h2><span class="kt-badge kt-badge-outline {{ $account->status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }}">{{ ucfirst($account->status) }}</span></div>
                    <div class="kt-card-content p-6 grid gap-4 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-secondary-foreground">Plan</span><strong class="text-mono">{{ $account->plan?->name ?? 'Sin plan' }}</strong></div>
                        <div class="flex justify-between gap-3"><span class="text-secondary-foreground">Panel</span><span class="text-mono truncate">{{ $account->custom_panel_domain ?: ($instance?->panel_domain ?? 'Preparando') }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-secondary-foreground">Versión</span><span class="text-mono">{{ $instance?->version ?? 'Pendiente' }}</span></div>
                        <a class="kt-btn kt-btn-outline justify-center mt-2" href="{{ route('client.host.account', $account) }}">Ver hosting</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
