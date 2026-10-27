<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_location_id',
        'vehicle_type_id',
        'name',
        'tariff_type',
        'initial_duration_minutes',
        'initial_rate',
        'subsequent_rate_per_hour',
        'max_daily_cap',
        'overnight_surcharge',
        'lost_ticket_fee',
        'grace_period_minutes',
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'initial_duration_minutes' => 'integer',
            'initial_rate' => 'decimal:2',
            'subsequent_rate_per_hour' => 'decimal:2',
            'max_daily_cap' => 'decimal:2',
            'overnight_surcharge' => 'decimal:2',
            'lost_ticket_fee' => 'decimal:2',
            'grace_period_minutes' => 'integer',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'parking_location_id');
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    /**
     * Calculate parking fee based on duration in minutes
     */
    public function calculateFee(int $durationMinutes, bool $isLostTicket = false): array
    {
        if ($isLostTicket) {
            $penalty = (float) $this->lost_ticket_fee;
            $subtotal = $penalty;
            return [
                'subtotal' => $subtotal,
                'penalty' => $penalty,
                'is_grace_period' => false,
                'billable_hours' => ceil($durationMinutes / 60),
            ];
        }

        // 1. Grace period check (free drop-off)
        if ($durationMinutes <= $this->grace_period_minutes) {
            return [
                'subtotal' => 0.00,
                'penalty' => 0.00,
                'is_grace_period' => true,
                'billable_hours' => 0,
            ];
        }

        // 2. Multi-day / 24-hour cycle calculation
        $totalHours = (int) ceil($durationMinutes / 60);
        $fullDays = (int) floor($totalHours / 24);
        $remainingHours = $totalHours % 24;

        $dailyCap = (float) $this->max_daily_cap;
        $initialRate = (float) $this->initial_rate;
        $subsequentRate = (float) $this->subsequent_rate_per_hour;

        $feeForRemaining = 0.00;
        if ($remainingHours > 0) {
            $initialHours = max(1, (int) ceil($this->initial_duration_minutes / 60));
            if ($remainingHours <= $initialHours) {
                $feeForRemaining = $initialRate;
            } else {
                $subsequentHours = $remainingHours - $initialHours;
                $feeForRemaining = $initialRate + ($subsequentHours * $subsequentRate);
            }

            // Cap remaining hours at daily cap
            if ($dailyCap > 0 && $feeForRemaining > $dailyCap) {
                $feeForRemaining = $dailyCap;
            }
        }

        $overnightSurcharge = (float) ($this->overnight_surcharge ?? 0.00);
        $overnightFee = ($fullDays > 0 && $overnightSurcharge > 0) ? ($fullDays * $overnightSurcharge) : 0.00;

        $subtotal = ($fullDays * $dailyCap) + $feeForRemaining + $overnightFee;

        return [
            'subtotal' => round($subtotal, 2),
            'penalty' => 0.00,
            'is_grace_period' => false,
            'billable_hours' => $totalHours,
        ];
    }
}
