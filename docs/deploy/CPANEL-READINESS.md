# Readiness cPanel — pré-deploy Etapa 10

**Classificação atual: BLOCKED.** A aplicação e as dependências foram verificadas localmente, mas não houve acesso ao cPanel nem confirmação do domínio, PHP Web/CLI, raiz pública, SSL, banco remoto ou privilégios. Não usar os dados locais como prova do ambiente de hospedagem. Nenhum deploy ou comando em produção foi executado.

Legenda: `Verificado` significa evidência local disponível; `Pendente` significa que o responsável pelo cPanel precisa confirmar; `Bloqueio` indica que o deploy não pode avançar até a confirmação/ação.

| Item | Valor encontrado | Status | Bloqueio? | Ação necessária |
|---|---|---|---|---|
| PHP Web | Não informado pelo cPanel; PHP Web local não aplicável | Pendente | Sim | Conferir versão e handler em MultiPHP Manager/Select PHP Version |
| PHP CLI | Local: PHP 8.5.11 em `/opt/homebrew/Cellar/php/8.5.11/bin/php`; CLI cPanel desconhecido | Pendente | Sim | No Terminal cPanel, registrar `php -v` e `which php`; comparar com PHP Web |
| Compatibilidade Laravel 13 | `composer.json` exige `^8.3`; código local testado em 8.5.11 | Parcial | Sim | Confirmar PHP Web e CLI compatíveis com o lock e `composer check-platform-reqs` |
| Extensões PHP | Local: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, PDO MySQL, Session, Tokenizer e XML disponíveis | Pendente no host | Sim | Conferir `php -m` no CLI e extensões selecionadas no PHP Web |
| Composer | Local: Composer 2.10.3; disponibilidade cPanel desconhecida | Pendente | Sim | Confirmar `composer --version` e capacidade de instalar dependências sem `require-dev` |
| Estratégia Composer/vendor | Não definida até confirmação de Composer e PHP no host | Pendente | Sim | Usar Composer no host (A) se compatível; caso contrário empacotar `vendor/` construído com PHP compatível (B) |
| Node/pnpm | Local: Node 26.8.2 e pnpm 11.15.1; não necessários em produção | Verificado local | Não | Compilar assets localmente e enviar `public/build` |
| `public/build` | Gerado/validado pelo build local ao concluir a etapa | Verificado local | Não | Confirmar `public/build/manifest.json` no artefato de release |
| Domínio/subdomínio | Não fornecido | Pendente | Sim | Definir e criar domínio/subdomínio no cPanel e registrar DNS |
| DNS | Não verificado | Pendente | Sim | Confirmar resolução para o host antes do SSL |
| Document Root | Não verificado; requisito: raiz Laravel `.../public` | Pendente | Sim — crítico | Confirmar que o domínio aponta para `.../denarius/current/public`; se impossível, interromper e reavaliar hospedagem |
| SSL/HTTPS | Não verificado | Pendente | Sim | Confirmar AutoSSL/Let's Encrypt, cobertura do domínio e renovação antes de ativar cookie secure/HSTS |
| Symlink/releases | Suporte do cPanel desconhecido | Pendente | Não isoladamente | Testar permissão de symlink no diretório privado; usar releases/current somente se suportado |
| MySQL/MariaDB | Local: MySQL 8.4.11; engine do cPanel desconhecido | Pendente | Sim | Executar `SELECT VERSION()` no banco de hospedagem |
| Window functions | MySQL local 8.4.11 suporta `ROW_NUMBER()`; cPanel não testado | Pendente | Sim | Executar query real/`EXPLAIN` do ranking no engine remoto antes de release |
| Database | O usuário informou que o banco foi criado; nome remoto não fornecido | Pendente | Sim | Registrar nome real sem senha e validar conexão pela aplicação |
| Database user | O usuário informou que o usuário do banco foi criado; nome remoto não fornecido | Pendente | Sim | Registrar usuário cPanel e confirmar associação explícita ao banco |
| Database privileges | Associação e privilégios remotos não confirmados | Pendente | Sim | Confirmar usuário adicionado ao banco e privilégios DML/DDL necessários para migrações |
| Charset/collation | Local: `utf8mb4` / `utf8mb4_0900_ai_ci`; remoto desconhecido | Pendente | Sim | Conferir charset/collation do banco cPanel; exigir `utf8mb4` |
| Migrations | Todas as 8 migrations locais aparecem como `Ran`; remoto desconhecido | Pendente | Sim | Backup, `migrate:status`, revisão e então `php artisan migrate --force` na Etapa 11 |
| Seeders | `FinancialTermSeeder` idempotente; `AdminUserSeeder` exige env completo e não sobrescreve conta existente | Verificado em código | Não | Executar somente `FinancialTermSeeder` e `AdminUserSeeder`; nunca gerar usuários fake |
| `APP_ENV` | Ambiente de produção ainda não configurado | Pendente | Sim | Definir `production` no `.env` privado |
| `APP_DEBUG` | `.env.example` local usa `true` apenas para desenvolvimento | Pendente | Sim | Definir e verificar `false` em produção |
| `APP_KEY` | Não fornecida (corretamente não versionada) | Pendente | Sim | Criar chave exclusiva no servidor com `php artisan key:generate`; nunca reutilizar chave local |
| Session | Configuração `database`; migration cria tabela `sessions` | Parcial | Sim | Confirmar migração e conexão; manter cookie HTTP-only/SameSite=Lax e secure após HTTPS |
| Cache | Configuração padrão `database`; migration cria tabela `cache` | Parcial | Não | Confirmar migration/permissão de leitura e escrita no host |
| Queue | `.env.example` usa `sync`; código não despacha jobs | Verificado em código | Não | Manter `QUEUE_CONNECTION=sync`; nenhum worker requerido no MVP atual |
| Scheduler/cron | `routes/console.php` contém somente comando manual `inspire`; sem tarefas agendadas | Verificado em código | Não | Nenhum cron requerido pelo app atual |
| Ranking/polling | `wire:poll` HTTP a cada 5 segundos; sem WebSocket/Reverb/daemon | Verificado em código | Não | Smoke test via HTTPS e observar custo/carga em evento real |
| Storage/uploads | Sem upload/uso de `storage/app/public` encontrado; assets em `public/` | Verificado em código | Não | Não executar `storage:link` no momento |
| Permissões | Permissões cPanel não verificadas | Pendente | Sim | Garantir escrita pelo usuário PHP em `storage/` e `bootstrap/cache`; nunca usar `777` |
| `.htaccess` | Regras padrão Laravel presentes em `public/.htaccess` | Verificado local | Não | Confirmar Apache/LiteSpeed e rewrite habilitado no host |
| APP logs | `stack/single`, caminho padrão `storage/logs`; nível local `debug` | Parcial | Sim | Produção usar `warning`/`notice` conforme operação, diretório gravável e sem dados secretos |
| Vite/Node no servidor | Não é necessário | Verificado em arquitetura | Não | Não instalar Node/pnpm em produção; subir artefato `public/build` |
| Backup | Nenhum backup remoto confirmado | Pendente | Sim | Definir backup do banco, `.env` privado e arquivos antes de migration/release |
| Rollback | Nenhum procedimento remoto testado | Pendente | Sim | Preservar release anterior/backup; preparar reversão de código e plano de banco compatível |
| Release/ZIP | Instruções reproduzíveis em `CPANEL-DEPLOY.md`; ZIP ainda não criado | Parcial | Sim | Confirmar PHP e estratégia vendor, então produzir artefato sem segredos/caches/dados locais |
| Smoke tests | Checklist preparado no roteiro de deploy; produção indisponível | Pendente | Sim | Executar checks de rota, autenticação, jogo, score, ranking e admin após deploy |

## Valores locais medidos — não extrapolar para o cPanel

- PHP CLI: 8.5.11; binário `/opt/homebrew/Cellar/php/8.5.11/bin/php`.
- Extensões obrigatórias listadas acima estão presentes localmente.
- Composer: 2.10.3; Node: 26.8.2; pnpm: 11.15.1.
- MySQL: 8.4.11, charset `utf8mb4`, collation `utf8mb4_0900_ai_ci`.
- `EXPLAIN FORMAT=JSON` da janela principal escolheu `game_sessions_status_finished_at_index`, com `using_filesort=true` e `using_temporary_table=true`. A estimativa local é minúscula e não representa o volume do evento; nenhum índice adicional foi criado.
- Todas as migrations locais foram reportadas como aplicadas. A suíte automatizada usa SQLite in-memory.

## Evidência pendente do responsável pelo cPanel

Fornecer, sem senhas, chaves ou tokens: versões PHP Web/CLI e caminho, saída de extensões, disponibilidade Composer/Terminal, domínio/DNS/Document Root/SSL, suporte a symlink, versão MySQL/MariaDB/charset, nome do banco e usuário (sem senha), confirmação da associação e privilégios, permissões de `storage`/`bootstrap/cache`, e confirmação do mecanismo de backup.

Enquanto os itens críticos acima permanecerem pendentes, o estado permanece **BLOCKED**. Nenhuma senha deve ser registrada neste arquivo.
