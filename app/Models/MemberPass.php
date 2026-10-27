<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberPass extends Model
{
    use HasFactory;

    protected $fillable = [
        'pass_number',
        'rfid_uid',
        'member_name',
        'phone',
        'email',
        'plate_number',
        'vehicle_type_id',
        'plan_type',
        'price_paid',
        'valid_from',
        'valid_until',
        'status',
        'is_inside',
        'last_entry_at',
        'last_exit_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'price_paid' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_inside' => 'boolean',
            'last_entry_at' => 'datetime',
            'last_exit_at' => 'datetime',
        ];
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ParkingTicket::class);
    }

    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $today = Carbon::today();
        return $today->gte($this->valid_from) && $today->lte($this->valid_until);
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, Carbon::today()->diffInDays($this->valid_until, false));
    }
}
