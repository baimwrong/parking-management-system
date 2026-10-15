<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GateLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'gate_id',
        'ticket_id',
        'action',
        'plate_number_detected',
        'confidence_score',
        'operator_user_id',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ParkingTicket::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }
}
