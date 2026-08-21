<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerNode extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'ip_address', 'port', 'auth_token', 'is_active'];

    public function sites()
    {
        return $this->hasMany(Site::class);
    }
}
