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
- [Guia de contribuição para IA](AGENTS.md)

## Estado atual

Etapa 1 concluída: fundação Laravel e ambiente local validados. Autenticação, domínio do jogo, interface e painel administrativo serão implementados nas próximas etapas, com testes e commits separados.
