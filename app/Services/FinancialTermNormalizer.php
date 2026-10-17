<?php

namespace App\Services;

use Illuminate\Support\Str;

class FinancialTermNormalizer
{
    public const MAX_LENGTH = 24;

    public static function normalize(string $term): string
    {
        $asciiTerm = Str::ascii(Str::squish($term), 'pt');
        $normalizedTerm = preg_replace('/[^A-Z]/', '', Str::upper($asciiTerm));

        return $normalizedTerm ?? '';
    }
}
