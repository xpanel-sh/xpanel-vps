@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl border-x border-b border-input bg-background mx-5 mb-5 lg:ms-(--sidebar-width) lg:mt-(--navbar-height)">
    <div class="flex grow flex-col kt-scrollable-y-auto p-5 lg:p-8">
        <main class="mx-auto flex w-full max-w-7xl grow flex-col gap-5" role="content">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div><div class="text-sm text-secondary-foreground">Administración / Finanzas</div><h1 class="mt-1 text-2xl font-semibold text-mono">Pagos</h1><p class="mt-1 text-sm text-secondary-foreground">Boletas e historial financiero de todos los hostings.</p></div>
                <div class="flex gap-2"><a class="kt-btn {{ request('payment_status') || request('submitted') ? 'kt-btn-outline' : 'kt-btn-primary' }}" href="{{ route('admin.orders.index') }}">Todos</a><a class="kt-btn {{ request('payment_status') === 'pending' && !request('submitted') ? 'kt-btn-primary' : 'kt-btn-outline' }}" href="{{ route('admin.orders.index', ['payment_status' => 'pending']) }}">Pendientes</a><a class="kt-btn {{ request('submitted') ? 'kt-btn-primary' : 'kt-btn-outline' }}" href="{{ route('admin.orders.index', ['submitted' => 1]) }}">Por revisar</a></div>
            </div>
            <div class="kt-card">
                <div class="kt-card-header"><div><h2 class="kt-card-title">Historial de boletas</h2><p class="mt-1 text-xs text-secondary-foreground">La operación del servicio se administra desde Hostings; aquí solo se controla el pago.</p></div><span class="kt-badge kt-badge-outline">{{ $orders->total() }} registros</span></div>
                <div class="overflow-x-auto"><table class="kt-table min-w-[980px]"><thead><tr><th>Boleta</th><th>Cliente</th><th>Hosting</th><th>Plan</th><th>Total</th><th>Vence</th><th>Pago</th><th></th></tr></thead><tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="font-medium text-mono">{{ $order->number }}</td>
                            <td><div class="font-medium">{{ $order->tenant->name }}</div><div class="text-xs text-secondary-foreground">{{ $order->tenant->user?->email }}</div></td>
                            <td>@if($order->hostingAccount?->hostInstance)<a class="font-medium text-primary hover:underline" href="{{ route('admin.instances.show', $order->hostingAccount->hostInstance) }}">{{ $order->hostingAccount->name }}</a>@else<span class="text-secondary-foreground">Sin instancia</span>@endif</td>
                            <td>{{ $order->plan->name }}</td><td class="font-medium text-mono">{{ $order->currency }} {{ number_format((float) $order->amount, 2) }}</td><td>{{ $order->payment_due_at?->format('d/m/Y') ?? '—' }}</td>
                            <td><span class="kt-badge kt-badge-outline {{ $order->payment_status === 'paid' ? 'kt-badge-success' : ($order->payment_submitted_at ? 'kt-badge-primary' : 'kt-badge-warning') }}">{{ $order->payment_status === 'paid' ? 'Pagado' : ($order->payment_submitted_at ? 'Por revisar' : 'Pendiente') }}</span></td>
                            <td class="text-right"><a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('admin.orders.show', $order) }}">Ver boleta</a></td>
                        </tr>
                    @empty<tr><td colspan="8" class="py-12 text-center text-secondary-foreground">No hay pagos registrados todavía.</td></tr>@endforelse
                </tbody></table></div>
            </div>
            <div>{{ $orders->links() }}</div>
        </main>
        @include('layouts.partials.admin.footer')
    </div>
</div>
@endsection
