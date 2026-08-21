<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostBrokerResource extends Model
{
    protected $fillable = ['host_instance_id', 'type', 'name'];
}
