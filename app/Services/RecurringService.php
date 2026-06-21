<?php

namespace App\Services;

use App\Models\RecurringOccurrence;
use App\Models\RecurringTemplate;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class RecurringService
{
    public function __construct(private TransactionService $transactionService) {}

    public function createTemplate(User $user, array $data): RecurringTemplate
    {
        return RecurringTemplate::create([
            'user_id'              => $user->id,
            'account_id'           => $data['account_id'],
            'category_id'          => $data['category_id'] ?? null,
            'type'                 => $data['type'],
            'amount'               => $data['amount'],
            'currency'             => $data['currency'],
            'description'          => $data['description'],
            'frequency'            => $data['frequency'],
            'next_due_date'        => $data['next_due_date'],
            'reminder_enabled'     => $data['reminder_enabled'] ?? true,
            'reminder_days_before' => $data['reminder_days_before'] ?? 1,
            'is_active'            => true,
        ]);
    }

    public function listActive(User $user): Collection
    {
        return $user->recurringTemplates()
            ->with(['account', 'category'])
            ->where('is_active', true)
            ->orderBy('next_due_date')
            ->get();
    }

    public function deactivate(RecurringTemplate $template): void
    {
        $template->update(['is_active' => false]);
    }

    /** Templates whose reminder should fire today. */
    public function dueForReminder(Carbon $today): Collection
    {
        return RecurringTemplate::query()
            ->where('is_active', true)
            ->where('reminder_enabled', true)
            ->whereRaw('DATE_SUB(next_due_date, INTERVAL reminder_days_before DAY) = ?', [$today->toDateString()])
            ->with(['user', 'account', 'category'])
            ->get();
    }

    /** Templates whose next_due_date is today or overdue with no pending occurrence yet. */
    public function dueForConfirmation(Carbon $today): Collection
    {
        return RecurringTemplate::query()
            ->where('is_active', true)
            ->whereDate('next_due_date', '<=', $today->toDateString())
            ->with(['user', 'account', 'category'])
            ->get()
            ->filter(fn (RecurringTemplate $t) =>
                !$t->occurrences()
                    ->whereDate('due_date', $t->next_due_date)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists()
            );
    }

    public function createPendingOccurrence(RecurringTemplate $template): RecurringOccurrence
    {
        return RecurringOccurrence::create([
            'template_id' => $template->id,
            'due_date'    => $template->next_due_date,
            'status'      => 'pending',
        ]);
    }

    public function confirmOccurrence(RecurringOccurrence $occurrence, ?float $amount = null): Transaction
    {
        $template = $occurrence->template;
        $user     = $template->user;

        $transaction = $this->transactionService->createTransaction($user, [
            'type'        => $template->type,
            'account_id'  => $template->account_id,
            'category_id' => $template->category_id,
            'amount'      => $amount ?? (float) $template->amount,
            'currency'    => $template->currency,
            'description' => $template->description,
            'occurred_at' => $occurrence->due_date->toDateTimeString(),
            'source'      => 'manual',
        ]);

        $occurrence->update([
            'status'                   => 'confirmed',
            'confirmed_transaction_id' => $transaction->id,
        ]);

        $this->advanceNextDueDate($template);

        return $transaction;
    }

    public function skipOccurrence(RecurringOccurrence $occurrence): void
    {
        $occurrence->update(['status' => 'skipped']);
        $this->advanceNextDueDate($occurrence->template);
    }

    public function advanceNextDueDate(RecurringTemplate $template): void
    {
        $next = match ($template->frequency) {
            'daily'   => $template->next_due_date->copy()->addDay(),
            'weekly'  => $template->next_due_date->copy()->addWeek(),
            'monthly' => $template->next_due_date->copy()->addMonth(),
            'yearly'  => $template->next_due_date->copy()->addYear(),
        };

        $template->update(['next_due_date' => $next]);
    }
}
