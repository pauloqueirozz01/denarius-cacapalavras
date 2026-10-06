# Domínio

## Entidades

- `User`: participante ou administrador.
- `FinancialTerm`: termo exibido, versão normalizada, descrição, dificuldade e estado ativo.
- `GameSession`: tentativa de um participante, grid imutável, estado, tempos e totais derivados.
- `GameSessionWord`: palavra sorteada com posicionamento e eventual acerto.

## Invariantes

- Uma sessão pertence ao usuário autenticado.
- Uma seleção só é válida se corresponder exatamente a uma palavra ainda não encontrada da sessão.
- O cliente nunca define pontuação, status, duração ou contadores.
- Palavras cabem no grid, usam uma das oito direções e só cruzam letras compatíveis.
- Normalização remove acentos, espaços e sinais do grid, preservando o termo de exibição.
- Ranking: maior pontuação, menor duração e conclusão mais antiga.

## Estados planejados

`active -> completed` ou `active -> abandoned`; estados finais não recebem novos acertos.
