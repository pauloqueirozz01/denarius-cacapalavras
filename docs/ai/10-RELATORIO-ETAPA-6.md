# Relatório da Etapa 6 — Interface jogável desktop/mobile

## 1. O que foi implementado

Foi implementada a experiência jogável de `/game` com Laravel Livewire, Blade, Alpine.js, Tailwind CSS e um módulo JavaScript pequeno para Pointer Events.

O participante autenticado pode iniciar ou retomar uma partida, visualizar o snapshot real, selecionar palavras com mouse ou toque, acompanhar progresso e tempo, concluir a partida, abandoná-la explicitamente e consultar instruções sem sair da tela.

Pontuação não foi exibida porque esse domínio ainda não existe e pertence à Etapa 7.

## 2. Arquivos criados

- `app/Livewire/Game/GameBoard.php`
- `resources/views/livewire/game/game-board.blade.php`
- `resources/js/word-search-selection.js`
- `resources/js/game-clock.js`
- `tests/Feature/Livewire/Game/GameBoardTest.php`
- `tests/JavaScript/word-search-selection.test.js`
- `docs/ai/10-RELATORIO-ETAPA-6.md`

## 3. Arquivos modificados

- `routes/web.php`
- `config/denarius.php`
- `resources/views/game.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/css/app.css`
- `resources/js/app.js`
- `package.json`
- `tests/Feature/Auth/AuthorizationTest.php`
- `README.md`
- `docs/ai/00-CONTEXTO.md`
- `docs/ai/01-ARQUITETURA.md`
- `docs/ai/02-DOMINIO.md`
- `docs/ai/03-TASKS.md`
- `docs/ai/06-DIARIO.md`

## 4. Interface do jogo

### Grid

O grid é lido diretamente de `GameSession.grid`. Cada célula contém somente linha, coluna e letra. O HTML não recebe coordenadas inicial/final, direção ou placements de palavras pendentes.

Palavras encontradas recebem destaque permanente a partir dos snapshots já revelados. A seleção temporária possui contraste próprio.

### Termos

A lista apresenta `original_term`, preservando acentos e grafia educativa. Cada item informa textual e visualmente se está pendente ou encontrado, sem revelar posição.

### Progresso

Contador e barra usam `found_words_count` e `total_words` persistidos. Nenhum contador exclusivamente JavaScript existe.

### Cronômetro

Durante a partida, o relógio visual deriva de `started_at` e `Date.now()`, sem writes periódicos. Reload reconstrói o mesmo ponto de origem. Ao concluir ou abandonar, a interface usa `duration_seconds` oficial.

### Estados

- Sem sessão: chamada para iniciar.
- Ativa: grid interativo, progresso, tempo, termos e abandono.
- Concluída: grid bloqueado, progresso integral, duração oficial e nova partida.
- Abandonada: grid bloqueado, duração e nova partida.
- Processando: botões e seleção bloqueiam duplicidade visual.
- Erro: feedback curto e recuperável em região `aria-live`.

## 5. Interação

### Mouse/pointer e touch

`pointerdown`, `pointermove`, `pointerup` e `pointercancel` atendem mouse, caneta e toque com a mesma implementação. `touch-action: none`, seleção de texto e highlight nativo são limitados ao tabuleiro, sem bloquear o scroll da página inteira.

### Horizontal, vertical, diagonal e invertida

`cellsBetween()` aceita diferença de linha zero, diferença de coluna zero ou módulos iguais. O sinal do deslocamento preserva qualquer orientação e permite o sentido invertido.

Trajetórias irregulares são rejeitadas visualmente. No fim do gesto, somente início/fim seguem para o Livewire e o backend repete a validação autoritativa.

## 6. Integração com o domínio

### Criação/retomada

O componente resolve a sessão pelo usuário autenticado. Não recebe `user_id` ou `game_session_id`. Sem sessão ativa, `StartGameSessionAction` cria o snapshot; com sessão ativa, a tela apenas o retoma.

### Validação

`FindGameSessionWordAction` recebe quatro coordenadas inteiras. O componente não aceita texto, `found`, status, duração ou score do navegador.

### Conclusão

A última palavra conclui a sessão na action existente. O retorno desabilita o gesto e a nova renderização mostra status e duração oficiais.

### Abandono

Um `dialog` nativo solicita confirmação antes de chamar `AbandonGameSessionAction`. Fechar ou recarregar a página nunca abandona automaticamente.

### Idempotência

O JavaScript bloqueia submissões sobrepostas e o domínio mantém locks/idempotência. Repetir uma palavra mostra feedback sem incrementar o progresso.

## 7. Responsividade

### Desktop

A partir de `lg`, o grid ocupa a coluna principal e progresso/termos formam uma lateral sticky de 22rem.

### Tablet

Grid e painéis permanecem em uma coluna; a lista de termos usa até três colunas para aproveitar a largura.

### Mobile

Padding, gaps, raio e fonte das células usam valores fluidos. O tabuleiro ocupa 100% da área disponível, mantém células quadradas e não cria overflow horizontal da página. Os controles possuem altura mínima de toque.

### Validação visual

A revisão automatizada em navegador nas larguras 360, 390, 430, 768 e desktop foi tentada, mas não executada: a conexão do browser disponibilizado pelo ambiente foi recusada pela ponte nativa. Não foram produzidos screenshots nem alegada inspeção visual real. A revisão responsiva ficou limitada ao código, ao HTML renderizado nos testes, ao build CSS/JS e às regras mobile-first.

## 8. Segurança

- Ownership: todas as consultas são `whereBelongsTo($user)` e as actions reaplicam policies.
- Coordenadas: payload passa por validação de inteiros não negativos e pelas regras da Etapa 5.
- CSRF: requests usam o transporte padrão do Livewire e middleware `web`.
- Manipulação: nenhum método público aceita sessão, usuário, termo, status, score ou contadores.
- XSS: termos e feedback usam `{{ }}`; teste cobre snapshot malicioso.
- Placements: somente células de palavras já encontradas são reveladas.
- Rate limiting: GET `/game` e actions de início, seleção e abandono possuem limites; actions usam chave de usuário + IP.
- Concorrência: o frontend bloqueia nova submissão enquanto processa, sem substituir locks/idempotência do backend.

## 9. Testes

Foram adicionados 14 testes PHPUnit e 5 testes JavaScript.

PHPUnit cobre autenticação, início, grid/snapshot, lista, acerto, erro, coordenada manipulada, seleção reversa, duplicidade, rate limit, conclusão, abandono, isolamento entre usuários, reload e XSS.

O runner nativo do Node cobre trajetórias horizontais, verticais, diagonais, invertidas, irregulares e formatação do cronômetro.

Resultado:

```text
152 testes PHPUnit aprovados
1.127 assertions
5 testes JavaScript aprovados
0 falhas
```

Não foi adicionada uma infraestrutura E2E. A interação real de Pointer Events permanece como pendência de teste browser quando o ambiente oferecer uma conexão compatível.

## 10. Validações

- PHPUnit: aprovado com `php artisan test --compact`.
- JavaScript: aprovado com `pnpm test:js`.
- Pint: aprovado com `vendor/bin/pint --dirty --format agent`.
- Composer: `composer validate --strict` aprovado.
- Vite: `pnpm build` aprovado; apenas aviso do pacote opcional `fontaine`.
- Composer audit: nenhuma vulnerabilidade com `composer audit --locked`.
- pnpm audit: nenhuma vulnerabilidade em nível high.
- Rotas: 11 rotas de aplicação; `/game` segue autenticada e nenhuma API REST foi adicionada.
- `git diff --check`: aprovado.
- Análise estática: PHPStan/Larastan não está configurado.

## 11. Git

- Branch: `feat/playable-game-interface`.
- Commit funcional: `8ef16f5 feat: add playable word search interface`.
- Commit documental: `docs: add playable game interface report`.
- Push: branch publicada em `origin/feat/playable-game-interface`.
- Merge em `main`: não realizado.

## 12. Pendências

- Executar inspeção visual real e gesto touch em dispositivos/browser quando a automação estiver disponível.
- Adicionar E2E de Pointer Events se a stack de browser for incorporada ao projeto futuramente.
- O grid de 15 colunas usa células fluidas em telas estreitas; testes manuais em aparelhos físicos ainda são recomendados para ergonomia fina.
- Pontuação não existe no domínio e não foi antecipada.
- Ranking, mascote, sons e animações definitivas permanecem fora do escopo.

## 13. Próxima etapa

ETAPA 7 — Pontuação e regras de recompensa.

A próxima etapa deve implementar cálculo autoritativo no backend e apenas expor o resultado para esta interface, sem transferir fórmulas para JavaScript.
