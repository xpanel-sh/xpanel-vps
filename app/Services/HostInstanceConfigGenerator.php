<?php

namespace App\Services;

use App\Models\HostInstance;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class HostInstanceConfigGenerator
{
    public function __construct(private readonly HostInstanceResourceLimiter $limiter) {}

    /** @return array{directory:string,environment:string,runtime:string,fpm:string,fpm_global:string,fpm_service:string,nginx:string,apache:string,apache_service:string} */
    public function generate(HostInstance $instance): array
    {
        $this->assertSafe($instance);

        $directory = rtrim(config('xpanel.host_instances.staging_root'), '/\\').DIRECTORY_SEPARATOR.$instance->uuid;
        File::ensureDirectoryExists($directory, 0700, true);

        $environmentPath = $directory.DIRECTORY_SEPARATOR.'instance.env';
        $values = $this->environmentValues($instance, $environmentPath);

        File::put($environmentPath, $this->dotenv($values));
        @chmod($environmentPath, 0600);
        File::put($directory.DIRECTORY_SEPARATOR.'runtime.sh', $this->shellEnvironment($values));
        @chmod($directory.DIRECTORY_SEPARATOR.'runtime.sh', 0600);
        File::put($directory.DIRECTORY_SEPARATOR.'php-fpm.conf', $this->fpmPool($instance, $values));
        File::put($directory.DIRECTORY_SEPARATOR.'php-fpm-global.conf', $this->fpmGlobal($instance));
        File::put($directory.DIRECTORY_SEPARATOR.'php-fpm.service', $this->fpmService($instance));
        File::put($directory.DIRECTORY_SEPARATOR.'nginx.conf', $this->nginxVhost($instance));
        File::put($directory.DIRECTORY_SEPARATOR.'apache.conf', $this->apacheConfig($instance));
        File::put($directory.DIRECTORY_SEPARATOR.'apache.service', $this->apacheService($instance));

        return [
            'directory' => $directory,
            'environment' => $environmentPath,
            'runtime' => $directory.DIRECTORY_SEPARATOR.'runtime.sh',
            'fpm' => $directory.DIRECTORY_SEPARATOR.'php-fpm.conf',
            'fpm_global' => $directory.DIRECTORY_SEPARATOR.'php-fpm-global.conf',
            'fpm_service' => $directory.DIRECTORY_SEPARATOR.'php-fpm.service',
            'nginx' => $directory.DIRECTORY_SEPARATOR.'nginx.conf',
            'apache' => $directory.DIRECTORY_SEPARATOR.'apache.conf',
            'apache_service' => $directory.DIRECTORY_SEPARATOR.'apache.service',
        ];
    }

    private function environmentValues(HostInstance $instance, string $environmentPath): array
    {
        $existingKey = null;
        if (File::exists($environmentPath) && preg_match('/^APP_KEY=(.+)$/m', File::get($environmentPath), $match)) {
            $existingKey = trim($match[1], " \t\n\r\0\x0B\"");
        }

        $cache = $instance->instance_root.'/storage/framework/cache';
        $instance->loadMissing(['hostingAccount.plan', 'tenant.plan']);
        $plan = $instance->hostingAccount?->plan ?? $instance->tenant?->plan;
        $limits = $this->limiter->limitsFor($instance);
        $panelUrl = $instance->panelUrl();
        $panelDomain = parse_url($panelUrl, PHP_URL_HOST) ?: $instance->panel_domain;

        return [
            'APP_NAME' => 'XPanel Host',
            'APP_ENV' => 'production',
            'APP_KEY' => $existingKey ?: 'base64:'.base64_encode(random_bytes(32)),
            'APP_DEBUG' => 'false',
            'APP_URL' => $panelUrl,
            'APP_CONFIG_CACHE' => $cache.'/config.php',
            'APP_EVENTS_CACHE' => $cache.'/events.php',
            'APP_PACKAGES_CACHE' => $cache.'/packages.php',
            'APP_ROUTES_CACHE' => $cache.'/routes.php',
            'APP_SERVICES_CACHE' => $cache.'/services.php',
            'LOG_CHANNEL' => 'stack',
            'LOG_LEVEL' => 'warning',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $instance->database_path,
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'XPANEL_MANAGEMENT_MODE' => 'vps-instance',
            'XPANEL_INSTANCE_ID' => $instance->uuid,
            'XPANEL_PROJECT_ID' => 100000 + $instance->id,
            'XPANEL_INSTANCE_ROOT' => $instance->instance_root,
            'XPANEL_CONTROL_PLANE_URL' => config('xpanel.host_instances.control_plane_url'),
            'XPANEL_BROKER_URL' => config('xpanel.host_instances.broker_url'),
            'XPANEL_BROKER_SECRET' => $instance->broker_secret,
            'XPANEL_PANEL_DOMAIN' => $panelDomain,
            'XPANEL_PANEL_ACCESS_MODE' => 'domain',
            'XPANEL_SERVER_IPV4' => config('xpanel.server_ip'),
            'XPANEL_PANEL_PORT' => $instance->access_port ?: 80,
            'XPANEL_SSO_ENABLED' => 'true',
            // Host enables mutations, but ServerCommandRunner sends its helper calls
            // to the signed VPS broker; the tenant process itself never gets sudo.
            'XPANEL_APPLY_SYSTEM_CHANGES' => 'true',
            'XPANEL_TERMINAL_ENABLED' => 'true',
            'XPANEL_TERMINAL_INTERNAL_PORT' => $instance->access_port,
            'XPANEL_WEB_ROOT' => '/var/www/xpanel-instances/'.$instance->uuid,
            'XPANEL_ACCOUNT_USER' => $instance->system_user,
            'XPANEL_ACCOUNT_HOME' => '/home/'.$instance->system_user,
            'XPANEL_SITE_USER' => $instance->system_user,
            'XPANEL_SITE_GROUP' => $instance->system_user,
            'XPANEL_SYSTEMD_SLICE' => 'xpanel-instance-'.$instance->uuid.'.slice',
            'XPANEL_ASSIGNED_CPU' => max(1, (int) ceil($limits['cpu_percent'] / 100)),
            'XPANEL_ASSIGNED_CPU_PERCENT' => $limits['cpu_percent'],
            'XPANEL_ASSIGNED_MEMORY_MIB' => $limits['memory_max_mb'],
            'XPANEL_ASSIGNED_STORAGE_MIB' => max(0, (int) ($plan?->storage_mb ?? 0)),
            'XPANEL_ASSIGNED_DISK_GIB' => max(0, (int) ceil(((int) ($plan?->storage_mb ?? 0)) / 1024)),
            'XPANEL_ASSIGNED_INODES' => max(0, (int) ($plan?->inode_limit ?? 0)),
            'XPANEL_ASSIGNED_BANDWIDTH_GB' => max(0, (int) ($plan?->bandwidth_gb ?? 0)),
            'XPANEL_ASSIGNED_MAX_SITES' => max(0, (int) ($plan?->max_sites ?? 0)),
            'XPANEL_ASSIGNED_MAX_DATABASES' => max(0, (int) ($plan?->max_databases ?? 0)),
            'XPANEL_ASSIGNED_EMAIL_ACCOUNTS' => max(0, (int) ($plan?->email_accounts ?? 0)),
            'XPANEL_FPM_POOL_DIR' => '/etc/xpanel-vps/instances/'.$instance->uuid.'/php-fpm-pools',
            'XPANEL_FPM_CONFIG' => '/etc/xpanel-vps/instances/'.$instance->uuid.'/php-fpm.conf',
            'XPANEL_FPM_SERVICE' => 'xpanel-instance-'.$instance->uuid.'-fpm.service',
            'XPANEL_APACHE_BACKEND_PORT' => $instance->access_port >= 10000 && $instance->access_port <= 19999
                ? $instance->access_port + 40000 : 0,
            'XPANEL_APACHE_CONFIG' => '/etc/xpanel-vps/instances/'.$instance->uuid.'/apache.conf',
            'XPANEL_APACHE_SERVICE' => 'xpanel-instance-'.$instance->uuid.'-apache.service',
            'XPANEL_PHP_PROFILE_ROOT' => '/etc/xpanel-vps/instances/'.$instance->uuid.'/php-profiles',
        ];
    }

    private function assertSafe(HostInstance $instance): void
    {
        $root = rtrim(config('xpanel.host_instances.root'), '/');
        $releaseRoot = '/opt/xpanel-host/';

        if (! preg_match('/^[a-f0-9-]{36}$/', $instance->uuid)
            || ! preg_match('/^xhi[a-f0-9]{12}$/', $instance->system_user)
            || ! filter_var($instance->panel_domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            || $instance->instance_root !== $root.'/'.$instance->uuid
            || $instance->database_path !== $instance->instance_root.'/database/database.sqlite'
            || ! preg_match('/^[a-f0-9]{64}$/', (string) $instance->broker_secret)
            || ($instance->access_port !== null && ($instance->access_port < 1024 || $instance->access_port > 65535))
            || ! str_starts_with($instance->release_path.'/', $releaseRoot)) {
            throw new InvalidArgumentException('The host instance contains unsafe paths or identifiers.');
        }
    }

    private function dotenv(array $values): string
    {
        return collect($values)->map(fn ($value, $key) => $key.'="'.addcslashes((string) $value, "\\\"").'"')->implode("\n")."\n";
    }

    private function shellEnvironment(array $values): string
    {
        return "#!/usr/bin/env bash\n".collect($values)->map(function ($value, $key) {
            return 'export '.$key."='".str_replace("'", "'\\''", (string) $value)."'";
        })->implode("\n")."\n";
    }

    private function fpmPool(HostInstance $instance, array $values): string
    {
        $pool = 'xpanel-instance-'.$instance->uuid;
        $socket = '/run/php/php'.$instance->php_version.'-fpm-'.$pool.'.sock';
        $environment = collect($values)->map(fn ($value, $key) => 'env['.$key.'] = "'.addcslashes((string) $value, "\\\"").'"')->implode("\n");

        return "[{$pool}]\nuser = {$instance->system_user}\ngroup = {$instance->system_user}\nlisten = {$socket}\nlisten.owner = www-data\nlisten.group = www-data\nlisten.mode = 0660\npm = ondemand\npm.max_children = 8\npm.process_idle_timeout = 20s\nchdir = {$instance->release_path}\nclear_env = yes\n{$environment}\n";
    }

    private function nginxVhost(HostInstance $instance): string
    {
        $instance->loadMissing('hostingAccount');
        $socket = '/run/php/php'.$instance->php_version.'-fpm-xpanel-instance-'.$instance->uuid.'.sock';
        $fallbackTls = '';
        if ($instance->access_port) {
            $certificate = config('xpanel.host_instances.fallback_certificate');
            $certificateKey = config('xpanel.host_instances.fallback_certificate_key');
            $fallbackTls = "    listen {$instance->access_port} ssl;\n    listen [::]:{$instance->access_port} ssl;\n    ssl_certificate {$certificate};\n    ssl_certificate_key {$certificateKey};\n    ssl_protocols TLSv1.2 TLSv1.3;\n";
        }

        $serverNames = collect([$instance->panel_domain, $instance->hostingAccount?->custom_panel_domain])
            ->filter()->implode(' ');

        return "server {\n    listen 80;\n    listen [::]:80;\n{$fallbackTls}    include /etc/nginx/snippets/xpanel-instance-{$instance->uuid}-tls.conf;\n    server_name {$serverNames};\n    root {$instance->release_path}/public;\n    index index.php;\n\n    location ^~ /.well-known/acme-challenge/ { root /var/lib/letsencrypt; }\n    location = /internal/terminal/consume {\n        allow 127.0.0.1;\n        deny all;\n        include fastcgi_params;\n        fastcgi_param SCRIPT_FILENAME \$document_root/index.php;\n        fastcgi_param SCRIPT_NAME /index.php;\n        fastcgi_pass unix:{$socket};\n    }\n    location = /internal/terminal/runtime/start {\n        allow 127.0.0.1;\n        deny all;\n        include fastcgi_params;\n        fastcgi_param SCRIPT_FILENAME \$document_root/index.php;\n        fastcgi_param SCRIPT_NAME /index.php;\n        fastcgi_pass unix:{$socket};\n        fastcgi_read_timeout 1800s;\n    }\n    location /terminal-ws {\n        proxy_pass http://127.0.0.1:7093;\n        proxy_http_version 1.1;\n        proxy_set_header Upgrade \$http_upgrade;\n        proxy_set_header Connection \"upgrade\";\n        proxy_set_header Host \$host;\n        proxy_read_timeout 3600s;\n    }\n    location / { try_files \$uri \$uri/ /index.php?\$query_string; }\n    location ~ \\.php$ {\n        include snippets/fastcgi-php.conf;\n        fastcgi_pass unix:{$socket};\n        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n    }\n    location ~ /\\. { deny all; }\n}\n";
    }

    private function fpmGlobal(HostInstance $instance): string
    {
        $configRoot = '/etc/xpanel-vps/instances/'.$instance->uuid;
        $pid = '/run/php/xpanel-instance-'.$instance->uuid.'.pid';

        return "[global]\npid = {$pid}\nerror_log = syslog\ndaemonize = no\ninclude = {$configRoot}/php-fpm-pools/*.conf\n";
    }

    private function fpmService(HostInstance $instance): string
    {
        $unit = 'xpanel-instance-'.$instance->uuid;
        $config = '/etc/xpanel-vps/instances/'.$instance->uuid.'/php-fpm.conf';

        return "[Unit]\nDescription=XPanel Host PHP-FPM {$instance->uuid}\nAfter=network.target\nBefore=nginx.service\n\n[Service]\nType=simple\nSlice={$unit}.slice\nExecStart=/usr/sbin/php-fpm{$instance->php_version} --nodaemonize --fpm-config {$config}\nExecReload=/bin/kill -USR2 \$MAINPID\nRestart=on-failure\nRestartSec=3\nNoNewPrivileges=true\nPrivateTmp=true\nProtectSystem=full\nReadWritePaths={$instance->instance_root} -/var/www/xpanel-instances/{$instance->uuid} /run/php\n\n[Install]\nWantedBy=multi-user.target\n";
    }

    private function apacheConfig(HostInstance $instance): string
    {
        if (! $instance->access_port || $instance->access_port < 10000 || $instance->access_port > 19999) {
            // Existing instances with a custom recovery-port range still run
            // Nginx; Apache is withheld until an isolated port is assigned.
            return "# Apache no disponible: puerto interno sin asignar.\n";
        }

        $port = $instance->access_port + 40000;
        $uuid = $instance->uuid;
        $root = '/etc/xpanel-vps/instances/'.$uuid;

        return "ServerRoot /etc/apache2\nDefaultRuntimeDir /run/apache2\nPidFile /run/apache2/xpanel-instance-{$uuid}.pid\nListen 127.0.0.1:{$port}\nServerName 127.0.0.1\nUser {$instance->system_user}\nGroup {$instance->system_user}\nErrorLog /var/log/apache2/xpanel-instance-{$uuid}-error.log\nLogLevel warn\nLogFormat \"%h %l %u %t \\\"%r\\\" %>s %O \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\"\" combined\nIncludeOptional /etc/apache2/mods-enabled/*.load\nIncludeOptional /etc/apache2/mods-enabled/*.conf\nTypesConfig /etc/mime.types\nIncludeOptional /etc/apache2/conf-enabled/*.conf\nIncludeOptional {$root}/apache/sites/*.conf\n";
    }

    private function apacheService(HostInstance $instance): string
    {
        $uuid = $instance->uuid;
        $config = '/etc/xpanel-vps/instances/'.$uuid.'/apache.conf';

        return "[Unit]\nDescription=XPanel Host Apache {$uuid}\nAfter=network.target\nConditionPathExists=/usr/sbin/apache2\n\n[Service]\nType=simple\nSlice=xpanel-instance-{$uuid}.slice\nEnvironment=APACHE_RUN_DIR=/run/apache2\nEnvironment=APACHE_LOCK_DIR=/var/lock/apache2\nEnvironment=APACHE_LOG_DIR=/var/log/apache2\nEnvironment=APACHE_RUN_USER={$instance->system_user}\nEnvironment=APACHE_RUN_GROUP={$instance->system_user}\nExecStart=/usr/sbin/apache2 -f {$config} -DFOREGROUND\nExecReload=/bin/kill -USR1 \$MAINPID\nRestart=on-failure\nRestartSec=3\nNoNewPrivileges=true\nPrivateTmp=true\nProtectSystem=full\n\n[Install]\nWantedBy=multi-user.target\n";
    }
}
