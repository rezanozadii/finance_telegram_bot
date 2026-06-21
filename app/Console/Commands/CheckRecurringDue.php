<?php

namespace App\Console\Commands;

use App\Services\RecurringService;
use App\Telegram\Handlers\RecurringHandler;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckRecurringDue extends Command
{
    protected $signature   = 'recurring:check {--date= : Override today\'s date (YYYY-MM-DD) for testing}';
    protected $description = 'Send reminders and due-date confirmation prompts for recurring transactions';

    public function handle(RecurringService $service, RecurringHandler $handler): int
    {
        $today = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $this->info("Checking recurring transactions for {$today->toDateString()}");

        // 1. Reminders (heads-up, not yet due)
        $reminders = $service->dueForReminder($today);
        foreach ($reminders as $template) {
            try {
                $handler->sendReminderNotice($template);
                $this->line("  Reminder sent: {$template->description} → @{$template->user->username}");
            } catch (\Throwable $e) {
                $this->error("  Failed reminder for template #{$template->id}: {$e->getMessage()}");
            }
        }

        $this->info("Reminders sent: {$reminders->count()}");

        // 2. Due-date confirmations
        $due = $service->dueForConfirmation($today);
        foreach ($due as $template) {
            try {
                $occurrence = $service->createPendingOccurrence($template);
                $handler->sendDuePrompt($template, $occurrence);
                $this->line("  Due prompt sent: {$template->description} → @{$template->user->username}");
            } catch (\Throwable $e) {
                $this->error("  Failed due prompt for template #{$template->id}: {$e->getMessage()}");
            }
        }

        $this->info("Due prompts sent: {$due->count()}");

        return self::SUCCESS;
    }
}
