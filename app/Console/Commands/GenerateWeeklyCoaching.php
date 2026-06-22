<?php

namespace App\Console\Commands;

use App\Jobs\GenerateWeeklyCoachingJob;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateWeeklyCoaching extends Command
{
    protected $signature   = 'ai:weekly-coaching';
    protected $description = 'Generate weekly coaching for all users';

    public function handle(): void
    {
        User::all()->each(fn ($user) => GenerateWeeklyCoachingJob::dispatch($user->id));
        $this->info('Weekly coaching jobs dispatched.');
    }
}
