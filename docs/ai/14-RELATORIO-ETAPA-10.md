# Relatório da Etapa 10 — Administração final, QA, segurança e pré-deploy cPanel

## 1. Resumo

Etapa concluída no código e na preparação documental, sem deploy nem alteração de produção. O estado funcional local está validado; **readiness de hospedagem: BLOCKED**, pois os dados reais do cPanel (versões, domínio, Document Root, SSL, banco remoto, privilégios e permissões) não foram disponibilizados/confirmados. Não declarar a aplicação pronta para produção até fechar esses bloqueios.

Base confirmada: branch `feat/mascot-visual-polish`, contendo os commits das Etapas 7, 8 e 9; nova branch de trabalho `chore/pre-deploy-hardening`. Worktree estava limpo antes das alterações. A suíte de base foi executada antes da implementação: 187 testes e 1.280 assertions.

## 2. Admin

- **Termos:** criação, edição, busca, filtros, ativação/desativação permanecem disponíveis. Removemos exclusão individual/em lote do Filament e bloqueamos `delete`/`deleteAny` na policy. Desativar preserva histórico e impede seleção em partidas futuras.
- **Usuários:** novo resource somente leitura. Administradores podem pesquisar nome/e-mail, visualizar papel e cadastro, quantidade de partidas e melhor score entre partidas concluídas. Senha, hash, remember token não são exibidos. Não há criação, edição, exclusão nem alteração de papel.
- **Partidas:** novo resource somente leitura com participante, status, score, palavras encontradas/total, duração e datas. Há filtro por status, busca por participante/e-mail e detalhe operacional. Grid, placements, configuração e dados de solução não são mostrados; não há ação para alterar score, estado, duração ou snapshot.
- **Ranking:** continua consumido pela tela autenticada `/ranking` e pelo `RankingService`; nenhum ranking paralelo ou CRUD foi criado no Filament.
- Recursos protegidos pelo login Filament e `User::canAccessPanel()`; testes de participante verificam 403.

## 3. QA

- **Visitante:** testes existentes cobrem login, cadastro, redirect de `/game`, `/ranking` e `/admin`; respostas públicas seguem acessíveis. O endpoint `/up` existe no bootstrap Laravel.
- **Participante:** regressão automatizada mantém cadastro como `participant`, login/logout, início/retomada, acerto, erro, repetição idempotente, score, conclusão, ranking, replay, abandono, ownership, CSRF pelo middleware web e rate limiting nas ações de jogo/cadastro.
- **Administrador:** testes verificam acesso ao painel e ao catálogo; os novos testes cobrem busca/visão de usuários e partidas, filtro, bloqueio de participantes e ausência de edição/exclusão em registros operacionais.
- **HTTP/security:** teste confirma `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` e `Permissions-Policy`.
- **Execução manual:** não foi feita sessão autenticada real em navegador nem operação real de cPanel. Os testes automatizados usam SQLite in-memory; `migrate:status` e leitura de versão/charset foram confirmados no MySQL local.
- **Validação visual:** tentativa de conexão ao navegador falhou com `privileged native pipe bridge is not available; browser-client is not trusted`. Nenhum screenshot ou breakpoint foi alegado.

Checklist manual a executar fora deste ambiente antes/depois do deploy:

| Viewport | Home | Login/cadastro | Jogo/tutorial/resultado | Ranking/navegação | Verificações |
|---|---|---|---|---|---|
| 360 px | [ ] | [ ] | [ ] | [ ] | Sem overflow; grid e toque usáveis |
| 390 px | [ ] | [ ] | [ ] | [ ] | Texto/score/tempo legíveis; modal acessível |
| 430 px | [ ] | [ ] | [ ] | [ ] | Botões e lista de termos sem sobreposição |
| 768 px | [ ] | [ ] | [ ] | [ ] | Transição tablet sem quebra de layout |
| 1024 px | [ ] | [ ] | [ ] | [ ] | Grid/painel lateral e tabela legíveis |
| 1440 px | [ ] | [ ] | [ ] | [ ] | Largura aproveitada sem excesso de espaço |

Em todos os tamanhos, verificar foco/teclado, erro de formulário, `prefers-reduced-motion`, mascot placeholder, acerto/erro/fim/abandono, reload e rolagem; se possível repetir em Android, iPhone/Safari, Chrome e Firefox.

## 4. Segurança

### Achados por severidade

- **Crítico:** nenhum achado crítico confirmado no código auditado.
- **Alto:** nenhuma vulnerabilidade alta/crítica foi reportada pelas auditorias de dependências; nenhum bypass de autorização/payload foi observado nos fluxos cobertos.
- **Médio:** condições do cPanel ainda não verificadas: PHP Web/CLI, Document Root `/public`, HTTPS, usuário/privileges do banco, engine/window functions e permissões. Isso bloqueia produção, embora não seja uma falha confirmada no app.
- **Baixo:** CSP e HSTS não foram habilitados nesta etapa. HSTS requer HTTPS confirmado e CSP precisa de validação de compatibilidade Livewire/Filament/Vite. O placeholder da onça permanece conhecido/documentado, sem risco de execução remota.

### Correções e controles

- Filament expõe somente campos permitidos por tabela/infolist; resources de usuário/partida não têm ações mutantes. Policies negam exclusão de termos. O admin não consegue editar papéis pelo painel.
- `GameSessionPolicy` permite consulta admin necessária ao resource somente leitura; participantes continuam sujeitos ao ownership. Partida não ganha capacidade de update/delete via Filament.
- Adicionado middleware global com `nosniff`, frame denial, política de referer e bloqueio de permissões browser não utilizadas.
- Autenticação usa sessão Laravel, regeneração no login/cadastro, invalidação/token novo no logout; rotas da aplicação em middleware web/CSRF. Login/cadastro/jogo possuem rate limits já existentes.
- Cadastro valida campos e seleciona somente nome/e-mail/senha; `role` continua não fillable. `GameSession` e `GameSessionWord` permanecem totalmente guarded. Score/tempo/status vêm do backend.
- Ranking usa bindings/query builder e nomes Blade escapados; placements pendentes não são enviados ao cliente. Nenhum upload foi localizado.
- `public/.htaccess` mantém regras padrão do Laravel; o Document Root público é condição essencial para não expor arquivos privados.
- `.env` está ignorado; busca de arquivos versionados proibidos será repetida antes do push. Nenhum segredo de produção foi usado.
- `AdminUserSeeder` exige `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, valida força mínima, é idempotente, não sobrescreve usuário e não imprime senha. O e-mail precisa estar livre; não promove participante existente.

## 5. Testes e validações

- `php artisan test --compact`: **197 testes, 1.325 assertions, 0 falhas**.
- Linha base da Etapa 9: 187 testes, 1.280 assertions; Etapa 10 adiciona cobertura de autorização/admin/headers/health/error escaping e atualiza a regra de remoção de termo.
- `pnpm test:js`: **5 aprovados, 0 falhas**.
- `./vendor/bin/pint --test`: aprovado; `pint --dirty --format agent` aplicado aos PHP alterados.
- `composer validate --strict`: aprovado.
- `composer check-platform-reqs`: aprovado no PHP local 8.5.11 para requisitos instalados.
- `pnpm build`: aprovado; gerou `public/build/manifest.json`. Permaneceu o aviso opcional conhecido de `fontaine`.
- `composer audit --locked`: nenhuma vulnerabilidade reportada.
- `pnpm audit --audit-level=high`: nenhuma vulnerabilidade reportada.
- `pnpm install --frozen-lockfile`: concluído sem mudanças na lockfile.
- `git diff --check`: será repetido após revisão final.
- `php artisan route:list`: executado; rota `/up`, aplicação, `admin/users` e `admin/game-sessions` registradas, sem rotas de criação/edição para os novos resources.
- `php artisan migrate:status`: todas as 8 migrations locais em `Ran`. Sem migrations novas nesta etapa.
- PHPStan/Larastan: não configurado; não instalado no fim do MVP.

## 6. cPanel

- **PHP Web:** desconhecido. O PHP local não representa o handler web remoto.
- **PHP CLI:** desconhecido no cPanel. Local foi PHP 8.5.11 em `/opt/homebrew/Cellar/php/8.5.11/bin/php`.
- **Extensões:** todas as solicitadas estão presentes localmente; no host ainda sem prova.
- **Composer:** local 2.10.3; cPanel desconhecido. A estratégia A/B para `vendor/` continua pendente da verificação.
- **Node/pnpm:** localmente disponíveis; não necessários em produção. Build local validado e `public/build/manifest.json` existe.
- **Domínio/DNS/Document Root:** não informados/verificados. Document Root precisa apontar a `.../public`; se não puder, bloqueia publicação.
- **SSL:** não informado; somente ativar cookies secure depois de HTTPS válido.
- **Symlink:** suporte desconhecido; estrutura `releases/shared/current` é proposta condicionada.
- **Cron:** não requerido pelo código atual; `routes/console.php` não agenda tarefas.
- Readiness detalhado e ações pendentes: `docs/deploy/CPANEL-READINESS.md`.

## 7. Banco

- **Engine/versão local:** MySQL 8.4.11.
- **Charset/collation local:** `utf8mb4` / `utf8mb4_0900_ai_ci`.
- **Banco/user/privileges cPanel:** o usuário informou que os recursos foram criados; nomes, associação e grants não foram confirmados. Senhas não foram solicitadas nem registradas.
- **Migrations:** nenhuma alteração na Etapa 10; as oito do ambiente local aparecem como aplicadas.
- **Seeders produtivos:** somente `FinancialTermSeeder` (idempotente) e `AdminUserSeeder` (configuração explícita, senha forte, sem overwrite). Não rodar fábricas/dados fake.
- **Window functions:** MySQL local suporta a consulta. `EXPLAIN FORMAT=JSON` da janela particionada escolheu índice `game_sessions_status_finished_at_index` por `status`; usou filesort e temporary table. Custo estimado local baixo com cardinalidade quase vazia; não extrapolar ao evento nem criar índice sem dados.
- **phpMyAdmin:** ferramenta de inspeção, não usada como mecanismo de migration; conexão remota não verificada.

## 8. Produção

- `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS e `APP_KEY` exclusiva são requisitos; `.env.example` é explicitamente local e não deve ser copiado como `.env` de produção.
- Session: `database`, tabela criada pela migration; `SESSION_SECURE_COOKIE=true` somente após SSL, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`.
- Cache: `database`, migration existente; confirmar permissões/conexão no host.
- Queue: `sync` no exemplo local revisado; o código não despacha jobs, então não há worker requerido atualmente.
- Logs: padrão `storage/logs/laravel.log`; produção deve usar nível `warning`/`notice` conforme operação, garantir escrita e não registrar senha/token.
- Storage: sem upload público identificado; `storage:link` não necessário atualmente.
- `storage/` e `bootstrap/cache/` precisam ser graváveis pelo usuário PHP com permissão mínima; nenhum `chmod 777`.
- Não há mudança de DNS, SSL, ambiente, migração ou banco remoto nesta etapa.

## 9. Release

- **Estratégia:** guia condicional A/B documentado, escolha final bloqueada até confirmar Composer e PHP cPanel. Estratégia B exige gerar `vendor/` em runtime/ambiente PHP compatível com o host; não reutilizar vendor sem confirmar plataforma.
- **Node/pnpm:** ficam no build local; servidor não precisa deles.
- **`public/build`:** build local validado; manifesto produzido. ZIP final não foi criado sem confirmação do PHP-alvo.
- **Conteúdo:** allowlist Laravel, `vendor/` só em estratégia B; excluir `.env`, `.git`, `node_modules`, logs, caches locais, backups, dumps e secrets.
- **Estrutura:** `releases/`, `shared/.env`, `shared/storage`, `current` e Document Root em `current/public`, condicionados a suporte de symlinks. Se host não suporta Document Root seguro, interromper e reavaliar hospedagem; não improvisar `index.php`.
- Procedimento reproduzível, migrations, seeders, cache e smoke test: `docs/deploy/CPANEL-DEPLOY.md`.

## 10. Backup e rollback

- Antes do deploy: backup verificável do banco, `.env` privado, storage e arquivos/release anterior. Ainda não há backup de cPanel confirmado.
- Com releases/symlink: retorno do `current` para release anterior sem apagar a antiga; se sem symlink, definir procedimento manual com o provedor antes de publicar.
- Banco: não desfazer migrations automaticamente; restaurar backup somente após avaliar gravações posteriores e autorização responsável.
- Plano e comandos condicionais estão em `CPANEL-DEPLOY.md`; ainda não foram testados no host.

## 11. Bloqueios

1. Confirmar versão PHP Web e PHP CLI, seus caminhos e compatibilidade/extensões.
2. Confirmar Composer no host para escolher estratégia A ou B e obter vendor compatível.
3. Definir domínio/DNS e provar Document Root terminando em `/public`.
4. Confirmar HTTPS/SSL/renovação.
5. Confirmar engine/versão MySQL ou MariaDB, `ROW_NUMBER()`, charset, conexão, associação do user e privilégios.
6. Confirmar symlinks ou aprovar topologia segura alternativa.
7. Confirmar permissões `storage`/`bootstrap/cache`, backup e rollback.
8. Executar QA visual manual em browser/dispositivos; navegador integrado indisponível nesta execução.

Responsáveis: proprietário do domínio/conta cPanel e provedor de hospedagem para itens 1–7; equipe de produto/QA para item 8. Não informar senha, token, `.env` ou `APP_KEY` em resposta/chat/relatório.

## 12. Git

- **Branch:** `chore/pre-deploy-hardening`, criada sobre `feat/mascot-visual-polish` com Etapas 7–9.
- **Commit funcional:** `a7caa15 chore: harden application for production deployment`.
- **Commit documental:** `docs: add cpanel deployment readiness report` (SHA registrado no handoff da entrega).
- **Push:** será publicada em `origin/chore/pre-deploy-hardening`, sem merge automático em `main`.
- **Secrets:** `.env` permanece ignorado; varredura final dos arquivos staged necessária.

## 13. Checklist de readiness

Os 30 itens operacionais, valores locais/remotos, bloqueios e ações estão detalhados na tabela de `docs/deploy/CPANEL-READINESS.md`. Resumo das 30 perguntas:

1. PHP Web — não verificado no cPanel.
2. PHP CLI — não verificado no cPanel; local 8.5.11.
3. Laravel 13 — `composer.json` pede PHP `^8.3`; compatibilidade do host pendente.
4. Extensões — presentes localmente; remotas pendentes.
5. Composer — presente localmente; disponibilidade remota pendente.
6. ZIP com `vendor/` — ainda depende da confirmação Composer/PHP (A ou B).
7. Node/pnpm — não necessários no runtime.
8. `public/build` — build local criado e validado.
9. Domínio/subdomínio — não informado.
10. Document Root `/public` — não verificado, bloqueio crítico.
11. SSL — não verificado.
12. Banco — usuário relata criação; nome remoto não confirmado.
13. Usuário associado — não confirmado.
14. Privilégios — não confirmados.
15. Window functions — local MySQL 8.4.11 suporta; cPanel pendente.
16. `migrate --force` — requer conexão e grants cPanel ainda não provados.
17. Seeders — apenas `FinancialTermSeeder` e `AdminUserSeeder`; sem dados fake.
18. Admin inicial — env temporário privado, seeder idempotente, remover senha depois.
19. Storage/cache graváveis — host pendente; local não prova.
20. `storage:link` — não necessário no código atual.
21. Session — database, migration `sessions` presente localmente; confirmar remoto.
22. Cache — database, migration presente localmente; confirmar remoto.
23. Queue — nenhum job real; manter `sync`, sem worker.
24. Scheduler/cron — nenhum agendamento, sem cron.
25. Ranking — Livewire HTTP polling, sem WebSocket.
26. Backup — não confirmado; obrigatório antes do deploy.
27. Rollback — roteiro definido; execução/paths do host pendentes.
28. Pacote — instruções exatas no `CPANEL-DEPLOY.md`; ZIP final após compatibilidade.
29. Bloqueios — sim, os itens 1–7 de “Bloqueios” mais QA visual externo.
30. Status — **BLOCKED** até confirmar os itens críticos do host.

## 14. Próxima etapa

**ETAPA 11 — Deploy no cPanel + smoke tests + documentação final do MVP.** Não iniciada nesta sessão e condicionada à resolução dos bloqueios, backups, aprovação do proprietário e autorização explícita do deploy.
