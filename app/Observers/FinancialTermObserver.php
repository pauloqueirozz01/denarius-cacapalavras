<?php

namespace App\Observers;

use App\Models\FinancialTerm;
use App\Services\FinancialTermNormalizer;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinancialTermObserver
{
    /**
     * @throws ValidationException
     */
    public function saving(FinancialTerm $financialTerm): void
    {
        $financialTerm->term = Str::squish((string) $financialTerm->term);
        $normalizedTerm = FinancialTermNormalizer::normalize($financialTerm->term);

        if ($normalizedTerm === '') {
            throw ValidationException::withMessages([
                'term' => 'O termo deve conter ao menos uma letra de A a Z.',
            ]);
        }

        if (strlen($normalizedTerm) > FinancialTermNormalizer::MAX_LENGTH) {
            throw ValidationException::withMessages([
                'term' => 'O termo normalizado deve possuir no máximo 24 letras.',
            ]);
        }

        $financialTerm->normalized_term = $normalizedTerm;
    }
}
