<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'name', 'amount', 'currency',
        'period', 'spent_amount', 'last_reset_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'spent_amount'  => 'decimal:2',
        'last_reset_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
