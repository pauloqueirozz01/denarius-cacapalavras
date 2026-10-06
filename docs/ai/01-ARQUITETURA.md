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

- `Auth`: cadastro, login, logout e rate limiting.
- `Terms`: `FinancialTerm`, normalização Unicode e catálogo ativo.
- `Game`: `GameSession`, `GameSessionWord`, snapshot auditável e validação server-side.
- `Scoring`: `ScoreCalculator` puro e transparente.
- `Ranking`: consulta indexada por pontuação, tempo e conclusão; Livewire polling.
- `Admin`: resources Filament protegidos por autorização explícita.

## Serviços-alvo

- `WordSearchGeneratorService`
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
