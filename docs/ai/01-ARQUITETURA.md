# Arquitetura

## Componentes

```text
Blade / Livewire / Alpine
          |
          v
Form Requests / Livewire validation + authorization
          |
          v
Actions (casos de uso) -> Services (regras reutilizáveis)
          |
          v
Eloquent models + transactions
          |
          v
MySQL
```

## Módulos planejados

- `Auth`: controllers HTTP + Form Requests, sessão Laravel, logout seguro e rate limiting nativo.
- `Terms`: `FinancialTerm`, normalização Unicode centralizada, catálogo ativo e administração protegida.
- `Game`: `GameSession`, `GameSessionWord`, snapshot auditável e validação server-side.
- `Scoring`: `ScoreCalculator` puro e transparente.
- `Ranking`: consulta indexada por pontuação, tempo e conclusão; Livewire polling.
- `Admin`: resources Filament protegidos por autorização explícita.
- `Production readiness`: perfil de configuração produtivo descrito em `docs/deploy/`; não há deploy, DNS ou alteração de banco remoto executados na Etapa 10.

## Autenticação e autorização

- O guard `web` e a sessão nativa atendem a aplicação pública e o painel.
- `UserRole` é um enum persistido como string; o `User` centraliza `isAdmin()` e `isParticipant()`.
- `FilamentUser::canAccessPanel()` permite o painel `admin` somente para administradores.
- Login limita falhas por e-mail normalizado + IP; cadastro limita requisições por IP.
- O cadastro seleciona explicitamente campos seguros e nunca aceita `role` do request.
- `UserResource` e `GameSessionResource` são somente leitura: não permitem criar/editar/excluir usuários ou partidas. Usuários podem ser pesquisados por nome/e-mail e mostram perfil, quantidade de partidas e maior score concluído; partidas exibem apenas dados operacionais, sem grid ou placements.
- `FinancialTermResource` permite cadastrar, editar, ativar e desativar termos; exclusão individual/em lote foi removida para evitar perda desnecessária da rastreabilidade do catálogo.
- `AddSecurityHeaders` aplica cabeçalhos básicos globalmente. HSTS depende de confirmar HTTPS no host; CSP não foi aplicada porque precisa de teste específico das diretivas necessárias a Livewire/Filament/Vite.

## Catálogo de termos

- `FinancialTermNormalizer` translitera, converte para maiúsculas e preserva somente `A-Z`, com limite de 24 letras para o futuro grid.
- `FinancialTermObserver` aplica a normalização em toda gravação Eloquent; `normalized_term` não é mass-assignable.
- O índice `UNIQUE` em `normalized_term` protege a integridade mesmo sob gravações concorrentes.
- `FinancialTermDifficulty` persiste `easy`, `medium` ou `hard` e fornece rótulos/cores ao Filament.
- `FinancialTermPolicy` restringe leitura e escrita administrativa a usuários `admin`.
- `FinancialTermSeeder` usa `firstOrCreate`: pode ser repetido sem duplicar nem sobrescrever edições administrativas.
- Nenhuma rota ou API pública expõe o catálogo nesta etapa.

## Motor do caça-palavras

- `WordSearchGeneratorService::generate()` consulta termos ativos uma única vez e executa seleção e posicionamento em memória.
- `generateFromTerms()` separa o posicionamento de uma coleção explícita da seleção normal do catálogo; esse caminho é usado para testes e integrações controladas.
- `WordDirection` modela as oito direções com deltas explícitos de linha e coluna.
- `WordSearchResult`, `WordPlacement`, `WordCoordinate` e `SelectedFinancialTerm` são contratos `readonly` preparados para o snapshot da futura `GameSession`.
- O algoritmo randomiza seleção, coordenadas, direções e preenchimento; `Randomizer` pode ser injetado com uma engine seeded para testes reproduzíveis.
- As palavras são tentadas da maior para a menor. Entre candidatos compatíveis, o algoritmo prioriza o maior número de cruzamentos e escolhe aleatoriamente entre empates.
- Cada candidato é validado integralmente antes de alterar o grid. Letras iguais podem cruzar; letras diferentes bloqueiam a posição.
- Falhas são explícitas para configuração inválida, catálogo insuficiente, duplicidade normalizada, palavra incompatível e esgotamento das tentativas.
- O grid padrão é `15x15`, com 10 palavras e até 20 reinicializações, configurados em `config/denarius.php`.

## Partidas persistentes

- `StartGameSessionAction` autoriza o usuário, gera e valida o `WordSearchResult` e persiste `GameSession` + `GameSessionWord` atomicamente.
- A criação bloqueia a linha do usuário e reconsulta partidas ativas dentro da transação, serializando tentativas concorrentes para a regra de uma partida ativa por usuário.
- `GameSessionSnapshotValidator` verifica dimensões, alfabeto, coordenadas, direção, correspondência entre termos e placements e reconstrução de cada palavra antes da persistência.
- `FindGameSessionWordAction` recebe somente coordenadas, aceita o placement nos dois sentidos e bloqueia sessão e palavra antes de alterar o estado.
- Acertos repetidos são idempotentes. O contador é recalculado a partir das palavras encontradas e a última palavra conclui a partida na mesma transação.
- `AbandonGameSessionAction` realiza somente a transição `ACTIVE -> ABANDONED`, com horário e duração definidos pelo servidor.
- `GameSessionPolicy` restringe leitura e mutações ao proprietário. Ainda não existem endpoints públicos para essas actions.
- O snapshot da palavra preserva os textos original e normalizado. A referência ao catálogo usa `nullOnDelete`, mantendo o histórico mesmo se o termo for removido.
- Modelos são totalmente protegidos contra mass assignment e impedem alterações Eloquent em snapshots e estados finais.

## Interface jogável

- `GameBoard` é um componente Livewire class-based embutido na rota pública `/game`.
- O componente resolve a sessão pelo usuário autenticado ou, para visitantes, pela partida guardada na sessão HTTP (`GuestGameStore`); nenhum método público recebe `user_id` ou `game_session_id`.
- Visitantes usam `StartGuestGameAction`, `FindGuestGameWordAction` e `AbandonGuestGameAction`, que operam sobre modelos não persistidos; o rate limit do visitante usa o ID da sessão + IP.
- Login e cadastro chamam `ClaimGuestGameAction`; uma falha nessa etapa é reportada e não impede a autenticação.
- Início, acerto e abandono delegam respectivamente para `StartGameSessionAction`, `FindGameSessionWordAction` e `AbandonGameSessionAction`.
- A view recebe o grid persistido, termos originais, contadores e somente as células de palavras já encontradas. Placements pendentes permanecem no servidor.
- `word-search-selection.js` usa Pointer Events para produzir trajetórias horizontais, verticais e diagonais, inclusive invertidas, e envia somente coordenadas no fim do gesto.
- `game-clock.js` deriva o tempo visual do `started_at`; a duração final exibida vem do snapshot oficial do backend.
- As actions públicas do componente usam limites por usuário + IP; o frontend bloqueia submissões sobrepostas, mas locks e idempotência continuam no domínio.
- O layout Tailwind é mobile-first: grid fluido sem overflow horizontal, painel lateral em desktop e lista de termos abaixo no mobile/tablet.
- JavaScript de seleção é coberto pelo runner nativo do Node; nenhuma dependência frontend adicional foi introduzida.

## Pontuação e recompensas

- `ScoreCalculator` é um serviço determinístico, sem dependência de HTTP, Livewire ou sessão web.
- A fórmula padrão concede 100 pontos por palavra, 500 pela conclusão e bônus de velocidade de 500/300/150 pontos para conclusões em até 120/180/300 segundos.
- `StartGameSessionAction` salva a configuração de pontuação em `generation_config.scoring`; mudanças futuras de configuração não alteram a regra de partidas já iniciadas.
- `FindGameSessionWordAction` calcula e persiste o prêmio depois dos locks de sessão/palavra e dentro da mesma transação do acerto.
- `ScoreAward` e `WordSelectionResult` explicitam pontos da palavra, bônus de conclusão, bônus de velocidade e total concedido.
- Seleções repetidas retornam prêmio zero. Partidas abandonadas preservam pontos já conquistados, sem bônus finais.
- `GameSession.score` é inteiro não negativo, não pode diminuir e permanece protegido contra mass assignment.

## Ranking e jornada do jogador

- `/ranking` exige autenticação e renderiza uma página Blade com um componente Livewire separado para o ranking dinâmico.
- `RankingService` consulta somente sessões `COMPLETED` pertencentes a usuários `participant`; um anti-join `NOT EXISTS` e a ordem total elegem a melhor sessão por usuário sem window functions, CTEs ou sintaxe exclusiva do MySQL 8.
- A regra SQL aplica score decrescente, duração crescente, conclusão mais antiga e ID da sessão crescente; duração e conclusão nulas ficam explicitamente depois de valores conhecidos.
- A consulta usa paginação limitada; as posições da página derivam do offset, e a posição individual fora da página conta os participantes que vêm antes da melhor sessão. O histórico não é carregado integralmente para PHP.
- O score vem sempre de `game_sessions.score`. Nenhuma fórmula é executada no ranking.
- `LeaderboardBoard` atualiza sua área a cada cinco segundos com `wire:poll`; polling é somente leitura, não inicia partidas e respeita a redução automática de frequência em abas em segundo plano do Livewire.
- A interface pública mostra nome, posição, score e duração. Emails, snapshots, placements e IDs de sessão não são renderizados.
- `GameBoard` calcula o breakdown final apenas a partir do snapshot de pontuação da sessão e compara o resultado com o score persistido antes de exibi-lo. A posição mostrada é sempre a melhor posição concluída do participante.
- Não foi mantido índice adicional: no `EXPLAIN FORMAT=JSON` do MySQL local, o plano preferiu o índice existente por status e término; a janela usa ordenação temporária e será reavaliada com volume de evento representativo.
- Partidas anteriores permanecem imutáveis ao iniciar outra. O fluxo de start já serializa requests simultâneas e retoma sessão ativa existente.

## Preparação cPanel

- O banco local é MySQL 8.4.11 (`utf8mb4`/`utf8mb4_0900_ai_ci`). O alvo informado é Percona Server 5.7.44-48 com `utf8mb4_unicode_ci`; Laravel também usa `utf8mb4_unicode_ci` por padrão. Não foi alterada collation nem migration.
- O `composer.lock` inclui dependências com PHP mínimo `>=8.4.1`; por isso o requisito foi alinhado para `^8.4.1`. A máquina de desenvolvimento validou somente PHP CLI 8.5.11; PHP 8.4 ainda precisa ser testado no host.
- O ranking não depende mais de `ROW_NUMBER()`/window functions; usa `NOT EXISTS` e comparações compatíveis com MySQL 5.7. A suíte SQLite verifica a regra; falta executar a query e `EXPLAIN` no Percona real.
- Em produção, gerar `public/build` localmente, incluir `vendor/` no ZIP de release, manter `.env` e `storage` fora do Document Root e executar migrations com `--force` somente após backup. Composer remoto não será requisito.
- O domínio, Document Root, PHP CLI/extensões, associação/grants do banco, SSL/DNS, permissões, symlinks, backups e rollback continuam condicionantes a serem verificados no cPanel.

## Apresentação e mascote

- `x-mascot` centraliza caminhos, texto acessível, tamanho e estado visual do personagem; views não repetem caminhos de imagem.
- Os estados `idle`, `correct`, `error`, `celebration`, `victory` e `abandoned` são apresentação derivada do resultado/estado já autorizado pelo backend. Não mudam score nem regras da partida.
- Assets finais podem ser instalados por estado em `public/images/mascot/{idle,correct,error,celebration,victory,abandoned}.webp`. Enquanto ausentes, todos usam o SVG provisório local, sem chamada externa.
- O tutorial usa a configuração de pontuação congelada na sessão, ou a configuração vigente antes de iniciar; nenhum score é calculado no navegador.
- `resources/css/app.css` aplica microanimações curtas e desativa movimento/transições com `prefers-reduced-motion: reduce`.
- Nenhuma biblioteca JS foi adicionada. Livewire continua orquestrando estados e JavaScript permanece restrito à seleção/relógio já existentes.

## Serviços-alvo

- `WordSearchGeneratorService` — implementado na Etapa 4.
- `StartGameSessionAction` — implementado na Etapa 5.
- `FindGameSessionWordAction` — implementado na Etapa 5.
- `AbandonGameSessionAction` — implementado na Etapa 5.
- `ScoreCalculator` — implementado na Etapa 7.
- `RankingService` — implementado na Etapa 8.

O frontend envia somente coordenadas da seleção. Palavras, relógio, conclusão e pontos permanecem sob autoridade do servidor.

## Modelo de dados

```text
User 1---* GameSession 1---* GameSessionWord *---1 FinancialTerm
```

`GameSession.grid` e `generation_config` são JSON. Cada palavra guarda a referência opcional ao catálogo, termo original e normalizado, coordenadas, direção e momento do acerto. Essa combinação preserva o snapshot e permite auditoria sem serializar DTOs PHP.
