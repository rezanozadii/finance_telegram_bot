<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'currency', 'current_balance', 'is_archived'];

    protected $casts = ['current_balance' => 'decimal:2', 'is_archived' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_account_id');
    }
}
