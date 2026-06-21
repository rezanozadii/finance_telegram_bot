<?php

namespace App\Telegram\Keyboards;

class ReportKeyboard
{
    public static function periodNav(string $activePeriod): array
    {
        $periods = [
            'month'      => 'This Month',
            'last_month' => 'Last Month',
            'quarter'    => 'This Quarter',
            'year'       => 'This Year',
        ];

        $buttons = [];
        foreach ($periods as $key => $label) {
            $buttons[] = [
                'text'          => ($key === $activePeriod ? '• ' : '') . $label,
                'callback_data' => "report:{$key}",
            ];
        }

        // Two rows: [This Month] [Last Month] / [This Quarter] [This Year]
        return ['inline_keyboard' => [
            array_slice($buttons, 0, 2),
            array_slice($buttons, 2, 2),
        ]];
    }
}
