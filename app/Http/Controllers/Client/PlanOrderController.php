<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use App\Models\HostingAccount;
use App\Models\PlanOrder;
use App\Services\HostInstanceProvisioner;
use App\Services\HostInstanceCertificateProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanOrderController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $orders = $tenant->planOrders()->with(['plan', 'hostingAccount'])->latest()->paginate(10);

        return view('client.orders.index', compact('tenant', 'orders'));
    }

    public function store(Request $request, HostingPlan $plan, HostInstanceProvisioner $provisioner, HostInstanceCertificateProvisioner $certificates)
    {
        abort_unless($plan->is_active, 404);
        $tenant = $request->attributes->get('tenant');

        if (config('xpanel.host_instances.enabled')) {
            try {
                $provisioner->assertPlanReady($plan);
            } catch (\RuntimeException $exception) {
                report($exception);

                return back()->withErrors(['plan' => 'Este plan todavía no está disponible: el servidor necesita completar la configuración de sus límites.']);
            }
        }

        $activatedAt = now();
        [$order, $account] = DB::transaction(function () use ($tenant, $plan, $activatedAt): array {
            $order = PlanOrder::create([
                'number' => 'XP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'tenant_id' => $tenant->id,
                'hosting_plan_id' => $plan->id,
                'status' => PlanOrder::STATUS_ACTIVE,
                'payment_status' => PlanOrder::PAYMENT_PENDING,
                'amount' => (float) $plan->monthly_price * $plan->billing_period_months,
                'currency' => 'USD',
                'billing_period_months' => $plan->billing_period_months,
                'payment_due_at' => now()->addDays($plan->payment_due_days),
                'activated_at' => $activatedAt,
                'service_ends_at' => $activatedAt->copy()->addMonths($plan->billing_period_months),
            ]);
            $account = HostingAccount::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'hosting_plan_id' => $plan->id,
                'plan_order_id' => $order->id,
                'name' => $plan->name.' #'.($tenant->hostingAccounts()->count() + 1),
                'status' => 'active',
            ]);
            $order->update(['hosting_account_id' => $account->id]);
            $tenant->update(['status' => 'active']);

            return [$order->fresh(), $account];
        });

        $provisioningWarning = null;
        if (config('xpanel.host_instances.enabled')) {
            try {
                $instance = $provisioner->create(
                    $account->fresh(['tenant']),
                    null,
                    Str::password(24),
                );
                if ($instance->status === 'active') {
                    $certificates->issue($instance->load('tenant.user'));
                }
            } catch (\Throwable $exception) {
                report($exception);
                $provisioningWarning = 'El plan está activo, pero la instancia requiere revisión administrativa.';
            }
        }

        return redirect()->route('client.orders.show', $order)
            ->with('success', 'Plan activo. Ya tienes acceso mientras la boleta permanece pendiente de pago.')
            ->with('warning', $provisioningWarning);
    }

    public function show(Request $request, PlanOrder $order)
    {
        $tenant = $request->attributes->get('tenant');
        abort_unless($order->tenant_id === $tenant->id, 404);
        $order->load(['plan', 'hostingAccount']);

        return view('client.orders.show', compact('tenant', 'order'));
    }
}
