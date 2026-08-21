<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Site;
use Carbon\CarbonImmutable;
use RuntimeException;

class NativeCertificateProvisioner
{
    public function __construct(
        private ServerCommandRunner $commands,
        private NativeSiteProvisioner $sites,
    ) {}

    public function issue(Domain $domain, string $email, bool $redirect = true): void
    {
        $site = $domain->site ?: Site::where('tenant_id', $domain->tenant_id)->where('domain', $domain->domain)->first();
        if (! $site || ! $site->isNative()) {
            throw new RuntimeException('El dominio no esta asociado a un sitio nativo.');
        }

        if (! config('xpanel.native_hosting.apply_system_changes', false)) {
            $site->update(['ssl_status' => 'staged', 'https_redirect' => $redirect]);
            $domain->update(['ssl_status' => 'staged']);

            return;
        }

        $output = $this->commands->run([
            'sudo', '-n', (string) config('xpanel.native_hosting.site_helper'), 'ssl-issue',
            strtolower($site->domain), $site->web_server, $site->project_type, $site->php_version,
            $site->nativeDocumentRoot(), $site->systemUser(), $email,
        ], null, 600);
        $metadata = $this->metadata($output);
        $site->update([
            'ssl_status' => 'active',
            'ssl_expires_at' => $metadata['not_after'] ?? now()->addDays(89),
            'ssl_issuer' => $metadata['issuer'] ?? "Let's Encrypt",
            'https_redirect' => $redirect,
        ]);
        $domain->update(['ssl_status' => 'active']);
        $this->sites->reconfigure($site->fresh());
    }

    private function metadata(string $output): array
    {
        $metadata = [];
        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            [$key, $value] = array_pad(explode('=', $line, 2), 2, null);
            if ($key === 'not_after' && $value) {
                $metadata[$key] = CarbonImmutable::parse($value);
            } elseif ($key === 'issuer' && $value) {
                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }
}
