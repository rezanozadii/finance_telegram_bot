<?php

namespace App\Telegram\Keyboards;

class ReportKeyboard
{
    public static function periodNav(string $activePeriod, string $lang = 'en'): array
    {
        $periods = $lang === 'fa' ? [
            'month'      => 'ماه جاری',
            'last_month' => 'ماه گذشته',
            'quarter'    => 'سه‌ماهه جاری',
            'year'       => 'سال جاری',
        ] : [
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

        $homeLabel = $lang === 'fa' ? '🏠 بازگشت به خانه' : '🏠 Home';

        return ['inline_keyboard' => [
            array_slice($buttons, 0, 2),
            array_slice($buttons, 2, 2),
            [['text' => $homeLabel, 'callback_data' => 'report:home']],
        ]];
    }
}
