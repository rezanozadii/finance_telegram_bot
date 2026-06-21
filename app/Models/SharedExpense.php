<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedExpense extends Model
{
    protected $fillable = [
        'from_user_id', 'to_user_id', 'amount', 'currency',
        'description', 'status', 'related_transaction_id',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'related_transaction_id');
    }
}
