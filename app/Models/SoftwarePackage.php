<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftwarePackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_node_id',
        'slug',
        'category',
        'enabled_for_clients',
    ];

    protected $casts = [
        'enabled_for_clients' => 'boolean',
    ];

    public function serverNode()
    {
        return $this->belongsTo(ServerNode::class);
    }
}
