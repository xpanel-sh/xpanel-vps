<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web']], function () {
    $adminLoginPath = config('xpanel.routes.admin_login_path', 'admin/login');
    $clientLoginPath = config('xpanel.routes.client_login_path', 'client/login');

    // ── Home pública ──────────────────────────────────────────────────────────
    Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/pages/{page}', [\App\Http\Controllers\PublicPageController::class, 'show'])
        ->whereIn('page', ['about', 'privacy', 'terms', 'refunds'])
        ->name('pages.show');

    // ── Autenticación Admin ───────────────────────────────────────────────────
    Route::get($adminLoginPath, [\App\Http\Controllers\Admin\AuthController::class,  'showLogin'])->name('admin.login');
    Route::post($adminLoginPath, [\App\Http\Controllers\Admin\AuthController::class,  'login'])->name('admin.login.post');
    Route::post('/admin/logout', [\App\Http\Controllers\Admin\AuthController::class,  'logout'])->name('admin.logout');

    // ── Autenticación Cliente ─────────────────────────────────────────────────
    Route::get($clientLoginPath, [\App\Http\Controllers\Client\AuthController::class, 'showLogin'])->name('client.login');
    Route::post($clientLoginPath, [\App\Http\Controllers\Client\AuthController::class, 'login'])->name('client.login.post');
    Route::post('/client/logout', [\App\Http\Controllers\Client\AuthController::class, 'logout'])->name('client.logout');

    // ============================================================
    // RUTAS ADMIN  (guard: admin)
    // ============================================================
    Route::middleware(['auth:admin'])->group(function () {

        Route::get('/admin/dashboard', function (\App\Services\NativeRuntimeMetrics $metrics) {
            $clientCount = \App\Models\Tenant::count();
            $siteCount = \App\Models\Site::count();
            $hostingCount = \App\Models\HostingAccount::count();
            $planCount = \App\Models\HostingPlan::count();
            $recentSites = \App\Models\Site::with('tenant')->latest()->take(8)->get();
            $planStats = \App\Models\HostingPlan::withCount('hostingAccounts')
                ->orderByDesc('hosting_accounts_count')
                ->take(5)
                ->get()
                ->map(fn ($plan) => [
                    'name' => $plan->name,
                    'tenants' => $plan->hosting_accounts_count,
                    'monthly' => (float) $plan->monthly_price * $plan->hosting_accounts_count,
                ]);
            $runtime = [];
            $runtimeError = null;

            try {
                $runtime = $metrics->snapshot();
            } catch (\Throwable $e) {
                $runtimeError = $e->getMessage();
            }

            return view('admin.dashboard', compact(
                'clientCount',
                'siteCount',
                'hostingCount',
                'planCount',
                'recentSites',
                'planStats',
                'runtime',
                'runtimeError'
            ));
        })->name('admin.dashboard');

        Route::get('/admin/dashboard/runtime', function (\App\Services\NativeRuntimeMetrics $metrics) {
            try {
                return response()->json([
                    'ok' => true,
                    'runtime' => $metrics->snapshot(),
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 503);
            }
        })->name('admin.dashboard.runtime');

        Route::get('/admin/plans', [\App\Http\Controllers\Admin\HostingPlanController::class, 'index'])->name('admin.plans.index');
        Route::get('/admin/plans/create', [\App\Http\Controllers\Admin\HostingPlanController::class, 'create'])->name('admin.plans.create');
        Route::post('/admin/plans', [\App\Http\Controllers\Admin\HostingPlanController::class, 'store'])->name('admin.plans.store');
        Route::get('/admin/plans/{plan}/edit', [\App\Http\Controllers\Admin\HostingPlanController::class, 'edit'])->name('admin.plans.edit');
        Route::put('/admin/plans/{plan}', [\App\Http\Controllers\Admin\HostingPlanController::class, 'update'])->name('admin.plans.update');
        Route::post('/admin/plans/{plan}/toggle', [\App\Http\Controllers\Admin\HostingPlanController::class, 'toggle'])->name('admin.plans.toggle');
        Route::get('/admin/orders', [\App\Http\Controllers\Admin\PlanOrderController::class, 'index'])->name('admin.orders.index');
        Route::get('/admin/orders/{order}', [\App\Http\Controllers\Admin\PlanOrderController::class, 'show'])->name('admin.orders.show');
        Route::post('/admin/orders/{order}/paid', [\App\Http\Controllers\Admin\PlanOrderController::class, 'markPaid'])->name('admin.orders.paid');
        Route::post('/admin/orders/{order}/cancel', [\App\Http\Controllers\Admin\PlanOrderController::class, 'cancel'])->name('admin.orders.cancel');

        Route::prefix('admin/clients')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('admin.clients.index');
            Route::get('/create', [\App\Http\Controllers\Admin\TenantController::class, 'create'])->name('admin.clients.create');
            Route::post('/', [\App\Http\Controllers\Admin\TenantController::class, 'store'])->name('admin.clients.store');
            Route::get('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'show'])->name('admin.clients.show');
            Route::get('/{tenant}/edit', [\App\Http\Controllers\Admin\TenantController::class, 'edit'])->name('admin.clients.edit');
            Route::put('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'update'])->name('admin.clients.update');
            Route::post('/{tenant}/toggle-status', [\App\Http\Controllers\Admin\TenantController::class, 'toggleStatus'])->name('admin.clients.toggle-status');
            Route::post('/{tenant}/host-instance', [\App\Http\Controllers\Admin\HostInstanceController::class, 'store'])->name('admin.clients.instances.store');
        });

        Route::get('/admin/instances', [\App\Http\Controllers\Admin\HostInstanceController::class, 'index'])->name('admin.instances.index');
        Route::get('/admin/instances/{instance}/access', [\App\Http\Controllers\Admin\HostInstanceController::class, 'access'])->name('admin.instances.access');
        Route::post('/admin/instances/{instance}/apply', [\App\Http\Controllers\Admin\HostInstanceController::class, 'apply'])->name('admin.instances.apply');
        Route::post('/admin/instances/{instance}/update', [\App\Http\Controllers\Admin\HostInstanceController::class, 'update'])->name('admin.instances.update');
        Route::get('/admin/instances/{instance}/update-status', [\App\Http\Controllers\Admin\HostInstanceController::class, 'updateStatus'])->middleware('throttle:30,1')->name('admin.instances.update-status');
        Route::post('/admin/instances/{instance}/retry-ssl', [\App\Http\Controllers\Admin\HostInstanceController::class, 'retrySsl'])->name('admin.instances.retry-ssl');

        Route::get('/admin/servers', [\App\Http\Controllers\Admin\ServerNodeController::class, 'index'])->name('admin.servers.index');
        Route::get('/admin/servers/create', [\App\Http\Controllers\Admin\ServerNodeController::class, 'create'])->name('admin.servers.create');
        Route::post('/admin/servers', [\App\Http\Controllers\Admin\ServerNodeController::class, 'store'])->name('admin.servers.store');
        Route::post('/admin/servers/{server}/toggle', [\App\Http\Controllers\Admin\ServerNodeController::class, 'toggle'])->name('admin.servers.toggle');

        // Docker Apps (admin)
        Route::prefix('admin/docker-apps')->name('admin.docker.')->middleware('docker.enabled')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DockerTemplateController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\DockerTemplateController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\DockerTemplateController::class, 'store'])->name('store');
            Route::get('/{dockerTemplate}/edit', [\App\Http\Controllers\Admin\DockerTemplateController::class, 'edit'])->name('edit');
            Route::put('/{dockerTemplate}', [\App\Http\Controllers\Admin\DockerTemplateController::class, 'update'])->name('update');
            Route::delete('/{dockerTemplate}', [\App\Http\Controllers\Admin\DockerTemplateController::class, 'destroy'])->name('destroy');
        });

        // Software Packages (admin) — webserver engines + PHP-FPM versions
        Route::prefix('admin/software-packages')->name('admin.software-packages.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SoftwarePackageController::class, 'index'])->name('index');
            Route::post('/install', [\App\Http\Controllers\Admin\SoftwarePackageController::class, 'install'])->name('install');
            Route::post('/toggle', [\App\Http\Controllers\Admin\SoftwarePackageController::class, 'toggle'])->name('toggle');
        });

        Route::get('/admin/sites', [\App\Http\Controllers\Admin\Web\SiteController::class, 'index'])->name('admin.sites.index');
        Route::get('/admin/domains', [\App\Http\Controllers\Admin\DomainController::class, 'index'])->name('admin.domains.index');
        Route::get('/admin/dns/nameservers', [\App\Http\Controllers\Admin\NameserverController::class, 'edit'])->name('admin.dns.nameservers');
        Route::put('/admin/dns/nameservers', [\App\Http\Controllers\Admin\NameserverController::class, 'update'])->name('admin.dns.nameservers.update');
        Route::get('/admin/daemon/operations', [\App\Http\Controllers\Admin\DaemonOperationController::class, 'index'])->name('admin.daemon.operations');

        Route::get('/admin/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings.index');
        Route::put('/admin/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('admin.settings.update');
        Route::get('/admin/pages', [\App\Http\Controllers\Admin\PageController::class, 'index'])->name('admin.pages.index');
        Route::get('/admin/pages/{page}', [\App\Http\Controllers\Admin\PageController::class, 'edit'])->name('admin.pages.edit');
        Route::put('/admin/pages/{page}', [\App\Http\Controllers\Admin\PageController::class, 'update'])->name('admin.pages.update');

        // Gestor de Archivos (Admin)
        Route::prefix('admin/files')->name('admin.files.')->group(function () {
            Route::get('/api/list', [\App\Http\Controllers\Admin\FileManagerController::class, 'list'])->name('list');
            Route::get('/api/read', [\App\Http\Controllers\Admin\FileManagerController::class, 'read'])->name('read');
            Route::post('/api/write', [\App\Http\Controllers\Admin\FileManagerController::class, 'write'])->name('write');
            Route::post('/api/mkdir', [\App\Http\Controllers\Admin\FileManagerController::class, 'mkdir'])->name('mkdir');
            Route::post('/api/create', [\App\Http\Controllers\Admin\FileManagerController::class, 'create'])->name('create');
            Route::post('/api/delete', [\App\Http\Controllers\Admin\FileManagerController::class, 'delete'])->name('delete');
            Route::post('/api/rename', [\App\Http\Controllers\Admin\FileManagerController::class, 'rename'])->name('rename');
            Route::post('/api/extract', [\App\Http\Controllers\Admin\FileManagerController::class, 'extract'])->name('extract');
            Route::post('/api/search', [\App\Http\Controllers\Admin\FileManagerController::class, 'search'])->name('search');
            Route::post('/api/upload', [\App\Http\Controllers\Admin\FileManagerController::class, 'upload'])->name('upload');
            Route::get('/api/download', [\App\Http\Controllers\Admin\FileManagerController::class, 'download'])->name('download');
            Route::get('/{domain?}', [\App\Http\Controllers\Admin\FileManagerController::class, 'index'])
                ->where('domain', '.*')
                ->name('index');
        });
    });

    // ============================================================
    // RUTAS CLIENTE  (guard: web)  — prefijo /client/
    // ============================================================
    Route::prefix('client')->middleware(['auth', \App\Http\Middleware\ResolveTenant::class])->group(function () {

        Route::get('/dashboard', function (Illuminate\Http\Request $request) {
            $tenant = $request->attributes->get('tenant');
            if (! $tenant) {
                return redirect()->route('client.login')->withErrors([
                    'email' => 'No tenant assigned to this account.',
                ]);
            }
            $hostingAccounts = $tenant->hostingAccounts()->with(['plan', 'hostInstance'])->latest()->get();
            $activeHostingCount = $hostingAccounts->where('status', 'active')->count();
            $pendingInvoiceCount = $tenant->planOrders()->where('payment_status', 'pending')->count();

            return view('client.dashboard', compact('tenant', 'hostingAccounts', 'activeHostingCount', 'pendingInvoiceCount'));
        })->name('client.dashboard');

        // Sitios Web
        Route::prefix('sites')->name('client.sites.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\Web\SiteController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Client\Web\SiteController::class, 'create'])->name('create');
            Route::get('/{site}/panel', [\App\Http\Controllers\Client\Web\SiteController::class, 'panel'])->name('panel');
            Route::get('/{site}/status', [\App\Http\Controllers\Client\Web\SiteController::class, 'status'])->name('status');
            Route::post('/', [\App\Http\Controllers\Client\Web\SiteController::class, 'store'])->name('store');
            Route::post('/{site}/restart', [\App\Http\Controllers\Client\Web\SiteController::class, 'restart'])->name('restart');
            Route::delete('/{site}', [\App\Http\Controllers\Client\Web\SiteController::class, 'destroy'])->name('destroy');
        });

        // Bases de Datos
        Route::prefix('databases')->name('client.databases.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\DatabaseController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Client\DatabaseController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Client\DatabaseController::class, 'store'])->name('store');
            Route::get('/{database}/phpmyadmin', [\App\Http\Controllers\Client\DatabaseController::class, 'phpMyAdmin'])->name('phpmyadmin');
            Route::post('/{database}/permissions', [\App\Http\Controllers\Client\DatabaseController::class, 'updatePermissions'])->name('permissions');
            Route::post('/{database}/users', [\App\Http\Controllers\Client\DatabaseController::class, 'addUser'])->name('users.store');
            Route::post('/{database}/users/{dbUser}/password', [\App\Http\Controllers\Client\DatabaseController::class, 'changeUserPassword'])->name('users.password');
            Route::post('/{database}/users/{dbUser}/permissions', [\App\Http\Controllers\Client\DatabaseController::class, 'updateUserPermissions'])->name('users.permissions');
            Route::delete('/{database}/users/{dbUser}', [\App\Http\Controllers\Client\DatabaseController::class, 'removeUser'])->name('users.destroy');
            Route::delete('/{database}', [\App\Http\Controllers\Client\DatabaseController::class, 'destroy'])->name('destroy');
        });

        // Dominios
        Route::prefix('domains')->name('client.domains.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\DomainController::class, 'index'])->name('index');
            Route::get('/search', [\App\Http\Controllers\Client\DomainController::class, 'search'])->name('search');
            Route::get('/transfers', [\App\Http\Controllers\Client\DomainController::class, 'transfers'])->name('transfers');
            Route::get('/create', [\App\Http\Controllers\Client\DomainController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Client\DomainController::class, 'store'])->name('store');
            Route::delete('/{domain}', [\App\Http\Controllers\Client\DomainController::class, 'destroy'])->name('destroy');
        });

        // Correos
        Route::prefix('mail')->name('client.mail.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\EmailAccountController::class, 'index'])->name('index');
            Route::get('/xmail', [\App\Http\Controllers\Client\MailController::class, 'index'])->name('xmail');
            Route::get('/create', [\App\Http\Controllers\Client\EmailAccountController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Client\EmailAccountController::class, 'store'])->name('store');
            Route::post('/{emailAccount}/reset-password', [\App\Http\Controllers\Client\EmailAccountController::class, 'resetPassword'])->name('reset-password');
            Route::delete('/{emailAccount}', [\App\Http\Controllers\Client\EmailAccountController::class, 'destroy'])->name('destroy');

            // XMail AJAX API
            Route::prefix('api')->name('api.')->group(function () {
                Route::get('/folders', [\App\Http\Controllers\Client\MailController::class, 'apiFolders'])->name('folders');
                Route::get('/messages', [\App\Http\Controllers\Client\MailController::class, 'apiMessages'])->name('messages');
                Route::get('/message', [\App\Http\Controllers\Client\MailController::class, 'apiMessage'])->name('message');
                Route::post('/flag', [\App\Http\Controllers\Client\MailController::class, 'apiFlag'])->name('flag');
                Route::post('/move', [\App\Http\Controllers\Client\MailController::class, 'apiMove'])->name('move');
                Route::post('/delete', [\App\Http\Controllers\Client\MailController::class, 'apiDelete'])->name('delete');
                Route::post('/send', [\App\Http\Controllers\Client\MailController::class, 'apiSend'])->name('send');
                Route::post('/folder/create', [\App\Http\Controllers\Client\MailController::class, 'apiFolderCreate'])->name('folder.create');
                Route::post('/folder/delete', [\App\Http\Controllers\Client\MailController::class, 'apiFolderDelete'])->name('folder.delete');
            });
        });

        Route::view('/builder', 'client.builder.index')->name('client.builder.index');
        Route::view('/vps', 'client.vps.index')->name('client.vps.index');

        // DNS
        Route::prefix('dns')->name('client.dns.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\DnsRecordController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Client\DnsRecordController::class, 'store'])->name('store');
            Route::delete('/{record}', [\App\Http\Controllers\Client\DnsRecordController::class, 'destroy'])->name('destroy');
            Route::post('/cf-token', [\App\Http\Controllers\Client\DnsRecordController::class, 'saveCfToken'])->name('cf-token');
        });

        Route::get('/account', [\App\Http\Controllers\Client\AccountController::class, 'show'])->name('client.account.show');
        Route::get('/hosting', [\App\Http\Controllers\Client\HostAccessController::class, 'show'])->name('client.host.show');
        Route::get('/hosting/{hostingAccount}', [\App\Http\Controllers\Client\HostAccessController::class, 'account'])->name('client.host.account');
        Route::post('/hosting/{hostingAccount}/access', [\App\Http\Controllers\Client\HostAccessController::class, 'access'])->name('client.host.access');
        Route::put('/hosting/{hostingAccount}/domain', [\App\Http\Controllers\Client\HostAccessController::class, 'updateDomain'])->name('client.host.domain');
        Route::get('/plans', [\App\Http\Controllers\Client\PlanController::class, 'index'])->name('client.plans.index');
        Route::post('/plans/{plan}/contract', [\App\Http\Controllers\Client\PlanOrderController::class, 'store'])->name('client.plans.contract');
        Route::get('/orders', [\App\Http\Controllers\Client\PlanOrderController::class, 'index'])->name('client.orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\Client\PlanOrderController::class, 'show'])->name('client.orders.show');

        // Docker Apps
        Route::prefix('docker')->name('client.docker.')->middleware('docker.enabled')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\DockerController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Client\DockerController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Client\DockerController::class, 'store'])->name('store');
            Route::get('/{dockerInstance}', [\App\Http\Controllers\Client\DockerController::class, 'show'])->name('show');
            Route::post('/{dockerInstance}/start', [\App\Http\Controllers\Client\DockerController::class, 'start'])->name('start');
            Route::post('/{dockerInstance}/stop', [\App\Http\Controllers\Client\DockerController::class, 'stop'])->name('stop');
            Route::post('/{dockerInstance}/restart', [\App\Http\Controllers\Client\DockerController::class, 'restart'])->name('restart');
            Route::get('/{dockerInstance}/logs', [\App\Http\Controllers\Client\DockerController::class, 'logs'])->name('logs');
            Route::delete('/{dockerInstance}', [\App\Http\Controllers\Client\DockerController::class, 'destroy'])->name('destroy');
        });

        // Gestor de Archivos por sitio
        Route::get('/website/file-manager', [\App\Http\Controllers\Client\FileManagerController::class, 'index'])
            ->name('client.website.file-manager.root');
        Route::get('/website/{domain}/file-manager', [\App\Http\Controllers\Client\Web\SiteController::class, 'fileManager'])
            ->where('domain', '[A-Za-z0-9.-]+')
            ->name('client.website.file-manager.entry');
        Route::get('/website/{domain}/file-manager/ikode', [\App\Http\Controllers\Client\FileManagerController::class, 'index'])
            ->where('domain', '[A-Za-z0-9.-]+')
            ->name('client.website.file-manager.ikode');

        // Gestor de Archivos (Cliente)
        Route::prefix('files')->name('client.files.')->group(function () {
            Route::get('/api/list', [\App\Http\Controllers\Client\FileManagerController::class, 'list'])->name('list');
            Route::get('/api/read', [\App\Http\Controllers\Client\FileManagerController::class, 'read'])->name('read');
            Route::post('/api/write', [\App\Http\Controllers\Client\FileManagerController::class, 'write'])->name('write');
            Route::post('/api/mkdir', [\App\Http\Controllers\Client\FileManagerController::class, 'mkdir'])->name('mkdir');
            Route::post('/api/create', [\App\Http\Controllers\Client\FileManagerController::class, 'create'])->name('create');
            Route::post('/api/delete', [\App\Http\Controllers\Client\FileManagerController::class, 'delete'])->name('delete');
            Route::post('/api/rename', [\App\Http\Controllers\Client\FileManagerController::class, 'rename'])->name('rename');
            Route::post('/api/extract', [\App\Http\Controllers\Client\FileManagerController::class, 'extract'])->name('extract');
            Route::post('/api/search', [\App\Http\Controllers\Client\FileManagerController::class, 'search'])->name('search');
            Route::post('/api/upload', [\App\Http\Controllers\Client\FileManagerController::class, 'upload'])->name('upload');
            Route::get('/api/download', [\App\Http\Controllers\Client\FileManagerController::class, 'download'])->name('download');
            Route::get('/{domain?}', [\App\Http\Controllers\Client\FileManagerController::class, 'index'])
                ->where('domain', '.*')
                ->name('index');
        });
    });

    Route::prefix('client')->middleware(['auth', \App\Http\Middleware\ResolveTenant::class])->group(function () {
        Route::prefix('websites')->name('client.websites.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\Web\SiteController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Client\Web\SiteController::class, 'create'])->name('create');
            // DNS zone editor — must be before the generic module route
            Route::get('/{domain}/advanced/dns-zone-editor', [\App\Http\Controllers\Client\DnsRecordController::class, 'zoneEditor'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->name('dns-zone-editor');
            Route::post('/{domain}/advanced/dns-zone-editor', [\App\Http\Controllers\Client\DnsRecordController::class, 'zoneEditorStore'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->name('dns-zone-editor.store');
            Route::delete('/{domain}/advanced/dns-zone-editor/{record}', [\App\Http\Controllers\Client\DnsRecordController::class, 'zoneEditorDestroy'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->name('dns-zone-editor.destroy');
            Route::post('/{domain}/advanced/dns-zone-editor/mode', [\App\Http\Controllers\Client\DnsRecordController::class, 'setMode'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->name('dns-zone-editor.mode');
            Route::post('/{domain}/advanced/ssl/issue', [\App\Http\Controllers\Client\DnsRecordController::class, 'sslIssue'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->name('ssl.issue');
            Route::post('/{domain}/advanced/php-configuration/version', [\App\Http\Controllers\Client\Web\SiteController::class, 'updatePhpVersion'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->name('php-version');
            Route::post('/{domain}/advanced/php-configuration/options', [\App\Http\Controllers\Client\Web\SiteController::class, 'updatePhpOptions'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->name('php-options');
            Route::post('/{domain}/advanced/php-configuration/engine', [\App\Http\Controllers\Client\Web\SiteController::class, 'switchEngine'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->name('engine');
            Route::get('/{domain}', [\App\Http\Controllers\Client\Web\SiteController::class, 'panelByDomain'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->name('show');
            Route::get('/{domain}/{section}/{page?}', [\App\Http\Controllers\Client\Web\SiteController::class, 'module'])
                ->where('domain', '[A-Za-z0-9.-]+')
                ->where('section', 'order|performance|analytics|hosting-security|domains|website|files|databases|advanced')
                ->where('page', '.*')
                ->name('module');
        });
    });
});
