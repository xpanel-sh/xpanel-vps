<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use App\Models\HostingPlan;
use App\Models\PlanOrder;
use App\Models\SystemSetting;
use App\Services\HostingPlanChanger;
use App\Services\HostInstanceProvisioner;
use App\Services\HostSsoLink;
use App\Services\HostInstancePanelDomainManager;
use App\Services\AccountNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HostAccessController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $accounts = $tenant->hostingAccounts()->with(['plan', 'hostInstance'])->latest()->get();

        return view('client.host.show', compact('tenant', 'accounts'));
    }

    public function account(Request $request, HostingAccount $hostingAccount)
    {
        $this->authorizeAccount($request, $hostingAccount);
        $hostingAccount->load(['plan', 'hostInstance', 'order']);
        $plans = HostingPlan::query()->where('is_active', true)->orderBy('monthly_price')->get();

        return view('client.host.account', compact('hostingAccount', 'plans'));
    }

    public function changePlan(Request $request, HostingAccount $hostingAccount, HostingPlanChanger $changer)
    {
        $this->authorizeAccount($request, $hostingAccount);
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:hosting_plans,id']]);
        try {
            $order = $changer->change($hostingAccount, HostingPlan::findOrFail($data['plan_id']));
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['plan' => $exception->getMessage()]);
        }

        return redirect()->route('client.orders.show', $order)->with('success', 'El nuevo plan fue aplicado a la misma instancia.');
    }

    public function cancel(Request $request, HostingAccount $hostingAccount, AccountNotifier $notifier)
    {
        $this->authorizeAccount($request, $hostingAccount);
        abort_unless($hostingAccount->status === 'active', 409, 'El hosting no está activo.');
        $order = $hostingAccount->order;
        $suspendAt = $order?->service_ends_at?->isFuture() ? $order->service_ends_at : now();
        $retention = (int) SystemSetting::get('cancelled_data_retention_days', '30');
        $hostingAccount->update([
            'status' => 'cancellation_pending',
            'cancellation_requested_at' => now(),
            'suspend_at' => $suspendAt,
            'purge_after' => $suspendAt->copy()->addDays($retention),
        ]);
        $order?->update(['status' => PlanOrder::STATUS_CANCELLATION_PENDING]);
        $notifier->send(
            $hostingAccount->tenant, 'hosting.cancellation_pending', 'Cancelación programada',
            "{$hostingAccount->name} seguirá activo hasta {$suspendAt->format('d/m/Y H:i')} y después conservará sus datos durante {$retention} días.",
            $hostingAccount, route('client.host.account', $hostingAccount, false),
        );

        return back()->with('info', 'Cancelación programada. El servicio funcionará hasta '.$suspendAt->format('d/m/Y H:i').'.');
    }

    public function restore(Request $request, HostingAccount $hostingAccount, HostInstanceProvisioner $provisioner, AccountNotifier $notifier)
    {
        $this->authorizeAccount($request, $hostingAccount);
        abort_unless(in_array($hostingAccount->status, ['cancellation_pending', 'suspended'], true), 409, 'El hosting no tiene una cancelación reversible.');
        if ($hostingAccount->purge_after?->isPast()) abort(410, 'El periodo de retención terminó. Contacta al administrador.');
        if ($hostingAccount->hostInstance?->status === 'suspended') $provisioner->setSuspended($hostingAccount->hostInstance, false);
        $hostingAccount->order?->update(['status' => PlanOrder::STATUS_ACTIVE]);
        $hostingAccount->update(['status' => 'active', 'cancellation_requested_at' => null, 'suspend_at' => null, 'suspended_at' => null, 'purge_after' => null]);
        $notifier->send(
            $hostingAccount->tenant, 'hosting.restored', 'Hosting restaurado',
            "La cancelación de {$hostingAccount->name} fue retirada y el servicio está activo.",
            $hostingAccount, route('client.host.account', $hostingAccount, false),
        );

        return back()->with('success', 'Cancelación retirada y hosting restaurado.');
    }

    public function access(Request $request, HostingAccount $hostingAccount, HostSsoLink $sso)
    {
        $this->authorizeAccount($request, $hostingAccount);
        $instance = $hostingAccount->hostInstance;
        abort_unless($hostingAccount->status === 'active' && $instance?->status === 'active', 409, 'El hosting todavía no está disponible.');

        return redirect()->away($sso->for($instance));
    }

    public function updateDomain(
        Request $request,
        HostingAccount $hostingAccount,
        HostInstancePanelDomainManager $domains,
    ) {
        $this->authorizeAccount($request, $hostingAccount);
        $data = $request->validate([
            'custom_panel_domain' => [
                'nullable', 'string', 'max:253',
                'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/',
                Rule::unique('hosting_accounts', 'custom_panel_domain')->ignore($hostingAccount),
                Rule::unique('host_instances', 'panel_domain'),
            ],
        ]);
        $domain = strtolower(trim((string) ($data['custom_panel_domain'] ?? '')));
        if ($instance = $hostingAccount->hostInstance) {
            $domains->stage($instance, $domain ?: null);
            $domains->apply($instance->fresh());
        } else {
            $hostingAccount->update([
                'custom_panel_domain' => $domain ?: null,
                'custom_domain_status' => $domain ? 'waiting_dns' : 'not_configured',
                'custom_domain_last_error' => null,
            ]);
        }

        return back()->with($domain ? 'success' : 'info', $domain
            ? 'Dominio guardado. XPanel emitirá el SSL cuando su DNS apunte al servidor.'
            : 'Se retiró el dominio personalizado; la dirección incluida continúa activa.');
    }

    private function authorizeAccount(Request $request, HostingAccount $account): void
    {
        abort_unless($account->tenant_id === $request->attributes->get('tenant')?->id, 404);
    }
}
