<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanOrder;
use Illuminate\Http\Request;
use App\Services\HostInstanceProvisioner;
use App\Models\SystemSetting;
use App\Services\AccountNotifier;

class PlanOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = PlanOrder::query()
            ->with(['tenant.user', 'plan', 'activator', 'paymentConfirmer', 'hostingAccount.hostInstance'])
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', (string) $request->input('payment_status')))
            ->when($request->boolean('submitted'), fn ($query) => $query->whereNotNull('payment_submitted_at')->where('payment_status', PlanOrder::PAYMENT_PENDING))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(PlanOrder $order)
    {
        $order->load(['tenant.user', 'plan', 'activator', 'paymentConfirmer', 'hostingAccount.hostInstance']);

        return view('admin.orders.show', compact('order'));
    }

    public function markPaid(Request $request, PlanOrder $order, HostInstanceProvisioner $provisioner, AccountNotifier $notifier)
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
            'payment_method' => filled($validated['payment_method'] ?? null) ? trim($validated['payment_method']) : ($order->payment_method ?: 'Confirmación manual'),
            'payment_reference' => filled($validated['payment_reference'] ?? null) ? trim($validated['payment_reference']) : $order->payment_reference,
            'paid_at' => now(),
            'marked_paid_by' => $request->user('admin')->id,
        ]);
        $account = $order->hostingAccount;
        $restored = false;
        $retentionExpired = $account?->status === 'suspended_overdue' && $account->purge_after?->isPast();
        if ($account?->status === 'suspended_overdue' && ! $retentionExpired) {
            if ($account->hostInstance?->status === 'suspended') {
                $provisioner->setSuspended($account->hostInstance, false);
            }
            $account->update(['status' => 'active', 'suspended_at' => null, 'purge_after' => null]);
            $restored = true;
        }
        $notifier->send(
            $order->tenant, 'payment.confirmed', 'Pago confirmado',
            "El pago de la boleta {$order->number} fue confirmado".($restored ? ' y el hosting fue restaurado.' : '.'),
            $account, route('client.orders.show', $order, false), ['order_id' => $order->id],
        );

        return redirect()->route('admin.orders.show', $order)
            ->with('success', $restored
                ? 'Pago confirmado y hosting restaurado.'
                : ($retentionExpired ? 'Pago confirmado. La retención terminó; revisa los datos antes de restaurar manualmente.' : 'Pago confirmado. El hosting continúa activo.'));
    }

    public function cancel(PlanOrder $order, AccountNotifier $notifier)
    {
        if ($order->status === PlanOrder::STATUS_ACTIVE && $order->hostingAccount) {
            $suspendAt = $order->service_ends_at?->isFuture() ? $order->service_ends_at : now();
            $retention = (int) SystemSetting::get('cancelled_data_retention_days', '30');
            $order->hostingAccount->update([
                'status' => 'cancellation_pending', 'cancellation_requested_at' => now(),
                'suspend_at' => $suspendAt, 'purge_after' => $suspendAt->copy()->addDays($retention),
            ]);
            $order->update(['status' => PlanOrder::STATUS_CANCELLATION_PENDING]);
            $notifier->send(
                $order->tenant, 'hosting.cancellation_pending', 'Cancelación programada por administración',
                "{$order->hostingAccount->name} seguirá activo hasta {$suspendAt->format('d/m/Y H:i')}.",
                $order->hostingAccount, route('client.host.account', $order->hostingAccount, false),
            );

            return back()->with('success', 'Cancelación programada para el final del periodo contratado.');
        }
        $order->update(['status' => PlanOrder::STATUS_CANCELLED]);

        return back()->with('success', 'Contratación cancelada.');
    }
}
