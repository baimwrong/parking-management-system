<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ParkingTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_uid',
        'ticket_number',
        'barcode_hash',
        'parking_location_id',
        'parking_zone_id',
        'parking_slot_id',
        'entry_gate_id',
        'exit_gate_id',
        'vehicle_type_id',
        'plate_number',
        'entry_image_url',
        'exit_image_url',
        'ticket_type',
        'member_pass_id',
        'entry_time',
        'exit_time',
        'paid_at',
        'status',
        'duration_minutes',
        'subtotal',
        'discount_amount',
        'penalty_amount',
        'tax_amount',
        'total_amount',
        'payment_method',
        'payment_reference',
        'cashier_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_time' => 'datetime',
            'exit_time' => 'datetime',
            'paid_at' => 'datetime',
            'duration_minutes' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_uid)) {
                $ticket->ticket_uid = (string) Str::uuid();
            }
            if (empty($ticket->barcode_hash)) {
                $ticket->barcode_hash = strtoupper(bin2hex(random_bytes(6)));
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'parking_location_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ParkingZone::class, 'parking_zone_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ParkingSlot::class, 'parking_slot_id');
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function entryGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'entry_gate_id');
    }

    public function exitGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'exit_gate_id');
    }

    public function memberPass(): BelongsTo
    {
        return $this->belongsTo(MemberPass::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    // Scopes
    public function scopeParked($query)
    {
        return $query->where('status', 'parked');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Compute real-time duration and charges
     */
    public function recalculateCharges(?Carbon $targetExitTime = null): array
    {
        $exitTime = $targetExitTime ?? ($this->exit_time ?? Carbon::now());
        $entryTime = $this->entry_time;

        $durationMinutes = max(1, $entryTime->diffInMinutes($exitTime));
        $this->duration_minutes = $durationMinutes;

        // If ticket is member and member is valid
        if ($this->ticket_type === 'member' && $this->memberPass && $this->memberPass->isValid()) {
            $this->subtotal = 0.00;
            $this->discount_amount = 0.00;
            $this->penalty_amount = 0.00;
            $this->tax_amount = 0.00;
            $this->total_amount = 0.00;

            return [
                'duration_minutes' => $durationMinutes,
                'billable_hours' => 0,
                'subtotal' => 0.00,
                'discount' => 0.00,
                'penalty' => 0.00,
                'tax' => 0.00,
                'total' => 0.00,
                'is_member' => true,
                'is_grace' => false,
            ];
        }

        // Find tariff for location + vehicle type
        $tariff = Tariff::where('parking_location_id', $this->parking_location_id)
            ->where('vehicle_type_id', $this->vehicle_type_id)
            ->where('is_active', true)
            ->first();

        $isLost = ($this->ticket_type === 'lost' || $this->status === 'lost');

        if ($tariff) {
            $calc = $tariff->calculateFee($durationMinutes, $isLost);
            $subtotal = $calc['subtotal'];
            $penalty = $calc['penalty'];
            $isGrace = $calc['is_grace_period'];
            $billableHours = $calc['billable_hours'] ?? (int) ceil($durationMinutes / 60);
        } else {
            // Fallback rates from vehicle type
            $vType = $this->vehicleType;
            $hours = (int) ceil($durationMinutes / 60);
            $isGrace = ($durationMinutes <= ($vType->grace_period_minutes ?? 15));
            $subtotal = $isGrace ? 0.00 : (($vType->base_rate ?? 5) + (($hours - 1) * ($vType->hourly_rate ?? 3)));
            $penalty = $isLost ? ($vType->lost_ticket_penalty ?? 50.00) : 0.00;
            if ($isLost) {
                $subtotal = $penalty;
            }
            $billableHours = $isGrace ? 0 : $hours;
        }

        // Zone surcharge if applicable
        if ($this->zone && $this->zone->hourly_surcharge > 0 && ! $isGrace) {
            $subtotal += ($this->zone->hourly_surcharge * ceil($durationMinutes / 60));
        }

        // VAT / Municipal Tax based on location settings (defaults to 10%)
        $vatRate = (float) ($this->location?->settings['vat_percentage'] ?? 10);
        $tax = round($subtotal * ($vatRate / 100), 2);
        $discount = (float) ($this->discount_amount ?? 0.00);
        $total = max(0.00, round(($subtotal + $tax) - $discount, 2));

        $this->subtotal = $subtotal;
        $this->discount_amount = $discount;
        $this->penalty_amount = $penalty;
        $this->tax_amount = $tax;
        $this->total_amount = $total;

        return [
            'duration_minutes' => $durationMinutes,
            'billable_hours' => $billableHours,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'penalty' => $penalty,
            'tax' => $tax,
            'total' => $total,
            'is_member' => false,
            'is_grace' => $isGrace,
        ];
    }
}
