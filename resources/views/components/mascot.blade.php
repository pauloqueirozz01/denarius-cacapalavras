@props([
    'state' => 'idle',
    'size' => 'medium',
    'caption' => null,
    'decorative' => false,
])

@php
    $stateCaptions = [
        'idle' => 'Pronta para o desafio',
        'correct' => 'Boa! Mandou bem',
        'error' => 'Vamos tentar outra vez',
        'celebration' => 'Você está avançando!',
        'victory' => 'Vitória! Todos os termos encontrados',
        'abandoned' => 'Até a próxima partida',
    ];
    $state = array_key_exists($state, $stateCaptions) ? $state : 'idle';
    $size = in_array($size, ['small', 'medium', 'large'], true) ? $size : 'medium';
    $stateAssetPaths = [
        'idle' => 'images/mascot/idle.webp',
        'correct' => 'images/mascot/correct.webp',
        'error' => 'images/mascot/error.webp',
        'celebration' => 'images/mascot/celebration.webp',
        'victory' => 'images/mascot/victory.webp',
        'abandoned' => 'images/mascot/abandoned.webp',
    ];
    $stateAssetPath = $stateAssetPaths[$state];
    $hasStateAsset = is_file(public_path($stateAssetPath));
    $assetPath = $hasStateAsset
        ? asset($stateAssetPath)
        : asset('images/mascot/denarius-jaguar-placeholder.svg');
@endphp

<figure
    {{ $attributes->class([
        'denarius-mascot',
        "denarius-mascot--{$state}",
        "denarius-mascot--{$size}",
    ]) }}
    data-mascot-state="{{ $state }}"
>
    <img
        src="{{ $assetPath }}"
        alt="{{ $decorative ? '' : ($hasStateAsset ? 'Onça Denarius em estado: '.$stateCaptions[$state] : 'Ilustração pixel-art temporária da onça Denarius') }}"
        @if ($decorative) aria-hidden="true" @endif
        width="128"
        height="128"
        loading="lazy"
        decoding="async"
    >
    @unless ($decorative)
        <figcaption>{{ $caption ?? $stateCaptions[$state] }}</figcaption>
    @endunless
</figure>
