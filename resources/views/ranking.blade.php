@extends('layouts.app')

@section('title', 'Ranking — Denarius')

@section('content')
    <div class="mx-auto min-h-[calc(100vh-73px)] max-w-6xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-4">
                <x-mascot state="idle" size="small" decorative class="hidden sm:grid" />
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-denarius-300">Denarius Finance Game</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">Ranking financeiro</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-denarius-100/70 sm:text-base">Veja as melhores partidas concluídas da comunidade.</p>
                </div>
            </div>
            <a href="{{ route('game') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-black text-denarius-900 transition hover:bg-denarius-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Voltar ao jogo</a>
        </header>

        <livewire:ranking.leaderboard-board />
    </div>
@endsection
