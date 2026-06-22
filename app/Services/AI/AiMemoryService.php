<?php

namespace App\Services\AI;

use App\Models\AiUserMemory;
use App\Models\User;
use Carbon\Carbon;

class AiMemoryService
{
    public function getProfile(User $user): AiUserMemory
    {
        return AiUserMemory::firstOrCreate(
            ['user_id' => $user->id],
            [
                'personality'             => 'Balanced Saver',
                'preferred_currency'      => $user->default_currency ?? 'USD',
                'risk_level'              => 'Low',
                'saving_rate'             => 0,
                'overspending_categories' => [],
                'goals_summary'           => [],
            ]
        );
    }

    public function updateFromStats(User $user, array $stats): void
    {
        $memory = $this->getProfile($user);

        $savingRate           = (float) ($stats['savings_rate'] ?? 0);
        $largestCategory      = $stats['top_categories'][0]['name'] ?? null;
        $overspendingCategories = collect($stats['top_categories'] ?? [])
            ->filter(fn ($c) => $c['pct'] > 30)
            ->pluck('name')
            ->values()
            ->all();

        $goals       = $user->goals()->where('status', 'active')->get();
        $goalSummary = $goals->map(fn ($g) => [
            'name'     => $g->name,
            'progress' => $g->progressPct(),
        ])->values()->all();

        $personality = $this->classifyPersonality($savingRate, $largestCategory, $overspendingCategories, $stats);

        $memory->update([
            'personality'             => $personality,
            'saving_rate'             => $savingRate,
            'largest_category'        => $largestCategory,
            'overspending_categories' => $overspendingCategories,
            'goals_summary'           => $goalSummary,
            'profile_updated_at'      => Carbon::now(),
        ]);
    }

    private function classifyPersonality(
        float $savingRate,
        ?string $largestCategory,
        array $overspendingCategories,
        array $stats,
    ): string {
        $lifestyleCategories = ['restaurants', 'entertainment', 'shopping', 'food', 'dining'];
        $isLifestyleSpender  = $largestCategory && collect($lifestyleCategories)
            ->contains(fn ($c) => stripos($largestCategory, $c) !== false);

        return match (true) {
            $savingRate < 0                             => 'Debt Heavy',
            $savingRate < 5 && $isLifestyleSpender      => 'Lifestyle Spender',
            $savingRate >= 30                            => 'Budget Master',
            $savingRate >= 20                            => 'Balanced Saver',
            default                                      => 'Balanced Saver',
        };
    }
}
