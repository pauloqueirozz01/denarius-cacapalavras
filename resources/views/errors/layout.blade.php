{{-- Standalone on purpose: no session, auth or database calls, so it still renders when those fail. --}}
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>@yield('title') — Denarius</title>

        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-denarius-950 font-sans text-white antialiased">
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-24 top-1/4 size-80 rounded-full bg-denarius-500/20 blur-3xl"></div>
            <div class="absolute -right-24 top-10 size-96 rounded-full bg-fuchsia-500/15 blur-3xl"></div>
        </div>

        <main class="relative z-10 mx-auto flex min-h-screen max-w-2xl items-center px-5 py-16">
            <section class="w-full rounded-[2rem] border border-white/15 bg-white/10 px-6 py-12 text-center shadow-2xl backdrop-blur-xl sm:px-12">
                <p class="text-xs font-bold uppercase tracking-[0.28em] text-denarius-300">Denarius · Erro @yield('code')</p>
                <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">@yield('heading')</h1>
                <p class="mx-auto mt-4 max-w-lg text-base leading-7 text-denarius-100/80">@yield('message')</p>

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    @yield('actions')
                    <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/20 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        Voltar ao início
                    </a>
                </div>
            </section>
        </main>
    </body>
</html>
