<?php

namespace App\AI\Tools;

use App\Models\User;
use App\Services\AI\FinancialCalculatorService;
use Carbon\Carbon;

class GetStatisticsTool implements AiToolInterface
{
    public function __construct(private FinancialCalculatorService $calculator) {}

    public function execute(User $user, array $params = []): array
    {
        $currency = $params['currency'] ?? $user->default_currency ?? 'USD';
        $start    = isset($params['start']) ? Carbon::parse($params['start']) : Carbon::now()->startOfMonth();
        $end      = isset($params['end']) ? Carbon::parse($params['end']) : Carbon::now()->endOfMonth();

        $stats = $this->calculator->getStats($user, $start, $end, $currency);
        $trend = $this->calculator->getMonthlyTrend($user, 3, $currency);

        return array_merge($stats, ['monthly_trend' => $trend]);
    }
}
