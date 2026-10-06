# Diário

## 2026-10-06 — Etapas 0 e 1

- Repositório vazio reconhecido; não havia Git, Laravel, arquivos ou skills locais.
- Ambiente inventariado e versões estáveis confirmadas em documentação oficial.
- Laravel 13 criado; dependências Livewire/Filament instaladas.
- Infraestrutura de banco local e documentação inicial adicionadas.
- Laravel Boost instalado conforme instrução do esqueleto Laravel 13.
- MySQL real migrado; testes, formatter, build, audits e resposta HTTP passaram.
- Próximo passo: implementar autenticação e autorização administrativa em commit separado.

## 2026-10-06 — Etapa 2

- Criados cadastro, login e logout com sessão nativa, CSRF e regeneração de identificador/token.
- Adicionado `UserRole`, papel participante padrão, factory states e migration aditiva com rollback validado.
- Filament instalado em `/admin` e protegido por `FilamentUser::canAccessPanel()`.
- Login limitado por e-mail + IP e cadastro por IP, com valores em `config/denarius.php`.
- `AdminUserSeeder` usa somente variáveis de ambiente completas, senha forte e execução idempotente.
- Verificação de e-mail e reset de senha permanecem como evoluções futuras.

## 2026-10-06 — Etapa 3

- Criados `FinancialTerm`, `FinancialTermDifficulty`, factory, migration e catálogo inicial com 90 termos educativos.
- A normalização determinística remove acentos, espaços, números e símbolos, preserva somente `A-Z` e limita palavras a 24 letras.
- Observer Eloquent gera `normalized_term`; unicidade também é garantida por constraint no MySQL.
- Seeder idempotente preserva alterações realizadas por administradores.
- Resource Filament oferece listagem, busca, filtros, criação, visualização, edição, ativação e exclusão.
- Policy explícita mantém todas as operações do catálogo restritas a administradores.
- O catálogo permanece interno e não possui endpoint público nesta etapa.
- Próximo passo: implementar o gerador determinístico do caça-palavras e seus testes de posicionamento.

## 2026-10-06 — Etapa 4

- Criado `WordSearchGeneratorService` para selecionar termos ativos e gerar grids configuráveis integralmente no backend.
- Modeladas as oito direções em `WordDirection`, com deltas explícitos de linha e coluna.
- Criados DTOs `readonly` para resultado, placements, coordenadas e snapshots dos termos selecionados.
- O posicionamento tenta palavras maiores primeiro, prioriza cruzamentos compatíveis e valida toda a posição antes de gravar no grid.
- A seleção, posições, direções e letras de preenchimento usam `Randomizer`; engines seeded tornam os testes reproduzíveis.
- Configurações padrão e limites foram centralizados em `config/denarius.php`.
- Exceções de domínio tratam catálogo insuficiente, entrada duplicada, configuração inválida, palavra incompatível e falha após tentativas limitadas.
- Não houve alteração de banco, `GameSession`, interface, pontuação ou ranking.
- Suíte completa: 95 testes, 930 assertions e nenhuma falha.
- Pint, build e auditorias Composer/pnpm passaram; não há analisador estático configurado.
- Próximo passo: implementar `GameSession`, snapshots e regras transacionais/antitrapaça.
