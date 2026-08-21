<?php

namespace Tests\Unit;

use App\Models\Site;
use App\Services\NativeSiteConfigGenerator;
use Tests\TestCase;

class NativeSiteConfigGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('xpanel.native_hosting.php_versions', ['8.2', '8.3', '8.4']);
    }

    public function test_nginx_php_site_uses_an_isolated_fpm_socket_and_native_document_root(): void
    {
        $site = $this->site(['web_server' => 'nginx']);
        $generator = app(NativeSiteConfigGenerator::class);

        $nginx = $generator->renderNginx($site);
        $pool = $generator->renderPhpPool($site);

        $this->assertStringContainsString('root /var/www/xpanel/acme/example.com;', $nginx);
        $this->assertStringContainsString('fastcgi_pass unix:/run/php/php8.3-fpm-xpanel-42.sock;', $nginx);
        $this->assertStringContainsString('user = xps16abcdef12', $pool);
        $this->assertStringContainsString('listen = /run/php/php8.3-fpm-xpanel-42.sock', $pool);
        $this->assertStringNotContainsString('docker', strtolower($nginx.$pool));
    }

    public function test_apache_is_kept_on_loopback_behind_the_nginx_gateway(): void
    {
        $site = $this->site(['web_server' => 'apache']);
        $generator = app(NativeSiteConfigGenerator::class);

        $nginx = $generator->renderNginx($site);
        $apache = $generator->renderApache($site);

        $this->assertStringContainsString('proxy_pass http://127.0.0.1:8082;', $nginx);
        $this->assertStringContainsString('<VirtualHost 127.0.0.1:8082>', $apache);
        $this->assertStringContainsString('proxy:unix:/run/php/php8.3-fpm-xpanel-42.sock', $apache);
    }

    public function test_static_site_does_not_receive_a_php_handler(): void
    {
        $site = $this->site(['project_type' => 'static']);
        $nginx = app(NativeSiteConfigGenerator::class)->renderNginx($site);

        $this->assertStringContainsString('try_files $uri $uri/ =404;', $nginx);
        $this->assertStringNotContainsString('fastcgi_pass', $nginx);
    }

    public function test_active_certificate_enables_tls_and_https_redirect(): void
    {
        $site = $this->site(['ssl_status' => 'active', 'https_redirect' => true]);
        $nginx = app(NativeSiteConfigGenerator::class)->renderNginx($site);

        $this->assertStringContainsString('listen 443 ssl http2;', $nginx);
        $this->assertStringContainsString('/etc/letsencrypt/live/example.com/fullchain.pem', $nginx);
        $this->assertStringContainsString('return 301 https://$host$request_uri;', $nginx);
    }

    /** @param array<string, mixed> $overrides */
    private function site(array $overrides = []): Site
    {
        $site = new Site;
        $site->setRawAttributes(array_merge([
            'id' => 42,
            'tenant_id' => 7,
            'domain' => 'example.com',
            'project_type' => 'php',
            'web_server' => 'nginx',
            'php_version' => '8.3',
            'php_options' => '[]',
            'provisioning_driver' => 'native',
            'system_user' => 'xps16abcdef12',
            'document_root' => '/var/www/xpanel/acme/example.com',
            'status' => 'active',
            'ssl_status' => 'disabled',
            'https_redirect' => true,
        ], $overrides), true);

        return $site;
    }
}
