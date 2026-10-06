<?php

namespace App\Rules;

use App\Models\FinancialTerm;
use App\Services\FinancialTermNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniqueNormalizedFinancialTerm implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Informe um termo financeiro válido.');

            return;
        }

        $normalizedTerm = FinancialTermNormalizer::normalize($value);

        if ($normalizedTerm === '') {
            $fail('O termo deve conter ao menos uma letra de A a Z.');

            return;
        }

        if (strlen($normalizedTerm) > FinancialTermNormalizer::MAX_LENGTH) {
            $fail('O termo normalizado deve possuir no máximo 24 letras.');

            return;
        }

        $query = FinancialTerm::query()->where('normalized_term', $normalizedTerm);

        if ($this->ignoreId !== null) {
            $query->whereKeyNot($this->ignoreId);
        }

        if ($query->exists()) {
            $fail('Já existe um termo equivalente no catálogo.');
        }
    }
}
