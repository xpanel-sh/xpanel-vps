<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'tenant_id', 'hosting_plan_id', 'plan_order_id', 'name', 'admin_name', 'admin_email', 'status',
        'custom_panel_domain', 'custom_domain_status', 'custom_domain_last_error',
        'initial_site_domain', 'initial_site_status', 'initial_site_error',
        'cancellation_requested_at', 'suspend_at', 'suspended_at', 'purge_after',
        'purge_archive_status', 'purge_archive_path', 'purge_archive_sha256', 'purge_archive_error',
        'purge_archive_requested_at', 'purge_archived_at',
        'purge_archive_verified_at',
        'purge_error', 'purge_requested_at', 'purged_at',
        'restore_error', 'restore_requested_at', 'restored_at',
    ];

    protected $casts = [
        'cancellation_requested_at' => 'datetime',
        'suspend_at' => 'datetime',
        'suspended_at' => 'datetime',
        'purge_after' => 'datetime',
        'purge_archive_requested_at' => 'datetime',
        'purge_archived_at' => 'datetime',
        'purge_archive_verified_at' => 'datetime',
        'purge_requested_at' => 'datetime',
        'purged_at' => 'datetime',
        'restore_requested_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(HostingPlan::class, 'hosting_plan_id');
    }

    public function order()
    {
        return $this->belongsTo(PlanOrder::class, 'plan_order_id');
    }

    public function hostInstance()
    {
        return $this->hasOne(HostInstance::class);
    }
}
