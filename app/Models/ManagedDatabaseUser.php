<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagedDatabaseUser extends Model
{
    protected $fillable = [
        'managed_database_id',
        'username',
        'password',
        'privileges',
        'status',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'privileges' => 'array',
    ];

    public function database()
    {
        return $this->belongsTo(ManagedDatabase::class, 'managed_database_id');
    }
}
