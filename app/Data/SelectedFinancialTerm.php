<?php

namespace App\Data;

use App\Models\FinancialTerm;
use App\Services\FinancialTermNormalizer;

readonly class SelectedFinancialTerm
{
    public function __construct(
        public ?int $financialTermId,
        public string $originalTerm,
        public string $normalizedTerm,
    ) {}

    public static function fromModel(FinancialTerm $financialTerm): self
    {
        return new self(
            financialTermId: $financialTerm->getKey() === null ? null : (int) $financialTerm->getKey(),
            originalTerm: (string) $financialTerm->term,
            normalizedTerm: FinancialTermNormalizer::normalize((string) $financialTerm->term),
        );
    }

    /**
     * @return array{financial_term_id: int|null, original_term: string, normalized_term: string}
     */
    public function toArray(): array
    {
        return [
            'financial_term_id' => $this->financialTermId,
            'original_term' => $this->originalTerm,
            'normalized_term' => $this->normalizedTerm,
        ];
    }
}
