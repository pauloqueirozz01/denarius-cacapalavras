@extends('layouts.app')

@section('title', 'Jogo — Denarius')

@section('content')
    <div class="mx-auto flex min-h-[calc(100vh-73px)] max-w-4xl items-center justify-center px-5 py-16">
        <section class="w-full rounded-3xl border border-white/15 bg-white/10 p-8 text-center shadow-2xl backdrop-blur-xl sm:p-12">
            <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-denarius-400 text-3xl shadow-lg shadow-denarius-500/30" aria-hidden="true">🎮</span>
            <p class="mt-6 text-sm font-bold uppercase tracking-[0.24em] text-denarius-200">Olá, {{ auth()->user()->name }}</p>
            <h1 class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">Seu desafio está quase pronto.</h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-denarius-100/80">
                A fundação segura da sua conta já está funcionando. O caça-palavras financeiro será disponibilizado na próxima etapa.
            </p>
        </section>
    </div>
@endsection
