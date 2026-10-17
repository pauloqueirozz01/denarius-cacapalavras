<?php

namespace Database\Factories;

use App\Enums\FinancialTermDifficulty;
use App\Models\FinancialTerm;
use App\Services\FinancialTermNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialTerm>
 */
class FinancialTermFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterMaking(function (FinancialTerm $financialTerm): void {
            $financialTerm->normalized_term = FinancialTermNormalizer::normalize($financialTerm->term);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $terms = [
            'Ação' => 'Parcela do capital de uma empresa negociada no mercado.',
            'Aporte' => 'Valor adicionado a um investimento.',
            'Bolsa' => 'Ambiente organizado para negociação de investimentos.',
            'Capital' => 'Recursos disponíveis para investir ou produzir.',
            'Carteira' => 'Conjunto de investimentos de uma pessoa.',
            'Crédito' => 'Valor disponibilizado com compromisso de pagamento futuro.',
            'Despesa' => 'Saída de dinheiro para pagar uma obrigação ou consumo.',
            'Dividendo' => 'Parte do lucro distribuída aos acionistas.',
            'Inflação' => 'Aumento generalizado dos preços ao longo do tempo.',
            'Investimento' => 'Aplicação de recursos buscando retorno futuro.',
            'Juro' => 'Preço pago pelo uso do dinheiro ao longo do tempo.',
            'Liquidez' => 'Facilidade de transformar um ativo em dinheiro.',
            'Orçamento' => 'Plano de receitas e despesas para um período.',
            'Patrimônio' => 'Conjunto de bens, direitos e obrigações.',
            'Poupança' => 'Reserva de dinheiro para objetivos futuros.',
            'Receita' => 'Dinheiro recebido por uma pessoa ou organização.',
            'Renda Fixa' => 'Investimento com regras de remuneração conhecidas.',
            'Risco' => 'Possibilidade de o resultado ser diferente do esperado.',
            'Saldo' => 'Valor disponível em uma conta.',
            'Tesouro Direto' => 'Programa de compra de títulos públicos por pessoas físicas.',
        ];
        $term = fake()->randomElement(array_keys($terms));

        return [
            'term' => $term,
            'normalized_term' => FinancialTermNormalizer::normalize($term),
            'description' => $terms[$term],
            'difficulty' => fake()->randomElement(FinancialTermDifficulty::cases()),
            'is_active' => fake()->boolean(85),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
