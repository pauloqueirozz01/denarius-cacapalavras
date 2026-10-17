@extends('layouts.app')

@section('title', 'Denarius — Educação Financeira Criativa')

@section('content')
    <div class="mx-auto grid min-h-[calc(100vh-73px)] max-w-6xl items-center gap-12 px-5 py-16 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="flex flex-col items-start gap-7">
            <span class="rounded-full border border-denarius-300/30 bg-denarius-400/10 px-4 py-2 text-sm font-bold text-denarius-100">
                Aprenda. Jogue. Conquiste o ranking.
            </span>
            <div class="flex flex-col gap-5">
                <h1 class="max-w-3xl text-5xl font-black leading-[0.98] tracking-tight sm:text-7xl">
                    Finanças ficam mais claras quando viram
                    <span class="text-denarius-300">jogo.</span>
                </h1>
                <p class="max-w-2xl text-lg leading-8 text-denarius-100/80">
                    Prepare-se para encontrar conceitos do mercado financeiro, acumular pontos e disputar posições no evento Denarius.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('game') }}" class="rounded-xl bg-denarius-400 px-6 py-3 font-black text-white shadow-xl shadow-denarius-500/30 hover:bg-denarius-300 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                        Ir para o jogo
                    </a>
                @else
                    <a href="{{ route('game') }}" class="rounded-xl bg-denarius-400 px-6 py-3 font-black text-white shadow-xl shadow-denarius-500/30 hover:bg-denarius-300 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                        Jogar agora
                    </a>
                    <a href="{{ route('register') }}" class="rounded-xl border border-white/20 px-6 py-3 font-bold hover:border-denarius-200 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                        Criar minha conta
                    </a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-white/20 px-6 py-3 font-bold hover:border-denarius-200 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                        Já tenho conta
                    </a>
                @endauth
            </div>
        </section>

        <aside class="relative mx-auto w-full max-w-md" aria-label="Prévia do desafio">
            <div class="absolute inset-0 rotate-3 rounded-3xl bg-denarius-400/25 blur-sm"></div>
            <div class="relative rounded-3xl border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur-xl">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-denarius-200">Próximo desafio</p>
                        <p class="mt-1 text-xl font-black">Caça-palavras financeiro</p>
                    </div>
                    <span class="rounded-xl bg-emerald-300 px-3 py-2 text-sm font-black text-emerald-950">+100 pts</span>
                </div>
                <div class="grid grid-cols-6 gap-2 font-mono text-lg font-black" aria-hidden="true">
                    @foreach (str_split('JUROSRACAOESPIXRENDAATIVOLUCRO') as $letter)
                        <span class="grid aspect-square place-items-center rounded-lg border border-white/10 bg-denarius-950/50">{{ $letter }}</span>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
@endsection
