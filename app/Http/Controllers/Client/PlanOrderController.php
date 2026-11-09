<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use App\Models\HostingAccount;
use App\Models\PlanOrder;
use App\Services\HostInstanceProvisioner;
use App\Services\HostInstanceCertificateProvisioner;
use App\Services\AccountNotifier;
use App\Models\SystemSetting;
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

    public function store(Request $request, HostingPlan $plan, HostInstanceProvisioner $provisioner, HostInstanceCertificateProvisioner $certificates, AccountNotifier $notifier, \App\Services\InitialHostSiteProvisioner $initialSites)
    {
        abort_unless($plan->is_active, 404);
        $tenant = $request->attributes->get('tenant');
        $initialDomain = $initialSites->validateAvailable($request->validate(['site_domain' => ['nullable', 'string', 'max:253']])['site_domain'] ?? null);

        if (config('xpanel.host_instances.enabled')) {
            try {
                $provisioner->assertPlanReady($plan);
            } catch (\RuntimeException $exception) {
                report($exception);

                return back()->withErrors(['plan' => 'Este plan todavía no está disponible: el servidor necesita completar la configuración de sus límites.']);
            }
        }

        $activatedAt = now();
        [$order, $account] = DB::transaction(function () use ($tenant, $plan, $activatedAt, $initialDomain): array {
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
                'initial_site_domain' => $initialDomain,
                'initial_site_status' => $initialDomain ? 'pending' : null,
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

        $notifier->send(
            $tenant, 'payment.pending', 'Nueva contratación pendiente de pago',
            "El plan {$plan->name} está activo. La boleta {$order->number} vence el {$order->payment_due_at->format('d/m/Y')}.",
            $account, route('client.orders.show', $order, false), ['order_id' => $order->id],
        );

        return redirect()->route('client.orders.show', $order)
            ->with('success', 'Plan activo. Ya tienes acceso mientras la boleta permanece pendiente de pago.')
            ->with('warning', $provisioningWarning);
    }

    public function show(Request $request, PlanOrder $order)
    {
        $tenant = $request->attributes->get('tenant');
        abort_unless($order->tenant_id === $tenant->id, 404);
        $order->load(['plan', 'hostingAccount']);

        $paymentMethodName = SystemSetting::get('payment_method_name', 'Transferencia bancaria');
        $paymentInstructions = SystemSetting::get('payment_instructions', 'Solicita los datos de pago al administrador.');

        return view('client.orders.show', compact('tenant', 'order', 'paymentMethodName', 'paymentInstructions'));
    }

    public function submitPayment(Request $request, PlanOrder $order, AccountNotifier $notifier)
    {
        $tenant = $request->attributes->get('tenant');
        abort_unless($order->tenant_id === $tenant?->id, 404);
        abort_unless($order->payment_status === PlanOrder::PAYMENT_PENDING, 409, 'Esta boleta ya está pagada.');
        $data = $request->validate([
            'payment_reference' => ['required', 'string', 'min:3', 'max:150'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $method = SystemSetting::get('payment_method_name', 'Transferencia bancaria');
        $order->update([
            'payment_method' => $method,
            'payment_reference' => trim($data['payment_reference']),
            'payment_note' => filled($data['payment_note'] ?? null) ? trim($data['payment_note']) : null,
            'payment_submitted_at' => now(),
        ]);
        $notifier->send(
            $tenant, 'payment.submitted', 'Pago enviado para revisión',
            "Registramos la referencia de la boleta {$order->number}. El administrador debe confirmarla.",
            $order->hostingAccount, route('client.orders.show', $order, false), ['order_id' => $order->id],
        );

        return back()->with('success', 'Referencia enviada. La boleta quedó pendiente de revisión administrativa.');
    }
}
