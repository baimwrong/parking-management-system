<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_location_id',
        'name',
        'code',
        'floor_level',
        'zone_type',
        'total_slots',
        'available_slots',
        'hourly_surcharge',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_slots' => 'integer',
            'available_slots' => 'integer',
            'hourly_surcharge' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'parking_location_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ParkingSlot::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ParkingTicket::class);
    }

    public function recalculateAvailableSlots(): void
    {
        $occupied = $this->slots()->where('status', 'occupied')->count();
        $this->available_slots = max(0, $this->total_slots - $occupied);
        $this->save();
    }
}
