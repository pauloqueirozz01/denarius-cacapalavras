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

Em produção, use HTTPS e configure `SESSION_SECURE_COOKIE=true`; `SESSION_HTTP_ONLY=true` e `SESSION_SAME_SITE=lax` já são os padrões documentados. O ambiente HTTP local mantém o cookie seguro desabilitado.

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
- [Guia de contribuição para IA](AGENTS.md)

## Estado atual

Etapa 3 concluída: autenticação, autorização e catálogo administrativo com 90 termos financeiros estão implementados. A próxima etapa implementará o gerador do caça-palavras.
