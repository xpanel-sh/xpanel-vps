<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountNotification extends Model
{
    protected $fillable = [
        'tenant_id', 'hosting_account_id', 'type', 'title', 'message', 'action_url', 'data', 'email_sent_at', 'email_error', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'email_sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hostingAccount()
    {
        return $this->belongsTo(HostingAccount::class);
    }
}
