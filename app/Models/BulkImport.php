<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'import_type',
        'filename',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'status',
        'error_details',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'successful_rows' => 'integer',
            'failed_rows' => 'integer',
            'error_details' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
