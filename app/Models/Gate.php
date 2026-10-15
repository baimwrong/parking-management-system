<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gate extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_location_id',
        'name',
        'code',
        'gate_type',
        'ip_address',
        'barrier_status',
        'camera_anpr_enabled',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'camera_anpr_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'parking_location_id');
    }

    public function entryTickets(): HasMany
    {
        return $this->hasMany(ParkingTicket::class, 'entry_gate_id');
    }

    public function exitTickets(): HasMany
    {
        return $this->hasMany(ParkingTicket::class, 'exit_gate_id');
    }

    public function gateLogs(): HasMany
    {
        return $this->hasMany(GateLog::class);
    }
}
