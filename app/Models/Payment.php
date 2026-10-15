<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_ticket_id',
        'invoice_number',
        'payment_method',
        'amount_due',
        'amount_paid',
        'change_given',
        'transaction_status',
        'reference_no',
        'cashier_user_id',
        'paid_at',
        'receipt_meta',
    ];

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_given' => 'decimal:2',
            'paid_at' => 'datetime',
            'receipt_meta' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ParkingTicket::class, 'parking_ticket_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }
}
