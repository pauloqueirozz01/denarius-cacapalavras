<section
    wire:poll.{{ $pollInterval }}s
    class="grid gap-6"
    aria-label="Classificação geral"
>
    <div class="flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-denarius-100/75">Atualização automática a cada {{ $pollInterval }} segundos</p>
        <div class="flex items-center gap-3">
            <span wire:loading class="text-xs font-semibold text-denarius-200">Atualizando…</span>
            <span wire:offline class="text-xs font-semibold text-amber-200">Sem conexão. A atualização será retomada quando a conexão voltar.</span>
            <button
                type="button"
                wire:click="$refresh"
                class="min-h-10 rounded-lg border border-white/15 px-3 py-2 text-xs font-bold text-white hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-denarius-300"
            >
                Atualizar agora
            </button>
        </div>
    </div>

    @if ($hasError)
        <section class="rounded-3xl border border-amber-300/25 bg-amber-400/10 p-6 text-center" role="status">
            <h2 class="text-xl font-black">Ranking temporariamente indisponível</h2>
            <p class="mt-2 text-sm text-white/70">Tente atualizar novamente em instantes.</p>
        </section>
    @elseif ($entries->total() === 0)
        <section class="rounded-3xl border border-white/10 bg-white/5 p-10 text-center" role="status">
            <h2 class="text-xl font-black">Ainda não há partidas concluídas</h2>
            <p class="mt-2 text-sm text-white/65">Conclua uma partida para aparecer na classificação.</p>
        </section>
    @else
        @if ($entries->currentPage() === 1)
            <section class="grid gap-3 sm:grid-cols-3" aria-label="Os três primeiros colocados">
                @foreach ($entries->getCollection()->take(3) as $entry)
                    <article
                        wire:key="podium-{{ $entry->position }}"
                        @class([
                            'rounded-3xl border p-5 shadow-xl',
                            'border-amber-200/40 bg-amber-300/10 sm:order-2' => $entry->position === 1,
                            'border-white/15 bg-white/5 sm:order-1' => $entry->position === 2,
                            'border-orange-200/25 bg-orange-300/5 sm:order-3' => $entry->position === 3,
                            'ring-2 ring-denarius-300' => $entry->userId === auth()->id(),
                        ])
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-denarius-200">{{ $entry->position }}º lugar</p>
                                <h2 class="mt-2 break-words text-xl font-black">{{ $entry->name }}</h2>
                            </div>
                            @if ($entry->userId === auth()->id())
                                <span class="rounded-full bg-denarius-300/20 px-2.5 py-1 text-xs font-bold text-denarius-100">Você</span>
                            @endif
                        </div>
                        <p class="mt-4 text-3xl font-black tabular-nums">{{ number_format($entry->score, 0, ',', '.') }} <span class="text-sm font-semibold text-white/55">pts</span></p>
                        <p class="mt-1 text-sm text-white/60">Tempo {{ $entry->durationSeconds === null ? '—' : sprintf('%02d:%02d', intdiv($entry->durationSeconds, 60), $entry->durationSeconds % 60) }}</p>
                    </article>
                @endforeach
            </section>
        @endif

        @php($visibleEntries = $entries->currentPage() === 1 ? $entries->getCollection()->slice(3) : $entries->getCollection())
        @if ($visibleEntries->isNotEmpty())
            <section class="overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-xl">
                <div class="grid grid-cols-[3rem_minmax(0,1fr)_5rem_4.5rem] gap-2 border-b border-white/10 px-3 py-3 text-[0.65rem] font-bold uppercase tracking-[0.12em] text-denarius-200 sm:grid-cols-[5rem_minmax(0,1fr)_8rem_7rem] sm:px-5">
                    <span>Pos.</span><span>Participante</span><span class="text-right">Pontos</span><span class="text-right">Tempo</span>
                </div>
                <ol>
                    @foreach ($visibleEntries as $entry)
                        <li
                            wire:key="rank-{{ $entry->position }}"
                            @class([
                                'grid grid-cols-[3rem_minmax(0,1fr)_5rem_4.5rem] items-center gap-2 border-b border-white/5 px-3 py-3 text-sm last:border-b-0 sm:grid-cols-[5rem_minmax(0,1fr)_8rem_7rem] sm:px-5',
                                'bg-denarius-300/10 font-bold ring-inset ring-1 ring-denarius-300/35' => $entry->userId === auth()->id(),
                            ])
                        >
                            <span class="font-mono text-denarius-100">{{ $entry->position }}</span>
                            <span class="min-w-0 truncate">{{ $entry->name }} @if ($entry->userId === auth()->id())<span class="ml-1 text-xs text-denarius-200">(você)</span>@endif</span>
                            <span class="text-right font-mono tabular-nums">{{ number_format($entry->score, 0, ',', '.') }}</span>
                            <span class="text-right font-mono text-xs tabular-nums text-white/70">{{ $entry->durationSeconds === null ? '—' : sprintf('%02d:%02d', intdiv($entry->durationSeconds, 60), $entry->durationSeconds % 60) }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        @if ($playerPosition instanceof \App\Data\LeaderboardEntry && ! $playerVisibleOnCurrentPage)
            <aside class="rounded-2xl border border-denarius-300/30 bg-denarius-400/10 p-4" aria-label="Sua posição atual">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-denarius-200">Sua melhor posição</p>
                <p class="mt-2 text-sm text-white">
                    {{ $playerPosition->position }}º lugar · {{ $playerPosition->name }} · {{ number_format($playerPosition->score, 0, ',', '.') }} pontos ·
                    {{ $playerPosition->durationSeconds === null ? 'tempo —' : sprintf('%02d:%02d', intdiv($playerPosition->durationSeconds, 60), $playerPosition->durationSeconds % 60) }}
                </p>
            </aside>
        @elseif ($playerPosition === null)
            <aside class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-white/70" role="status">
                Você ainda não tem uma partida concluída no ranking. <a href="{{ route('game') }}" class="font-bold text-denarius-200 underline underline-offset-4">Jogue uma partida</a> para entrar na classificação.
            </aside>
        @endif

        @if ($entries->hasPages())
            <nav aria-label="Paginação do ranking" class="rounded-2xl border border-white/10 bg-white/5 p-3">
                {{ $entries->links() }}
            </nav>
        @endif
    @endif
</section>
