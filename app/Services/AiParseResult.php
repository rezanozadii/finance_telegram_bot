<?php

namespace App\Services;

readonly class AiParseResult
{
    public function __construct(
        public string  $type,
        public float   $amount,
        public string  $currency,
        public ?int    $accountId,
        public string  $accountName,
        public ?int    $categoryId,
        public ?string $categoryName,
        public ?string $merchant,
        public ?string $description,
        public string  $occurredAt,
        public float   $confidence,
        public bool    $accountUnmatched  = false,
        public bool    $categoryUnmatched = false,
        public ?int    $toAccountId       = null,
        public ?string $toAccountName     = null,
    ) {}

    public function toTransactionData(string $rawInputText): array
    {
        return [
            'type'           => $this->type,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'account_id'     => $this->accountId,
            'category_id'    => $this->categoryId,
            'merchant'       => $this->merchant,
            'description'    => $this->description,
            'occurred_at'    => $this->occurredAt,
            'to_account_id'  => $this->toAccountId,
            'source'         => 'ai_parsed',
            'raw_input_text' => $rawInputText,
        ];
    }

    /** Serialise to cache-storable array (for conversation state). */
    public function toArray(): array
    {
        return [
            'type'              => $this->type,
            'amount'            => $this->amount,
            'currency'          => $this->currency,
            'account_id'        => $this->accountId,
            'account_name'      => $this->accountName,
            'category_id'       => $this->categoryId,
            'category_name'     => $this->categoryName,
            'merchant'          => $this->merchant,
            'description'       => $this->description,
            'occurred_at'       => $this->occurredAt,
            'confidence'        => $this->confidence,
            'account_unmatched' => $this->accountUnmatched,
            'category_unmatched'=> $this->categoryUnmatched,
            'to_account_id'     => $this->toAccountId,
            'to_account_name'   => $this->toAccountName,
        ];
    }

    public static function fromArray(array $a): self
    {
        return new self(
            type:              $a['type'],
            amount:            (float) $a['amount'],
            currency:          $a['currency'],
            accountId:         $a['account_id'],
            accountName:       $a['account_name'],
            categoryId:        $a['category_id'] ?? null,
            categoryName:      $a['category_name'] ?? null,
            merchant:          $a['merchant'] ?? null,
            description:       $a['description'] ?? null,
            occurredAt:        $a['occurred_at'],
            confidence:        (float) $a['confidence'],
            accountUnmatched:  $a['account_unmatched'] ?? false,
            categoryUnmatched: $a['category_unmatched'] ?? false,
            toAccountId:       $a['to_account_id'] ?? null,
            toAccountName:     $a['to_account_name'] ?? null,
        );
    }
}
