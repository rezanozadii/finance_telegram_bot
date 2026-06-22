<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('recurring:check')->dailyAt('09:00');
Schedule::command('ai:daily-insights')->dailyAt('08:00');
Schedule::command('ai:weekly-coaching')->weeklyOn(1, '09:00');
Schedule::command('ai:monthly-review')->monthlyOn(1, '10:00');
