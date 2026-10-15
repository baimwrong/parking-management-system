<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParkingSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_zone_id',
        'slot_number',
        'vehicle_type',
        'status',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ParkingZone::class, 'parking_zone_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ParkingTicket::class);
    }

    public function activeTicket(): HasOne
    {
        return $this->hasOne(ParkingTicket::class)->where('status', 'parked');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ParkingViolation::class);
    }
}
