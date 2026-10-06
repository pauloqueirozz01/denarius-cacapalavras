<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name'))</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-denarius-950 font-sans text-white antialiased">
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-24 top-1/4 size-80 rounded-full bg-denarius-500/20 blur-3xl"></div>
            <div class="absolute -right-24 top-10 size-96 rounded-full bg-fuchsia-500/15 blur-3xl"></div>
            <div class="denarius-grid absolute inset-0 opacity-30"></div>
        </div>

        <header class="relative z-10 border-b border-white/10 bg-denarius-950/75 backdrop-blur-xl">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4" aria-label="Navegação principal">
                <a href="{{ route('home') }}" class="group flex items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-denarius-300">
                    <span class="grid size-10 place-items-center rounded-xl bg-denarius-500 font-black shadow-lg shadow-denarius-500/30">D</span>
                    <span class="hidden sm:block">
                        <span class="block text-lg font-black tracking-[0.18em]">DENARIUS</span>
                        <span class="block text-xs text-denarius-200">Educação Financeira Criativa</span>
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('game') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-denarius-100 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-denarius-300">
                            Jogo
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-white/15 px-3 py-2 text-sm font-semibold hover:border-denarius-300 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-denarius-300">
                                Sair
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-denarius-100 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-denarius-300">
                            Entrar
                        </a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-denarius-800 shadow-lg shadow-black/20 hover:bg-denarius-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            Criar conta
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="relative z-10">
            @yield('content')
        </main>
    </body>
</html>
