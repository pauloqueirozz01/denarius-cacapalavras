# Denarius Finance Game

MVP gamificado da Denarius EdTech para ensinar educação financeira por meio de um caça-palavras competitivo. A aplicação pública será Blade + Livewire; o Filament será usado somente na administração.

## Stack

- PHP 8.5, Laravel 13, Livewire 4 e Filament 5
- Tailwind CSS 4 e Vite 8
- MySQL 8.4 e phpMyAdmin 5.2 em Docker
- PHPUnit 12 nesta fundação (Pest será avaliado na etapa de testes)

## Requisitos

- PHP 8.3 ou superior com `intl`, `mbstring` e `pdo_mysql`
- Composer 2
- Node.js 22 LTS ou superior e pnpm 10 ou superior
- Docker Desktop com Docker Compose

## Instalação local

```bash
cp .env.example .env
composer install
pnpm install
php artisan key:generate
docker compose up -d
php artisan migrate --seed
pnpm build
php artisan serve
```

A aplicação fica em <http://localhost:8000> e o phpMyAdmin, exclusivo para desenvolvimento, em <http://localhost:8081>.

Credenciais locais padrão do banco: banco `denarius`, usuário `denarius` e senha `denarius`. Não reutilize essas credenciais fora do ambiente local.

Para criar o administrador local, preencha `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` no `.env` antes de `php artisan db:seed`. A senha deve ter pelo menos 12 caracteres, letras maiúsculas e minúsculas, números e símbolos. Se a configuração estiver incompleta, nenhum administrador será criado.

O `.env.example` contém somente valores para desenvolvimento local e não deve ser enviado ao servidor. Para produção, configure `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS real, `APP_KEY` própria, banco e credenciais exclusivos, `SESSION_SECURE_COOKIE=true` após confirmar SSL, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` e log level apropriado. Siga [CPANEL-DEPLOY](docs/deploy/CPANEL-DEPLOY.md) e confira [CPANEL-READINESS](docs/deploy/CPANEL-READINESS.md); o cPanel ainda precisa ser verificado antes de declarar readiness.

## Verificação

```bash
composer test
vendor/bin/pint --test
pnpm build
composer audit
pnpm audit --audit-level=high
```

## Documentação

- [Contexto](docs/ai/00-CONTEXTO.md)
- [Arquitetura](docs/ai/01-ARQUITETURA.md)
- [Domínio](docs/ai/02-DOMINIO.md)
- [Backlog](docs/ai/03-TASKS.md)
- [Relatório da Etapa 3 — Catálogo de termos financeiros](docs/ai/07-RELATORIO-ETAPA-3.md)
- [Relatório da Etapa 4 — Algoritmo de geração do caça-palavras](docs/ai/08-RELATORIO-ETAPA-4.md)
- [Relatório da Etapa 5 — GameSession, snapshots e regras transacionais](docs/ai/09-RELATORIO-ETAPA-5.md)
- [Relatório da Etapa 6 — Interface jogável desktop/mobile](docs/ai/10-RELATORIO-ETAPA-6.md)
- [Relatório da Etapa 7 — Pontuação e recompensas](docs/ai/11-RELATORIO-ETAPA-7.md)
- [Relatório da Etapa 8 — Ranking e fluxo do jogador](docs/ai/12-RELATORIO-ETAPA-8.md)
- [Relatório da Etapa 9 — Mascote e polimento visual](docs/ai/13-RELATORIO-ETAPA-9.md)
- [Relatório da Etapa 10 — QA, segurança e pré-deploy](docs/ai/14-RELATORIO-ETAPA-10.md)
- [Readiness cPanel](docs/deploy/CPANEL-READINESS.md)
- [Roteiro de deploy cPanel](docs/deploy/CPANEL-DEPLOY.md)
- [Guia de contribuição para IA](AGENTS.md)

## Estado atual

Etapas 9 e 10 concluídas no código: autenticação, catálogo, motor, partidas, jogo, pontuação, ranking, fluxo de retorno, mascote substituível, tutorial refinado, painel administrativo de consulta, hardening básico e documentação pré-deploy. A compatibilidade real do cPanel continua **BLOCKED** até confirmar ambiente, Document Root, SSL, banco, privilégios, backup e rollback; o deploy pertence à Etapa 11.
