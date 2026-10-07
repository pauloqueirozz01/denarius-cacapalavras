# Diário

## 2026-10-06 — Etapas 0 e 1

- Repositório vazio reconhecido; não havia Git, Laravel, arquivos ou skills locais.
- Ambiente inventariado e versões estáveis confirmadas em documentação oficial.
- Laravel 13 criado; dependências Livewire/Filament instaladas.
- Infraestrutura de banco local e documentação inicial adicionadas.
- Laravel Boost instalado conforme instrução do esqueleto Laravel 13.
- MySQL real migrado; testes, formatter, build, audits e resposta HTTP passaram.
- Próximo passo: implementar autenticação e autorização administrativa em commit separado.

## 2026-10-06 — Etapa 2

- Criados cadastro, login e logout com sessão nativa, CSRF e regeneração de identificador/token.
- Adicionado `UserRole`, papel participante padrão, factory states e migration aditiva com rollback validado.
- Filament instalado em `/admin` e protegido por `FilamentUser::canAccessPanel()`.
- Login limitado por e-mail + IP e cadastro por IP, com valores em `config/denarius.php`.
- `AdminUserSeeder` usa somente variáveis de ambiente completas, senha forte e execução idempotente.
- Verificação de e-mail e reset de senha permanecem como evoluções futuras.

## 2026-10-06 — Etapa 3

- Criados `FinancialTerm`, `FinancialTermDifficulty`, factory, migration e catálogo inicial com 90 termos educativos.
- A normalização determinística remove acentos, espaços, números e símbolos, preserva somente `A-Z` e limita palavras a 24 letras.
- Observer Eloquent gera `normalized_term`; unicidade também é garantida por constraint no MySQL.
- Seeder idempotente preserva alterações realizadas por administradores.
- Resource Filament oferece listagem, busca, filtros, criação, visualização, edição, ativação e exclusão.
- Policy explícita mantém todas as operações do catálogo restritas a administradores.
- O catálogo permanece interno e não possui endpoint público nesta etapa.
- Próximo passo: implementar o gerador determinístico do caça-palavras e seus testes de posicionamento.

## 2026-10-06 — Etapa 4

- Criado `WordSearchGeneratorService` para selecionar termos ativos e gerar grids configuráveis integralmente no backend.
- Modeladas as oito direções em `WordDirection`, com deltas explícitos de linha e coluna.
- Criados DTOs `readonly` para resultado, placements, coordenadas e snapshots dos termos selecionados.
- O posicionamento tenta palavras maiores primeiro, prioriza cruzamentos compatíveis e valida toda a posição antes de gravar no grid.
- A seleção, posições, direções e letras de preenchimento usam `Randomizer`; engines seeded tornam os testes reproduzíveis.
- Configurações padrão e limites foram centralizados em `config/denarius.php`.
- Exceções de domínio tratam catálogo insuficiente, entrada duplicada, configuração inválida, palavra incompatível e falha após tentativas limitadas.
- Não houve alteração de banco, `GameSession`, interface, pontuação ou ranking.
- Suíte completa: 95 testes, 930 assertions e nenhuma falha.
- Pint, build e auditorias Composer/pnpm passaram; não há analisador estático configurado.
- Próximo passo: implementar `GameSession`, snapshots e regras transacionais/antitrapaça.

## 2026-10-06 — Etapa 5

- Criados `GameSession` e `GameSessionWord`, com grid/configuração JSON, snapshots dos termos e placements, casts explícitos, índices e chaves estrangeiras.
- `GameSessionStatus` centraliza `ACTIVE`, `COMPLETED` e `ABANDONED`; somente partidas ativas podem concluir ou abandonar.
- `StartGameSessionAction` valida o resultado do gerador e persiste sessão e palavras em uma transação, após bloquear o usuário e revalidar a regra de uma partida ativa.
- `FindGameSessionWordAction` aceita coordenadas nos dois sentidos, bloqueia sessão/palavra, mantém idempotência, recalcula o contador autoritativo e conclui automaticamente a última palavra.
- `AbandonGameSessionAction` registra término e duração no servidor sem permitir transições de estados finais.
- Snapshots e estados finais são imutáveis pelos modelos; campos sensíveis são totalmente protegidos contra mass assignment.
- `GameSessionPolicy` restringe consulta, acerto e abandono ao proprietário. Nenhuma rota pública nova foi criada.
- Exclusão futura de `FinancialTerm` preserva o snapshot e apenas torna sua referência nula.
- Migrations aplicadas com sucesso no MySQL 8.4.
- Suíte completa: 138 testes, 1.062 assertions e nenhuma falha; 43 casos foram adicionados nesta etapa.
- Pint, build e auditorias Composer/pnpm passaram; não há analisador estático configurado.
- Próximo passo: implementar a interface jogável desktop/mobile em Livewire, consumindo as actions sem duplicar regras no frontend.

## 2026-10-06 — Etapa 6

- A rota autenticada `/game` passou a renderizar `GameBoard`, componente Livewire que inicia, retoma, atualiza e abandona a sessão do próprio usuário.
- O grid consumido é o snapshot persistido; a lista usa termos originais e nenhum placement pendente é enviado ao HTML.
- Pointer Events unificam mouse, caneta e toque. JavaScript calcula somente trajetórias visuais retas e envia as quatro coordenadas ao backend.
- Palavras encontradas ficam destacadas, progresso é autoritativo e conclusão/abandono bloqueiam novas seleções.
- O cronômetro visual deriva de `started_at`, sobrevive a reload e é substituído pela duração oficial ao encerrar.
- Foram adicionados tutorial em dialog nativo, confirmação de abandono, feedback `aria-live`, foco visível e mensagens além de cor.
- Rate limits por usuário + IP protegem início, seleções e abandono; payloads não aceitam sessão, termo, score ou estado.
- Layout mobile-first mantém o tabuleiro fluido e move os painéis de progresso/termos para baixo do grid antes do breakpoint desktop.
- Adicionados 14 testes PHPUnit e 5 testes JavaScript; suíte final: 152 testes PHP, 1.127 assertions e 5 testes JS, sem falhas.
- Pint, Composer validate, Vite build e auditorias Composer/pnpm passaram.
- A validação visual automatizada não pôde ser executada porque a conexão do navegador disponibilizado pelo ambiente foi recusada; a pendência foi documentada sem alegação de screenshots.
- Próximo passo: implementar pontuação e regras de recompensa no backend.

## 2026-10-06 — Etapa 7

- Criados `ScoreCalculator` e `ScoreAward` para centralizar pontos por palavra, bônus de conclusão e faixas de velocidade.
- `GameSession` passou a persistir `score` inteiro não negativo; o modelo impede redução e mantém proteção total contra mass assignment.
- A migration aditiva também recalcula partidas pré-existentes e inclui a configuração de pontuação em seus snapshots.
- Novas partidas congelam a fórmula em `generation_config.scoring`, preservando auditabilidade mesmo após alterações globais.
- `FindGameSessionWordAction` atribui pontos dentro da transação e dos locks existentes; repetição retorna prêmio zero e conclusão recebe bônus uma única vez.
- Partidas abandonadas mantêm os pontos acumulados, mas não recebem bônus de conclusão ou velocidade.
- A interface Livewire exibe score persistido durante a partida, feedback por acerto e pontuação final/conquistada nos estados finais.
- Migration, rollback e reaplicação foram validados no MySQL 8.4.
- Suíte completa: 169 testes PHP, 1.186 assertions e 5 testes JavaScript, sem falhas.
- Pint, Composer validate, Vite build e auditorias Composer/pnpm passaram.
- Próximo passo: implementar ranking geral consumindo somente partidas concluídas e scores persistidos.

## 2026-10-06 — Etapa 8

- Criados `RankingService`, `LeaderboardEntry` e o componente Livewire `LeaderboardBoard`; `/ranking` foi adicionado sob autenticação.
- A consulta usa duas janelas SQL para selecionar uma partida vencedora por participante e ordenar posições sem carregar todo o histórico para PHP.
- Somente usuários `participant` com partidas `COMPLETED` são elegíveis; sessões `ACTIVE` e `ABANDONED` não aparecem, mesmo com score alto.
- Desempates usam score descendente, duração crescente, `finished_at` mais antigo e ID crescente. Duração/término nulos são ordenados explicitamente depois de valores conhecidos.
- A pontuação vem sempre de `game_sessions.score`; posição individual usa o recorde concluído do participante, inclusive quando fora da página atual.
- O ranking pagina até 50 posições por página, mostra Top 3, destaca o participante e atualiza a própria área a cada cinco segundos com `wire:poll`.
- O resultado final agora exibe score, duração oficial, breakdown derivado e conferido com a configuração histórica, melhor posição, link para ranking e ação de jogar novamente.
- Abandono não mostra colocação. Nova tentativa mantém registros anteriores, e requests repetidas retomam a sessão ativa existente.
- O plano `EXPLAIN FORMAT=JSON` em MySQL local escolheu o índice já existente `(status, finished_at)`; um índice adicional experimental foi aplicado e revertido porque não foi selecionado. A janela global gera filesort/tabela temporária, a reavaliar com volume real de evento.
- A consulta do serviço foi executada no MySQL e retornou zero participantes na base local atual; suíte automatizada executada em SQLite.
- Suíte final: 186 testes PHPUnit, 1.266 assertions e 5 testes JavaScript, sem falhas.
- A inspeção visual foi tentada pelo browser integrado, mas o ambiente recusou a ponte nativa; não foram alegadas medições ou screenshots.
- Próximo passo: Etapa 9 — mascote/onça 8-bit, tutorial, feedback visual e polimento responsivo.
