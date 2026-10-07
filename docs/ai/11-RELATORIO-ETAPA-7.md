# Relatório da Etapa 7 — Pontuação e regras de recompensa

## 1. O que foi implementado

Foi implementado um sistema de pontuação persistente, determinístico e controlado integralmente pelo backend. Cada primeiro acerto concede pontos, a conclusão concede bônus único e a duração oficial determina o bônus de velocidade.

A fórmula é congelada no snapshot de cada partida. O Livewire apenas coordena a ação e exibe os valores devolvidos pelo domínio; JavaScript e Blade não calculam score.

## 2. Arquivos criados

- `app/Data/ScoreAward.php`
- `app/Services/ScoreCalculator.php`
- `database/migrations/2026_10_06_235843_add_score_to_game_sessions_table.php`
- `tests/Unit/ScoreCalculatorTest.php`
- `docs/ai/11-RELATORIO-ETAPA-7.md`

## 3. Arquivos modificados

- `app/Actions/Game/FindGameSessionWordAction.php`
- `app/Actions/Game/StartGameSessionAction.php`
- `app/Data/WordSelectionResult.php`
- `app/Livewire/Game/GameBoard.php`
- `app/Models/GameSession.php`
- `config/denarius.php`
- `database/factories/GameSessionFactory.php`
- `resources/views/livewire/game/game-board.blade.php`
- testes de actions, modelo e Livewire
- `README.md`
- documentação de contexto, arquitetura, domínio, tarefas e diário

## 4. Banco de dados

### Migration

`add_score_to_game_sessions_table` adiciona `game_sessions.score` como `unsignedInteger`, obrigatório e com padrão zero. A migration foi aplicada, revertida e reaplicada com sucesso no MySQL 8.4.

### Score

O valor persistido é a fonte autoritativa da interface e do futuro ranking. Registros anteriores à Etapa 7 são atualizados pela migration: palavras encontradas recebem a pontuação base e partidas concluídas recebem os bônus compatíveis com sua duração oficial.

### Integridade

O modelo converte `score` para inteiro, rejeita valores negativos, impede redução após persistência e continua totalmente protegido contra mass assignment. Nenhuma coluna de bônus foi criada: o detalhamento é reconstruível com contadores, status, duração e configuração congelada no snapshot.

## 5. Regra de pontuação

### Pontos por palavra

Cada palavra encontrada pela primeira vez concede 100 pontos. Seleções inválidas ou repetidas concedem zero.

### Bônus de conclusão

A transição única de `ACTIVE` para `COMPLETED` concede 500 pontos.

### Bônus de velocidade

- até 120 segundos: 500 pontos;
- de 121 a 180 segundos: 300 pontos;
- de 181 a 300 segundos: 150 pontos;
- acima de 300 segundos: zero.

As faixas usam exclusivamente `duration_seconds`, calculado no servidor.

### Score máximo

O máximo padrão é `total_words * 100 + 500 + 500`. Uma partida com 10 palavras pode alcançar 2.000 pontos. A quantidade de palavras não é fixada no calculador.

## 6. Integração com o domínio

### FindGameSessionWordAction

A action reutiliza os locks pessimistas da Etapa 5. Após validar ownership, status e placement, marca a palavra, recalcula o contador, conclui quando necessário, calcula o prêmio e salva score/estado dentro da mesma transação.

### Conclusão

O prêmio da última palavra contém pontos da palavra, bônus de conclusão e bônus de velocidade. `ScoreAward` e `WordSelectionResult` mantêm o retorno estruturado.

### Abandono

Uma partida abandonada preserva os pontos já conquistados. Não recebe bônus de conclusão nem de velocidade e não aceita novos acertos.

### Idempotência

Uma palavra já encontrada retorna todos os campos de prêmio com zero. O contador, `found_at` e score permanecem inalterados.

### Concorrência

Sessão e palavra continuam bloqueadas com `lockForUpdate()`. A releitura do estado dentro da transação impede duas requests de pontuarem a mesma palavra ou aplicarem o bônus final duas vezes.

## 7. Interface

### Score durante a partida

O painel lateral exibe `GameSession.score` ao lado de progresso e tempo.

### Feedback

Um acerto informa os pontos concedidos. A conclusão informa pontos da palavra, bônus aplicáveis e score final.

### Score final

Partidas concluídas mostram “Pontuação final”; abandonadas mostram “Pontuação conquistada”.

### Reload

O componente lê o score persistido. Recarregar a página não recalcula nem reaplica pontos ou bônus.

## 8. Segurança

- Manipulação de payload: os métodos públicos não recebem score, bônus, duração, status ou palavra.
- Mass assignment: `GameSession` permanece `Guarded(['*'])`.
- Ownership: policies e resolução por usuário autenticado continuam obrigatórias.
- Tempo autoritativo: bônus usa somente timestamps e duração do servidor.
- Requests duplicadas: locks, status persistido e `is_found` garantem prêmio único.
- Falha no cálculo: a transação reverte palavra, contador e score, evitando estado parcial.
- Fórmula: a configuração de cada partida fica em `generation_config.scoring`, que é imutável.

## 9. Testes

Foram adicionados 17 casos PHP, elevando a suíte de 152 para 169 testes.

A cobertura inclui fórmula, múltiplas palavras, limites das três faixas de tempo, score máximo, configuração inválida, snapshot da fórmula, primeiro acerto, repetição, seleção inválida, conclusão, abandono, rollback transacional, ownership, mass assignment, score negativo, redução de score, feedback Livewire e reload.

Resultado:

```text
169 testes PHPUnit aprovados
1.186 assertions
5 testes JavaScript aprovados
0 falhas
```

## 10. Validações

- PHPUnit: aprovado com `php artisan test --compact`.
- JavaScript: aprovado com `pnpm test:js`.
- Pint: aprovado com `vendor/bin/pint --test` após formatação.
- Composer: `composer validate --strict` aprovado.
- Vite: `pnpm build` aprovado; permaneceu apenas o aviso conhecido do pacote opcional `fontaine`.
- Composer audit: nenhuma vulnerabilidade com `composer audit --locked`.
- pnpm audit: nenhuma vulnerabilidade em nível high.
- Migration MySQL: aplicação, rollback e reaplicação aprovados.
- `git diff --check`: aprovado.
- Análise estática: PHPStan/Larastan não está configurado.

## 11. Git

- Branch: `feat/scoring-system`.
- Commit funcional: `90ba15b feat: add authoritative scoring system`.
- Commit documental: `docs: add scoring system report`.
- Push: branch publicada em `origin/feat/scoring-system`, sem merge automático em `main`.

## 12. Pendências

- Ranking geral e atualização em tempo quase real permanecem fora do escopo.
- Multiplicadores por dificuldade, combos, conquistas e penalidades não foram antecipados.
- A inspeção visual física da nova terceira métrica no painel móvel pode ser refinada junto aos testes manuais futuros; build e HTML renderizado foram validados.

## 13. Próxima etapa

ETAPA 8 — Ranking geral e atualização em tempo quase real.

O ranking deverá consumir somente scores persistidos de partidas concluídas, sem recalcular a pontuação.
