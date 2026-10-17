# ETAPA 11 CONTRACT

Este contrato é a fonte de verdade para o agente que fará o deploy. Foi produzido pelo check-up de compatibilidade cPanel, não autoriza executar deploy agora e não autoriza alterar produção sem confirmação explícita do proprietário.

## Base aprovada

- Branch de trabalho: `fix/cpanel-compatibility`.
- Commit funcional aprovado: `f2f3c48 fix: ensure cpanel production compatibility`.
- O pacote inclui, além da compatibilidade cPanel (`f2f3c48`): jogo sem cadastro com gravação da partida no login/cadastro (sem migration), limites de autenticação para uma sala inteira na mesma rede, páginas de erro em pt-BR, proteção contra envio duplicado e ranking servido de cache de 10 s.
- **Pacote de produção gerado a partir de `5e1b088466f3bdc887dff159bfe7227e6aac524f`** (`5e1b088`). Ver seção "Pacote de produção".
- Testes em `5e1b088`: **235 PHPUnit / 1.573 assertions**, 0 falhas.
- JavaScript: **11 testes aprovados**, 0 falhas.
- Build: `pnpm install --frozen-lockfile` e `pnpm build` aprovados; `public/build/manifest.json` presente e incluído no pacote.
- Pint, `composer validate --strict`, `composer audit --locked`, `pnpm audit --audit-level=high` e `git diff --check` aprovados.
- `vendor/` gerado e `composer check-platform-reqs` aprovado em **PHP 8.4.26** (container Linux `php:8.4-cli`).
- Ranking, migrations e suíte completa validados num container local **Percona Server 5.7.44-48**; o banco do cPanel e o host ainda não foram testados. A readiness segue **BLOCKED** até fechar as pendências abaixo.
- A branch é separada de `main`; não fazer merge automático.

## Compatibilidade

- PHP mínimo efetivo do lock: **8.4.1**. `composer.json` exige `^8.4.1`. PHP 8.3 e PHP 8.4.0 não são aceitos para esta release.
- PHP alvo: **8.4** (`ea-php84`, confirmado pelo proprietário para o domínio), patch 8.4.1 ou superior, tanto no handler Web quanto no CLI de Artisan. O patch e o path do CLI ainda precisam ser confirmados no host. O pacote foi validado em PHP 8.4.26.
- Banco alvo: database `denarius_gamefinanceiro`, user `denarius_financeuser`, host `localhost`, porta `3306` (valores informados pelo proprietário; associação/grants pendentes).
- Engine/versão informada: **Percona Server 5.7.44-48**. Não usar a versão exibida por cliente como versão do servidor.
- Ranking: seleção da melhor tentativa via anti-join `NOT EXISTS`; ordenação total e mesma regra de desempate. Sem `ROW_NUMBER`, `RANK`, `OVER`, CTE ou dependência de função de janela. Score lido do campo persistido.
- Charset/collation: `utf8mb4` / `utf8mb4_unicode_ci`. Não mudar para collation `utf8mb4_0900_*`; nenhuma alteração necessária no código atual.
- `SESSION_DRIVER=database` e `CACHE_STORE=database`; validar tabelas/migrations no destino.
- `QUEUE_CONNECTION=sync`; não há worker obrigatório.
- Cron/scheduler: não exigido pelo código atual.
- Ranking: HTTP `wire:poll`, sem Reverb, WebSocket, daemon ou Redis obrigatório.
- Aviso de suporte: Percona 5.7 chegou ao fim de vida upstream; confirmar suporte de patches com o provedor ou plano de migração aprovado. Não atualizar o banco nesta etapa sem aprovação.

## Alterações obrigatórias já incorporadas

1. As duas funções `ROW_NUMBER() OVER` foram removidas do `RankingService` e substituídas pela seleção anti-join e contagem/offset compatíveis com Percona/MySQL 5.7.
2. O requisito PHP foi alinhado de `^8.3` para `^8.4.1`, pois o `composer.lock` atual possui dependências que requerem PHP `>=8.4.1`; nenhuma versão de dependência foi alterada.
3. Foi adicionado teste do desempate global pelo menor ID da sessão, incluindo IDs criados fora da ordem de inserção.

Não há migration ou alteração de schema pendente decorrente deste check-up.

## Não alterar na Etapa 11

- Não reintroduzir funções de janela, CTE ou consultas incompatíveis com Percona 5.7.
- Não alterar fórmula, snapshot ou persistência de score; ranking lê `game_sessions.score` e nunca o recalcula.
- Não alterar a ordem de elegibilidade/desempates do ranking.
- Não alterar migrations existentes, criar coluna/index/collation sem evidência e aprovação; não fazer edição manual de schema pelo phpMyAdmin.
- Não baixar o requisito Composer para `^8.3` enquanto o lock exigir `>=8.4.1`.
- Não colocar `.env`, `.git`, `vendor`, `storage`, `config`, `database`, `artisan`, backups ou dumps sob Document Root.
- Não mover `.env` para diretório público, copiar a raiz Laravel para `public_html` nem modificar `public/index.php` como workaround do Document Root.
- Não usar `migrate:fresh`, `migrate:refresh`, `db:wipe`, `chmod 777`, force push ou apagar release anterior.
- Não exigir Node/pnpm, Redis, queue worker, cron, Docker, Reverb ou WebSocket no servidor.
- Não versionar ou registrar senha, APP_KEY, token, `.env` ou credenciais.
- Não alterar DNS, SSL, domínio ou Document Root sem autorização específica do proprietário.

## Pendências de infraestrutura

Só permanecem estes itens sem evidência direta:

- Confirmar o patch **8.4.1+** do PHP Web `ea-php84` e as extensões selecionadas no domínio.
- Confirmar PHP CLI/caminho `8.4.1+` e extensões CLI; executar `php -v`, `which php` e `php -m` no Terminal cPanel.
- Confirmar as extensões do Laravel e Composer descritas em `CPANEL-COMPATIBILITY-CHECK.md`, em especial `pdo_mysql`.
- Confirmar a resolução DNS de `gamefinanceiro.com`. A primeira subida é em HTTP; certificado HTTPS válido é pendência para a troca de `APP_URL` e `SESSION_SECURE_COOKIE`, não para o teste inicial.
- Document Root informado pelo proprietário como já configurado em `/home1/denarius/gamefinanceiro.com/public`; conferir no cPanel antes de extrair o pacote.
- Confirmar usuário do banco associado a `denarius_gamefinanceiro` e grants necessários para migrations/leitura/escrita.
- Executar conexão, `SELECT VERSION()`, charset e consulta real/`EXPLAIN` do ranking no Percona 5.7.44-48; validar migrações em backup/staging seguro.
- Confirmar permissões mínimas de escrita em `storage/` e `bootstrap/cache/`, suporte a symlink para a topologia escolhida e caminho seguro fora do Document Root.
- Confirmar backup verificável de banco, `.env`, storage e release atual; responsável e procedimento de rollback.
- Confirmar suporte de manutenção/patching do Percona 5.7 EOL junto ao provedor.

Composer no servidor não é pendência para instalação: o ZIP deve incluir `vendor/` de produção. A disponibilidade do binário pode ser registrada, mas não pode mudar a estratégia sem nova aprovação.

## Pacote de produção

| Item | Valor |
|---|---|
| Arquivo | `dist/denarius-cacapalavras-5e1b0884.zip` (fora do Git; `/dist` está no `.gitignore`) |
| Tamanho | 21.092.659 bytes (21 MB); 14.474 arquivos |
| SHA-256 | `d921f5374e4c91b9bd3825cb3830c0d961bc88cbe11aafdf6a039acedeffba6b` |
| Branch / commit | `fix/cpanel-compatibility` / `5e1b088466f3bdc887dff159bfe7227e6aac524f` |
| Destino da extração | `/home1/denarius/gamefinanceiro.com` (conteúdo na raiz do ZIP: `artisan`, `app/`, `public/`, `vendor/`…) |
| `vendor/` | Incluído; `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction` em PHP 8.4.26; sem dependências de desenvolvimento; `platform_check.php` exige PHP 8.4.1+ |
| `public/build` | Incluído, com `manifest.json`; assets do Filament publicados em `public/css`, `public/js` e `public/fonts` |
| Excluídos | `.env` e `.env.example`, `.git/`, `.github/`, `node_modules/`, `tests/`, `phpunit.xml`, `docs/`, configs de IA/ferramentas, logs, dumps, `.DS_Store` |
| Caches | Sem `bootstrap/cache/config.php`, rotas ou views compiladas; só `packages.php`/`services.php` do install sem dev |
| Secrets | Varredura sem `APP_KEY=base64:`, senhas, tokens ou chaves privadas |

Como o pacote foi gerado (sem tocar no servidor):

```bash
git archive 5e1b088 | tar -x -C dist/stage-5e1b0884   # checkout limpo, sem .env/.git/node_modules
cp -R public/build dist/stage-5e1b0884/public/        # após pnpm install --frozen-lockfile && pnpm build
# remover do staging: tests, phpunit.xml, docs, .env.example, configs de ferramentas e de Node
docker run --rm -v "$PWD/dist/stage-5e1b0884:/app" -w /app denarius-php84-build \
  sh -c 'composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction && composer check-platform-reqs --no-dev'
(cd dist/stage-5e1b0884 && zip -qr -X ../denarius-cacapalavras-5e1b0884.zip .)
shasum -a 256 dist/denarius-cacapalavras-5e1b0884.zip
```

A imagem `denarius-php84-build` é `php:8.4-cli` com `intl`, `zip` e `pdo_mysql` e o Composer 2; o container é descartado com `--rm`. Antes de extrair no servidor, conferir o SHA-256 do arquivo enviado (`sha256sum denarius-cacapalavras-5e1b0884.zip`). Se não bater, abortar.

## Estratégia de release

- **Estratégia final: vendor incluído no ZIP.** O pacote acima já contém `vendor/` gerado em PHP 8.4.26; não rodar Composer no servidor.
- O ZIP inclui `app/`, `bootstrap/`, `config/`, `database/`, `public/` (com `build/manifest.json` e assets compilados), `resources/`, `routes/`, estrutura necessária de `storage/`, `vendor/`, `artisan`, `composer.json` e `composer.lock`.
- Excluir `.env`/segredos, `.git/`, `node_modules/`, testes, logs, caches de execução, dumps, backups, `auth.json` e arquivos temporários.
- `public/build/manifest.json` precisa existir. Build local com `pnpm install --frozen-lockfile` e `pnpm build`; Node/pnpm não vão para o servidor.
- Document Root: `/home1/denarius/gamefinanceiro.com/public`. Se releases/symlinks não estiverem disponíveis, interromper e obter aprovação de uma topologia privada segura; não improvisar paths.
- O `.env` será criado/configurado no diretório privado do servidor com `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=http://gamefinanceiro.com` na fase de teste sem SSL (decisão do proprietário em 2026-10-07; trocar para `https://` após SSL), DB host/port/name/user acima, segredo DB privado, sessão/cache database, queue sync, as variáveis de limite e de ranking do template em `PRODUCTION-ENV.md`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` e `SESSION_SECURE_COOKIE=true` somente após SSL confirmado. Gerar APP_KEY exclusiva uma única vez; nunca substituir chave existente de uma instalação ativa.

## Comandos autorizados de deploy

Estes comandos são para a Etapa 11, após autorização explícita, backup confirmado e check dos bloqueios; não foram executados neste check-up:

```bash
# no cPanel: validar o pacote enviado e extrair em /home1/denarius/gamefinanceiro.com
sha256sum denarius-cacapalavras-5e1b0884.zip   # deve ser d921f5374e4c91b9bd3825cb3830c0d961bc88cbe11aafdf6a039acedeffba6b

# no cPanel, sempre com o binário PHP 8.4.1+ confirmado
<PHP84_CLI> artisan about
<PHP84_CLI> artisan migrate:status
<PHP84_CLI> artisan migrate --force
<PHP84_CLI> artisan db:seed --class=AdminUserSeeder --force
<PHP84_CLI> artisan db:seed --class=FinancialTermSeeder --force
<PHP84_CLI> artisan optimize:clear
<PHP84_CLI> artisan config:cache
<PHP84_CLI> artisan route:cache
<PHP84_CLI> artisan view:cache
```

Antes das migrations: backup, validar conexão, revisar `migrate:status` e migration pendente. APP_KEY só é gerada se ainda não houver uma instalação/chave, via `<PHP84_CLI> artisan key:generate`, nunca sobre uma chave ativa. Depois, executar checklist de smoke test de `/`, login/cadastro, `/game`, `/ranking`, `/admin`, `/up`, acerto/score/conclusão/replay/abandono e logout.

## Critérios de abortar

Parar e não apontar tráfego se qualquer item ocorrer:

- PHP Web ou CLI abaixo de 8.4.1, incompatível ou sem `pdo_mysql`/extensão obrigatória.
- Document Root não puder apontar ao `public/` seguro indicado; `.env`/código privado ficaria acessível pela web.
- HTTPS válido não estiver pronto antes de ativar cookie `Secure` (na fase HTTP, `SESSION_SECURE_COOKIE=false`).
- SHA-256 do ZIP no servidor diferente de `d921f5374e4c91b9bd3825cb3830c0d961bc88cbe11aafdf6a039acedeffba6b`.
- Banco não for o Percona 5.7.44-48 informado, conexão/grants falharem, charset não for utf8mb4, consulta do ranking ou migration falhar.
- Não houver backup validado e rollback viável; storage/cache não forem graváveis com permissões mínimas.
- ZIP não contiver `vendor/` compatível e `public/build/manifest.json`, ou contiver segredos, `.git`, `node_modules`, dumps/backups.
- Testes/build/audits da base aprovada falharem, `APP_DEBUG` estiver ligado, APP_KEY for reutilizada/ausente, ou for necessário mexer em regra/schema sem aprovação.
- Provedor não esclarecer a cobertura de segurança do Percona 5.7 EOL, caso isso represente risco sem mitigação aceita pelo proprietário.

Ao abortar: manter a versão anterior ativa e dados intactos; registrar evidência e solicitar decisão. Não executar deploy até receber nova autorização; este contrato não autoriza qualquer etapa adicional de implementação.
