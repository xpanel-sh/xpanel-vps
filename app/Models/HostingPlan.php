<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'max_sites',
        'max_databases',
        'storage_mb',
        'bandwidth_gb',
        'email_accounts',
        'memory_mb',
        'swap_mb',
        'cpu_percent',
        'tasks_max',
        'monthly_price',
        'billing_period_months',
        'payment_due_days',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'monthly_price' => 'decimal:2',
        'billing_period_months' => 'integer',
        'payment_due_days' => 'integer',
        'memory_mb' => 'integer',
        'swap_mb' => 'integer',
        'cpu_percent' => 'integer',
        'tasks_max' => 'integer',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }

    public function orders()
    {
        return $this->hasMany(PlanOrder::class);
    }
}
