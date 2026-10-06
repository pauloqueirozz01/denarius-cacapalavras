<?php

namespace Database\Seeders;

use App\Enums\FinancialTermDifficulty as Difficulty;
use App\Models\FinancialTerm;
use App\Services\FinancialTermNormalizer;
use Illuminate\Database\Seeder;

class FinancialTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->terms() as [$term, $description, $difficulty]) {
            FinancialTerm::query()->firstOrCreate(
                ['normalized_term' => FinancialTermNormalizer::normalize($term)],
                [
                    'term' => $term,
                    'description' => $description,
                    'difficulty' => $difficulty,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return list<array{string, string, Difficulty}>
     */
    private function terms(): array
    {
        return [
            ['Ação', 'Título que representa uma pequena parcela do capital de uma empresa.', Difficulty::Easy],
            ['Bolsa', 'Ambiente organizado onde investimentos são negociados.', Difficulty::Easy],
            ['Juros', 'Valor pago ou recebido pelo uso do dinheiro ao longo do tempo.', Difficulty::Easy],
            ['Crédito', 'Recurso disponibilizado com o compromisso de pagamento futuro.', Difficulty::Easy],
            ['Débito', 'Valor retirado de uma conta ou obrigação que precisa ser paga.', Difficulty::Easy],
            ['Inflação', 'Aumento generalizado dos preços que reduz o poder de compra.', Difficulty::Medium],
            ['Tesouro', 'Conjunto de recursos e títulos administrados pelo governo.', Difficulty::Easy],
            ['Dividendos', 'Parte do lucro de uma empresa distribuída aos acionistas.', Difficulty::Medium],
            ['Investimento', 'Aplicação de recursos com expectativa de retorno futuro.', Difficulty::Easy],
            ['Liquidez', 'Facilidade de transformar um ativo em dinheiro sem perda relevante.', Difficulty::Medium],
            ['Renda', 'Dinheiro recebido por trabalho, negócio ou investimento.', Difficulty::Easy],
            ['Capital', 'Recursos disponíveis para investir, produzir ou manter um negócio.', Difficulty::Easy],
            ['Lucro', 'Resultado positivo após descontar os custos das receitas.', Difficulty::Easy],
            ['Risco', 'Possibilidade de o resultado real ser diferente do esperado.', Difficulty::Easy],
            ['Fundo', 'Investimento coletivo administrado por um gestor profissional.', Difficulty::Easy],
            ['Poupança', 'Reserva de dinheiro para objetivos ou necessidades futuras.', Difficulty::Easy],
            ['Câmbio', 'Troca de uma moeda por outra conforme uma taxa de conversão.', Difficulty::Medium],
            ['Carteira', 'Conjunto de investimentos pertencentes a uma pessoa.', Difficulty::Easy],
            ['Ativo', 'Bem ou direito com valor econômico que pode gerar benefício.', Difficulty::Easy],
            ['Passivo', 'Obrigação financeira que deverá ser paga no futuro.', Difficulty::Easy],
            ['Selic', 'Taxa básica de juros da economia brasileira.', Difficulty::Medium],
            ['IPCA', 'Índice oficial usado para acompanhar a inflação no Brasil.', Difficulty::Medium],
            ['Pix', 'Meio brasileiro de pagamento e transferência instantânea.', Difficulty::Easy],
            ['Financiamento', 'Crédito destinado à compra de um bem ou serviço específico.', Difficulty::Medium],
            ['Empréstimo', 'Dinheiro recebido para devolução futura com condições acordadas.', Difficulty::Easy],
            ['Patrimônio', 'Conjunto de bens, direitos e obrigações de uma pessoa.', Difficulty::Medium],
            ['Receita', 'Entrada de dinheiro obtida por trabalho, vendas ou investimentos.', Difficulty::Easy],
            ['Despesa', 'Saída de dinheiro para pagar consumo ou obrigação.', Difficulty::Easy],
            ['Orçamento', 'Plano que organiza receitas e despesas de um período.', Difficulty::Easy],
            ['Economia', 'Administração de recursos escassos para atender necessidades.', Difficulty::Easy],
            ['Reserva', 'Dinheiro separado para objetivos ou situações inesperadas.', Difficulty::Easy],
            ['Rentabilidade', 'Percentual que indica quanto um investimento ganhou ou perdeu.', Difficulty::Medium],
            ['Volatilidade', 'Intensidade das variações de preço de um investimento.', Difficulty::Hard],
            ['Renda Fixa', 'Investimento com regras de remuneração definidas previamente.', Difficulty::Medium],
            ['Renda Variável', 'Investimento cujo retorno pode variar com o mercado.', Difficulty::Medium],
            ['Tesouro Direto', 'Programa para pessoas físicas comprarem títulos públicos.', Difficulty::Medium],
            ['CDB', 'Título emitido por banco para captar dinheiro de investidores.', Difficulty::Medium],
            ['LCI', 'Título bancário ligado ao financiamento do setor imobiliário.', Difficulty::Hard],
            ['LCA', 'Título bancário ligado ao financiamento do agronegócio.', Difficulty::Hard],
            ['ETF', 'Fundo negociado em bolsa que acompanha uma carteira de ativos.', Difficulty::Hard],
            ['FII', 'Fundo que reúne recursos para investir no mercado imobiliário.', Difficulty::Hard],
            ['Aporte', 'Valor adicionado a um investimento.', Difficulty::Easy],
            ['Corretora', 'Instituição que intermedeia a compra e venda de investimentos.', Difficulty::Medium],
            ['Banco', 'Instituição que oferece serviços de pagamento, crédito e guarda.', Difficulty::Easy],
            ['Tarifa', 'Valor cobrado pela prestação de um serviço.', Difficulty::Easy],
            ['Boleto', 'Documento usado para realizar pagamentos até uma data definida.', Difficulty::Easy],
            ['Cartão', 'Meio de pagamento que pode usar débito ou crédito.', Difficulty::Easy],
            ['Saldo', 'Valor disponível em uma conta em determinado momento.', Difficulty::Easy],
            ['Parcelamento', 'Divisão de um pagamento em várias prestações.', Difficulty::Medium],
            ['Inadimplência', 'Situação de quem não paga uma obrigação no prazo.', Difficulty::Hard],
            ['Amortização', 'Redução gradual do saldo de uma dívida por pagamentos.', Difficulty::Hard],
            ['Taxa', 'Percentual ou valor cobrado em uma operação financeira.', Difficulty::Easy],
            ['Imposto', 'Valor obrigatório cobrado pelo governo para financiar serviços.', Difficulty::Easy],
            ['Tributo', 'Pagamento obrigatório ao poder público previsto em lei.', Difficulty::Medium],
            ['Diversificação', 'Distribuição do dinheiro entre ativos para reduzir riscos.', Difficulty::Hard],
            ['Deflação', 'Queda generalizada dos preços durante um período.', Difficulty::Medium],
            ['Custo', 'Valor necessário para adquirir ou produzir algo.', Difficulty::Easy],
            ['Consumo', 'Uso de bens e serviços para atender necessidades.', Difficulty::Easy],
            ['Poupador', 'Pessoa que guarda parte da renda para o futuro.', Difficulty::Easy],
            ['Investidor', 'Pessoa que aplica recursos buscando retorno.', Difficulty::Easy],
            ['Juro Composto', 'Juro calculado sobre o valor inicial e os juros acumulados.', Difficulty::Medium],
            ['Juro Simples', 'Juro calculado somente sobre o valor inicial.', Difficulty::Medium],
            ['Endividamento', 'Acúmulo de compromissos financeiros a pagar.', Difficulty::Medium],
            ['Patrimônio Líquido', 'Diferença entre tudo o que se possui e tudo o que se deve.', Difficulty::Hard],
            ['Fluxo de Caixa', 'Registro das entradas e saídas de dinheiro em um período.', Difficulty::Medium],
            ['Capital de Giro', 'Recursos usados para manter as operações diárias de um negócio.', Difficulty::Hard],
            ['CDI', 'Taxa de referência comum em investimentos de renda fixa.', Difficulty::Medium],
            ['IOF', 'Imposto aplicado a determinadas operações financeiras.', Difficulty::Medium],
            ['Spread', 'Diferença entre taxas de compra e venda ou captação e empréstimo.', Difficulty::Hard],
            ['Câmbio Flutuante', 'Regime em que a cotação da moeda varia conforme o mercado.', Difficulty::Hard],
            ['Previdência', 'Planejamento financeiro voltado à renda no futuro.', Difficulty::Medium],
            ['Seguro', 'Proteção financeira contratada contra riscos definidos.', Difficulty::Easy],
            ['Franquia', 'Parte do prejuízo que fica sob responsabilidade do segurado.', Difficulty::Medium],
            ['Sinistro', 'Evento coberto por uma apólice de seguro.', Difficulty::Medium],
            ['Consórcio', 'Grupo que contribui para compras planejadas por contemplação.', Difficulty::Medium],
            ['Garantia', 'Bem ou compromisso que reduz o risco de uma operação.', Difficulty::Easy],
            ['Hipoteca', 'Garantia de dívida vinculada a um imóvel.', Difficulty::Hard],
            ['Cheque Especial', 'Crédito automático ligado ao saldo da conta corrente.', Difficulty::Medium],
            ['Score de Crédito', 'Indicador que estima a chance de pagamento de compromissos.', Difficulty::Medium],
            ['Limite', 'Valor máximo disponibilizado para uma operação de crédito.', Difficulty::Easy],
            ['Cashback', 'Devolução de parte do valor gasto em uma compra.', Difficulty::Easy],
            ['Transferência', 'Movimentação de dinheiro entre contas.', Difficulty::Easy],
            ['TED', 'Transferência bancária tradicional entre instituições.', Difficulty::Medium],
            ['DOC', 'Modalidade tradicional de transferência bancária.', Difficulty::Medium],
            ['Open Finance', 'Sistema de compartilhamento autorizado de dados financeiros.', Difficulty::Hard],
            ['Conta Corrente', 'Conta bancária usada para movimentações do dia a dia.', Difficulty::Easy],
            ['Conta Poupança', 'Conta voltada à guarda de dinheiro com rendimento.', Difficulty::Easy],
            ['Fundo Imobiliário', 'Fundo que investe em imóveis ou ativos do setor.', Difficulty::Hard],
            ['Fundo de Emergência', 'Reserva para despesas urgentes e imprevistas.', Difficulty::Medium],
            ['Poder de Compra', 'Quantidade de bens e serviços que o dinheiro pode adquirir.', Difficulty::Medium],
        ];
    }
}
