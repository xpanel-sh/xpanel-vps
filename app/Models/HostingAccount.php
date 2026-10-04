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
