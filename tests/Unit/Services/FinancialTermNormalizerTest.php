<?php

namespace Tests\Unit\Services;

use App\Services\FinancialTermNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FinancialTermNormalizerTest extends TestCase
{
    #[DataProvider('normalizationCases')]
    public function test_terms_are_normalized_for_the_word_search(string $term, string $expected): void
    {
        $this->assertSame($expected, FinancialTermNormalizer::normalize($term));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function normalizationCases(): array
    {
        return [
            'ação' => ['Ação', 'ACAO'],
            'crédito' => ['Crédito', 'CREDITO'],
            'câmbio' => ['Câmbio', 'CAMBIO'],
            'renda fixa' => ['Renda Fixa', 'RENDAFIXA'],
            'tesouro direto' => ['Tesouro Direto', 'TESOURODIRETO'],
            'juro composto' => ['Juro Composto', 'JUROCOMPOSTO'],
            'espaços extras e lowercase' => ['  renda    variável  ', 'RENDAVARIAVEL'],
            'uppercase' => ['LIQUIDEZ', 'LIQUIDEZ'],
            'hífen, símbolos e números' => ['custo-benefício! @2026', 'CUSTOBENEFICIO'],
        ];
    }

    public function test_normalized_value_contains_only_ascii_letters(): void
    {
        $normalizedTerm = FinancialTermNormalizer::normalize('Crédito & Ação — 100%');

        $this->assertSame('CREDITOACAO', $normalizedTerm);
        $this->assertMatchesRegularExpression('/\A[A-Z]+\z/', $normalizedTerm);
    }
}
