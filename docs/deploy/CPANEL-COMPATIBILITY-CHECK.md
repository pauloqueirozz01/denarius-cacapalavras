# Check-up de compatibilidade cPanel — pré-Etapa 11

**Resultado:** código ajustado para o ambiente declarado; **readiness do deploy: BLOCKED** até que o responsável do cPanel confirme os itens de infraestrutura pendentes e haja validação direta no banco de destino. Este check-up não é a Etapa 11. Nenhum deploy, migration, alteração de DNS/SSL/Document Root ou escrita remota foi executada.

## Ambiente alvo informado pelo proprietário

| Item | Valor informado | Situação nesta tarefa |
|---|---|---|
| Home | `/home1/denarius` | Informado; acesso não realizado |
| Domínio | `gamedaoncinha.com` | Informado; DNS/SSL ainda não verificados |
| Document Root atual | `/home1/denarius/gamefinanceiro.com` (diretório vazio, conforme informado) | Não alterado |
| Document Root desejado | `/home1/denarius/gamefinanceiro.com/public` | Requisito crítico pendente de ação do cPanel |
| PHP | 8.3 no sistema; PHP 8.4 disponível por domínio | Patch/handler e CLI ainda desconhecidos |
| Banco | Percona Server `5.7.44-48` | Versão informada; sem conexão direta nesta tarefa |
| Database | `denarius_gamefinanceiro` | Informado; conexão não validada |
| Database user | `denarius_financeirouser` | Informado; associação/grants não confirmados |
| Host/porta | `localhost:3306` | Informado como esperado |
| Charset/collation | `utf8mb4` / `utf8mb4_unicode_ci` | Informado; compatível com a configuração Laravel existente |

Não foi copiada nem registrada senha. O client local indicar MySQL 8.4 não representa a versão do servidor remoto.

## Achados e alterações

### Ranking — incompatibilidade corrigida

Antes, `RankingService` usava duas funções `ROW_NUMBER() OVER`, não disponíveis no MySQL 5.7. O serviço agora seleciona a melhor tentativa com anti-join `NOT EXISTS` e compara tentativas lexicograficamente. Para a listagem, a posição é `offset + índice da linha`; para posição individual, é contada a quantidade de entradas vencedoras anteriores. Não carrega o histórico inteiro no PHP e continua usando o campo persistido `game_sessions.score`.

A ordem continua idêntica: somente sessão `COMPLETED` e usuário `participant`; por participante, maior score, menor duração, término mais antigo e menor ID. Valores nulos de duração/término ficam depois dos conhecidos. O desempate por ID ganhou teste próprio com IDs inseridos fora da ordem de criação.

A consulta usa apenas Query Builder, `NOT EXISTS`, subquery derivada, igualdade/ordenação e `IS NULL`; não usa window functions, CTEs, parâmetros dinâmicos em SQL cru nem função JSON avançada. Foi validada pela suíte em SQLite, mas **a query real e o `EXPLAIN` ainda precisam ser executados no Percona 5.7.44-48** antes do release. O container MySQL 5.7 não estava disponível localmente; a instância Docker ativa é MySQL 8.4 e o Docker socket não permitiu inspeção adicional. Não foi inventada evidência de teste em Percona.

### PHP e Composer

O `composer.lock` contém pacotes de runtime com requisito `PHP >=8.4.1`. A exigência raiz anterior `^8.3` permitiria uma instalação que falharia ao validar o lock. `composer.json` e a plataforma raiz no lock foram alinhados para `^8.4.1`, sem atualizar versões de dependências. Nenhum pacote exige exclusivamente PHP 8.5; o PHP local 8.5.11 satisfaz o lock. Não há binário PHP 8.4/8.3 local, então não houve execução real nesses runtimes. A versão efetiva do handler cPanel precisa ser 8.4.1 ou superior.

Laravel 13 documenta PHP >=8.3 como base do framework, mas o lock concreto deste projeto eleva o mínimo efetivo. Requisito Laravel de referência: [documentação oficial de deployment Laravel 13](https://laravel.com/docs/13.x/deployment).

## Banco, migrations e charset

- Nenhuma migration foi alterada ou criada.
- Foram revisadas as oito migrations. As colunas JSON são armazenamento JSON básico; MySQL 5.7 introduziu o tipo nativo JSON desde 5.7.8, portanto a versão informada 5.7.44 é compatível com esse tipo ([release notes oficiais 5.7.8](https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-8.html)). Não há default JSON, índice sobre JSON, generated column, CTE ou sintaxe de migration específica de MySQL 8 identificada.
- As migrations locais aparecem como `Ran` em MySQL 8.4.11; isso não comprova aplicação no Percona remoto. A validação remota deve ocorrer na Etapa 11, após backup, por `migrate:status` e revisão de pendências.
- `config/database.php` já usa `utf8mb4_unicode_ci` por padrão. A collation confirmada para o servidor é a mesma; nenhuma alteração foi necessária. Não há ocorrência de `utf8mb4_0900_ai_ci` na aplicação/configuração.
- Percona Server 5.7 está em fim de vida upstream; o provedor precisa confirmar cobertura de correções/patches ou apresentar plano de atualização. Referência: [documentação oficial Percona Server 5.7](https://docs.percona.com/percona-server/5.7/index.html).

## PHP e extensões

O único runtime local testado é PHP CLI 8.5.11. `composer check-platform-reqs` passou nesse runtime; não representa PHP Web/CLI cPanel.

Extensões de servidor requeridas pelo Laravel 13: `Ctype`, `cURL`, `DOM`, `Fileinfo`, `Filter`, `Hash`, `Mbstring`, `OpenSSL`, `PCRE`, `PDO`, `Session`, `Tokenizer` e `XML`; para este app com `DB_CONNECTION=mysql`, também é obrigatório o driver `PDO MySQL` (`pdo_mysql`).

Além disso, `composer check-platform-reqs --no-dev --lock` identificou no lock de produção `ext-iconv`, `ext-intl`, `ext-json`, `ext-libxml`, `ext-xmlreader` e `ext-zip`; `ext-ctype` e `ext-mbstring` são atendidas localmente por polyfills, mas permanecem na lista requerida pelo Laravel. A máquina local possui esses módulos e `pdo_mysql`; as extensões Web e CLI do cPanel não foram verificadas.

## Estratégias de runtime/build

- **Composer:** estratégia B adotada: incluir `vendor/` de produção no ZIP para não depender de Composer no cPanel, cuja disponibilidade não foi confirmada. Gerar o diretório com `composer install --no-dev --prefer-dist --optimize-autoloader` em ambiente PHP 8.4.1+ e usar o `composer.lock` aprovado. Composer remoto não é requisito.
- **Frontend:** Node/pnpm não são necessários em produção. O build é local e `public/build/manifest.json` deve acompanhar o artefato. `node_modules/` não entra no ZIP.
- **Runtime Laravel:** `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`; não há worker ou cron obrigatório. Ranking usa `wire:poll` por HTTP, sem Reverb/WebSocket. Não há uso atual que exija `storage:link`.
- **Proteção da raiz:** Document Root precisa apontar para o `public/` informado. O estado atual não foi modificado. Não mover `.env` para `public_html`, não copiar a raiz Laravel para uma pasta pública e não improvisar `public/index.php`.

## Arquivos alterados

- `app/Services/RankingService.php` — substituição da consulta com janela por consulta sem window functions.
- `tests/Feature/Services/RankingServiceTest.php` — cobertura explícita do desempate global por menor ID.
- `composer.json` e `composer.lock` — alinhamento do mínimo efetivo de PHP para 8.4.1, sem atualização de pacotes.
- `docs/ai/00-CONTEXTO.md`, `01-ARQUITETURA.md`, `02-DOMINIO.md`, `03-TASKS.md`, `06-DIARIO.md` — estado atual e conclusão do check-up.
- `docs/deploy/CPANEL-READINESS.md` e `CPANEL-DEPLOY.md` — atualização para dados confirmados, ranking 5.7 e estratégia final de release.
- `docs/deploy/CPANEL-COMPATIBILITY-CHECK.md` e `ETAPA-11-CONTRACT.md` — este relatório e contrato de passagem.

Nenhuma migration, configuração de sessão/cache/queue, collation, regra de score, layout ou dependência foi alterada.

## Validações e limitações

| Verificação | Resultado |
|---|---|
| Baseline pré-alteração | 197 PHPUnit / 1.325 assertions aprovados |
| Suíte final | 198 PHPUnit / 1.327 assertions, 0 falhas |
| JavaScript | 5 testes aprovados, 0 falhas |
| Testes de RankingService após correção | 8 testes / 29 assertions aprovados |
| Pint | `./vendor/bin/pint --test` e `pint --dirty --format agent` aprovados |
| Composer | `composer validate --strict` aprovado; `composer check-platform-reqs` aprovado no PHP 8.5.11 local |
| Vite | `pnpm build` aprovado; `public/build/manifest.json` existe; aviso não bloqueante de `fontaine` opcional |
| Audits | `composer audit --locked` e `pnpm audit --audit-level=high` sem vulnerabilidades |
| pnpm lock | `pnpm install --frozen-lockfile` concluído sem alteração de dependências/lock |
| Diff | `git diff --check` aprovado |
| MySQL/Percona | Sem teste direto no Percona 5.7; ambiente disponível localmente é SQLite para testes e MySQL 8.4 no Docker |
| PHP 8.4.1 | Não instalado localmente; verificado estaticamente pelo requisito das dependências lockadas |
| Migrations | 8 status `Ran` no banco local MySQL 8.4; nenhuma migration executada no cPanel |
| Estado cPanel | Sem sessão/acesso remoto; pendências no readiness e contrato |

As validações finais da branch e os SHAs serão registrados no commit documental e no relatório entregue ao proprietário. O resultado do deploy permanece **BLOCKED**, pois o PHP 8.4.1+ real, o PHP CLI, Document Root `/public`, SSL/DNS, grants, permissões, backups e a consulta/migrations no banco remoto não foram confirmados.
