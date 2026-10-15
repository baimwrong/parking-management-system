<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'total_capacity',
        'operating_hours',
        'contact_phone',
        'settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'total_capacity' => 'integer',
        ];
    }

    public function zones(): HasMany
    {
        return $this->hasMany(ParkingZone::class);
    }

    public function gates(): HasMany
    {
        return $this->hasMany(Gate::class);
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ParkingTicket::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ParkingViolation::class);
    }

    public function getOccupiedCountAttribute(): int
    {
        return $this->tickets()->where('status', 'parked')->count();
    }

    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->total_capacity - $this->occupied_count);
    }

    public function getOccupancyPercentageAttribute(): float
    {
        if ($this->total_capacity <= 0) return 0.0;
        return round(($this->occupied_count / $this->total_capacity) * 100, 1);
    }
}
