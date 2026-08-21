<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'server_node_id',
        'domain',
        'project_type',
        'web_server',
        'php_version',
        'php_options',
        'provisioning_driver',
        'system_user',
        'document_root',
        'ssl_status',
        'ssl_expires_at',
        'ssl_issuer',
        'https_redirect',
        'status',
    ];

    protected $casts = [
        'php_options' => 'array',
        'ssl_expires_at' => 'datetime',
        'https_redirect' => 'boolean',
    ];

    public function isNative(): bool
    {
        return $this->provisioning_driver === 'native'
            || in_array($this->project_type, ['php', 'static'], true);
    }

    public function systemUser(): string
    {
        $user = $this->system_user;

        if (! $user && $this->id && $this->domain) {
            $user = 'xps'.base_convert((string) $this->id, 10, 36)
                .substr(hash('sha256', $this->tenant_id.'|'.$this->domain), 0, 8);
        }

        if (! is_string($user) || ! preg_match('/^xps[a-z0-9]{9,29}$/', $user)) {
            throw new \RuntimeException('El sitio no tiene una identidad Unix válida.');
        }

        return $user;
    }

    public function nativeDocumentRoot(): string
    {
        if (is_string($this->document_root) && $this->document_root !== '') {
            return $this->document_root;
        }

        $tenant = $this->relationLoaded('tenant') ? $this->tenant : $this->tenant()->first();
        $tenantCode = strtolower((string) ($tenant?->ensureCode() ?? 'tenant'.$this->tenant_id));
        $tenantCode = preg_replace('/[^a-z0-9]/', '', $tenantCode) ?: 'tenant'.$this->tenant_id;
        $base = rtrim((string) config('xpanel.native_hosting.web_root', '/var/www/xpanel'), '/');

        if (! preg_match('#^/(?:var|srv)/www(?:/[A-Za-z0-9._-]+)*$#', $base)) {
            throw new \RuntimeException('XPANEL_WEB_ROOT debe estar bajo /var/www o /srv/www.');
        }

        return $base.'/'.$tenantCode.'/'.strtolower($this->domain);
    }

    public function ensureNativeRuntime(): void
    {
        $this->forceFill([
            'provisioning_driver' => 'native',
            'system_user' => $this->systemUser(),
            'document_root' => $this->nativeDocumentRoot(),
        ])->save();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function managedDatabases()
    {
        return $this->hasMany(ManagedDatabase::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }
}
