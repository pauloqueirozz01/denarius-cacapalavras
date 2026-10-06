# Erros e soluções

## 2026-10-06 — ferramentas ausentes

- Situação: Composer e cliente MySQL não estavam no host.
- Solução: Composer 2.10.3 instalado via Homebrew; MySQL isolado em Docker, dispensando instalação do servidor no host.
- Efeito colateral: Homebrew atualizou o PHP CLI de 8.4.26 para 8.5.11, versão suportada pelo Laravel 13.

## 2026-10-06 — runtime Docker indisponível

- Situação: o contexto ativo apontava para `colima-ceara-percona57`, que estava parado; Docker Desktop também não iniciou.
- Solução: criado o perfil Colima isolado `denarius`, sem modificar ou remover o perfil preexistente.
- Resultado: MySQL e phpMyAdmin subiram saudáveis e as migrations foram executadas no MySQL.

## 2026-10-06 — instalação parcial do Boost para Codex

- Situação: o Boost atualizou `AGENTS.md`, instalou skills em `.claude/` e configurou o MCP, mas não pôde gravar nas pastas protegidas `.agents/.codex` desta sessão.
- Impacto: nenhum no runtime; as guidelines versionadas estão ativas e o MCP fica disponível após reiniciar uma sessão compatível.
- Ação futura: repetir `php artisan boost:install` em um terminal com permissão de escrita nessas pastas se a sincronização local do Codex for desejada.

Registre aqui falhas reproduzíveis, causa-raiz e correção; nunca registre segredos.
