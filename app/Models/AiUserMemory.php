<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUserMemory extends Model
{
    protected $fillable = [
        'user_id', 'personality', 'preferred_currency', 'salary_day',
        'risk_level', 'saving_rate', 'largest_category',
        'overspending_categories', 'goals_summary', 'last_summary', 'profile_updated_at',
    ];

    protected $casts = [
        'overspending_categories' => 'array',
        'goals_summary'           => 'array',
        'profile_updated_at'      => 'datetime',
        'saving_rate'             => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
