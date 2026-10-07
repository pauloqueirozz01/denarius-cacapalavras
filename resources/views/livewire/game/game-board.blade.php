@php
    $isActive = $session?->status === \App\Enums\GameSessionStatus::Active;
    $isCompleted = $session?->status === \App\Enums\GameSessionStatus::Completed;
    $isAbandoned = $session?->status === \App\Enums\GameSessionStatus::Abandoned;
    $visualMascotState = $isCompleted ? 'victory' : ($isAbandoned ? 'abandoned' : $mascotState);
    $progress = $session === null ? 0 : (int) round(($session->found_words_count / $session->total_words) * 100);
    $startedAtMilliseconds = $session?->started_at?->getTimestamp() * 1000;
    $scoringRules = $session?->generation_config['scoring'] ?? config('denarius.scoring');
    $scoringRules = is_array($scoringRules) ? $scoringRules : config('denarius.scoring');
    $speedBonusTiers = $scoringRules['speed_bonus_tiers'] ?? [];
    $speedBonusTiers = is_array($speedBonusTiers)
        ? array_filter($speedBonusTiers, fn (mixed $tier): bool => is_array($tier) && isset($tier['up_to_seconds'], $tier['points']))
        : [];
@endphp

<div
    class="mx-auto min-h-[calc(100vh-73px)] max-w-7xl px-3 py-6 sm:px-5 sm:py-10 lg:px-8"
    x-data
>
    <header class="mb-6 flex flex-col gap-4 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.28em] text-denarius-300">Denarius Finance Game</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">Caça-Palavras Financeiro</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-denarius-100/70 sm:text-base">
                Encontre conceitos que fazem parte de uma vida financeira mais inteligente.
            </p>
        </div>

        <button
            type="button"
            aria-haspopup="dialog"
            aria-controls="tutorial-dialog"
            class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:border-denarius-300 hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-denarius-300"
            x-on:click="$refs.tutorial.showModal()"
        >
            <span aria-hidden="true">?</span>
            Como jogar
        </button>
    </header>

    <div
        aria-live="polite"
        aria-atomic="true"
        @class([
            'mb-5 rounded-2xl border px-4 py-3 text-sm font-semibold shadow-lg' => $feedbackMessage !== '',
            'hidden' => $feedbackMessage === '',
            'border-emerald-300/30 bg-emerald-400/15 text-emerald-100' => $feedbackTone === 'success',
            'border-rose-300/30 bg-rose-400/15 text-rose-100' => $feedbackTone === 'error',
            'border-denarius-300/30 bg-denarius-400/15 text-denarius-100' => $feedbackTone === 'info',
        ])
    >
        {{ $feedbackMessage }}
    </div>

    @if ($session === null)
        <section class="mx-auto grid max-w-3xl place-items-center rounded-[2rem] border border-white/15 bg-white/10 px-6 py-14 text-center shadow-2xl backdrop-blur-xl sm:px-12 sm:py-20">
            <x-mascot :state="$visualMascotState" size="large" class="mt-7" />
            <p class="mt-7 text-sm font-bold uppercase tracking-[0.24em] text-denarius-200">Olá, {{ auth()->user()->name }}</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-5xl">Seu desafio começa agora.</h2>
            <p class="mt-4 max-w-xl text-base leading-7 text-denarius-100/75 sm:text-lg">
                Um novo tabuleiro será criado no servidor com termos financeiros escolhidos especialmente para esta partida.
            </p>
            <button
                type="button"
                wire:click="startGame"
                wire:loading.attr="disabled"
                wire:target="startGame"
                class="mt-8 inline-flex min-h-12 items-center justify-center rounded-xl bg-white px-7 py-3 font-black text-denarius-900 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:bg-denarius-50 disabled:cursor-wait disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
            >
                <span wire:loading.remove wire:target="startGame">Iniciar partida</span>
                <span wire:loading wire:target="startGame">Montando tabuleiro…</span>
            </button>
        </section>
    @else
        @if ($isCompleted || $isAbandoned)
            <section @class([
                'mb-6 rounded-3xl border p-5 shadow-2xl sm:p-7',
                'border-emerald-300/25 bg-emerald-400/10' => $isCompleted,
                'border-amber-300/25 bg-amber-400/10' => $isAbandoned,
            ])>
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-4 sm:gap-5">
                        <x-mascot :state="$visualMascotState" size="small" class="shrink-0" />
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.24em] {{ $isCompleted ? 'text-emerald-200' : 'text-amber-200' }}">
                                {{ $isCompleted ? 'Desafio concluído' : 'Partida encerrada' }}
                            </p>
                            <h2 class="mt-2 text-2xl font-black sm:text-3xl">
                                {{ $isCompleted ? 'Parabéns! Você encontrou todos os termos.' : 'Esta partida foi abandonada.' }}
                            </h2>
                            <p class="mt-2 text-sm text-white/70">
                                Tempo registrado: <strong class="text-white">{{ sprintf('%02d:%02d', intdiv($session->duration_seconds ?? 0, 60), ($session->duration_seconds ?? 0) % 60) }}</strong>
                            </p>
                            <p class="mt-1 text-sm text-white/70">
                                {{ $isCompleted ? 'Pontuação final' : 'Pontuação conquistada' }}: <strong class="text-white">{{ number_format($session->score, 0, ',', '.') }}</strong>
                            </p>
                            @if ($isCompleted && $scoreBreakdown !== null)
                                <p class="mt-2 text-sm text-white/65">
                                    Palavras: +{{ number_format($scoreBreakdown->wordPoints, 0, ',', '.') }}
                                    · Conclusão: +{{ number_format($scoreBreakdown->completionBonus, 0, ',', '.') }}
                                    · Velocidade: +{{ number_format($scoreBreakdown->speedBonus, 0, ',', '.') }}
                                </p>
                            @endif
                            @if ($isCompleted)
                                <p class="mt-2 text-sm text-white/75">
                                    @if ($rankingPosition !== null)
                                        Sua melhor posição: <strong class="text-white">{{ $rankingPosition->position }}º lugar</strong>
                                    @elseif ($rankingUnavailable)
                                        Sua posição será consultada ao abrir o ranking.
                                    @else
                                        Seu resultado entrará na classificação geral.
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col gap-3 sm:items-end">
                        @if ($isCompleted)
                            <a href="{{ route('ranking') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/20 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Ver ranking</a>
                        @endif
                        <button
                            type="button"
                            wire:click="startGame"
                            wire:loading.attr="disabled"
                            wire:target="startGame"
                            class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-xl bg-white px-6 py-3 font-black text-denarius-900 transition hover:bg-denarius-50 disabled:cursor-wait disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                        >
                            <span wire:loading.remove wire:target="startGame">{{ $isCompleted ? 'Jogar novamente' : 'Nova partida' }}</span>
                            <span wire:loading wire:target="startGame">Preparando…</span>
                        </button>
                    </div>
                </div>
            </section>
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-7">
            <section class="min-w-0 rounded-[1.75rem] border border-white/15 bg-white/10 p-2.5 shadow-2xl backdrop-blur-xl sm:p-5">
                <div class="mb-3 flex items-center justify-between gap-3 px-1 sm:mb-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-denarius-200">Tabuleiro</p>
                        <p class="mt-1 text-xs text-white/55">Arraste da primeira à última letra</p>
                    </div>
                    <span wire:loading.flex wire:target="selectWord" class="items-center gap-2 rounded-full bg-denarius-300/15 px-3 py-1.5 text-xs font-bold text-denarius-100">
                        <span class="size-2 animate-pulse rounded-full bg-denarius-300"></span>
                        Validando
                    </span>
                </div>

                <div
                    wire:key="word-search-board-{{ $session->id }}-{{ $session->status->value }}"
                    class="word-search-board mx-auto grid w-full max-w-[42rem] touch-none select-none gap-[clamp(1px,0.35vw,4px)] rounded-2xl bg-denarius-950/65 p-[clamp(4px,1.2vw,10px)] shadow-inner ring-1 ring-white/10"
                    style="grid-template-columns: repeat({{ $session->columns }}, minmax(0, 1fr));"
                    role="grid"
                    aria-label="Tabuleiro de caça-palavras com {{ $session->rows }} linhas e {{ $session->columns }} colunas"
                    aria-disabled="{{ $isActive ? 'false' : 'true' }}"
                    tabindex="0"
                    x-data="wordSearchBoard($wire, @js(! $isActive))"
                    x-on:pointerdown="beginSelection($event)"
                    x-on:pointermove="moveSelection($event)"
                    x-on:pointerup="finishSelection($event)"
                    x-on:pointercancel="cancelSelection($event)"
                    x-on:contextmenu.prevent
                >
                    @foreach ($session->grid as $rowIndex => $row)
                        @foreach ($row as $columnIndex => $letter)
                            @php($isFoundCell = isset($foundCells["{$rowIndex}:{$columnIndex}"]))
                            <span
                                wire:key="cell-{{ $session->id }}-{{ $rowIndex }}-{{ $columnIndex }}"
                                data-word-cell
                                data-row="{{ $rowIndex }}"
                                data-column="{{ $columnIndex }}"
                                role="gridcell"
                                aria-label="Linha {{ $rowIndex + 1 }}, coluna {{ $columnIndex + 1 }}, letra {{ $letter }}{{ $isFoundCell ? ', encontrada' : '' }}"
                                @class([
                                    'grid aspect-square min-w-0 place-items-center rounded-[clamp(3px,0.7vw,8px)] font-black leading-none text-[clamp(0.58rem,2.1vw,1.15rem)] transition duration-150',
                                    'bg-emerald-300 text-emerald-950 shadow-md shadow-emerald-400/25 ring-1 ring-emerald-100/70' => $isFoundCell,
                                    'bg-white/8 text-white ring-1 ring-white/8 pointer-fine:hover:bg-white/15' => ! $isFoundCell,
                                ])
                                x-bind:class="isSelected({{ $rowIndex }}, {{ $columnIndex }}) ? '!bg-amber-300 !text-denarius-950 !ring-amber-100 scale-95' : ''"
                            >
                                {{ $letter }}
                            </span>
                        @endforeach
                    @endforeach
                </div>

                <p class="mt-3 min-h-5 px-2 text-center text-xs font-semibold text-amber-200" x-text="selectionHint" aria-live="polite"></p>
            </section>

            <aside class="grid gap-5 lg:sticky lg:top-5">
                <section class="flex items-center gap-4 rounded-3xl border border-denarius-300/15 bg-denarius-900/35 px-4 py-3 shadow-xl sm:justify-center lg:justify-start" aria-label="Mascote da partida">
                    <x-mascot :state="$visualMascotState" size="small" class="shrink-0" />
                    <p class="text-sm leading-6 text-denarius-100/75">{{ $visualMascotState === 'idle' ? 'Procure os termos em todas as direções.' : ($feedbackMessage !== '' ? $feedbackMessage : 'Continue no seu ritmo.') }}</p>
                </section>

                <section class="rounded-3xl border border-white/15 bg-white/10 p-5 shadow-xl backdrop-blur-xl sm:p-6">
                    <div class="grid grid-cols-3 gap-2 sm:gap-3">
                        <div
                            wire:key="game-clock-{{ $session->id }}-{{ $session->status->value }}"
                            class="rounded-2xl bg-denarius-950/55 p-4 ring-1 ring-white/10"
                            x-data="gameClock(@js($startedAtMilliseconds), @js($session->duration_seconds), @js($isActive))"
                        >
                            <p class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-denarius-200">Progresso</p>
                            <p class="mt-2 text-2xl font-black">{{ $session->found_words_count }}<span class="text-sm text-white/45"> / {{ $session->total_words }}</span></p>
                        </div>
                        <div class="rounded-2xl bg-denarius-950/55 p-4 ring-1 ring-white/10">
                            <p class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-denarius-200">Tempo</p>
                            <p class="mt-2 font-mono text-2xl font-black tabular-nums" x-text="formattedTime">00:00</p>
                        </div>
                        <div class="rounded-2xl bg-denarius-950/55 p-3 ring-1 ring-white/10 sm:p-4">
                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.14em] text-denarius-200 sm:text-[0.65rem] sm:tracking-[0.2em]">Pontuação</p>
                            <p class="mt-2 text-xl font-black tabular-nums sm:text-2xl">{{ number_format($session->score, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10" role="progressbar" aria-label="Progresso da partida" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
                        <div class="h-full rounded-full bg-gradient-to-r from-denarius-300 to-fuchsia-400 transition-[width] duration-500" style="width: {{ $progress }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-white/55">{{ $session->found_words_count }} de {{ $session->total_words }} palavras encontradas</p>
                </section>

                <section class="rounded-3xl border border-white/15 bg-white/10 p-5 shadow-xl backdrop-blur-xl sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-black">Termos da partida</h2>
                        <span class="rounded-full bg-denarius-300/15 px-2.5 py-1 text-xs font-bold text-denarius-100">{{ $words->count() }}</span>
                    </div>

                    <ul class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-1" aria-label="Lista de termos financeiros">
                        @foreach ($words as $word)
                            <li
                                wire:key="word-{{ $word->id }}"
                                @class([
                                    'flex min-h-10 items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold transition',
                                    'bg-emerald-300/15 text-emerald-100 line-through decoration-emerald-300/70' => $word->is_found,
                                    'bg-white/5 text-white/75' => ! $word->is_found,
                                ])
                            >
                                <span class="grid size-5 shrink-0 place-items-center rounded-full {{ $word->is_found ? 'bg-emerald-300 text-emerald-950' : 'border border-white/25 text-transparent' }}" aria-hidden="true">✓</span>
                                <span>{{ $word->original_term }}</span>
                                <span class="sr-only">{{ $word->is_found ? 'encontrado' : 'pendente' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($isActive)
                    <button type="button" class="min-h-11 rounded-xl border border-rose-300/25 bg-rose-400/10 px-4 py-2.5 text-sm font-bold text-rose-100 transition hover:border-rose-200 hover:bg-rose-400/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-300" x-on:click="$refs.abandon.showModal()">
                        Abandonar partida
                    </button>
                @endif
            </aside>
        </div>
    @endif

    <dialog id="tutorial-dialog" x-ref="tutorial" aria-modal="true" class="m-auto max-h-[90vh] w-[min(92vw,38rem)] overflow-y-auto rounded-3xl border border-white/15 bg-denarius-950 p-0 text-white shadow-2xl backdrop:bg-black/70">
        <section class="p-5 sm:p-8" aria-labelledby="tutorial-title" aria-describedby="tutorial-description">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-denarius-300">Instruções</p>
                    <h2 id="tutorial-title" class="mt-2 text-2xl font-black">Como jogar</h2>
                </div>
                <button type="button" class="grid size-10 place-items-center rounded-full bg-white/10 text-xl hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-denarius-300" x-on:click="$refs.tutorial.close()" aria-label="Fechar instruções">×</button>
            </div>
            <p id="tutorial-description" class="mt-3 text-sm leading-6 text-white/70">Encontre os conceitos financeiros e acompanhe sua evolução. Você pode consultar estas instruções quando quiser.</p>

            <div class="mt-5 rounded-2xl border border-denarius-300/20 bg-denarius-900/35 p-4" role="group" aria-label="Exemplo de seleção da palavra PIX">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-denarius-200">Exemplo de seleção</p>
                <div class="mt-3 flex items-center gap-2 font-mono text-lg font-black" aria-hidden="true">
                    <span class="grid size-9 place-items-center rounded-lg bg-amber-300 text-denarius-950">P</span>
                    <span class="text-denarius-300">→</span>
                    <span class="grid size-9 place-items-center rounded-lg bg-amber-300 text-denarius-950">I</span>
                    <span class="text-denarius-300">→</span>
                    <span class="grid size-9 place-items-center rounded-lg bg-amber-300 text-denarius-950">X</span>
                </div>
                <p class="sr-only">Arraste em linha reta da letra P até a letra X.</p>
            </div>

            <ol class="mt-5 grid gap-3 text-sm leading-6 text-denarius-100/80">
                <li><strong class="text-white">Objetivo:</strong> encontre todos os termos da lista. Clique/toque na primeira letra e arraste até a última.</li>
                <li><strong class="text-white">Direções:</strong> as palavras aparecem na horizontal, vertical ou diagonal, também ao contrário.</li>
                <li><strong class="text-white">Pontos:</strong> cada palavra encontrada vale {{ (int) ($scoringRules['points_per_word'] ?? 0) }} pontos. Completar a partida concede mais {{ (int) ($scoringRules['completion_bonus'] ?? 0) }}.</li>
                <li><strong class="text-white">Velocidade:</strong>
                    @if (count($speedBonusTiers) > 0)
                        @foreach ($speedBonusTiers as $tier)
                            até {{ (int) $tier['up_to_seconds'] }} s: +{{ (int) $tier['points'] }}@if (! $loop->last); @endif
                        @endforeach
                        pontos; acima da última faixa não há bônus.
                    @else
                        o bônus depende do tempo e das regras desta partida.
                    @endif
                </li>
                <li><strong class="text-white">Ranking:</strong> partidas concluídas disputam a classificação; nela vale sua melhor partida.</li>
                <li><strong class="text-white">Jogar novamente:</strong> ao terminar, escolha “Jogar novamente”. Seu resultado anterior fica salvo.</li>
            </ol>
        </section>
    </dialog>

    <dialog x-ref="abandon" class="m-auto w-[min(92vw,28rem)] rounded-3xl border border-rose-300/20 bg-denarius-950 p-0 text-white shadow-2xl backdrop:bg-black/70">
        <section class="p-6 sm:p-8" aria-labelledby="abandon-title">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-rose-300">Confirmar abandono</p>
            <h2 id="abandon-title" class="mt-2 text-2xl font-black">Tem certeza?</h2>
            <p class="mt-3 text-sm leading-6 text-white/65">A partida atual será encerrada e não poderá ser retomada. Seu histórico continuará preservado.</p>
            <div class="mt-7 grid grid-cols-2 gap-3">
                <button type="button" class="min-h-11 rounded-xl border border-white/15 px-4 py-2 font-bold hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-denarius-300" x-on:click="$refs.abandon.close()">Continuar jogando</button>
                <button type="button" wire:click="abandonGame" wire:loading.attr="disabled" wire:target="abandonGame" class="min-h-11 rounded-xl bg-rose-500 px-4 py-2 font-bold text-white hover:bg-rose-400 disabled:cursor-wait disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-rose-200" x-on:click="$refs.abandon.close()">Abandonar</button>
            </div>
        </section>
    </dialog>
</div>
