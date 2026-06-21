<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringOccurrence extends Model
{
    protected $fillable = ['template_id', 'due_date', 'status', 'confirmed_transaction_id'];

    protected $casts = ['due_date' => 'date'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecurringTemplate::class);
    }

    public function confirmedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'confirmed_transaction_id');
    }
}
