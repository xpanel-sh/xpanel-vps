<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'hosting_account_id', 'uuid', 'panel_domain', 'access_port', 'system_user', 'release_path',
        'instance_root', 'database_path', 'broker_secret', 'initial_password', 'php_version', 'version',
        'update_channel', 'status', 'ssl_status', 'last_error', 'ssl_last_error', 'ssl_attempted_at', 'provisioned_at',
    ];

    protected $hidden = ['broker_secret', 'initial_password'];

    protected $casts = [
        'access_port' => 'integer',
        'provisioned_at' => 'datetime',
        'ssl_attempted_at' => 'datetime',
        'broker_secret' => 'encrypted',
        'initial_password' => 'encrypted',
    ];

    public function brokerOperations()
    {
        return $this->hasMany(HostBrokerOperation::class)->latest();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hostingAccount()
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function panelUrl(): string
    {
        $customDomain = $this->hostingAccount?->custom_panel_domain;
        if ($customDomain && $this->hostingAccount?->custom_domain_status === 'active') {
            return 'https://'.$customDomain;
        }

        if ($this->ssl_status === 'active') {
            return 'https://'.$this->panel_domain;
        }

        return $this->fallbackUrl() ?: 'http://'.$this->panel_domain;
    }

    public function fallbackUrl(): ?string
    {
        $serverIp = trim((string) config('xpanel.server_ip'));

        return $serverIp !== '' && $this->access_port
            ? 'https://'.$serverIp.':'.$this->access_port
            : null;
    }
}
