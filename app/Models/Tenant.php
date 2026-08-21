<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'domain', 'code', 'user_id', 'plan_id', 'status'];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (blank($tenant->code)) {
                $tenant->code = static::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'X'.random_int(100000, 999999);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function ensureCode(): string
    {
        if (filled($this->code)) {
            return $this->code;
        }

        $this->forceFill(['code' => static::generateCode()])->save();

        return $this->code;
    }

    public function databasePrefix(): string
    {
        return strtolower($this->ensureCode()).'_';
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function hostInstance()
    {
        return $this->hasOne(HostInstance::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function emailAccounts()
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function dnsRecords()
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(HostingPlan::class, 'plan_id');
    }

    public function planOrders()
    {
        return $this->hasMany(PlanOrder::class);
    }
}
