@extends('layouts.client-store')

@section('content')
<div class="kt-container-fixed max-w-[980px]">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-7.5">
        <div><a class="kt-link text-sm" href="{{ route('client.orders.index') }}">Volver a contrataciones</a><h1 class="text-xl font-medium text-mono mt-2">Boleta {{ $order->number }}</h1></div>
        <div class="flex gap-2"><span class="kt-badge kt-badge-outline {{ $order->status === 'active' ? 'kt-badge-success' : '' }}">Servicio {{ $order->status === 'active' ? 'activo' : 'cancelado' }}</span><span class="kt-badge kt-badge-outline {{ $order->payment_status === 'paid' ? 'kt-badge-success' : 'kt-badge-warning' }}">Pago {{ $order->payment_status === 'paid' ? 'confirmado' : 'pendiente' }}</span></div>
    </div>
    @if(session('success'))<div class="kt-alert kt-alert-success mb-5">{{ session('success') }}</div>@endif
    <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5">
        <div class="kt-card lg:col-span-2">
            <div class="kt-card-header"><h3 class="kt-card-title">Detalle de la contratación</h3></div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table align-middle text-sm">
                    <tr><td class="text-secondary-foreground">Cliente</td><td class="text-right font-medium">{{ $tenant->name }}</td></tr>
                    <tr><td class="text-secondary-foreground">Plan</td><td class="text-right font-medium">{{ $order->plan->name }}</td></tr>
                    <tr><td class="text-secondary-foreground">Periodo</td><td class="text-right">{{ $order->billing_period_months }} mes(es)</td></tr>
                    <tr><td class="text-secondary-foreground">Método de pago</td><td class="text-right">{{ $order->payment_method ?: 'Pendiente de integración' }}</td></tr>
                    <tr><td class="text-secondary-foreground">Fecha límite de pago</td><td class="text-right font-medium">{{ $order->payment_due_at->format('d/m/Y H:i') }}</td></tr>
                    @if($order->service_ends_at)<tr><td class="text-secondary-foreground">Servicio vigente hasta</td><td class="text-right font-medium">{{ $order->service_ends_at->format('d/m/Y') }}</td></tr>@endif
                </table>
            </div>
        </div>
        <div class="kt-card content-start">
            <div class="kt-card-content p-7.5">
                <div class="text-sm text-secondary-foreground">Total</div><div class="text-3xl text-mono font-semibold mt-2">{{ $order->currency }} {{ number_format((float) $order->amount, 2) }}</div>
                @if($order->payment_status === 'pending')<p class="text-sm text-secondary-foreground leading-6 mt-5">Tu hosting ya está habilitado. Por ahora no hay métodos de pago conectados; el administrador confirmará el pago sin modificar tu acceso.</p>@endif
                <a class="kt-btn kt-btn-primary justify-center w-full mt-6" href="{{ $order->hostingAccount ? route('client.host.account', $order->hostingAccount) : route('client.host.show') }}">Ir a este hosting</a>
                <a class="kt-btn kt-btn-outline justify-center w-full mt-2" href="mailto:{{ \App\Support\PublicPageRegistry::content('home')['sales_email'] }}?subject=Boleta {{ $order->number }}">Contactar sobre esta boleta</a>
            </div>
        </div>
    </div>
</div>
@endsection
