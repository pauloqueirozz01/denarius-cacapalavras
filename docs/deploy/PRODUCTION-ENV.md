# `.env` de produção — cPanel

Template e justificativa das variáveis do `.env` de produção. Este arquivo não contém segredos e não substitui o `.env` real, que existe somente no servidor, em `/home1/denarius/gamefinanceiro.com/.env` (fora do Document Root `/home1/denarius/gamefinanceiro.com/public`).

## Ambiente cPanel confirmado

| Item | Valor |
|---|---|
| Domínio | `gamefinanceiro.com` (HTTP enquanto não houver SSL) |
| Diretório da aplicação | `/home1/denarius/gamefinanceiro.com` |
| Document Root | `/home1/denarius/gamefinanceiro.com/public` |
| PHP Web | PHP 8.4 (`ea-php84`); o `composer.lock` exige 8.4.1 ou superior |
| Banco | Percona Server 5.7.44-48, `utf8mb4` / `utf8mb4_unicode_ci` |
| Database | `denarius_gamefinanceiro` |
| Usuário | `denarius_financeuser` |
| Host / porta | `localhost` / `3306` |
| Sessão / cache | `database` |
| Fila | `sync` (sem worker, sem cron) |

A primeira subida é feita em **HTTP**, sem SSL, para teste (decisão do proprietário em 2026-10-07).

Este template vale só para o servidor. O `.env` local de desenvolvimento e o `.env.example` continuam com a configuração local e não devem receber estes valores.

## Template

Valores entre `<...>` são preenchidos manualmente no servidor. Nunca registrar o valor real no Git, em documentação ou em relatórios.

```dotenv
APP_NAME="Denarius Caça-Palavras"
APP_ENV=production
# Deixar vazio: gerado uma única vez no servidor por key:generate.
APP_KEY=
APP_DEBUG=false
APP_URL=http://gamefinanceiro.com

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=denarius_gamefinanceiro
DB_USERNAME=denarius_financeuser
DB_PASSWORD='<PREENCHER_NO_SERVIDOR>'

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
# Fase HTTP: false. Após SSL: APP_URL=https://... e true + config:cache.
SESSION_SECURE_COOKIE=false

CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log

# Evento: 100–300 pessoas na mesma rede (ver "Limites para o evento").
AUTH_LOGIN_MAX_ATTEMPTS=5
AUTH_LOGIN_DECAY_SECONDS=60
AUTH_LOGIN_IP_MAX_ATTEMPTS=300
AUTH_REGISTER_EMAIL_MAX_ATTEMPTS=5
AUTH_REGISTER_IP_MAX_ATTEMPTS=120
AUTH_REGISTER_DECAY_MINUTES=1
LEADERBOARD_POLL_INTERVAL_SECONDS=10
LEADERBOARD_CACHE_SECONDS=10

# Somente durante a criação do primeiro administrador; remover em seguida.
ADMIN_NAME='<PREENCHER_NO_SERVIDOR>'
ADMIN_EMAIL='<PREENCHER_NO_SERVIDOR>'
ADMIN_PASSWORD='<PREENCHER_NO_SERVIDOR>'
```

## Por que cada variável é obrigatória

| Variável                                                                                        | Default do código                  | Motivo para definir explicitamente                                                                                                                               |
| ----------------------------------------------------------------------------------------------- | ---------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `APP_NAME`                                                                                      | `Laravel`                          | Título das páginas (`layouts/app.blade.php`), nome do cookie de sessão e prefixo do cache. Aspas obrigatórias por causa do espaço e do `ç`.                      |
| `APP_ENV`                                                                                       | `production`                       | Explícito para não depender do default.                                                                                                                          |
| `APP_KEY`                                                                                       | nenhum                             | Sem chave a aplicação não criptografa sessão/cookies. Gerada no servidor (ver abaixo).                                                                           |
| `APP_DEBUG`                                                                                     | `false`                            | Explícito; `true` em produção expõe stack trace e é critério de abortar.                                                                                         |
| `APP_URL`                                                                                       | `http://localhost`                 | Geração de URLs, assets e redirects.                                                                                                                             |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE`                                                            | `en`                               | O app é em português; o default seria inglês.                                                                                                                    |
| `LOG_CHANNEL` / `LOG_STACK`                                                                     | `stack` / `single`                 | Iguais ao default; mantidos para deixar o destino do log (`storage/logs/laravel.log`) explícito.                                                                 |
| `LOG_LEVEL`                                                                                     | `debug`                            | Em produção, `warning` evita log verboso e vazamento de detalhes.                                                                                                |
| `DB_*`                                                                                          | `127.0.0.1`, `laravel`, `root`     | Defaults não correspondem ao cPanel. Charset e collation (`utf8mb4` / `utf8mb4_unicode_ci`) já são default em `config/database.php` e não precisam ir no `.env`. |
| `SESSION_DRIVER`                                                                                | `database`                         | Igual ao default; explícito porque a tabela `sessions` precisa existir (criada em `0001_01_01_000000_create_users_table`).                                       |
| `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_PATH`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE` | `120`, `false`, `/`, `true`, `lax` | Iguais ao default; explícitos para auditoria.                                                                                                                    |
| `SESSION_DOMAIN`                                                                                | `null`                             | `null` (ou vazio) gera cookie restrito ao host `gamefinanceiro.com`; não é necessário compartilhar com subdomínios.                                              |
| `SESSION_SECURE_COOKIE`                                                                         | `null`                             | Ver seção SSL.                                                                                                                                                   |
| `CACHE_STORE`                                                                                   | `database`                         | Igual ao default; tabelas `cache`/`cache_locks` vêm de `0001_01_01_000001_create_cache_table`.                                                                   |
| `QUEUE_CONNECTION`                                                                              | **`database`**                     | **Obrigatório**: o default exigiria tabela de jobs e worker. O app não despacha jobs, então `sync` dispensa worker e cron.                                       |
| `MAIL_MAILER`                                                                                   | `log`                              | Igual ao default; explícito para garantir que nada seja enviado.                                                                                                 |

## Variáveis opcionais

Têm default adequado no código e só devem ser adicionadas se houver motivo:

- As variáveis de limite e ranking da seção abaixo já têm esses mesmos valores como default em `config/denarius.php`. Ficam no template para que um ajuste no dia do evento seja só editar o `.env` e rodar `config:cache`.
- `LOG_STACK=daily` com `LOG_DAILY_DAYS=14`: rotaciona o log se o arquivo único crescer demais.
- `SESSION_LIFETIME`: aumentar se partidas longas causarem logout.

## Limites para o evento

Cenário: 100–300 pessoas na mesma rede Wi-Fi (um IP público) ou atrás de CGNAT no 4G, cadastrando, jogando e com o ranking aberto ao mesmo tempo.

| Variável | Valor | Por quê |
|---|---|---|
| `AUTH_REGISTER_EMAIL_MAX_ATTEMPTS` | `5` por minuto | Por e-mail + IP. Barra quem insiste com o mesmo e-mail; a pessoa volta ao formulário com os dados preenchidos e a mensagem com os segundos de espera. |
| `AUTH_REGISTER_IP_MAX_ATTEMPTS` | `120` por minuto | Teto da rede inteira. Comporta uma sala de 300 pessoas se cadastrando em poucos minutos, incluindo reenvios por senha fraca; acima disso, é flood (página 429 em pt-BR). O antigo `3` por IP barrava a 4ª pessoa da sala. |
| `AUTH_LOGIN_MAX_ATTEMPTS` / `AUTH_LOGIN_DECAY_SECONDS` | `5` em `60` s | Falhas por e-mail + IP, sem mudança: protege cada conta contra tentativa de senha. |
| `AUTH_LOGIN_IP_MAX_ATTEMPTS` | `300` por minuto | Teto da rede para todas as tentativas de login, com ou sem sucesso. Um evento inteiro entrando de uma vez cabe; um robô testando muitos e-mails, não. |
| `LEADERBOARD_POLL_INTERVAL_SECONDS` | `10` | Cada ranking aberto consulta o servidor nesse intervalo, e só com a aba visível (`wire:poll.visible`). |
| `LEADERBOARD_CACHE_SECONDS` | `10` | O ranking inteiro fica em cache (`CACHE_STORE=database`) por esse tempo. A consulta pesada roda uma vez por intervalo para todos, não uma vez por celular. Depois de concluir uma partida, a tela de resultado mostra a posição na hora; a página do ranking, em até 10 s. |

Se o evento crescer ou a rede tiver mais gente, aumente os dois tetos por IP. Se o servidor ficar lento com muitos rankings abertos, aumente `LEADERBOARD_CACHE_SECONDS` e `LEADERBOARD_POLL_INTERVAL_SECONDS` (por exemplo para 15). Depois de editar o `.env`, rode `<PHP84_CLI> artisan config:cache`.

As chaves de rate limit e o cache do ranking ficam na tabela `cache`. Sem cron, linhas vencidas não são apagadas automaticamente, mas cada chave é reaproveitada e o volume de um evento é pequeno. Se quiser limpar depois do evento: `<PHP84_CLI> artisan cache:clear`.

## Variáveis do `.env.example` que não vão para produção

- `DB_ROOT_PASSWORD`, `FORWARD_DB_PORT`, `PHPMYADMIN_PORT`: usadas somente pelo `compose.yaml` local.
- `APP_FAKER_LOCALE`: só para factories; nenhum dado fake é gerado em produção.
- `APP_MAINTENANCE_DRIVER`, `BCRYPT_ROUNDS`, `BROADCAST_CONNECTION`, `FILESYSTEM_DISK`: defaults do framework já servem.
- `MEMCACHED_*`, `REDIS_*`, `AWS_*`: serviços não usados.
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`, `MAIL_FROM_*`: o MVP não envia e-mail (sem reset de senha, verificação de e-mail, notificações ou `Mail::`). Não configurar SMTP.
- `VITE_APP_NAME`: não é lido por nenhum código em `resources/`; o build é feito localmente e enviado em `public/build/`. Não existe `VITE_DEV_SERVER`; `public/hot` não pode ir para o servidor.

## Valores com caracteres especiais

`DB_PASSWORD` e `ADMIN_PASSWORD` devem ficar entre **aspas simples** no `.env`. Assim `#` não vira comentário e `$` não é interpretado como variável. Se a senha contiver aspas simples, trocar a senha em vez de escapar.

## APP_KEY

- Deixar `APP_KEY=` vazio ao criar o `.env` e rodar uma única vez, no servidor, com o PHP CLI 8.4.1+ confirmado: `<PHP84_CLI> artisan key:generate --force`.
- Não copiar a chave local, não reutilizar chave de outra instalação, não imprimir a chave em relatórios.
- Nunca rodar `key:generate` de novo numa instalação ativa: invalida todas as sessões.

## Administrador inicial

`AdminUserSeeder` lê `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` via `config/denarius.php`. Se qualquer um estiver vazio, o seeder apenas avisa e não cria nada. Se o e-mail já existir, não sobrescreve. A senha precisa de no mínimo 12 caracteres com maiúsculas, minúsculas, números e símbolos.

Ordem segura:

1. Preencher as três variáveis no `.env` do servidor.
2. `<PHP84_CLI> artisan optimize:clear` (garante que o config não esteja em cache).
3. `<PHP84_CLI> artisan db:seed --class=AdminUserSeeder --force`.
4. Apagar as três linhas `ADMIN_*` do `.env` (ou deixá-las vazias). Depois da criação elas não são mais usadas.
5. Só então `<PHP84_CLI> artisan config:cache`.

**Importante:** `config:cache` grava os valores em `bootstrap/cache/config.php`. Se for executado com `ADMIN_PASSWORD` preenchida, a senha fica em texto puro nesse arquivo. Por isso o cache vem depois de remover as variáveis.

## SSL e cookies

Em 2026-10-07, `gamefinanceiro.com` retornava **NXDOMAIN** nos resolvedores públicos: não há DNS nem certificado verificável. O proprietário decidiu subir primeiro em HTTP para teste, com `APP_URL=http://gamefinanceiro.com` e `SESSION_SECURE_COOKIE=false`. Com `true` sem HTTPS, o navegador descartaria o cookie de sessão e login/CSRF falhariam.

Na fase HTTP, senhas e cookie de sessão trafegam sem criptografia. Usar só para teste: contas e senhas descartáveis, sem participantes reais, e não ativar Force HTTPS Redirect.

**Ao confirmar o HTTPS**, trocar para `APP_URL=https://gamefinanceiro.com` e `SESSION_SECURE_COOKIE=true`, depois rodar `<PHP84_CLI> artisan config:clear` e `config:cache`. Configuração final esperada: `SESSION_HTTP_ONLY=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`. Não usar `SESSION_SAME_SITE=none`.

Nenhum TrustProxies ou `URL::forceScheme` foi adicionado, porque não há evidência de proxy reverso na frente do PHP. Em cPanel típico o próprio Apache/LiteSpeed termina o TLS e informa `HTTPS=on` ao PHP, mas isso não foi verificado neste host. Só reavaliar se, após o deploy com SSL, o Laravel gerar URLs `http://`, redirecionar em loop ou emitir cookie sem `Secure`.

## Permissões do arquivo

O `.env` deve pertencer ao usuário cPanel, com permissão restritiva (`600` ou `640`, conforme o handler PHP). Nunca colocá-lo dentro de `public/`.
