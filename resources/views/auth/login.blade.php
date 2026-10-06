@extends('layouts.app')

@section('title', 'Entrar — Denarius')

@section('content')
    <div class="mx-auto flex min-h-[calc(100vh-73px)] max-w-6xl items-center justify-center px-5 py-12">
        <x-auth-card title="Bem-vindo de volta" description="Entre para continuar sua jornada de educação financeira.">
            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
                @csrf

                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-bold">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-denarius-500 focus:ring-4 focus:ring-denarius-100" aria-describedby="email-error">
                    @error('email')
                        <p id="email-error" class="text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="password" class="text-sm font-bold">Senha</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-denarius-500 focus:ring-4 focus:ring-denarius-100" aria-describedby="password-error">
                    @error('password')
                        <p id="password-error" class="text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input name="remember" type="checkbox" value="1" class="size-4 rounded border-slate-300 text-denarius-600 focus:ring-denarius-500">
                    Lembrar de mim
                </label>

                <button type="submit" class="rounded-xl bg-denarius-600 px-5 py-3 font-black text-white shadow-lg shadow-denarius-600/20 hover:bg-denarius-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-denarius-700">
                    Entrar
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                Ainda não participa?
                <a href="{{ route('register') }}" class="font-black text-denarius-700 underline decoration-denarius-300 underline-offset-4">Crie sua conta</a>
            </p>
        </x-auth-card>
    </div>
@endsection
