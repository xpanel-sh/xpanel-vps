<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'number', 'tenant_id', 'hosting_account_id', 'hosting_plan_id', 'status', 'payment_status', 'amount', 'currency',
        'billing_period_months', 'payment_due_at', 'payment_method',
        'payment_reference', 'paid_at', 'marked_paid_by', 'activated_at', 'service_ends_at', 'activated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_due_at' => 'datetime',
        'activated_at' => 'datetime',
        'service_ends_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(HostingPlan::class, 'hosting_plan_id');
    }

    public function hostingAccount()
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function activator()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function paymentConfirmer()
    {
        return $this->belongsTo(User::class, 'marked_paid_by');
    }
}
