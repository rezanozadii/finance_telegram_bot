<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTemplate extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'category_id', 'type', 'amount', 'currency',
        'description', 'frequency', 'next_due_date', 'reminder_enabled',
        'reminder_days_before', 'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'next_due_date' => 'date',
        'reminder_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(RecurringOccurrence::class, 'template_id');
    }
}
