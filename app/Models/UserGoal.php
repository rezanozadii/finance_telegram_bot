<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGoal extends Model
{
    protected $fillable = [
        'user_id', 'name', 'target_amount', 'current_amount',
        'currency', 'deadline', 'status', 'notes',
    ];

    protected $casts = [
        'target_amount'  => 'decimal:2',
        'current_amount' => 'decimal:2',
        'deadline'       => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progressPct(): float
    {
        if ((float) $this->target_amount <= 0) {
            return 0.0;
        }
        return min(100.0, round((float) $this->current_amount / (float) $this->target_amount * 100, 1));
    }

    public function remaining(): float
    {
        return max(0.0, (float) $this->target_amount - (float) $this->current_amount);
    }
}
