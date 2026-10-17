@extends('errors.layout')

@php($retryAfter = (int) ($exception->getHeaders()['Retry-After'] ?? 60))

@section('title', 'Muitas tentativas')
@section('code', '429')
@section('heading', 'Muitas tentativas em pouco tempo')
@section('message')
    Muita gente está acessando ao mesmo tempo a partir desta rede. Aguarde {{ $retryAfter }} segundos e tente de novo.
@endsection

@section('actions')
    <a href="{{ url()->previous() }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-black text-denarius-900 transition hover:bg-denarius-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
        Tentar de novo
    </a>
@endsection
