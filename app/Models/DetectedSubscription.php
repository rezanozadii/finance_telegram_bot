<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetectedSubscription extends Model
{
    protected $fillable = [
        'user_id', 'merchant', 'amount', 'currency', 'frequency',
        'last_payment_at', 'next_predicted_at', 'is_confirmed',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'last_payment_at'   => 'date',
        'next_predicted_at' => 'date',
        'is_confirmed'      => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
