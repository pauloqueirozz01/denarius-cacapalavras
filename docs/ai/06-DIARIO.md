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

## 2026-10-06 — Etapa 9

- Adicionado o componente Blade `x-mascot`, com estados `idle`, `correct`, `error`, `celebration`, `victory` e `abandoned`; os estados são apresentação, nunca regra de domínio.
- Criado SVG pixel-art provisório e local de uma onça original. O componente procura arquivos WebP por estado e usa o SVG como fallback até a arte final aprovada estar disponível.
- GameBoard integra o mascote na abertura, partida e resultado; acerto/erro/marco de progresso derivam do retorno/estado autoritativo já existente.
- Tutorial atualizado com exemplo de seleção, direções, fórmula, bônus históricos, ranking e replay; os valores vêm do snapshot da sessão.
- Refinados os cabeçalhos do ranking e jogo, foco visível, semântica do diálogo, fallback de altura do modal e suporte global a `prefers-reduced-motion`.
- Não houve mudança nas actions, consultas, score, modelo de sessão, migrations ou dependências JavaScript.
- Suíte completa: 187 testes PHPUnit, 1.280 assertions e 5 testes JavaScript, sem falhas.
- Pint, Composer validate, Vite build, audits Composer/pnpm e `git diff --check` aprovados.
- A inspeção visual foi tentada com o navegador integrado, mas a conexão foi recusada (`privileged native pipe bridge is not available; browser-client is not trusted`); sem screenshots ou teste físico alegados.
- Próximo passo: Etapa 10 — Filament final, QA, auditoria de segurança e correções.

## 2026-10-06 — Etapa 10

- Confirmada a base completa da Etapa 9 em `feat/mascot-visual-polish`; worktree inicial limpo e branch própria `chore/pre-deploy-hardening` criada.
- Ampliado o Filament com consulta somente leitura de usuários (busca, papel, cadastro, quantidade de partidas e melhor score concluído) e partidas (status, score, progresso, duração e datas), com acesso apenas admin e sem exposição de grid/placements.
- Removidas ações de exclusão de termos e bloqueadas as abilities `delete`/`deleteAny`; desativação continua sendo a forma de retirar termos do gerador, preservando a trilha do catálogo.
- Adicionados cabeçalhos HTTP `nosniff`, `DENY`, `strict-origin-when-cross-origin` e `Permissions-Policy` sem CSP/HSTS prematuros. Teste de segurança valida os cabeçalhos.
- `.env.example` marcado como referência local, `QUEUE_CONNECTION=sync` por ausência de jobs ativos, e porta MySQL do Compose passou a escutar somente em loopback.
- QA inicial: baseline 187 testes/1.280 assertions; suíte final 197/1.325, JS 5; Pint, Composer validate/platform, build, Composer/pnpm audit aprovados.
- PHP CLI local 8.5.11 e extensões exigidas foram verificadas; migrations locais estão aplicadas. MySQL local: 8.4.11, `utf8mb4` e collation `utf8mb4_0900_ai_ci`; `EXPLAIN` da janela de ranking escolhe índice por status e utiliza filesort/tabela temporária.
- Não houve migration nova. Seeder de termos continua idempotente; seeder admin valida senha forte e não sobrescreve conta. Nenhum upload, job ou cron necessário foi identificado; `storage:link` não é usado pelos assets atuais.
- A conexão do browser integrado foi recusada (`privileged native pipe bridge is not available; browser-client is not trusted`); checklist manual de 360, 390, 430, 768, 1024 e 1440 px foi incluído no relatório.
- Sem acesso ao cPanel, valores de PHP Web/CLI, domínio, Document Root, SSL, symlink, database/user/grants, charset e permissões permanecem pendentes. `docs/deploy/CPANEL-READINESS.md` classifica readiness como `BLOCKED`; `CPANEL-DEPLOY.md` prepara fluxo, backup, migrations, seeders e rollback, sem executar operações remotas.
- Commits criados: `a7caa15 chore: harden application for production deployment` e `f03ef7f docs: add cpanel deployment readiness report`; branch publicada em `origin/chore/pre-deploy-hardening`, sem merge em `main`.
- Próxima etapa: Etapa 11 — publicação no cPanel após resolver bloqueios e obter autorização explícita, seguida de smoke tests e documentação final.

## 2026-10-07 — Check-up de compatibilidade cPanel pré-Etapa 11

- Confirmado pelo proprietário: alvo `gamedaoncinha.com`, PHP 8.4 disponível por domínio, Percona Server 5.7.44-48, database `denarius_gamefinanceiro`, user `denarius_financeirouser`, `localhost:3306`, charset/collation `utf8mb4`/`utf8mb4_unicode_ci`; Document Root desejado termina em `/public`. Nenhuma conexão ao host foi feita.
- O `RankingService` anterior usava `ROW_NUMBER() OVER`, incompatível com MySQL/Percona 5.7. Foi substituído por anti-join `NOT EXISTS`, comparador lexicográfico com os mesmos desempates e posição derivada da paginação/contagem de entradas melhores; não há mudança de regra nem recálculo de score.
- Acrescentado teste de desempate global por menor ID mesmo quando o menor ID é inserido depois. Baseline antes das alterações: 197 testes/1.325 assertions; ranking após a mudança: 8 testes/29 assertions aprovados.
- A auditoria de `composer.lock` encontrou dependências de runtime com PHP mínimo `>=8.4.1`, embora `composer.json` declarasse `^8.3`. O requisito foi alinhado a `^8.4.1` e o lock atualizado sem alterar versões de pacotes.
- As migrations usam JSON básico e tipos/índices tradicionais compatíveis com 5.7.44; configuração de database já usa `utf8mb4_unicode_ci`. Nenhuma migration, collation, sessão, cache ou fila foi alterada.
- Estratégia de release definida: `vendor/` no ZIP, gerado com PHP 8.4.1+; Node/pnpm só no build local, `public/build` incluído. Nenhuma alteração no host, migration de produção ou deploy executada.
- Permanece `BLOCKED`: PHP CLI/patch/extensões, associação/grants, SSL/DNS, Document Root, permissões, symlinks, backup/rollback e validação direta da consulta/migrations no Percona real ainda não foram confirmados. Percona 5.7 está em fim de vida upstream e requer confirmação de suporte/patching do provedor.
