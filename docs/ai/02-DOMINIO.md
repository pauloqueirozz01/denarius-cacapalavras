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
- Um usuário possui no máximo uma sessão `ACTIVE`; nova criação é rejeitada até conclusão ou abandono explícito.
- Uma seleção só é válida se suas coordenadas corresponderem exatamente a uma palavra da sessão, em qualquer dos dois sentidos.
- Repetir uma seleção já encontrada é uma operação idempotente e não altera o contador.
- O cliente nunca define pontuação, status, duração ou contadores.
- O cliente também não define grid, placements, palavras ou timestamps.
- `total_words` corresponde ao número de snapshots de palavras e `found_words_count` permanece entre zero e o total.
- A última palavra encontrada conclui automaticamente a sessão na mesma transação.
- Snapshots do grid, configuração e placements não mudam após a criação; sessões concluídas ou abandonadas são finais.
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

## Estados da partida

```text
ACTIVE
 ├──> COMPLETED
 └──> ABANDONED
```

- `ACTIVE -> COMPLETED`: automático quando todas as palavras são encontradas.
- `ACTIVE -> ABANDONED`: explícito pela action de abandono.
- `COMPLETED` e `ABANDONED` não retornam a `ACTIVE` nem transitam entre si.
- Estados finais não recebem novos acertos nem alterações de snapshot.
- `started_at`, `found_at`, `finished_at` e `duration_seconds` são definidos ou calculados no backend.

## Dificuldade dos termos

- `easy`: termos curtos e comuns para iniciantes.
- `medium`: conceitos intermediários ou palavras mais longas.
- `hard`: conceitos técnicos ou menos familiares.
