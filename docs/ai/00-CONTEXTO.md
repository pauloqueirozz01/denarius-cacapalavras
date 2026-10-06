# Contexto do projeto

## Produto

Denarius Finance Game é um MVP para eventos da Denarius EdTech. O fluxo-alvo é cadastro, login, partida aleatória de caça-palavras financeiros, resultado e ranking dinâmico. O posicionamento é **Educação Financeira Criativa**.

## Escopo

O MVP inclui autenticação, catálogo de termos, jogo responsivo, pontuação calculada no servidor, ranking com polling, mascote substituível, tutorial e administração Filament. Pagamentos, moedas virtuais, chat, IA, microserviços, app mobile e WebSockets estão fora do escopo.

## Estado em 2026-10-06

- Repositório recebido vazio.
- Laravel 13.34.0 inicializado sobre PHP 8.5.11.
- Livewire 4.4.7 e Filament 5.9.0 instalados como dependências de fundação.
- Laravel Boost 2.10.2 instalado para guidelines, skills e MCP de desenvolvimento.
- MySQL 8.4 e phpMyAdmin 5.2 definidos em Docker Compose.
- Autenticação de sessão, papéis `admin`/`participant`, rota protegida `/game` e acesso administrativo Filament estão implementados.
- Catálogo com 90 termos financeiros educativos, dificuldades, ativação e gestão administrativa Filament está implementado.

## Decisões

- Aplicativo monolítico Laravel; sem SPA separada.
- Livewire/Blade para o jogo e Filament apenas em `/admin`.
- MySQL é a persistência da aplicação; SQLite em memória pode ser usado por testes unitários/feature.
- `pnpm` é o gerenciador JavaScript do projeto.
- Cadastro público sempre cria `participant`; somente o seeder configurado por ambiente cria `admin`.
- Verificação de e-mail e recuperação de senha ficam desabilitadas no MVP para reduzir atrito em eventos.
- O catálogo não possui endpoint público; o jogo consumirá somente termos ativos em uma etapa posterior.
