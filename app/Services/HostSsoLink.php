<?php

namespace App\Services;

use App\Models\HostInstance;

class HostSsoLink
{
    public function for(HostInstance $instance): string
    {
        $instance->loadMissing(['hostingAccount', 'tenant.user']);
        $account = $instance->hostingAccount;
        $fallbackUser = $instance->tenant?->user;
        $email = $account?->admin_email ?: $fallbackUser?->email;
        $name = $account?->admin_name ?: $fallbackUser?->name;

        if (blank($email)) {
            throw new \RuntimeException('La instancia no tiene un administrador configurado para el acceso SSO.');
        }

        $ttl = max(15, min(300, (int) config('xpanel.host_instances.sso_ttl_seconds', 60)));
        $payload = $this->encode(json_encode([
            'iss' => rtrim((string) config('app.url'), '/'),
            'aud' => $instance->uuid,
            'sub' => (string) ($account?->uuid ?: $fallbackUser?->id),
            'email' => $email,
            'name' => $name ?: 'Administrador',
            'iat' => now()->timestamp,
            'exp' => now()->addSeconds($ttl)->timestamp,
            'jti' => bin2hex(random_bytes(16)),
        ], JSON_THROW_ON_ERROR));
        $signature = $this->encode(hash_hmac('sha256', $payload, $instance->broker_secret, true));

        return rtrim($instance->panelUrl(), '/').'/auth/control-plane?token='.$payload.'.'.$signature;
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
