<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\CategorizationRule;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use OpenAI;

class AiParsingService
{
    private const FUZZY_THRESHOLD = 60;

    public function parse(User $user, string $text): ?AiParseResult
    {
        if (!config('services.deepseek.api_key')) {
            return null;
        }

        $accounts   = $user->accounts()->where('is_archived', false)->get();
        $categories = $user->categories()->get();
        $rules      = $this->matchingRules($user, $text);

        $systemPrompt = $this->buildSystemPrompt($accounts, $categories, $rules);

        $raw = $this->callApi($systemPrompt, $text);

        if ($raw === null) {
            return null;
        }

        $data = $this->extractJson($raw);

        if (!$data || !isset($data['type'], $data['amount'])) {
            Log::warning('AI parsing: invalid JSON structure', ['raw' => $raw]);
            return null;
        }

        return $this->buildResult($user, $data, $accounts, $categories);
    }

    // ── Prompt ───────────────────────────────────────────────────────────────

    private function buildSystemPrompt(
        \Illuminate\Database\Eloquent\Collection $accounts,
        \Illuminate\Database\Eloquent\Collection $categories,
        \Illuminate\Database\Eloquent\Collection $rules,
    ): string {
        $today = now()->toDateString();

        $accountList = $accounts->map(fn (Account $a) =>
            "- {$a->name} ({$a->type}, {$a->currency}, balance: {$a->current_balance})"
        )->join("\n");

        $expenseNames = $categories->where('type', 'expense')->pluck('name')->join(', ');
        $incomeNames  = $categories->where('type', 'income')->pluck('name')->join(', ');

        $ruleHints = $rules->isNotEmpty()
            ? "\nCategorization hints (apply these exactly):\n" .
              $rules->map(fn (CategorizationRule $r) => "- \"{$r->merchant_or_keyword}\" → {$r->category->name}")->join("\n")
            : '';

        return <<<PROMPT
You are a financial transaction parser. Extract transaction data from the user's message and return ONLY valid JSON — no explanation, no markdown, no code fences.

Today's date: {$today}

User's accounts:
{$accountList}

Expense categories: {$expenseNames}
Income categories: {$incomeNames}
{$ruleHints}

Return exactly this JSON shape:
{
  "type": "expense|income|transfer",
  "amount": <positive number>,
  "currency": "<3-letter code, match the account's currency or default to the first account's currency>",
  "account_name": "<best match from accounts list>",
  "to_account_name": "<only for transfer type, best match from accounts list, otherwise null>",
  "category_name": "<best match from category list, null for transfers>",
  "merchant": "<merchant or payee name if identifiable, otherwise null>",
  "description": "<brief description, null if none>",
  "occurred_at": "<ISO 8601 datetime, today if not specified>",
  "confidence": <0.0 to 1.0>
}

Rules:
- If the message mentions a transfer between accounts, set type to "transfer"
- Pick the account that best matches; if unclear, use the first account
- confidence < 0.7 means you are not sure about key fields
PROMPT;
    }

    // ── API call ─────────────────────────────────────────────────────────────

    private function callApi(string $systemPrompt, string $userText): ?string
    {
        try {
            $client = OpenAI::factory()
                ->withApiKey(config('services.deepseek.api_key'))
                ->withBaseUri(config('services.deepseek.base_url'))
                ->make();

            $response = $client->chat()->create([
                'model'      => config('services.deepseek.model'),
                'messages'   => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userText],
                ],
                'max_tokens' => 512,
            ]);

            return $response->choices[0]->message->content ?? null;

        } catch (\Throwable $e) {
            Log::error('AI parsing API error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ── JSON extraction ──────────────────────────────────────────────────────

    private function extractJson(string $raw): ?array
    {
        // Strip reasoning tags that deepseek-reasoner may include
        $cleaned = preg_replace('/<think>.*?<\/think>/s', '', $raw);
        $cleaned = trim($cleaned ?? $raw);

        // Try direct decode first
        $decoded = json_decode($cleaned, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Extract first {...} block
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)?\}/s', $cleaned, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    // ── Result assembly ──────────────────────────────────────────────────────

    private function buildResult(
        User $user,
        array $data,
        \Illuminate\Database\Eloquent\Collection $accounts,
        \Illuminate\Database\Eloquent\Collection $categories,
    ): AiParseResult {
        $type = in_array($data['type'] ?? '', ['income', 'expense', 'transfer'])
            ? $data['type']
            : 'expense';

        [$account, $accountUnmatched] = $this->resolveAccount($accounts, $data['account_name'] ?? '');
        [$category, $categoryUnmatched] = $type !== 'transfer'
            ? $this->resolveCategory($categories, $data['category_name'] ?? '', $type)
            : [null, false];

        $toAccount = null;
        if ($type === 'transfer' && !empty($data['to_account_name'])) {
            [$toAccount] = $this->resolveAccount($accounts, $data['to_account_name']);
        }

        $occurredAt = $this->parseDate($data['occurred_at'] ?? null);

        $categoryName = $category
            ? ($category->icon ? $category->icon . ' ' : '') . $category->name
            : ($data['category_name'] ?? null);

        return new AiParseResult(
            type:              $type,
            amount:            abs((float) ($data['amount'] ?? 0)),
            currency:          strtoupper($data['currency'] ?? $account?->currency ?? 'USD'),
            accountId:         $account?->id,
            accountName:       $account?->name ?? ($data['account_name'] ?? ''),
            categoryId:        $category?->id,
            categoryName:      $categoryName,
            merchant:          !empty($data['merchant']) ? $data['merchant'] : null,
            description:       !empty($data['description']) ? $data['description'] : null,
            occurredAt:        $occurredAt,
            confidence:        (float) ($data['confidence'] ?? 0.8),
            accountUnmatched:  $accountUnmatched,
            categoryUnmatched: $categoryUnmatched,
            toAccountId:       $toAccount?->id,
            toAccountName:     $toAccount?->name,
        );
    }

    // ── Fuzzy matching ───────────────────────────────────────────────────────

    private function resolveAccount(
        \Illuminate\Database\Eloquent\Collection $accounts,
        string $name,
    ): array {
        if ($accounts->isEmpty()) {
            return [null, true];
        }

        $match = $this->fuzzyFind($name, $accounts, fn (Account $a) => $a->name);

        return $match
            ? [$match, false]
            : [$accounts->first(), true];
    }

    private function resolveCategory(
        \Illuminate\Database\Eloquent\Collection $categories,
        string $name,
        string $type,
    ): array {
        $typed = $categories->where('type', $type);

        if (empty($name) || $typed->isEmpty()) {
            return [null, !empty($name)];
        }

        $match = $this->fuzzyFind($name, $typed, fn (Category $c) => $c->name);

        return $match
            ? [$match, false]
            : [null, true];
    }

    private function fuzzyFind(string $needle, iterable $items, callable $nameOf): mixed
    {
        $needle = strtolower(trim($needle));

        if ($needle === '') {
            return null;
        }

        $best        = null;
        $bestPercent = 0;

        foreach ($items as $item) {
            $hayName = strtolower($nameOf($item));

            // Exact or contains wins immediately
            if ($hayName === $needle || str_contains($hayName, $needle) || str_contains($needle, $hayName)) {
                return $item;
            }

            similar_text($needle, $hayName, $percent);
            if ($percent > $bestPercent) {
                $bestPercent = $percent;
                $best        = $item;
            }
        }

        return $bestPercent >= self::FUZZY_THRESHOLD ? $best : null;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function matchingRules(User $user, string $text): \Illuminate\Database\Eloquent\Collection
    {
        return $user->categorizationRules()->with('category')
            ->get()
            ->filter(fn (CategorizationRule $r) =>
                stripos($text, $r->merchant_or_keyword) !== false
            );
    }

    private function parseDate(?string $value): string
    {
        if (!$value) {
            return now()->toIso8601String();
        }

        try {
            return \Carbon\Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return now()->toIso8601String();
        }
    }
}
