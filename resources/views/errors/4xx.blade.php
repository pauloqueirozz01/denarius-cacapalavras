@extends('errors.layout')

@section('title', 'Não foi possível abrir a página')
@section('code', (string) $exception->getStatusCode())
@section('heading', 'Não foi possível abrir esta página')
@section('message', 'O pedido não pôde ser atendido. Volte para o início e tente de novo pelo menu.')
