<?php

namespace App\Services;

use App\Models\Site;
use RuntimeException;

class NativeSiteConfigGenerator
{
    /** @return array<string, string> */
    public function write(Site $site): array
    {
        $this->assertSupported($site);

        $paths = [
            'nginx' => $this->path('nginx', $site),
            'apache' => $this->path('apache', $site),
            'php_fpm' => $this->path('php-fpm', $site),
        ];

        $this->atomicWrite($paths['nginx'], $this->renderNginx($site));

        if ($site->web_server === 'apache') {
            $this->atomicWrite($paths['apache'], $this->renderApache($site));
        } else {
            $this->unlink($paths['apache']);
        }

        if ($site->project_type === 'php') {
            $this->atomicWrite($paths['php_fpm'], $this->renderPhpPool($site));
        } else {
            $this->unlink($paths['php_fpm']);
        }

        return $paths;
    }

    public function remove(Site $site): void
    {
        foreach (['nginx', 'apache', 'php-fpm'] as $type) {
            $this->unlink($this->path($type, $site));
        }
    }

    public function renderNginx(Site $site): string
    {
        $this->assertSupported($site);
        $domain = strtolower($site->domain);
        $root = $site->nativeDocumentRoot();

        if ($site->status === 'suspended') {
            return <<<CONF
server {
    listen 80;
    server_name {$domain} www.{$domain};
    default_type text/plain;
    return 503 "Servicio suspendido.\n";
}
CONF;
        }

        if ($site->web_server === 'apache') {
            $handler = <<<'CONF'
    location / {
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://127.0.0.1:8082;
    }
CONF;
        } else {
            $handler = $site->project_type === 'php'
            ? <<<CONF
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:{$this->phpSocket($site)};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
CONF
            : <<<'CONF'
    location / {
        try_files $uri $uri/ =404;
    }
CONF;
        }

        $tlsEnabled = $site->ssl_status === 'active';
        $listeners = $tlsEnabled && $site->https_redirect
            ? '    listen 443 ssl http2;'
            : "    listen 80;\n".($tlsEnabled ? '    listen 443 ssl http2;' : '');
        $tls = $tlsEnabled ? <<<CONF
    ssl_certificate /etc/letsencrypt/live/{$domain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$domain}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

CONF : '';
        $redirect = $tlsEnabled && $site->https_redirect ? <<<CONF
server {
    listen 80;
    server_name {$domain} www.{$domain};
    root {$root};
    location ^~ /.well-known/acme-challenge/ { try_files \$uri =404; }
    location / { return 301 https://\$host\$request_uri; }
}

CONF : '';

        return $redirect.<<<CONF
server {
{$listeners}
    server_name {$domain} www.{$domain};
    root {$root};
    index index.php index.html;

    location ^~ /.well-known/acme-challenge/ {
        try_files \$uri =404;
    }

{$tls}{$handler}

    location ~ /\. {
        deny all;
    }
}
CONF;
    }

    public function renderApache(Site $site): string
    {
        $this->assertSupported($site);
        $domain = strtolower($site->domain);
        $root = $site->nativeDocumentRoot();
        $phpHandler = $site->project_type === 'php'
            ? <<<CONF
    <FilesMatch \.php$>
        SetHandler "proxy:unix:{$this->phpSocket($site)}|fcgi://localhost"
    </FilesMatch>

CONF
            : '';

        return <<<CONF
<VirtualHost 127.0.0.1:8082>
    ServerName {$domain}
    ServerAlias www.{$domain}
    DocumentRoot {$root}

{$phpHandler}    <Directory {$root}>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/xpanel-{$domain}-error.log
    CustomLog \${APACHE_LOG_DIR}/xpanel-{$domain}-access.log combined
</VirtualHost>
CONF;
    }

    public function renderPhpPool(Site $site): string
    {
        $this->assertSupported($site);
        $options = $site->php_options ?? [];
        $user = $site->systemUser();
        $root = $site->nativeDocumentRoot();
        $memory = $this->option($options, 'memory_limit', ['64M', '128M', '256M', '512M', '1024M'], '256M');
        $upload = $this->option($options, 'upload_max_filesize', ['8M', '32M', '64M', '128M', '256M'], '64M');
        $post = $this->option($options, 'post_max_size', ['8M', '32M', '64M', '128M', '256M'], '64M');
        $execution = $this->option($options, 'max_execution_time', ['30', '60', '120', '300'], '60');
        $input = $this->option($options, 'max_input_time', ['30', '60', '120', '300'], '60');

        return <<<CONF
[xpanel-{$site->id}]
user = {$user}
group = {$user}
listen = {$this->phpSocket($site)}
listen.owner = www-data
listen.group = {$user}
listen.mode = 0660
pm = ondemand
pm.max_children = 10
pm.process_idle_timeout = 10s
pm.max_requests = 500
chdir = {$root}
catch_workers_output = yes
php_admin_value[memory_limit] = {$memory}
php_admin_value[upload_max_filesize] = {$upload}
php_admin_value[post_max_size] = {$post}
php_admin_value[max_execution_time] = {$execution}
php_admin_value[max_input_time] = {$input}
CONF;
    }

    private function phpSocket(Site $site): string
    {
        return '/run/php/php'.$site->php_version.'-fpm-xpanel-'.$site->id.'.sock';
    }

    private function path(string $type, Site $site): string
    {
        return storage_path('app/native/'.$type.'/'.strtolower($site->domain).'.conf');
    }

    private function assertSupported(Site $site): void
    {
        if (! preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/', strtolower($site->domain)) || ! str_contains($site->domain, '.')) {
            throw new RuntimeException('Dominio inválido para el alojamiento nativo.');
        }

        if (! in_array($site->project_type, ['php', 'static'], true)) {
            throw new RuntimeException('El motor nativo solo admite sitios PHP o estáticos.');
        }

        if (! in_array($site->web_server, ['nginx', 'apache'], true)) {
            throw new RuntimeException('El motor web nativo no está soportado.');
        }

        if (! in_array($site->php_version, ['8.1', '8.2', '8.3', '8.4'], true)) {
            throw new RuntimeException('La versión PHP no está habilitada por el administrador.');
        }
    }

    /** @param array<string, mixed> $options @param array<int, string> $allowed */
    private function option(array $options, string $key, array $allowed, string $default): string
    {
        $value = (string) ($options[$key] ?? $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function atomicWrite(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("No se pudo crear {$directory}.");
        }

        $temporary = tempnam($directory, '.xpanel-');
        if ($temporary === false) {
            throw new RuntimeException("No se pudo preparar {$path}.");
        }

        try {
            if (file_put_contents($temporary, $contents."\n", LOCK_EX) === false || ! rename($temporary, $path)) {
                throw new RuntimeException("No se pudo escribir {$path}.");
            }
        } finally {
            $this->unlink($temporary);
        }
    }

    private function unlink(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
