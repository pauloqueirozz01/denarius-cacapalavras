@extends('layouts.app')

@section('title', 'Criar conta — Denarius')

@section('content')
    <div class="mx-auto flex min-h-[calc(100vh-73px)] max-w-6xl items-center justify-center px-5 py-12">
        <x-auth-card title="Entre no desafio" description="Crie sua conta de participante e prepare-se para aprender jogando.">
            <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5" data-submit-once>
                @csrf

                <div class="flex flex-col gap-2">
                    <label for="name" class="text-sm font-bold">Nome</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" maxlength="120" class="rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-denarius-500 focus:ring-4 focus:ring-denarius-100" aria-describedby="name-error">
                    @error('name')
                        <p id="name-error" class="text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-bold">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-denarius-500 focus:ring-4 focus:ring-denarius-100" aria-describedby="email-error">
                    @error('email')
                        <p id="email-error" class="text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label for="password" class="text-sm font-bold">Senha</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-denarius-500 focus:ring-4 focus:ring-denarius-100" aria-describedby="password-help password-error">
                        <p id="password-help" class="text-xs leading-5 text-slate-500">Mínimo de 8 caracteres, com maiúscula, minúscula e número.</p>
                        @error('password')
                            <p id="password-error" class="text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="password_confirmation" class="text-sm font-bold">Confirmar senha</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-denarius-500 focus:ring-4 focus:ring-denarius-100">
                    </div>
                </div>

                <button type="submit" data-submitting-label="Criando conta…" class="rounded-xl bg-denarius-600 disabled:cursor-wait disabled:opacity-70 px-5 py-3 font-black text-white shadow-lg shadow-denarius-600/20 hover:bg-denarius-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-denarius-700">
                    Criar conta
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                Já tem uma conta?
                <a href="{{ route('login') }}" class="font-black text-denarius-700 underline decoration-denarius-300 underline-offset-4">Entre aqui</a>
            </p>
        </x-auth-card>
    </div>
@endsection
