<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostBrokerOperation extends Model
{
    protected $fillable = ['host_instance_id', 'request_id', 'action', 'arguments', 'status', 'output', 'error'];

    protected $casts = ['arguments' => 'array'];

    public function instance()
    {
        return $this->belongsTo(HostInstance::class, 'host_instance_id');
    }
}
