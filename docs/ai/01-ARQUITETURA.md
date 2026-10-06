# Arquitetura

## Componentes

```text
Blade / Livewire / Alpine
          |
          v
Form Requests / Livewire validation + authorization
          |
          v
Actions (casos de uso) -> Services (regras reutilizáveis)
          |
          v
Eloquent models + transactions
          |
          v
MySQL
```

## Módulos planejados

- `Auth`: controllers HTTP + Form Requests, sessão Laravel, logout seguro e rate limiting nativo.
- `Terms`: `FinancialTerm`, normalização Unicode centralizada, catálogo ativo e administração protegida.
- `Game`: `GameSession`, `GameSessionWord`, snapshot auditável e validação server-side.
- `Scoring`: `ScoreCalculator` puro e transparente.
- `Ranking`: consulta indexada por pontuação, tempo e conclusão; Livewire polling.
- `Admin`: resources Filament protegidos por autorização explícita.

## Autenticação e autorização

- O guard `web` e a sessão nativa atendem a aplicação pública e o painel.
- `UserRole` é um enum persistido como string; o `User` centraliza `isAdmin()` e `isParticipant()`.
- `FilamentUser::canAccessPanel()` permite o painel `admin` somente para administradores.
- Login limita falhas por e-mail normalizado + IP; cadastro limita requisições por IP.
- O cadastro seleciona explicitamente campos seguros e nunca aceita `role` do request.

## Catálogo de termos

- `FinancialTermNormalizer` translitera, converte para maiúsculas e preserva somente `A-Z`, com limite de 24 letras para o futuro grid.
- `FinancialTermObserver` aplica a normalização em toda gravação Eloquent; `normalized_term` não é mass-assignable.
- O índice `UNIQUE` em `normalized_term` protege a integridade mesmo sob gravações concorrentes.
- `FinancialTermDifficulty` persiste `easy`, `medium` ou `hard` e fornece rótulos/cores ao Filament.
- `FinancialTermPolicy` restringe leitura e escrita administrativa a usuários `admin`.
- `FinancialTermSeeder` usa `firstOrCreate`: pode ser repetido sem duplicar nem sobrescrever edições administrativas.
- Nenhuma rota ou API pública expõe o catálogo nesta etapa.

## Motor do caça-palavras

- `WordSearchGeneratorService::generate()` consulta termos ativos uma única vez e executa seleção e posicionamento em memória.
- `generateFromTerms()` separa o posicionamento de uma coleção explícita da seleção normal do catálogo; esse caminho é usado para testes e integrações controladas.
- `WordDirection` modela as oito direções com deltas explícitos de linha e coluna.
- `WordSearchResult`, `WordPlacement`, `WordCoordinate` e `SelectedFinancialTerm` são contratos `readonly` preparados para o snapshot da futura `GameSession`.
- O algoritmo randomiza seleção, coordenadas, direções e preenchimento; `Randomizer` pode ser injetado com uma engine seeded para testes reproduzíveis.
- As palavras são tentadas da maior para a menor. Entre candidatos compatíveis, o algoritmo prioriza o maior número de cruzamentos e escolhe aleatoriamente entre empates.
- Cada candidato é validado integralmente antes de alterar o grid. Letras iguais podem cruzar; letras diferentes bloqueiam a posição.
- Falhas são explícitas para configuração inválida, catálogo insuficiente, duplicidade normalizada, palavra incompatível e esgotamento das tentativas.
- O grid padrão é `15x15`, com 10 palavras e até 20 reinicializações, configurados em `config/denarius.php`.

## Serviços-alvo

- `WordSearchGeneratorService` — implementado na Etapa 4.
- `StartGameService`
- `ValidateWordSelectionService`
- `ScoreCalculator`
- `CompleteGameService`
- `RankingService`

O frontend envia somente coordenadas da seleção. Palavras, relógio, conclusão e pontos permanecem sob autoridade do servidor.

## Modelo de dados planejado

```text
User 1---* GameSession 1---* GameSessionWord *---1 FinancialTerm
```

`GameSession.grid` será JSON; cada palavra da sessão guardará termo, forma normalizada, coordenadas, direção e momento do acerto. Essa combinação preserva o snapshot e permite auditoria.
