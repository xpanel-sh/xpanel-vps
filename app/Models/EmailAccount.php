<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'domain_id',
        'local_part',
        'email',
        'password',
        'quota_mb',
        'status',
        'last_password_change_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_password_change_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
