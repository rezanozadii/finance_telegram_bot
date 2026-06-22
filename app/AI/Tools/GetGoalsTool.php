<?php

namespace App\AI\Tools;

use App\Models\User;

class GetGoalsTool implements AiToolInterface
{
    public function execute(User $user, array $params = []): array
    {
        $goals = $user->goals()->where('status', 'active')->get();

        return [
            'goals' => $goals->map(fn ($g) => [
                'name'           => $g->name,
                'target_amount'  => (float) $g->target_amount,
                'current_amount' => (float) $g->current_amount,
                'remaining'      => $g->remaining(),
                'progress_pct'   => $g->progressPct(),
                'currency'       => $g->currency,
                'deadline'       => $g->deadline?->toDateString(),
            ])->values()->all(),
        ];
    }
}
