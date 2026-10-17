# Readiness cPanel — check-up de compatibilidade pré-Etapa 11

**Classificação atual: BLOCKED.** O proprietário confirmou parte do ambiente real, inclusive Percona Server 5.7.44-48. O código do ranking foi adaptado para não depender de funções de janela, mas não houve acesso ao host/banco para validar query, migrações, permissões ou conexão. Nenhum deploy, migration ou alteração de domínio/SSL/Document Root foi executado.

Legenda: `Verificado` significa evidência local disponível; `Pendente` significa que o responsável pelo cPanel precisa confirmar; `Bloqueio` indica que o deploy não pode avançar até a confirmação/ação.

| Item | Valor encontrado | Status | Bloqueio? | Ação necessária |
|---|---|---|---|---|
| PHP Web | PHP 8.3 no sistema; PHP 8.4 disponível por domínio; patch/handler efetivo não confirmados | Parcial | Sim | Confirmar PHP 8.4.1+ aplicado a `gamefinanceiro.com` |
| PHP CLI | Local: PHP 8.5.11 em `/opt/homebrew/Cellar/php/8.5.11/bin/php`; CLI/path do cPanel desconhecidos | Pendente | Sim | Registrar `php -v` e `which php`; confirmar CLI 8.4.1+ |
| Compatibilidade Laravel/dependências | `composer.json` e lock exigem agora `^8.4.1`; Composer local em PHP 8.5.11 | Parcial | Sim | Conferir versão Web/CLI e extensões no host; local não prova PHP 8.4 |
| Extensões PHP | Composer runtime local validou; lista requerida está no contrato da Etapa 11 | Pendente no host | Sim | Conferir extensões em PHP Web e CLI, especialmente PDO MySQL |
| Composer | Composer local 2.10.3; disponibilidade no cPanel desconhecida | Pendente | Não (estratégia não depende dele) | Estratégia final inclui `vendor/` no ZIP; Composer remoto não será requisito |
| Estratégia Composer/vendor | B definida: `vendor/` de produção será incluído no ZIP e gerado com PHP 8.4.1+ | Definida | Sim até artefato | Não gerar pacote final até runtime PHP ser confirmado e plataforma verificada |
| Node/pnpm | Local: Node 26.8.2 e pnpm 11.15.1; não necessários em produção | Verificado local | Não | Compilar assets localmente e enviar `public/build` |
| `public/build` | Gerado/validado pelo build local ao concluir a etapa | Verificado local | Não | Confirmar `public/build/manifest.json` no artefato de release |
| Domínio/subdomínio | `gamefinanceiro.com` | Informado | Não | Confirmar cadastro e associação ao diretório no cPanel |
| DNS | Não verificado | Pendente | Sim | Confirmar resolução para o host antes do SSL |
| Document Root | Atual `/home1/denarius/gamefinanceiro.com` (diretório informado como vazio); alvo `/home1/denarius/gamefinanceiro.com/public` | Pendente | Sim — crítico | Responsável do cPanel deve configurar o alvo `/public`; não alterar nesta auditoria |
| SSL/HTTPS | Não verificado | Pendente | Sim | Confirmar AutoSSL/Let's Encrypt, cobertura do domínio e renovação antes de ativar cookie secure/HSTS |
| Symlink/releases | Suporte do cPanel desconhecido | Pendente | Não isoladamente | Testar permissão de symlink no diretório privado; usar releases/current somente se suportado |
| MySQL/Percona | Percona Server `5.7.44-48`, confirmado pelo proprietário; não confundir com a versão da biblioteca cliente | Informado | Sim para teste remoto | Validar conexão, migrações e consulta no servidor real antes do release; avaliar suporte de segurança EOL |
| Recursos SQL usados | Ranking reescrito sem `ROW_NUMBER`, `OVER`, CTE ou window functions; usa `NOT EXISTS`, subqueries, comparações e `IS NULL` | Verificado no código | Não exige MySQL 8 | Executar a consulta real e `EXPLAIN` no Percona antes do release; suíte local usa SQLite |
| Database | `denarius_gamefinanceiro`, host esperado `localhost`, porta `3306` | Informado | Sim | Validar conexão com credenciais privadas e banco selecionado |
| Database user | `denarius_financeuser` | Informado | Sim | Confirmar associação explícita ao database no cPanel |
| Database privileges | Associação e privilégios remotos não confirmados | Pendente | Sim | Confirmar usuário adicionado ao banco e privilégios DML/DDL necessários para migrações |
| Charset/collation | Servidor informado: `utf8mb4` / `utf8mb4_unicode_ci`; configuração Laravel também usa `utf8mb4_unicode_ci` | Informado/compatível | Validar durante conexão | Não trocar collation; confirmar database e tabelas no host |
| Migrations | 8 migrations locais em `Ran`; Percona remoto não migrado/testado | Pendente | Sim | Backup, `migrate:status`, revisão e então `php artisan migrate --force` somente na Etapa 11 |
| Seeders | `FinancialTermSeeder` idempotente; `AdminUserSeeder` exige env completo e não sobrescreve conta existente | Verificado em código | Não | Executar somente `FinancialTermSeeder` e `AdminUserSeeder`; nunca gerar usuários fake |
| `APP_ENV` | Ambiente de produção ainda não configurado | Pendente | Sim | Definir `production` no `.env` privado |
| `APP_DEBUG` | `.env.example` local usa `true` apenas para desenvolvimento | Pendente | Sim | Definir e verificar `false` em produção |
| `APP_KEY` | Não fornecida (corretamente não versionada) | Pendente | Sim | Criar chave exclusiva no servidor com `php artisan key:generate`; nunca reutilizar chave local |
| Session | Configuração `database`; migration cria tabela `sessions` | Parcial | Sim | Confirmar migração e conexão; manter cookie HTTP-only/SameSite=Lax e secure após HTTPS |
| Cache | Configuração padrão `database`; migration cria tabela `cache` | Parcial | Não | Confirmar migration/permissão de leitura e escrita no host |
| Queue | `.env.example` usa `sync`; código não despacha jobs | Verificado em código | Não | Manter `QUEUE_CONNECTION=sync`; nenhum worker requerido no MVP atual |
| Scheduler/cron | `routes/console.php` contém somente comando manual `inspire`; sem tarefas agendadas | Verificado em código | Não | Nenhum cron requerido pelo app atual |
| Ranking/polling | `wire:poll.visible` HTTP a cada 10 segundos, com o ranking em cache de 10 segundos; sem WebSocket/Reverb/daemon | Verificado em código | Não | Smoke test via HTTPS e observar custo/carga em evento real |
| Storage/uploads | Sem upload/uso de `storage/app/public` encontrado; assets em `public/` | Verificado em código | Não | Não executar `storage:link` no momento |
| Permissões | Permissões cPanel não verificadas | Pendente | Sim | Garantir escrita pelo usuário PHP em `storage/` e `bootstrap/cache`; nunca usar `777` |
| `.htaccess` | Regras padrão Laravel presentes em `public/.htaccess` | Verificado local | Não | Confirmar Apache/LiteSpeed e rewrite habilitado no host |
| APP logs | `stack/single`, caminho padrão `storage/logs`; nível local `debug` | Parcial | Sim | Produção usar `warning`/`notice` conforme operação, diretório gravável e sem dados secretos |
| Vite/Node no servidor | Não é necessário | Verificado em arquitetura | Não | Não instalar Node/pnpm em produção; subir artefato `public/build` |
| Backup | Nenhum backup remoto confirmado | Pendente | Sim | Definir backup do banco, `.env` privado e arquivos antes de migration/release |
| Rollback | Nenhum procedimento remoto testado | Pendente | Sim | Preservar release anterior/backup; preparar reversão de código e plano de banco compatível |
| Release/ZIP | `dist/denarius-cacapalavras-5e1b0884.zip` gerado de `5e1b088`, com `vendor/` produzido em PHP 8.4.26 e `public/build/manifest.json`; SHA-256 `d921f5374e4c91b9bd3825cb3830c0d961bc88cbe11aafdf6a039acedeffba6b` | Pronto | Não | Conferir o SHA-256 no servidor antes de extrair; ver `ETAPA-11-CONTRACT.md` |
| Smoke tests | Checklist preparado no roteiro de deploy; produção indisponível | Pendente | Sim | Executar checks de rota, autenticação, jogo, score, ranking e admin após deploy |

## Valores locais medidos — não extrapolar para o cPanel

- PHP CLI: 8.5.11; binário `/opt/homebrew/Cellar/php/8.5.11/bin/php`.
- Extensões obrigatórias listadas acima estão presentes localmente.
- Composer: 2.10.3; Node: 26.8.2; pnpm: 11.15.1.
- MySQL local: 8.4.11, charset `utf8mb4`, collation `utf8mb4_0900_ai_ci`; isso não representa o Percona remoto.
- O ranking anterior com funções de janela foi substituído por seleção anti-join `NOT EXISTS` e ordenação estável; não foi criado índice porque falta `EXPLAIN` representativo no Percona. A consulta nova ainda precisa de verificação remota.
- `composer.lock` contém dependências de runtime que exigem PHP `>=8.4.1`; o requisito do projeto foi alinhado para `^8.4.1`. A máquina local só tem PHP 8.5.11, portanto 8.4 não foi executado aqui.
- Todas as migrations locais foram reportadas como aplicadas. A suíte automatizada usa SQLite in-memory.

## Evidência pendente do responsável pelo cPanel

Fornecer, sem senhas, chaves ou tokens: patch/handler do PHP Web 8.4 e PHP CLI/caminho (ambos 8.4.1+), extensões, DNS/Document Root `/home1/denarius/gamefinanceiro.com/public`/SSL, suporte a symlink, confirmação da associação e privilégios do banco, permissões de `storage`/`bootstrap/cache`, e mecanismo de backup/rollback. A consulta de ranking, migrations e conexão ainda devem ser testadas no Percona 5.7.44-48 real.

Enquanto os itens críticos acima permanecerem pendentes, o estado permanece **BLOCKED**. Nenhuma senha deve ser registrada neste arquivo.
