<?php

namespace App\Http\Controllers\Client\Web;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\EmailAccount;
use App\Models\ManagedDatabase;
use App\Models\Site;
use App\Services\DaemonClient;
use App\Services\SiteProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    /**
     * Display a listing of the sites.
     */
    public function index()
    {
        $tenant = request()->attributes->get('tenant');
        $search = trim((string) request('search'));
        $tenant?->loadMissing('plan');
        $sites = Site::where('tenant_id', $tenant->id)
            ->when($search !== '', fn ($query) => $query->where('domain', 'like', "%{$search}%"))
            ->latest()
            ->get();

        return view('client.web.index', compact('sites', 'tenant', 'search'));
    }

    /**
     * Show the form for creating a new site.
     */
    public function create(SiteProvisioner $provisioner)
    {
        $availableEngines = $provisioner->availableEngines();
        $availablePhpVersions = $provisioner->availablePhpVersions();

        return view('client.web.create', compact('availableEngines', 'availablePhpVersions'));
    }

    public function panel(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');

        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        return redirect()->route('client.websites.show', ['domain' => $site->domain]);
    }

    public function panelByDomain(Request $request, string $domain)
    {
        $site = $this->siteForDomain($request, $domain);

        return $this->renderSitePanel($site);
    }

    public function module(Request $request, string $domain, string $section, ?string $page = null)
    {
        $site = $this->siteForDomain($request, $domain);
        $activePath = trim($section.'/'.trim((string) $page, '/'), '/');

        if ($activePath === 'files/file-manager') {
            return redirect()->route('client.website.file-manager.entry', ['domain' => $site->domain]);
        }

        return $this->renderSitePanel($site, $activePath);
    }

    public function fileManager(Request $request, string $domain)
    {
        $site = $this->siteForDomain($request, $domain);

        return view('client.web.files.index', compact('site'));
    }

    private function renderSitePanel(Site $site, ?string $activePath = null)
    {
        $site->loadMissing('tenant.plan');
        $tenant = $site->tenant;

        $stats = [
            'domains' => Domain::where('tenant_id', $site->tenant_id)->where('site_id', $site->id)->count(),
            'databases' => ManagedDatabase::where('tenant_id', $site->tenant_id)->where('site_id', $site->id)->count(),
            'emails' => EmailAccount::where('tenant_id', $site->tenant_id)
                ->whereHas('domain', fn ($query) => $query->where('site_id', $site->id))
                ->count(),
            'tenant_sites' => $tenant?->sites()->count() ?? 0,
            'tenant_databases' => ManagedDatabase::where('tenant_id', $site->tenant_id)->count(),
            'tenant_emails' => EmailAccount::where('tenant_id', $site->tenant_id)->count(),
            'ssl_active' => Domain::where('tenant_id', $site->tenant_id)
                ->where('site_id', $site->id)
                ->whereIn('ssl_status', ['active', 'managed', 'issued'])
                ->exists(),
        ];

        $siteMenu = $this->websiteMenu($site);
        $activeModule = $this->moduleForPath($siteMenu, $activePath);
        $databases = collect();

        if ($activePath === 'databases/my-sql-databases') {
            $databases = ManagedDatabase::query()
                ->with('dbUsers')
                ->where('tenant_id', $site->tenant_id)
                ->where('site_id', $site->id)
                ->latest()
                ->get();
        }

        if ($activePath) {
            return view($this->moduleViewForPath($activePath), compact('site', 'stats', 'siteMenu', 'activePath', 'activeModule', 'databases'));
        }

        return view('client.web.panel.index', compact('site', 'stats', 'siteMenu', 'activePath', 'activeModule'));
    }

    /**
     * Store a newly created site in storage.
     */
    public function store(Request $request, SiteProvisioner $provisioner)
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'domain' => 'required|regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i|unique:sites,domain',
            'project_type' => ['required', Rule::in(SiteProvisioner::NATIVE_TYPES)],
            'web_server' => ['required', Rule::in($provisioner->availableEngines())],
            'php_version' => ['required', Rule::in($provisioner->availablePhpVersions())],
        ]);

        try {
            $provisioner->provisionForTenant($tenant, $validated);
        } catch (\Throwable $e) {
            Log::warning('Site provisioning failed', ['tenant_id' => $tenant->id, 'domain' => $validated['domain'] ?? null, 'exception' => $e]);

            return back()->withErrors(['domain' => 'No se pudo preparar el sitio nativo. Revisa la configuración del servidor o contacta soporte.'])->withInput();
        }

        return redirect()->route('client.websites.index')->with('success', 'Sitio nativo preparado correctamente.');
    }

    public function updatePhpVersion(Request $request, string $domain, SiteProvisioner $provisioner)
    {
        $site = $this->siteForDomain($request, $domain);

        if ($site->project_type !== 'php') {
            return back()->withErrors(['php_version' => 'Este sitio no usa PHP.']);
        }

        $validated = $request->validate([
            'php_version' => ['required', Rule::in($provisioner->availablePhpVersions())],
        ]);

        try {
            $provisioner->reconfigure($site, ['php_version' => $validated['php_version']]);
        } catch (\Throwable $e) {
            Log::error('Native PHP version change failed', ['site' => $domain, 'exception' => $e]);

            return redirect()->route('client.websites.module', ['domain' => $domain, 'section' => 'advanced', 'page' => 'php-configuration'])
                ->withErrors(['php_version' => 'No se pudo aplicar la versión PHP en el servidor.'])
                ->with('php_tab', 'version');
        }

        return redirect()->route('client.websites.module', ['domain' => $domain, 'section' => 'advanced', 'page' => 'php-configuration'])
            ->with('success', 'PHP '.$validated['php_version'].' activado. El sitio fue reiniciado.')
            ->with('php_tab', 'version');
    }

    public function switchEngine(Request $request, string $domain, SiteProvisioner $provisioner)
    {
        $site = $this->siteForDomain($request, $domain);

        if (! SiteProvisioner::isNativeType($site->project_type)) {
            return back()->withErrors(['web_server' => 'Este sitio no soporta cambio de motor web.']);
        }

        $validated = $request->validate([
            'web_server' => ['required', Rule::in($provisioner->availableEngines())],
        ]);

        if (! in_array($validated['web_server'], $provisioner->availableEngines(), true)) {
            return back()->withErrors(['web_server' => 'Ese motor web no está disponible en este servidor.']);
        }

        try {
            $provisioner->reconfigure($site, ['web_server' => $validated['web_server']]);
        } catch (\Throwable $e) {
            Log::error('Native engine switch failed', ['site' => $domain, 'exception' => $e]);

            return redirect()->route('client.websites.module', ['domain' => $domain, 'section' => 'advanced', 'page' => 'php-configuration'])
                ->withErrors(['web_server' => 'No se pudo aplicar el motor web en el servidor.'])
                ->with('php_tab', 'engine');
        }

        return redirect()->route('client.websites.module', ['domain' => $domain, 'section' => 'advanced', 'page' => 'php-configuration'])
            ->with('success', 'Motor web cambiado a '.$validated['web_server'].'.')
            ->with('php_tab', 'engine');
    }

    public function updatePhpOptions(Request $request, string $domain, SiteProvisioner $provisioner)
    {
        $site = $this->siteForDomain($request, $domain);

        if ($site->project_type !== 'php') {
            return back()->withErrors(['options' => 'Este sitio no usa PHP.']);
        }

        $validated = $request->validate([
            'memory_limit' => 'required|in:64M,128M,256M,512M,1024M',
            'upload_max_filesize' => 'required|in:8M,32M,64M,128M,256M',
            'post_max_size' => 'required|in:8M,32M,64M,128M,256M',
            'max_execution_time' => 'required|in:30,60,120,300',
            'max_input_time' => 'required|in:30,60,120,300',
        ]);

        try {
            $provisioner->reconfigure($site, ['php_options' => $validated]);
        } catch (\Throwable $e) {
            Log::error('Native PHP options update failed', ['site' => $domain, 'exception' => $e]);

            return redirect()->route('client.websites.module', ['domain' => $domain, 'section' => 'advanced', 'page' => 'php-configuration'])
                ->withErrors(['options' => 'No se pudieron aplicar las opciones PHP en el servidor.'])
                ->with('php_tab', 'options');
        }

        return redirect()->route('client.websites.module', ['domain' => $domain, 'section' => 'advanced', 'page' => 'php-configuration'])
            ->with('success', 'Opciones PHP guardadas. El sitio fue reiniciado.')
            ->with('php_tab', 'options');
    }

    public function status(Request $request, Site $site, DaemonClient $daemon): \Illuminate\Http\JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if (SiteProvisioner::isNativeType($site->project_type)) {
            // Native sites are provisioned synchronously — there's no
            // container to poll for health, the stored status is final.
            return response()->json([
                'site_status' => $site->status,
                'container_status' => $site->status === 'active' ? 'running' : $site->status,
            ]);
        }

        $containerName = 'xpanel-site-'.str_replace('.', '-', strtolower($site->domain));

        try {
            $daemonStatus = $daemon->siteStatus($containerName);
        } catch (\Throwable $e) {
            Log::warning('Site status check failed', ['site_id' => $site->id, 'exception' => $e]);

            return response()->json([
                'site_status' => $site->status,
                'container_status' => 'unknown',
            ]);
        }

        $containerState = $daemonStatus['status'] ?? 'unknown';

        $newSiteStatus = match ($containerState) {
            'running' => 'active',
            'restarting' => 'provisioning',
            'exited' => 'provision_error',
            'not_found' => $site->status === 'provisioning' ? 'provisioning' : 'provision_error',
            default => $site->status,
        };

        if ($newSiteStatus !== $site->status) {
            $site->update(['status' => $newSiteStatus]);
        }

        return response()->json([
            'site_status' => $newSiteStatus,
            'container_status' => $containerState,
        ]);
    }

    public function restart(Request $request, Site $site, SiteProvisioner $provisioner)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        try {
            $provisioner->restart($site);

            return redirect()->route('client.websites.index')->with('success', 'Sitio reiniciado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Site restart failed', ['site_id' => $site->id, 'exception' => $e]);

            return redirect()->route('client.websites.index')
                ->withErrors(['site' => 'No se pudo reiniciar el sitio. Revisa la configuración del servidor.']);
        }
    }

    public function destroy(Request $request, Site $site, SiteProvisioner $provisioner)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        try {
            $provisioner->remove($site);
        } catch (\Throwable $e) {
            Log::warning('Site deletion failed', ['site_id' => $site->id, 'exception' => $e]);
            $site->update(['status' => 'delete_error']);

            return redirect()->route('client.websites.index')
                ->withErrors(['site' => 'No se pudo retirar la configuración del sitio. Revisa el servidor o contacta soporte.']);
        }

        $site->delete();

        return redirect()->route('client.websites.index')->with('success', 'Sitio eliminado correctamente.');
    }

    private function siteForDomain(Request $request, string $domain): Site
    {
        $tenant = $request->attributes->get('tenant');
        $domain = strtolower(trim($domain));

        return Site::where('tenant_id', $tenant->id)->where('domain', $domain)->firstOrFail();
    }

    private function websiteMenu(Site $site): array
    {
        $moduleUrl = fn (string $path) => route('client.websites.module', [
            'domain' => $site->domain,
            'section' => strtok($path, '/'),
            'page' => str_contains($path, '/') ? substr($path, strpos($path, '/') + 1) : null,
        ]);

        return [
            [
                'label' => 'Panel',
                'icon' => 'ki-element-11',
                'path' => null,
                'url' => route('client.websites.show', ['domain' => $site->domain]),
                'description' => 'Resumen general del sitio, estado y accesos rapidos.',
            ],
            [
                'label' => 'Plan de hosting',
                'icon' => 'ki-dollar',
                'children' => [
                    ['label' => 'Detalles del pedido', 'path' => 'order/details', 'url' => $moduleUrl('order/details')],
                    ['label' => 'Uso del pedido', 'path' => 'order/order-usage', 'url' => $moduleUrl('order/order-usage')],
                    ['label' => 'Mejorar plan', 'path' => 'order/upgrade', 'url' => $moduleUrl('order/upgrade')],
                ],
            ],
            [
                'label' => 'Rendimiento',
                'icon' => 'ki-chart-line-up',
                'children' => [
                    ['label' => 'AI troubleshooter', 'path' => 'performance/ai-troubleshooter', 'url' => $moduleUrl('performance/ai-troubleshooter')],
                    ['label' => 'Page speed', 'path' => 'performance/page-speed', 'url' => $moduleUrl('performance/page-speed')],
                    ['label' => 'CDN', 'path' => 'performance/cdn', 'url' => $moduleUrl('performance/cdn')],
                ],
            ],
            [
                'label' => 'Analisis',
                'icon' => 'ki-chart-simple',
                'path' => 'analytics',
                'url' => $moduleUrl('analytics'),
                'description' => 'Trafico, consumo y tendencias del sitio.',
            ],
            [
                'label' => 'Seguridad',
                'icon' => 'ki-shield-tick',
                'children' => [
                    ['label' => 'Malware scanner', 'path' => 'hosting-security/malware-scanner', 'url' => $moduleUrl('hosting-security/malware-scanner')],
                    ['label' => 'SSL', 'path' => 'hosting-security/ssl', 'url' => $moduleUrl('hosting-security/ssl')],
                ],
            ],
            [
                'label' => 'Dominios',
                'icon' => 'ki-click',
                'children' => [
                    ['label' => 'Subdominios', 'path' => 'domains/subdomains', 'url' => $moduleUrl('domains/subdomains')],
                    ['label' => 'Dominios aparcados', 'path' => 'domains/parked-domains', 'url' => $moduleUrl('domains/parked-domains')],
                    ['label' => 'Redirecciones', 'path' => 'domains/redirects', 'url' => $moduleUrl('domains/redirects')],
                ],
            ],
            [
                'label' => 'Sitio web',
                'icon' => 'ki-screen',
                'children' => [
                    ['label' => 'Instalar WordPress', 'path' => 'website/wordpress', 'url' => $moduleUrl('website/wordpress')],
                    ['label' => 'Instalador automatico', 'path' => 'website/auto-installer', 'url' => $moduleUrl('website/auto-installer')],
                    ['label' => 'Migrar sitio web', 'path' => 'website/migration', 'url' => $moduleUrl('website/migration')],
                    ['label' => 'Paginas de error', 'path' => 'website/error-pages', 'url' => $moduleUrl('website/error-pages')],
                    ['label' => 'Creador de sitios', 'path' => 'website/builder', 'url' => $moduleUrl('website/builder')],
                ],
            ],
            [
                'label' => 'Archivos',
                'icon' => 'ki-folder',
                'children' => [
                    ['label' => 'Administrador de archivos', 'path' => 'files/file-manager', 'url' => route('client.website.file-manager.entry', ['domain' => $site->domain]), 'primary_url' => route('client.website.file-manager.ikode', ['domain' => $site->domain])],
                    ['label' => 'Backups', 'path' => 'files/backups', 'url' => $moduleUrl('files/backups')],
                    ['label' => 'Cuentas FTP', 'path' => 'files/ftp-accounts', 'url' => $moduleUrl('files/ftp-accounts')],
                ],
            ],
            [
                'label' => 'Bases de datos',
                'icon' => 'ki-data',
                'children' => [
                    ['label' => 'MySQL databases', 'path' => 'databases/my-sql-databases', 'url' => $moduleUrl('databases/my-sql-databases')],
                    ['label' => 'phpMyAdmin', 'path' => 'databases/php-my-admin', 'url' => $moduleUrl('databases/php-my-admin')],
                    ['label' => 'Remote MySQL', 'path' => 'databases/remote-my-sql', 'url' => $moduleUrl('databases/remote-my-sql')],
                ],
            ],
            [
                'label' => 'Avanzado',
                'icon' => 'ki-setting-2',
                'children' => [
                    ['label' => 'Acceso SSH', 'path' => 'advanced/ssh-access', 'url' => $moduleUrl('advanced/ssh-access')],
                    ['label' => 'Configuracion PHP', 'path' => 'advanced/php-configuration', 'url' => $moduleUrl('advanced/php-configuration')],
                    ['label' => 'Cron jobs', 'path' => 'advanced/cron-jobs', 'url' => $moduleUrl('advanced/cron-jobs')],
                    ['label' => 'PHP info', 'path' => 'advanced/php-info', 'url' => $moduleUrl('advanced/php-info')],
                    ['label' => 'Cache manager', 'path' => 'advanced/cache-manager', 'url' => $moduleUrl('advanced/cache-manager')],
                    ['label' => 'Git', 'path' => 'advanced/git', 'url' => $moduleUrl('advanced/git')],
                    ['label' => 'Proteger directorios', 'path' => 'advanced/password-protect-directories', 'url' => $moduleUrl('advanced/password-protect-directories')],
                    ['label' => 'IP manager', 'path' => 'advanced/ip-manager', 'url' => $moduleUrl('advanced/ip-manager')],
                    ['label' => 'Hotlink protection', 'path' => 'advanced/hotlink-protection', 'url' => $moduleUrl('advanced/hotlink-protection')],
                    ['label' => 'Folder index manager', 'path' => 'advanced/folder-index-manager', 'url' => $moduleUrl('advanced/folder-index-manager')],
                    ['label' => 'Fix file ownership', 'path' => 'advanced/fix-file-ownership', 'url' => $moduleUrl('advanced/fix-file-ownership')],
                    ['label' => 'Activity log', 'path' => 'advanced/activity-log', 'url' => $moduleUrl('advanced/activity-log')],
                ],
            ],
        ];
    }

    private function moduleForPath(array $menu, ?string $activePath): array
    {
        if (! $activePath) {
            return $menu[0];
        }

        foreach ($menu as $item) {
            if (($item['path'] ?? null) === $activePath) {
                return $item;
            }

            foreach ($item['children'] ?? [] as $child) {
                if (($child['path'] ?? null) === $activePath) {
                    return $child + [
                        'parent_label' => $item['label'],
                        'parent_icon' => $item['icon'],
                    ];
                }
            }
        }

        return [
            'label' => str($activePath)->after('/')->replace('-', ' ')->title()->toString(),
            'path' => $activePath,
            'description' => 'Modulo preparado para esta ruta del sitio.',
        ];
    }

    private function moduleViewForPath(string $activePath): string
    {
        return [
            'order/details' => 'client.web.hosting-plan.details',
            'order/order-usage' => 'client.web.hosting-plan.order-usage',
            'order/upgrade' => 'client.web.hosting-plan.upgrade',
            'performance/ai-troubleshooter' => 'client.web.performance.ai-troubleshooter',
            'performance/page-speed' => 'client.web.performance.page-speed',
            'performance/cdn' => 'client.web.performance.cdn',
            'analytics' => 'client.web.analytics.index',
            'hosting-security/malware-scanner' => 'client.web.security.malware-scanner',
            'hosting-security/ssl' => 'client.web.security.ssl',
            'domains/subdomains' => 'client.web.domains.subdomains',
            'domains/parked-domains' => 'client.web.domains.parked-domains',
            'domains/redirects' => 'client.web.domains.redirects',
            'website/wordpress' => 'client.web.website.wordpress',
            'website/auto-installer' => 'client.web.website.auto-installer',
            'website/migration' => 'client.web.website.migration',
            'website/error-pages' => 'client.web.website.error-pages',
            'website/builder' => 'client.web.website.builder',
            'files/backups' => 'client.web.files.backups',
            'files/ftp-accounts' => 'client.web.files.ftp',
            'databases/my-sql-databases' => 'client.web.db.my-sql-databases',
            'databases/php-my-admin' => 'client.web.db.php-my-admin',
            'databases/remote-my-sql' => 'client.web.db.remote-my-sql',
            'advanced/ssh-access' => 'client.web.advanced.ssh-access',
            'advanced/php-configuration' => 'client.web.advanced.php-configuration',
            'advanced/cron-jobs' => 'client.web.advanced.cron-jobs',
            'advanced/php-info' => 'client.web.advanced.php-info',
            'advanced/cache-manager' => 'client.web.advanced.cache-manager',
            'advanced/git' => 'client.web.advanced.git',
            'advanced/password-protect-directories' => 'client.web.advanced.password-protect-directories',
            'advanced/ip-manager' => 'client.web.advanced.ip-manager',
            'advanced/hotlink-protection' => 'client.web.advanced.hotlink-protection',
            'advanced/folder-index-manager' => 'client.web.advanced.folder-index-manager',
            'advanced/fix-file-ownership' => 'client.web.advanced.fix-file-ownership',
            'advanced/activity-log' => 'client.web.advanced.activity-log',
        ][$activePath] ?? 'client.web.panel.index';
    }
}
