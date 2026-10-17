#!/bin/bash
# Deploy do Denarius Caça-Palavras no cPanel.
# Executado pelo Git™ Version Control (Deploy HEAD Commit) via .cpanel.yml.
# Copia a release do repositório para o diretório da aplicação, preservando
# .env, storage/ e bootstrap/cache/, e roda migrations e caches de produção.
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ACCOUNT_HOME="$(dirname "$(dirname "$REPO_DIR")")"
APP_DIR="${ACCOUNT_HOME}/gamefinanceiro.denariusstartup.com.br"
# DENARIUS_PHP_BIN só serve para simular o deploy localmente.
PHP_BIN="${DENARIUS_PHP_BIN:-/usr/local/bin/ea-php84}"
LOG_FILE="${ACCOUNT_HOME}/denarius-deploy.log"

exec >>"$LOG_FILE" 2>&1
echo "=== $(date '+%Y-%m-%d %H:%M:%S') release $(cat "$REPO_DIR/RELEASE" 2>/dev/null || echo desconhecida)"

fail() {
    echo "ERRO: $*"
    exit 1
}

# Pré-condições: nada é alterado se alguma falhar.
[ -x "$PHP_BIN" ] || fail "PHP CLI não encontrado em $PHP_BIN (instalar ea-php84 no EasyApache 4)."
"$PHP_BIN" -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);' \
    || fail "PHP CLI precisa ser 8.4.1 ou superior."
[ -f "$APP_DIR/.env" ] || fail "Crie $APP_DIR/.env antes do deploy (modelo em docs/deploy/PRODUCTION-ENV.md)."
command -v rsync >/dev/null || fail "rsync não disponível no servidor."
[ -f "$REPO_DIR/vendor/autoload.php" ] || fail "vendor/ ausente na release; use a branch deploy."

mkdir -p \
    "$APP_DIR/storage/app/public" \
    "$APP_DIR/storage/framework/cache/data" \
    "$APP_DIR/storage/framework/sessions" \
    "$APP_DIR/storage/framework/views" \
    "$APP_DIR/storage/logs" \
    "$APP_DIR/bootstrap/cache"

if [ -f "$APP_DIR/vendor/autoload.php" ] && [ -f "$APP_DIR/artisan" ]; then
    "$PHP_BIN" "$APP_DIR/artisan" down --retry=15 || true
fi

# A partir daqui, uma falha deixa o site em manutenção até a correção.
for dir in app config database resources routes vendor; do
    rsync -a --delete "$REPO_DIR/$dir/" "$APP_DIR/$dir/"
done

rsync -a --delete --exclude 'cache/' "$REPO_DIR/bootstrap/" "$APP_DIR/bootstrap/"

# O MultiPHP Manager (handlers não FPM) grava a versão do PHP num bloco do
# public/.htaccess; ele é preservado ao substituir o arquivo da release.
HTACCESS="$APP_DIR/public/.htaccess"
HANDLER_BLOCK=""
if [ -f "$HTACCESS" ]; then
    HANDLER_BLOCK="$(sed -n '/^# php -- BEGIN cPanel-generated handler/,/^# php -- END cPanel-generated handler/p' "$HTACCESS")"
fi

# Arquivos que o cPanel cria na raiz pública não fazem parte da release.
rsync -a --delete \
    --exclude '.well-known/' \
    --exclude 'cgi-bin/' \
    --exclude '.user.ini' \
    --exclude 'php.ini' \
    --exclude 'error_log' \
    "$REPO_DIR/public/" "$APP_DIR/public/"

if [ -n "$HANDLER_BLOCK" ]; then
    printf '\n%s\n' "$HANDLER_BLOCK" >> "$HTACCESS"
fi

cp "$REPO_DIR/artisan" "$REPO_DIR/composer.json" "$REPO_DIR/composer.lock" "$REPO_DIR/RELEASE" "$APP_DIR/"

cd "$APP_DIR"

# Com CACHE_STORE=database, a limpeza completa só funciona depois das
# migrations: num banco novo a tabela cache ainda não existe.
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan optimize:clear

# O catálogo inicial entra só no primeiro deploy, para não recriar termos
# que o administrador tenha removido depois.
if [ ! -f storage/app/.financial-terms-seeded ]; then
    "$PHP_BIN" artisan db:seed --class=FinancialTermSeeder --force
    touch storage/app/.financial-terms-seeded
fi

if grep -Eq "^[[:space:]]*ADMIN_PASSWORD=['\"]?[^'\"[:space:]#]" .env; then
    "$PHP_BIN" artisan db:seed --class=AdminUserSeeder --force
    echo "AVISO: ADMIN_* preenchido no .env. Caches de configuração não foram gerados."
    echo "AVISO: remova ADMIN_NAME, ADMIN_EMAIL e ADMIN_PASSWORD do .env e faça o deploy de novo."
else
    "$PHP_BIN" artisan optimize
fi

"$PHP_BIN" artisan up
echo "=== deploy concluído"
