@extends('layouts.client-store')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-7.5">
        <div><h1 class="text-xl font-medium text-mono">Mis contrataciones</h1><p class="text-sm text-secondary-foreground mt-1">Boletas, vencimientos y estado de los planes solicitados.</p></div>
        <a class="kt-btn kt-btn-primary" href="{{ route('client.plans.index') }}"><i class="ki-filled ki-plus"></i> Contratar plan</a>
    </div>
    <div class="kt-card">
        <div class="kt-card-table kt-scrollable-x-auto">
            <table class="kt-table align-middle text-sm">
                <thead><tr><th>Boleta</th><th>Hosting</th><th>Plan</th><th>Total</th><th>Fecha límite</th><th>Servicio</th><th>Pago</th><th></th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="font-medium text-mono">{{ $order->number }}</td>
                        <td>{{ $order->hostingAccount?->name ?? 'Servicio anterior' }}</td>
                        <td>{{ $order->plan->name }}</td>
                        <td>{{ $order->currency }} {{ number_format((float) $order->amount, 2) }}</td>
                        <td>{{ $order->payment_due_at->format('d/m/Y') }}</td>
                        <td><span class="kt-badge kt-badge-outline {{ $order->status === 'active' ? 'kt-badge-success' : '' }}">{{ $order->status === 'active' ? 'Activo' : 'Cancelado' }}</span></td>
                        <td><span class="kt-badge kt-badge-outline {{ $order->payment_status === 'paid' ? 'kt-badge-success' : 'kt-badge-warning' }}">{{ $order->payment_status === 'paid' ? 'Pagado' : 'Pendiente' }}</span></td>
                        <td class="text-right"><a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('client.orders.show', $order) }}">Ver detalle</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-secondary-foreground">Todavía no tienes contrataciones.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</div>
@endsection
