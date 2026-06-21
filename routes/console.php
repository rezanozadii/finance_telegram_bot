<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('recurring:check')->dailyAt('09:00');
