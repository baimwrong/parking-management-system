<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'icon',
        'base_rate',
        'hourly_rate',
        'max_daily_rate',
        'grace_period_minutes',
        'lost_ticket_penalty',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_rate' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'max_daily_rate' => 'decimal:2',
            'lost_ticket_penalty' => 'decimal:2',
            'grace_period_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ParkingTicket::class);
    }

    public function memberPasses(): HasMany
    {
        return $this->hasMany(MemberPass::class);
    }
}
