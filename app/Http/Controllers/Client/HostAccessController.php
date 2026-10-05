<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use App\Services\HostSsoLink;
use App\Services\HostInstancePanelDomainManager;
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
        $hostingAccount->load(['plan', 'hostInstance']);

        return view('client.host.account', compact('hostingAccount'));
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
