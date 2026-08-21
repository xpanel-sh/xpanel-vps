<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DockerInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'docker_app_template_id',
        'name',
        'slug',
        'compose_yaml',
        'env_values',
        'domain',
        'status',
    ];

    protected $casts = [
        'env_values' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template()
    {
        return $this->belongsTo(DockerAppTemplate::class, 'docker_app_template_id');
    }

    /** Nombre del proyecto docker compose: xpanel-docker-{tenant_code}-{slug} */
    public function projectName(): string
    {
        $code = strtolower($this->tenant?->code ?? 'x000000');

        return 'xpanel-docker-'.$code.'-'.$this->slug;
    }
}
