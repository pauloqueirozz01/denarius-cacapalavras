# ETAPA 11 CONTRACT

Este contrato é a fonte de verdade para o agente que fará o deploy. Foi produzido pelo check-up de compatibilidade cPanel, não autoriza executar deploy agora e não autoriza alterar produção sem confirmação explícita do proprietário.

## Base aprovada

- Branch de trabalho: `fix/cpanel-compatibility`.
- Commit funcional aprovado: `f2f3c48 fix: ensure cpanel production compatibility`.
- SHA: `f2f3c48` (código/runtime); o commit documental desta passagem só atualiza instruções e contrato. Etapa 11 deve verificar e usar a ponta publicada da branch `fix/cpanel-compatibility`.
- Testes: **198 PHPUnit / 1.327 assertions**, 0 falhas; baseline anterior 197 / 1.325.
- JavaScript: **5 testes aprovados**, 0 falhas.
- Build: `pnpm build` aprovado e `public/build/manifest.json` presente. O agente da Etapa 11 ainda deve revalidar após checkout.
- Pint, Composer validate/platform e Composer/pnpm audits passaram no ambiente local PHP 8.5.11.
- O ranking passou na suíte SQLite; Percona real, PHP 8.4.1 real e host ainda não foram testados. A readiness segue **BLOCKED** até fechar as pendências abaixo.
- A branch é separada de `main`; não fazer merge automático.

## Compatibilidade

- PHP mínimo efetivo do lock: **8.4.1**. `composer.json` exige `^8.4.1`. PHP 8.3 e PHP 8.4.0 não são aceitos para esta release.
- PHP alvo: **8.4.1 ou superior dentro do intervalo permitido**, tanto no handler Web quanto no CLI de Artisan. A versão/path efetivos do host ainda precisam ser confirmados.
- Banco alvo: database `denarius_gamefinanceiro`, user `denarius_financeirouser`, host `localhost`, porta `3306` (valores informados pelo proprietário; associação/grants pendentes).
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

- Confirmar PHP Web efetivo e patch **8.4.1+**, handler e extensões selecionadas no domínio.
- Confirmar PHP CLI/caminho `8.4.1+` e extensões CLI; executar `php -v`, `which php` e `php -m` no Terminal cPanel.
- Confirmar as extensões do Laravel e Composer descritas em `CPANEL-COMPATIBILITY-CHECK.md`, em especial `pdo_mysql`.
- Confirmar resolução DNS e certificado HTTPS válido/renovação para `gamedaoncinha.com`.
- Configurar/confirmar Document Root final exatamente em `/home1/denarius/gamefinanceiro.com/public`. O diretório raiz atual informado não termina em `/public`; não prosseguir enquanto isso não estiver resolvido.
- Confirmar usuário do banco associado a `denarius_gamefinanceiro` e grants necessários para migrations/leitura/escrita.
- Executar conexão, `SELECT VERSION()`, charset e consulta real/`EXPLAIN` do ranking no Percona 5.7.44-48; validar migrações em backup/staging seguro.
- Confirmar permissões mínimas de escrita em `storage/` e `bootstrap/cache/`, suporte a symlink para a topologia escolhida e caminho seguro fora do Document Root.
- Confirmar backup verificável de banco, `.env`, storage e release atual; responsável e procedimento de rollback.
- Confirmar suporte de manutenção/patching do Percona 5.7 EOL junto ao provedor.

Composer no servidor não é pendência para instalação: o ZIP deve incluir `vendor/` de produção. A disponibilidade do binário pode ser registrada, mas não pode mudar a estratégia sem nova aprovação.

## Estratégia de release

- **Estratégia final: vendor incluído no ZIP.** Gerar `vendor/` sem dependências de desenvolvimento usando o lock aprovado e PHP 8.4.1+ em ambiente de build controlado: `composer install --no-dev --prefer-dist --optimize-autoloader`.
- O ZIP inclui `app/`, `bootstrap/`, `config/`, `database/`, `public/` (com `build/manifest.json` e assets compilados), `resources/`, `routes/`, estrutura necessária de `storage/`, `vendor/`, `artisan`, `composer.json` e `composer.lock`.
- Excluir `.env`/segredos, `.git/`, `node_modules/`, testes, logs, caches de execução, dumps, backups, `auth.json` e arquivos temporários.
- `public/build/manifest.json` precisa existir. Build local com `pnpm install --frozen-lockfile` e `pnpm build`; Node/pnpm não vão para o servidor.
- Document Root: `/home1/denarius/gamefinanceiro.com/public`. Se releases/symlinks não estiverem disponíveis, interromper e obter aprovação de uma topologia privada segura; não improvisar paths.
- O `.env` será criado/configurado no diretório privado do servidor com `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://gamedaoncinha.com`, DB host/port/name/user acima, segredo DB privado, sessão/cache database, queue sync, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` e `SESSION_SECURE_COOKIE=true` somente após SSL confirmado. Gerar APP_KEY exclusiva uma única vez; nunca substituir chave existente de uma instalação ativa.

## Comandos autorizados de deploy

Estes comandos são para a Etapa 11, após autorização explícita, backup confirmado e check dos bloqueios; não foram executados neste check-up:

```bash
# build fora do servidor, em PHP 8.4.1+ e checkout limpo
composer install --no-dev --prefer-dist --optimize-autoloader
pnpm install --frozen-lockfile
pnpm build
test -f public/build/manifest.json

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
- HTTPS válido não estiver pronto antes de ativar cookie `Secure`.
- Banco não for o Percona 5.7.44-48 informado, conexão/grants falharem, charset não for utf8mb4, consulta do ranking ou migration falhar.
- Não houver backup validado e rollback viável; storage/cache não forem graváveis com permissões mínimas.
- ZIP não contiver `vendor/` compatível e `public/build/manifest.json`, ou contiver segredos, `.git`, `node_modules`, dumps/backups.
- Testes/build/audits da base aprovada falharem, `APP_DEBUG` estiver ligado, APP_KEY for reutilizada/ausente, ou for necessário mexer em regra/schema sem aprovação.
- Provedor não esclarecer a cobertura de segurança do Percona 5.7 EOL, caso isso represente risco sem mitigação aceita pelo proprietário.

Ao abortar: manter a versão anterior ativa e dados intactos; registrar evidência e solicitar decisão. Não executar deploy até receber nova autorização; este contrato não autoriza qualquer etapa adicional de implementação.
