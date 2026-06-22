<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInsight extends Model
{
    protected $fillable = ['user_id', 'type', 'content', 'insights_date', 'is_sent'];

    protected $casts = [
        'insights_date' => 'date',
        'is_sent'       => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
