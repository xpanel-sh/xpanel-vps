<?php

namespace App\Services;

use App\Models\HostInstance;
use App\Models\User;

class HostSsoLink
{
    public function for(HostInstance $instance, User $user): string
    {
        $ttl = max(15, min(300, (int) config('xpanel.host_instances.sso_ttl_seconds', 60)));
        $payload = $this->encode(json_encode([
            'iss' => rtrim((string) config('app.url'), '/'),
            'aud' => $instance->uuid,
            'sub' => (string) $user->id,
            'email' => $user->email,
            'name' => $user->name,
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
