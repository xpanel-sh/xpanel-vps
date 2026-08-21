<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DockerAppTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'compose_template',
        'parameters',
        'is_public',
        'category',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_public' => 'boolean',
    ];

    public function instances()
    {
        return $this->hasMany(DockerInstance::class);
    }
}
