<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DnsRecord;
use App\Models\Domain;
use App\Models\NameserverSetting;
use App\Services\DaemonClient;
use App\Services\NativeCertificateProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DnsRecordController extends Controller
{
    // ── /client/dns — domain picker only ──────────────────────────────────────

    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $domains = Domain::query()->where('tenant_id', $tenant->id)->orderBy('domain')->get();

        return view('client.domains.dns', compact('domains'));
    }

    // ── /client/websites/{domain}/advanced/dns-zone-editor ─────────────────────

    public function zoneEditor(Request $request, string $domain, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');
        $allDomains = Domain::where('tenant_id', $tenant->id)->orderBy('domain')->get();
        $domainRecord = $allDomains->firstWhere('domain', $domain);

        $records = $domainRecord
            ? DnsRecord::with('domain')
                ->where('domain_id', $domainRecord->id)
                ->latest()
                ->paginate(50)
                ->withQueryString()
            : collect();

        $nameservers = NameserverSetting::where('is_active', true)->first();

        // Detect current NS live (best-effort, never block the page)
        $liveNs = [];
        try {
            $lookup = $daemon->nsLookup($domain);
            $liveNs = $lookup['nameservers'] ?? [];
        } catch (\Throwable) {
        }

        $dnsMode = $domainRecord?->dns_mode ?? 'a_record';
        $cfToken = $tenant->cloudflare_api_token ?? '';
        $serverIp = config('xpanel.server_ip', '');

        return view('client.web.advanced.dns-zone-editor', compact(
            'domain', 'domainRecord', 'allDomains', 'records',
            'nameservers', 'liveNs', 'dnsMode', 'cfToken', 'serverIp'
        ));
    }

    // ── Mode switch ────────────────────────────────────────────────────────────

    public function setMode(Request $request, string $domain)
    {
        $tenant = $request->attributes->get('tenant');
        $domainRecord = Domain::where('tenant_id', $tenant->id)->where('domain', $domain)->firstOrFail();

        $request->validate([
            'dns_mode' => ['required', 'in:xpanel_ns,a_record,cloudflare'],
        ]);

        $domainRecord->update(['dns_mode' => $request->dns_mode]);

        return back()->with('success', 'Modo DNS actualizado.');
    }

    // ── Cloudflare token (per account) ─────────────────────────────────────────

    public function saveCfToken(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $request->validate([
            'cloudflare_api_token' => ['nullable', 'string', 'max:512'],
        ]);
        $tenant->update(['cloudflare_api_token' => $request->cloudflare_api_token]);

        return back()->with('success', 'Token de Cloudflare guardado.');
    }

    // ── SSL issue via daemon ───────────────────────────────────────────────────

    public function sslIssue(Request $request, string $domain, NativeCertificateProvisioner $certificates)
    {
        $tenant = $request->attributes->get('tenant');
        $domainRecord = Domain::where('tenant_id', $tenant->id)->where('domain', $domain)->firstOrFail();

        $request->validate([
            'mode' => ['required', 'in:cloudflare,http'],
        ]);

        $cfToken = $tenant->cloudflare_api_token ?? '';
        if ($request->mode === 'cloudflare' && empty($cfToken)) {
            return back()->withErrors(['ssl' => 'Configura tu Cloudflare API Token en tu cuenta primero.']);
        }

        try {
            $certificates->issue(
                $domainRecord,
                (string) ($tenant->user?->email ?? config('mail.from.address')),
                true
            );
        } catch (\Throwable $e) {
            Log::warning('SSL issue failed', ['domain' => $domain, 'error' => $e->getMessage()]);

            return back()->withErrors(['ssl' => 'Error al emitir el certificado: '.$e->getMessage()]);
        }

        return back()->with('success', 'Certificado SSL emitido correctamente para '.$domain);
    }

    public function zoneEditorStore(Request $request, string $domain, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');
        $domainRecord = Domain::where('tenant_id', $tenant->id)->where('domain', $domain)->firstOrFail();

        $request->validate([
            'type' => ['required', 'array', 'min:1'],
            'type.*' => ['required', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA'],
            'name' => ['required', 'array', 'min:1'],
            'name.*' => ['required', 'string', 'max:255'],
            'value' => ['required', 'array', 'min:1'],
            'value.*' => ['required', 'string', 'max:2000'],
            'ttl' => ['required', 'array', 'min:1'],
            'ttl.*' => ['required', 'integer', 'min:60', 'max:86400'],
            'priority' => ['nullable', 'array'],
            'priority.*' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $types = $request->input('type', []);
        $names = $request->input('name', []);
        $values = $request->input('value', []);
        $ttls = $request->input('ttl', []);
        $priorities = $request->input('priority', []);

        $saved = 0;
        $failed = 0;
        $daemonError = null;

        foreach ($types as $i => $type) {
            $record = DnsRecord::create([
                'tenant_id' => $tenant->id,
                'domain_id' => $domainRecord->id,
                'type' => $type,
                'name' => $names[$i] ?? '@',
                'value' => $values[$i] ?? '',
                'ttl' => (int) ($ttls[$i] ?? 3600),
                'priority' => isset($priorities[$i]) && $priorities[$i] !== '' ? (int) $priorities[$i] : null,
                'is_active' => true,
            ]);

            try {
                $daemon->upsertDnsRecord([
                    'domain' => $domainRecord->domain,
                    'type' => $record->type,
                    'name' => $record->name,
                    'value' => $record->value,
                    'ttl' => $record->ttl,
                    'priority' => $record->priority,
                ]);
                $saved++;
            } catch (\Throwable $e) {
                Log::warning('DNS zone editor upsert failed', ['record_id' => $record->id, 'exception' => $e]);
                $failed++;
                $daemonError = $e->getMessage();
            }
        }

        $domainRecord->update(['dns_status' => $failed === 0 ? 'managed' : 'provision_error']);

        if ($failed > 0 && $saved === 0) {
            return back()->withErrors(['dns' => 'Registros guardados pero el agente no pudo aplicarlos. Revisa las operaciones del agente.']);
        }

        $msg = $saved === 1
            ? 'Registro DNS añadido correctamente.'
            : "{$saved} registros DNS añadidos correctamente.";

        if ($failed > 0) {
            $msg .= " ({$failed} no pudieron aplicarse al agente)";
        }

        return back()->with('success', $msg);
    }

    public function zoneEditorDestroy(Request $request, string $domain, DnsRecord $record, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');

        if ($record->tenant_id !== $tenant->id) {
            abort(403);
        }

        try {
            $daemon->deleteDnsRecord([
                'domain' => $record->domain?->domain,
                'type' => $record->type,
                'name' => $record->name,
                'value' => $record->value,
            ]);
        } catch (\Throwable $e) {
            Log::warning('DNS zone editor delete failed', ['record_id' => $record->id, 'exception' => $e]);

            return back()->withErrors(['dns' => 'El agente no pudo eliminar el registro.']);
        }

        $record->delete();

        return back()->with('success', 'Registro DNS eliminado.');
    }

    // ── Legacy /client/dns store & destroy (kept for backward compat) ──────────

    public function store(Request $request, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'domain_id' => ['required', 'integer', 'exists:domains,id'],
            'type' => ['required', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA'],
            'name' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:2000'],
            'ttl' => ['required', 'integer', 'min:60', 'max:86400'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $domain = Domain::where('id', $validated['domain_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $record = DnsRecord::create([
            'tenant_id' => $tenant->id,
            'domain_id' => $domain->id,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'value' => $validated['value'],
            'ttl' => $validated['ttl'],
            'priority' => $validated['priority'] ?? null,
            'is_active' => true,
        ]);

        try {
            $daemon->upsertDnsRecord([
                'domain' => $domain->domain,
                'type' => $record->type,
                'name' => $record->name,
                'value' => $record->value,
                'ttl' => $record->ttl,
                'priority' => $record->priority,
            ]);
            $domain->update(['dns_status' => 'managed']);
        } catch (\Throwable $e) {
            Log::warning('DNS record upsert failed', ['record_id' => $record->id, 'exception' => $e]);
            $domain->update(['dns_status' => 'provision_error']);

            return back()->withErrors(['dns' => 'Registro guardado, pero el agente no pudo aplicarlo.']);
        }

        return back()->with('success', 'Registro DNS añadido correctamente.');
    }

    public function destroy(Request $request, DnsRecord $record, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');

        if ($record->tenant_id !== $tenant->id) {
            abort(403);
        }

        try {
            $daemon->deleteDnsRecord([
                'domain' => $record->domain?->domain,
                'type' => $record->type,
                'name' => $record->name,
                'value' => $record->value,
            ]);
        } catch (\Throwable $e) {
            Log::warning('DNS record deletion failed', ['record_id' => $record->id, 'exception' => $e]);

            return back()->withErrors(['dns' => 'El agente no pudo eliminar el registro.']);
        }

        $record->delete();

        return back()->with('success', 'Registro DNS eliminado.');
    }
}
