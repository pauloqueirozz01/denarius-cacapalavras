# Domínio

## Entidades

- `User`: participante ou administrador, com papel representado por `UserRole`.
- `FinancialTerm`: termo exibido, versão normalizada, descrição, dificuldade e estado ativo.
- `GameSession`: tentativa de um participante, grid imutável, estado, tempos e totais derivados.
- `GameSessionWord`: palavra sorteada com posicionamento e eventual acerto.

## Invariantes

- Todo cadastro público nasce como `participant`.
- Somente `admin` pode acessar o painel Filament; e-mail não concede privilégio.
- Papel não é mass-assignable e não pode ser escolhido no cadastro.
- Senhas usam o cast `hashed` do Laravel e não são serializadas.
- Uma sessão pertence ao usuário autenticado.
- Uma seleção só é válida se corresponder exatamente a uma palavra ainda não encontrada da sessão.
- O cliente nunca define pontuação, status, duração ou contadores.
- Palavras cabem no grid, usam uma das oito direções e só cruzam letras compatíveis.
- Normalização remove acentos, espaços e sinais do grid, preservando o termo de exibição.
- Ranking: maior pontuação, menor duração e conclusão mais antiga.

## Estados planejados

`active -> completed` ou `active -> abandoned`; estados finais não recebem novos acertos.
