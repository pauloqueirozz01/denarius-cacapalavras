# Roteiro de release cPanel — Etapa 11

Este roteiro prepara a execução futura; nada abaixo foi executado em produção. A Etapa 11 só começa após confirmar todos os bloqueios de [readiness](CPANEL-READINESS.md) e obter autorização explícita para o deploy.

## Pré-condições obrigatórias

1. Domínio/DNS criados, HTTPS válido e renovação automática confirmada.
2. PHP Web e CLI usados pelo Artisan em PHP 8.4.1+ compatíveis com o `composer.lock`, com todas as extensões obrigatórias disponíveis.
3. Document Root configurado diretamente para o diretório `public` da release. Nunca apontar para a raiz Laravel nem colocar `.env`, `vendor`, `.git`, `storage` ou `config` sob `public_html`.
4. Banco `denarius_gamefinanceiro` / Percona Server 5.7.44-48; usuário `denarius_financeuser` associado com privilégios necessários; conexão, migrations e consulta do ranking sem window functions ainda devem ser validadas nesse host. A consulta não usa `ROW_NUMBER()` nem exige MySQL 8.
5. PHP Web e CLI efetivos em PHP 8.4.1 ou superior, compatíveis entre si; extensões e `pdo_mysql` confirmados. O `composer.lock` atual exige `>=8.4.1` apesar de a versão base do Laravel aceitar PHP 8.3.
6. Suporte a symlinks confirmado antes de adotar `current`; se indisponível, parar e aprovar uma topologia alternativa segura antes de publicar.
7. Backup validado de banco, `.env` atual e arquivos da versão em produção. Definir responsável e janela de rollback.

## Preparar release local

Executar em checkout limpo da branch/release aprovada, nunca em produção:

```bash
git status --short
composer validate --strict
composer install --prefer-dist --optimize-autoloader
pnpm install --frozen-lockfile
php artisan test --compact
pnpm test:js
./vendor/bin/pint --test
pnpm build
composer audit --locked
pnpm audit --audit-level=high
git diff --check
test -f public/build/manifest.json
```

Somente depois dos testes, montar a release numa cópia/staging separado. A estratégia aprovada neste contrato é incluir `vendor/` no ZIP, pois Composer no cPanel não foi confirmado e não será requisito de runtime:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
zip -r denarius-release.zip app bootstrap config database public resources routes storage vendor artisan composer.json composer.lock \
  -x 'storage/logs/*' 'storage/framework/cache/data/*' 'storage/framework/sessions/*' 'storage/framework/views/*' 'database/*.sqlite*'
```

Inspecionar o conteúdo com `unzip -l denarius-release.zip` antes do upload. O ZIP não deve conter `.env`, `.git`, `node_modules`, `tests`, dumps, credenciais ou backups. Não gerar o artefato final até a versão PHP-alvo estar confirmada.

Gerar `vendor/` em ambiente controlado com PHP 8.4.1+ e o `composer.lock` aprovado. Composer no servidor é desconhecido, não deve ser pressuposto nem necessário. Se não houver ambiente de build compatível, não gerar nem publicar o pacote final.

Node/pnpm não são requisito de runtime. `public/build/manifest.json` e os assets versionados em `public/build/assets/` devem estar no pacote.

O ZIP deve conter a aplicação Laravel em allowlist: `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/` (estrutura necessária), `vendor/`, `artisan`, `composer.json` e `composer.lock`. Não incluir `.env`, `.env.*` preenchidos, `.git/`, `node_modules/`, `tests/`, caches de build locais, logs, dumps, backups, `auth.json` nem arquivos temporários. O arquivo `.env.example` é referência local, não configuração de servidor.

Revise o conteúdo do ZIP antes de enviar. O ZIP deve ser montado fora da árvore pública e nenhum segredo deve fazer parte dele.

## Estrutura privada proposta

Usar somente depois de confirmar permissões e symlinks:

```text
/home/CPANEL_USER/denarius/
├── releases/RELEASE_ID/       # código da versão, fora do Document Root
├── shared/.env                # configuração e segredos, fora do Document Root
├── shared/storage/            # logs, sessões/arquivos locais se necessários
└── current -> releases/RELEASE_ID

Document Root do domínio:
/home/CPANEL_USER/denarius/current/public
```

Não substituir o `public/index.php` nem improvisar caminhos relativos se o cPanel impedir o Document Root correto. Isso é bloqueio de hospedagem, não uma etapa para contornar sem revisão.

## Instalação de uma release

Os exemplos usam marcadores, não caminhos de servidor reais. Substituir pelo caminho de PHP CLI efetivamente confirmado no cPanel; nunca presumir que `php` aponta para a versão do domínio.

1. Fazer backup do banco, `.env`, storage e release ativa.
2. Enviar e extrair a release em `releases/RELEASE_ID/`, fora do diretório público.
3. Preparar `.env` privado. Valores mínimos:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://gamefinanceiro.com
APP_KEY=GERAR_NO_SERVIDOR
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=denarius_gamefinanceiro
DB_USERNAME=denarius_financeuser
DB_PASSWORD=SEGREDO_EXCLUSIVO
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync
```

Usar senha exclusiva, nunca valores locais do exemplo. O domínio é `gamefinanceiro.com`. A primeira subida é em HTTP, só para teste, com `SESSION_SECURE_COOKIE=false`; após DNS e HTTPS válidos, trocar para `APP_URL=https://gamefinanceiro.com` e `SESSION_SECURE_COOKIE=true` e recriar o config cache. Template completo em [PRODUCTION-ENV.md](PRODUCTION-ENV.md). O Document Root alvo é `/home1/denarius/gamefinanceiro.com/public`, mas não foi alterado nesta auditoria. Proteger o `.env` com proprietário correto e permissões restritivas compatíveis com o handler PHP; nunca colocá-lo sob Document Root.

4. Criar a chave uma única vez se o `.env` novo não tiver chave: `<PHP_CLI> artisan key:generate`. Não substituir uma chave já em uso; isso invalida sessões e dados criptografados.
5. Confirmar `vendor/` de produção íntegro no pacote; a estratégia aprovada inclui `vendor/` e não executa Composer no host.
6. Apontar o shared storage se a topologia aprovada usar symlink e ajustar apenas permissões necessárias para o usuário PHP poder gravar em `storage/` e `bootstrap/cache/`. Nunca usar `chmod 777`.
7. Confirmar configuração carregada com `artisan about` e validar conexão/migrations:

```bash
<PHP_CLI> artisan migrate:status
```

8. Somente após backup confirmado e revisão das migrations pendentes, aplicar:

```bash
<PHP_CLI> artisan migrate --force
```

Nunca usar `migrate:fresh`, `migrate:refresh` ou `db:wipe` em produção. Não editar schema manualmente pelo phpMyAdmin.

9. Seed inicial: usar `ADMIN_NAME`, `ADMIN_EMAIL` e senha forte em configuração privada temporária; garantir antes que o e-mail não pertence a outra conta. Executar `db:seed --class=AdminUserSeeder --force` e `db:seed --class=FinancialTermSeeder --force`. Remover `ADMIN_PASSWORD` do ambiente privado e limpar configuração carregada antes de cachear. Não executar factories nem seeders de usuários fictícios.
10. Executar cache de produção só com `.env` final e migrations concluídas:

```bash
<PHP_CLI> artisan optimize:clear
<PHP_CLI> artisan config:cache
<PHP_CLI> artisan route:cache
<PHP_CLI> artisan view:cache
```

Se algum comando falhar, não seguir com tráfego; diagnosticar e reverter a release. `storage:link` não é necessário para os assets atuais (não há upload público).

11. Ativar o `current` somente depois de validar a nova release e o Document Root. Manter a release anterior intacta até concluir smoke tests.

## Smoke tests obrigatórios após publicação

- `https://gamefinanceiro.com/`, `/login`, `/register` e `/up` respondem sem stack trace.
- Visitante é redirecionado ao login em `/game` e `/ranking`; `/admin` não revela conteúdo.
- Criar participante, entrar e sair; cadastro público não pode definir papel `admin`.
- Administrador entra no Filament, cria/edita/desativa termo e consulta usuários/partidas; score, role e snapshots não são editáveis.
- Iniciar, retomar após reload, acertar/errar/repetir palavra, concluir, conferir score e ranking, iniciar nova partida e abandonar outra.
- Verificar TLS/certificado, cookies `Secure`/`HttpOnly`/`SameSite`, cabeçalhos de segurança e inexistência de arquivos proibidos publicamente.
- Conferir `migrate:status`, logs graváveis sem informação sensível e ausência de erros PHP/Livewire.
- Testar ranking sob polling durante evento de teste e monitorar consulta/carga; polling não deve gravar dados.

## Rollback

1. Se a nova versão falhar nos smoke tests, parar as mudanças e voltar o Document Root/`current` para a release anterior sem apagá-la.
2. Restaurar o `.env` de backup somente se houver alteração acidental, preservando a chave que corresponde aos dados criptografados existentes.
3. Restaurar banco de backup apenas após avaliar os dados criados depois do backup e obter autorização operacional; nunca apagar dados recentes automaticamente.
4. Não executar rollback automático de migrations. Migrations desta versão devem ser avaliadas uma a uma; priorizar hotfix forward ou restauração coordenada com backup.
5. Registrar horário, release, erro, decisão, responsável e resultado de cada ação.

O roteiro precisa ser ajustado com os caminhos e procedimentos reais do provedor antes de ser executado. A Etapa 10 não autoriza execução em produção.
