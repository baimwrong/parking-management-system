<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parking_location_id',
        'gate_id',
        'started_at',
        'ended_at',
        'opening_float',
        'closing_cash_counted',
        'system_calculated_cash',
        'cash_variance',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'opening_float' => 'decimal:2',
            'closing_cash_counted' => 'decimal:2',
            'system_calculated_cash' => 'decimal:2',
            'cash_variance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'parking_location_id');
    }

    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
