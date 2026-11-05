<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\HostBrokerResource;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $managed = HostBrokerResource::query()
            ->where('type', 'site-domain')
            ->where('name', 'not like', '*.%')
            ->with(['hostInstance.tenant', 'hostInstance.hostingAccount'])
            ->get()
            ->map(fn (HostBrokerResource $resource): array => [
                'domain' => $resource->name,
                'tenant' => $resource->hostInstance?->tenant,
                'hosting' => $resource->hostInstance?->hostingAccount?->name,
                'kind' => 'Sitio en Host',
                'source' => 'managed',
                'status' => $resource->hostInstance?->status ?? 'unknown',
                'dns' => 'Gestionado en Host',
                'ssl' => 'Gestionado en Host',
                'site' => null,
                'instance' => $resource->hostInstance,
                'created_at' => $resource->created_at,
            ]);

        $native = Site::query()->with('tenant')->get()->map(fn (Site $site): array => [
            'domain' => $site->domain,
            'tenant' => $site->tenant,
            'hosting' => 'Sitio nativo',
            'kind' => 'Sitio web',
            'source' => 'native',
            'status' => $site->status,
            'dns' => 'Servidor VPS',
            'ssl' => $site->ssl_status ?: 'pending',
            'site' => $site,
            'instance' => null,
            'created_at' => $site->created_at,
        ]);

        $registered = Domain::query()->with(['tenant', 'site'])->get()->map(fn (Domain $domain): array => [
            'domain' => $domain->domain,
            'tenant' => $domain->tenant,
            'hosting' => $domain->site?->domain ? 'Asociado a '.$domain->site->domain : 'Sin sitio asociado',
            'kind' => $domain->site ? 'Dominio de sitio' : 'Dominio registrado',
            'source' => 'registered',
            'status' => $domain->is_active ? 'active' : 'inactive',
            'dns' => $domain->dns_status,
            'ssl' => $domain->ssl_status,
            'site' => $domain->site,
            'instance' => null,
            'created_at' => $domain->created_at,
        ]);

        $rows = $managed->concat($native)->concat($registered)
            ->unique(fn (array $row): string => strtolower($row['domain']))
            ->when($request->filled('search'), function ($rows) use ($request) {
                $search = mb_strtolower(trim((string) $request->input('search')));

                return $rows->filter(fn (array $row): bool => str_contains(mb_strtolower(
                    $row['domain'].' '.($row['tenant']?->name ?? '').' '.($row['hosting'] ?? '')
                ), $search));
            })
            ->sortBy('domain')
            ->values();

        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 20;
        $domains = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
        $summary = [
            'total' => $rows->count(),
            'managed' => $rows->where('source', 'managed')->count(),
            'native' => $rows->where('source', 'native')->count(),
            'registered' => $rows->where('source', 'registered')->count(),
        ];

        return view('admin.domains.index', compact('domains', 'summary'));
    }
}
