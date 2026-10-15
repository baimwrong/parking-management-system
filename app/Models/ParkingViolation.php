<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_location_id',
        'parking_slot_id',
        'plate_number',
        'violation_type',
        'fine_amount',
        'status',
        'officer_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fine_amount' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'parking_location_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ParkingSlot::class, 'parking_slot_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_user_id');
    }
}
