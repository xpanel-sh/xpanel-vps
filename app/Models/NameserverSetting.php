<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NameserverSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ns1',
        'ns2',
        'ns3',
        'ns4',
        'provider',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function records(): array
    {
        return array_values(array_filter([$this->ns1, $this->ns2, $this->ns3, $this->ns4]));
    }
}
