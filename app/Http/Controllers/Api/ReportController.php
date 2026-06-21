<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function show(Request $request): JsonResponse
    {
        $user     = $request->attributes->get('telegram_user');
        $period   = $request->query('period', 'month');   // month|quarter|year|last_month
        $currency = $request->query('currency', $user->default_currency ?? 'USD');

        // Custom range: ?from=YYYY-MM-DD&to=YYYY-MM-DD
        if ($request->query('from') && $request->query('to')) {
            $start  = Carbon::parse($request->query('from'))->startOfDay();
            $end    = Carbon::parse($request->query('to'))->endOfDay();
            $type   = 'custom';
        } elseif ($period === 'month' && $request->query('month')) {
            [$start, $end] = $this->reportService->periodBounds('month', $request->query('month'));
            $type = 'month';
        } else {
            [$start, $end] = $this->reportService->periodBounds($period);
            $type = $period;
        }

        [$prevStart, $prevEnd] = $this->reportService->previousPeriodBounds($type, $start, $end);

        $data = $this->reportService->generate($user, $start, $end, $currency);
        $prev = $this->reportService->generate($user, $prevStart, $prevEnd, $currency);

        return response()->json([
            'label'            => $this->reportService->periodLabel($type, $start, $end),
            'prev_label'       => $this->reportService->periodLabel($type, $prevStart, $prevEnd),
            'currency'         => $currency,
            'period_start'     => $start->toDateString(),
            'period_end'       => $end->toDateString(),
            'income'           => $data['income'],
            'expenses'         => $data['expenses'],
            'net'              => $data['net'],
            'count'            => $data['count'],
            'by_category'      => $data['by_category'],
            'other_currencies' => $data['other_currencies'],
            'prev_income'      => $prev['income'],
            'prev_expenses'    => $prev['expenses'],
            'income_change'    => $this->reportService->formatChange($data['income'], $prev['income']),
            'expense_change'   => $this->reportService->formatChange($data['expenses'], $prev['expenses']),
        ]);
    }
}
