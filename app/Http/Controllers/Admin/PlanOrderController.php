<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanOrder;
use Illuminate\Http\Request;

class PlanOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = PlanOrder::query()
            ->with(['tenant.user', 'plan', 'activator', 'paymentConfirmer'])
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', (string) $request->input('payment_status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(PlanOrder $order)
    {
        $order->load(['tenant.user', 'plan', 'activator', 'paymentConfirmer']);

        return view('admin.orders.show', compact('order'));
    }

    public function markPaid(Request $request, PlanOrder $order)
    {
        if ($order->payment_status === PlanOrder::PAYMENT_PAID) {
            return back()->with('info', 'Esta boleta ya figura como pagada.');
        }

        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:100'],
            'payment_reference' => ['nullable', 'string', 'max:150'],
        ]);
        $order->update([
            'payment_status' => PlanOrder::PAYMENT_PAID,
            'payment_method' => $validated['payment_method'] ?: 'Confirmación manual',
            'payment_reference' => $validated['payment_reference'] ?? null,
            'paid_at' => now(),
            'marked_paid_by' => $request->user('admin')->id,
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Pago confirmado. El acceso al hosting no fue modificado.');
    }

    public function cancel(PlanOrder $order)
    {
        abort_if($order->status === PlanOrder::STATUS_ACTIVE, 422, 'No se puede cancelar una contratación activa.');
        $order->update(['status' => PlanOrder::STATUS_CANCELLED]);

        return back()->with('success', 'Contratación cancelada.');
    }
}
