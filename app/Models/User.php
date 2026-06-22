<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $fillable = ['telegram_id', 'username', 'display_name', 'default_currency', 'language'];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function categorizationRules(): HasMany
    {
        return $this->hasMany(CategorizationRule::class);
    }

    public function recurringTemplates(): HasMany
    {
        return $this->hasMany(RecurringTemplate::class);
    }

    public function friendships(): HasMany
    {
        return $this->hasMany(Friendship::class);
    }

    public function sharedExpensesOwed(): HasMany
    {
        return $this->hasMany(SharedExpense::class, 'from_user_id');
    }

    public function sharedExpensesOwing(): HasMany
    {
        return $this->hasMany(SharedExpense::class, 'to_user_id');
    }

    public function aiMemory(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AiUserMemory::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(UserGoal::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function aiInsights(): HasMany
    {
        return $this->hasMany(AiInsight::class);
    }

    public function detectedSubscriptions(): HasMany
    {
        return $this->hasMany(DetectedSubscription::class);
    }
}
