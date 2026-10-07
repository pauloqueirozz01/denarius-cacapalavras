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
- Cada palavra gera pontos somente no primeiro acerto; repetição, seleção inválida e sessão final não alteram o score.
- O bônus de conclusão e o bônus de velocidade são aplicados somente na transição única para `COMPLETED`.
- Sessões `ABANDONED` preservam os pontos das palavras já encontradas e nunca recebem bônus finais.
- O score nunca é negativo nem pode diminuir, é persistido e sobrevive ao reload.
- A regra usada por uma partida pertence ao seu snapshot e não muda com alterações posteriores da configuração global.
- Somente usuários `participant` com ao menos uma sessão `COMPLETED` são elegíveis ao ranking.
- Cada usuário aparece uma única vez, pela sua melhor sessão concluída, e `score` é lido do valor persistido.
- A ordenação da melhor partida e do ranking global é: score decrescente, duração crescente, `finished_at` mais antigo e ID de sessão crescente.
- Para registros concluídos legados com duração ou término nulos, valores conhecidos vêm primeiro; o ID final mantém ordem total determinística.
- A posição individual representa sempre a melhor partida concluída, mesmo quando o resultado recém-finalizado não substitui o recorde.
- Sessões `ACTIVE` e `ABANDONED` nunca entram no ranking, mesmo que tenham score.
- Ler/atualizar ranking por polling não altera sessões nem cria partidas.
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

## Contrato da interface

- O usuário inicia ou retoma a própria sessão; a interface nunca seleciona uma sessão por ID recebido do cliente.
- A seleção pública contém somente `start_row`, `start_column`, `end_row` e `end_column`.
- Trajetórias irregulares podem ser recusadas visualmente, mas toda coordenada recebida ainda é validada no backend.
- O grid exibido é o snapshot persistido; alterações posteriores no catálogo não modificam a partida.
- Termos são exibidos com `original_term`; `normalized_term` continua sendo a representação do grid.
- O cronômetro visual pode avançar localmente, mas reload usa `started_at` e conclusão usa `duration_seconds` oficial.
- Somente placements já encontrados podem gerar destaques permanentes no HTML; soluções pendentes não são expostas.
- A interface apenas renderiza `GameSession.score` e o detalhamento devolvido pela action; nenhuma fórmula existe em Blade ou JavaScript.
- Após concluir, o participante vê score, duração oficial, detalhamento reconstruído do snapshot histórico quando consistente, melhor posição e links para ranking e nova partida.
- `/ranking` exige autenticação; o quadro periódico divulga somente nome, posição, score e duração, nunca email ou dados internos da sessão.

## Fórmula de pontuação

- Palavra encontrada: 100 pontos.
- Conclusão da partida: 500 pontos.
- Conclusão em até 120 segundos: 500 pontos de velocidade.
- Conclusão entre 121 e 180 segundos: 300 pontos de velocidade.
- Conclusão entre 181 e 300 segundos: 150 pontos de velocidade.
- Acima de 300 segundos: nenhum bônus de velocidade.
- Máximo padrão: `total_words * 100 + 1.000`; com 10 palavras, 2.000 pontos.

## Dificuldade dos termos

- `easy`: termos curtos e comuns para iniciantes.
- `medium`: conceitos intermediários ou palavras mais longas.
- `hard`: conceitos técnicos ou menos familiares.
