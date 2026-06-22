<?php

namespace App\AI\Tools;

use App\Models\User;
use App\Services\AI\BudgetAnalysisService;

class GetBudgetsTool implements AiToolInterface
{
    public function __construct(private BudgetAnalysisService $budgetAnalysis) {}

    public function execute(User $user, array $params = []): array
    {
        return ['budgets' => $this->budgetAnalysis->analyze($user)];
    }
}
