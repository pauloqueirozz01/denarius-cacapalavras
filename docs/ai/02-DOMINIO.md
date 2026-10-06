# Domínio

## Entidades

- `User`: participante ou administrador, com papel representado por `UserRole`.
- `FinancialTerm`: termo exibido, versão normalizada, descrição educativa, dificuldade e estado ativo.
- `GameSession`: tentativa de um participante, grid imutável, estado, tempos e totais derivados.
- `GameSessionWord`: palavra sorteada com posicionamento e eventual acerto.

## Invariantes

- Todo cadastro público nasce como `participant`.
- Somente `admin` pode acessar o painel Filament; e-mail não concede privilégio.
- Papel não é mass-assignable e não pode ser escolhido no cadastro.
- Senhas usam o cast `hashed` do Laravel e não são serializadas.
- A forma normalizada de um termo é automática, contém somente `A-Z`, possui no máximo 24 letras e é única.
- Termos equivalentes após remoção de acentos, espaços e símbolos não podem coexistir.
- Somente administradores gerenciam o catálogo; participantes não recebem uma listagem pública.
- Uma sessão pertence ao usuário autenticado.
- Uma seleção só é válida se corresponder exatamente a uma palavra ainda não encontrada da sessão.
- O cliente nunca define pontuação, status, duração ou contadores.
- Palavras cabem no grid, usam uma das oito direções e só cruzam letras compatíveis.
- Normalização remove acentos, espaços e sinais do grid, preservando o termo de exibição.
- Cada placement contém termo original e normalizado, coordenadas inicial/final e direção; sua leitura no grid deve reconstruir exatamente a palavra normalizada.
- A geração normal considera somente termos ativos, únicos após normalização e compatíveis com as dimensões solicitadas.
- Nenhuma célula do grid final fica vazia; espaços restantes recebem uma letra entre `A` e `Z`.
- Uma falha de posicionamento nunca retorna um resultado parcial e encerra após um número configurado de tentativas.
- Ranking: maior pontuação, menor duração e conclusão mais antiga.

## Direções do grid

- `RIGHT`: `(row + 0, column + 1)`.
- `LEFT`: `(row + 0, column - 1)`.
- `DOWN`: `(row + 1, column + 0)`.
- `UP`: `(row - 1, column + 0)`.
- `DOWN_RIGHT`: `(row + 1, column + 1)`.
- `DOWN_LEFT`: `(row + 1, column - 1)`.
- `UP_RIGHT`: `(row - 1, column + 1)`.
- `UP_LEFT`: `(row - 1, column - 1)`.

## Estados planejados

`active -> completed` ou `active -> abandoned`; estados finais não recebem novos acertos.

## Dificuldade dos termos

- `easy`: termos curtos e comuns para iniciantes.
- `medium`: conceitos intermediários ou palavras mais longas.
- `hard`: conceitos técnicos ou menos familiares.
