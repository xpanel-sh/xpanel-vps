<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Site;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $query = Domain::query()
            ->with('site')
            ->where('tenant_id', $tenant->id);

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where('domain', 'like', "%{$s}%");
        }

        $domains = $query->latest()->paginate(15)->withQueryString();

        return view('client.domains.index', compact('domains'));
    }

    public function create(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $sites = Site::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('domain')
            ->get(['id', 'domain']);

        return view('client.domains.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain', 'regex:/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/'],
            'type' => ['required', 'in:primary,alias,subdomain'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
        ]);

        if (! empty($validated['site_id'])) {
            $siteBelongsToTenant = Site::where('id', $validated['site_id'])
                ->where('tenant_id', $tenant->id)
                ->exists();

            if (! $siteBelongsToTenant) {
                return back()->withErrors(['site_id' => 'Selected site does not belong to your account.'])->withInput();
            }
        }

        Domain::create([
            'tenant_id' => $tenant->id,
            'site_id' => $validated['site_id'] ?? null,
            'domain' => strtolower($validated['domain']),
            'type' => $validated['type'],
            'dns_status' => 'pending',
            'ssl_status' => 'pending',
            'is_active' => true,
        ]);

        return redirect()->route('client.domains.index')->with('success', 'Dominio agregado correctamente.');
    }

    public function search(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $query = trim($request->input('q', ''));
        $domains = Domain::where('tenant_id', $tenant->id)->pluck('domain')->all();

        return view('client.domains.search', compact('query', 'domains'));
    }

    public function transfers(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $domains = Domain::where('tenant_id', $tenant->id)->orderBy('domain')->get(['id', 'domain', 'type', 'dns_status']);

        return view('client.domains.transfers', compact('domains'));
    }

    public function destroy(Request $request, Domain $domain)
    {
        $tenant = $request->attributes->get('tenant');

        if ($domain->tenant_id !== $tenant->id) {
            abort(403);
        }

        $domain->delete();

        return redirect()->route('client.domains.index')->with('success', 'Dominio eliminado correctamente.');
    }
}
