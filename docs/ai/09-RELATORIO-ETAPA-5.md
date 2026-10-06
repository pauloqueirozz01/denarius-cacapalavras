# Relatório da Etapa 5 — GameSession, snapshots e regras transacionais

## 1. O que foi implementado

Foi implementada a fundação persistente de partidas da Denarius. O resultado do gerador agora pode originar uma `GameSession` atômica e auditável, com snapshot do grid, configuração, palavras e placements.

O backend inicia partidas, valida seleções por coordenadas, mantém acertos idempotentes, conclui automaticamente a última palavra e permite abandono explícito. O cliente não controla estado, contadores, palavras, grid, timestamps ou duração.

## 2. Arquitetura da solução

```text
User
  -> StartGameSessionAction
  -> WordSearchGeneratorService
  -> GameSessionSnapshotValidator
  -> DB transaction + user lock
  -> GameSession + GameSessionWords

Coordinates
  -> FindGameSessionWordAction
  -> authorization
  -> DB transaction + session/word locks
  -> idempotent update
  -> automatic completion
```

Actions coordenam casos de uso, o validador protege a fronteira entre DTO e persistência, modelos concentram invariantes locais e policies definem propriedade. Não foram criados controller, componente Livewire ou endpoint nesta etapa.

## 3. GameSession

`GameSession` armazena proprietário, estado, grid JSON, dimensões, totais, configuração da geração e tempos controlados pelo servidor. Seus casts cobrem arrays, inteiros, enum e datetimes imutáveis.

O modelo é totalmente protegido contra mass assignment. Após criação, proprietário, grid, dimensões, total, configuração e início são imutáveis. Uma sessão em estado final não pode ser salva novamente por Eloquent.

## 4. GameSessionWord

Cada registro preserva:

- referência opcional ao `FinancialTerm`;
- termo original e normalizado;
- coordenadas inicial e final;
- direção tipada por `WordDirection`;
- estado e horário do acerto.

Placement e snapshot textual são imutáveis. Uma palavra encontrada não pode ser revertida nem contabilizada novamente.

## 5. Migrations

Foram criadas migrations aditivas para `game_sessions` e `game_session_words`. Ambas foram executadas com sucesso no MySQL 8.4, no lote 3, sem alterar migrations consolidadas.

O grid e a configuração utilizam JSON nativo. Status e direção usam strings estáveis compatíveis com enums PHP. Coordenadas e contadores usam inteiros sem sinal.

## 6. Snapshot das partidas

O `WordSearchResult` é validado antes de abrir a transação. O validador confirma dimensões, células `A-Z`, termos e placements correspondentes, unicidade normalizada, coordenadas, direção e reconstrução física de cada palavra no grid.

O grid é persistido uma única vez e nunca regenerado durante a partida. `generation_config` registra linhas, colunas e quantidade de palavras. A seed aleatória de produção não é persistida: o snapshot integral já é a fonte histórica autoritativa e não depende da capacidade de reproduzir o gerador.

## 7. Máquina de estados

```text
ACTIVE
 ├──> COMPLETED
 └──> ABANDONED
```

Transições de estados finais, retorno a `ACTIVE` e mudança entre `COMPLETED`/`ABANDONED` são rejeitados. Conclusão exige que `found_words_count` seja igual a `total_words`.

## 8. Criação de partida

`StartGameSessionAction`:

1. autoriza o usuário;
2. rejeita rapidamente uma partida ativa existente;
3. gera e valida o snapshot;
4. abre transação com até três tentativas para deadlock;
5. bloqueia a linha do usuário e revalida a ausência de sessão ativa;
6. cria a sessão com `started_at` do servidor;
7. insere todas as palavras em lote;
8. retorna a sessão com seus snapshots.

Se qualquer palavra falhar, a transação remove também a sessão. A regra do MVP é rejeitar uma segunda partida ativa, sem abandono silencioso.

## 9. Validação de palavras

`FindGameSessionWordAction` recebe somente início e fim. A action valida propriedade, estado ativo e limites, encontra um placement exato e aceita também a seleção invertida.

Seleções inexistentes ou fora do grid não alteram o banco. O texto da palavra e qualquer indicação de acerto enviados pelo cliente não participam da decisão.

## 10. Idempotência

Se a palavra já estiver encontrada, a action retorna o mesmo registro com `wasNewlyFound = false`, sem mudar `found_at` ou o contador. Testes executam a segunda chamada com uma instância deliberadamente desatualizada da sessão para comprovar a reconsulta dentro da transação.

## 11. Concorrência e transações

- Criação: lock da linha do usuário seguido de nova consulta por sessão ativa.
- Acerto: lock da sessão antes de estado/contagem e lock da palavra correspondente.
- Contador: recalculado a partir das linhas `is_found = true`, nunca incrementado a partir do cliente.
- Conclusão: acerto, contador, status, término e duração são persistidos na mesma transação.
- Deadlocks: as três actions transacionais permitem até três tentativas pelo Laravel.

O ambiente PHPUnit usa SQLite em memória e não reproduz concorrência paralela real do InnoDB. A proteção foi exercitada por reconsulta/idempotência sequencial, e as migrations foram validadas separadamente no MySQL 8.4.

## 12. Antitrapaça

O servidor controla grid, placements, palavra encontrada, contador, status e relógio. `started_at`, `found_at` e `finished_at` vêm de `now()`. `duration_seconds` é a diferença calculada entre início e término, nunca uma entrada do usuário.

A partida concluída ou abandonada rejeita novos acertos. O frontend futuro deverá enviar somente coordenadas da interação.

## 13. Authorization

`GameSessionPolicy` permite consultar, marcar palavra e abandonar somente ao proprietário. Usuários autenticados não podem ler ou mutar partidas alheias. Exclusão, restauração e exclusão permanente são negadas.

`viewAny` permite entrar no caso de uso de listagem; a consulta futura deve continuar escopada ao usuário autenticado. Nenhuma rota pública foi adicionada nesta etapa.

## 14. Banco de dados

### Migrations

- `2026_10_06_222726_create_game_sessions_table.php`
- `2026_10_06_222727_create_game_session_words_table.php`

### Models e factories

- `GameSession` com factory e states `active`, `completed` e `abandoned`.
- `GameSessionWord` com factory e states `pending` e `found`.
- Relacionamentos adicionados a `User` e `FinancialTerm`.

Nenhum seeder de partidas foi criado.

### Índices

- `game_sessions`: `(user_id, status)` e `(status, finished_at)`.
- `game_session_words`: `(game_session_id, is_found)` e unicidade de `(game_session_id, normalized_term)`.

### Foreign keys

- sessão para usuário: `restrictOnDelete`, preservando histórico;
- palavra para sessão: `cascadeOnDelete`, pois a palavra não existe sem o snapshot pai;
- palavra para termo: nullable + `nullOnDelete`, preservando o snapshot após remoção do catálogo.

## 15. Testes

A Etapa 5 adicionou 43 casos e 132 assertions. A suíte completa passou com:

```text
138 testes aprovados
1.062 assertions
0 falhas
```

Os testes cobrem criação, persistência exata do grid/placements, integração com o gerador real, snapshot histórico, rollback, uma sessão ativa, estados, casts, imutabilidade, mass assignment, seleção normal/reversa/inválida, idempotência, contador autoritativo, conclusão, abandono e autorização cross-user.

Os 95 testes e 930 assertions da Etapa 4 continuam passando.

## 16. Validações

- Laravel Pint: aprovado com `vendor/bin/pint --dirty --format agent`.
- Vite build: aprovado com `pnpm build`; somente o aviso informativo do pacote opcional `fontaine`.
- Composer audit: nenhuma vulnerabilidade conhecida.
- pnpm audit em nível high: nenhuma vulnerabilidade conhecida.
- MySQL 8.4: migrations aplicadas e status `Ran` no lote 3.
- Rotas: 11 rotas existentes; nenhuma rota pública de partida adicionada.
- `git diff --check`: aprovado.
- Análise estática: PHPStan/Larastan não está configurado no projeto.

## 17. Segurança

- Models usam proteção total de mass assignment.
- Todas as ações sensíveis autorizam explicitamente o usuário.
- Locks e transações protegem invariantes de concorrência.
- Exceções esperadas do domínio não poluem o relatório de erros.
- O DTO do gerador não é serializado cegamente.
- Nenhum segredo, `.env`, token ou credencial foi versionado.
- Rate limiting deverá ser configurado na Etapa 6 quando as actions ganharem uma superfície pública Livewire/HTTP.

## 18. Git

- Branch: `feat/game-sessions`.
- Commit funcional: `c93dbee feat: add game session domain and persistence`.
- Commit documental: `docs: add game session report`.
- Push: branch publicada em `origin/feat/game-sessions`.

## 19. Pendências conhecidas

- Não existe teste paralelo real de locking porque a suíte roda em SQLite em memória; o desenho transacional foi validado no código e as migrations no MySQL.
- A regra de uma sessão ativa depende do lock pessimista da linha do usuário; não há índice parcial portável no MySQL para essa condição.
- Ainda não existem endpoints ou componentes Livewire para consumir as actions.
- Pontuação definitiva, ranking e rate limiting das ações públicas pertencem às próximas etapas.

## 20. Próxima etapa

ETAPA 6 — Interface jogável desktop/mobile.

A interface deverá carregar o snapshot persistido e chamar as actions desta etapa, sem regenerar o tabuleiro nem duplicar validação no frontend.
