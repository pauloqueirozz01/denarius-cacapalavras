#!/bin/bash
# Monta uma release (código + vendor/ PHP 8.4 + public/build) e grava um commit
# novo na branch local "deploy". Não faz push.
# Uso: bash docs/deploy/build-release.sh . origin/<branch_aprovada>
# Requer Docker, pnpm e um clone com o remote origin.
set -euo pipefail

REPO="$(cd "$1" && pwd)"
SOURCE_REF="$2"
KIT="$(cd "$(dirname "$0")" && pwd)"
WORK="$(mktemp -d)"
SRC="$WORK/src"
TREE="$WORK/deploy-tree"
trap 'git -C "$REPO" worktree remove --force "$TREE" 2>/dev/null || true; rm -rf "$WORK"' EXIT

SHA="$(git -C "$REPO" rev-parse "$SOURCE_REF")"
SHORT="${SHA:0:8}"

docker build -q -t denarius-php84-build "$KIT" >/dev/null

git -C "$REPO" worktree prune
mkdir -p "$SRC"
git -C "$REPO" archive "$SHA" | tar -x -C "$SRC"

(cd "$SRC" && pnpm install --frozen-lockfile --silent && pnpm build)

# Composer roda no filesystem do container (bind mount no macOS falha em
# extrações grandes); vendor/ e assets do Filament voltam por tar no stdout.
tar -C "$SRC" -cf - . | docker run --rm -i denarius-php84-build bash -c '
    set -e
    mkdir /app && tar -C /app -xf - && cd /app
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction >&2
    composer check-platform-reqs --no-dev >&2
    tar -cf - vendor public/js/filament public/css/filament public/fonts/filament
' | tar -C "$SRC" -xf -

test -f "$SRC/public/build/manifest.json"
test -d "$SRC/public/js/filament"

# Worktree da branch deploy: continua o histórico publicado (o cPanel só
# atualiza por fast-forward) e só cria uma branch órfã na primeira release.
git -C "$REPO" fetch -q origin
HAS_LOCAL=0; HAS_REMOTE=0
git -C "$REPO" show-ref --verify --quiet refs/heads/deploy && HAS_LOCAL=1
git -C "$REPO" show-ref --verify --quiet refs/remotes/origin/deploy && HAS_REMOTE=1

if [ "$HAS_LOCAL" = 1 ] && [ "$HAS_REMOTE" = 1 ] \
    && ! git -C "$REPO" merge-base --is-ancestor origin/deploy deploy; then
    echo "ERRO: deploy local não descende de origin/deploy; o cPanel recusaria o update." >&2
    exit 1
fi

if [ "$HAS_LOCAL" = 1 ]; then
    git -C "$REPO" worktree add -q "$TREE" deploy
elif [ "$HAS_REMOTE" = 1 ]; then
    git -C "$REPO" worktree add -q -b deploy "$TREE" origin/deploy
else
    git -C "$REPO" worktree add -q --detach "$TREE"
    git -C "$TREE" checkout -q --orphan deploy
fi
git -C "$TREE" rm -rqf --ignore-unmatch .
find "$TREE" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +

for dir in app bootstrap config database public resources routes vendor; do
    cp -R "$SRC/$dir" "$TREE/$dir"
done
rm -f "$TREE"/bootstrap/cache/*.php
rm -f "$TREE/public/hot"
cp "$SRC/artisan" "$SRC/composer.json" "$SRC/composer.lock" "$TREE/"
cp "$SRC/docs/deploy/cpanel-deploy.sh" "$SRC/docs/deploy/.cpanel.yml" "$TREE/"
chmod +x "$TREE/cpanel-deploy.sh"
echo "$SHORT" > "$TREE/RELEASE"

git -C "$TREE" add -A -f .
git -C "$TREE" commit -q -m "release: $SHORT

Origem: $SOURCE_REF ($SHA)
vendor/ gerado com PHP 8.4 (--no-dev) e public/build com pnpm build.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"

git -C "$REPO" worktree remove --force "$TREE"
git -C "$REPO" log -1 --stat --format='%h %s' deploy | tail -1
