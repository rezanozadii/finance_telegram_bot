<?php

namespace App\Console\Commands;

use App\Jobs\GenerateMonthlyReviewJob;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateMonthlyReview extends Command
{
    protected $signature   = 'ai:monthly-review';
    protected $description = 'Generate monthly financial review for all users';

    public function handle(): void
    {
        User::all()->each(fn ($user) => GenerateMonthlyReviewJob::dispatch($user->id));
        $this->info('Monthly review jobs dispatched.');
    }
}
