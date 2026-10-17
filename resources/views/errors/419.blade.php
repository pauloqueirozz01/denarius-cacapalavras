@extends('errors.layout')

@section('title', 'Sessão expirada')
@section('code', '419')
@section('heading', 'Sua sessão expirou')
@section('message', 'Por segurança, esta página ficou aberta tempo demais ou foi enviada duas vezes. Recarregue a página e tente de novo.')

@section('actions')
    <a href="{{ url()->previous() }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-black text-denarius-900 transition hover:bg-denarius-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
        Voltar e tentar de novo
    </a>
@endsection
