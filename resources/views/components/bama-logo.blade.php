@props([
    'variant' => 'auth',
    'mark' => false,
    'src' => null,
    'alt' => 'BAMA',
])

@php
    $logoPath = $mark ? 'images/bama-favicon.png' : 'images/bama-solutions-02.png';
    $logoSrc = $src ?: asset($logoPath);
@endphp

<span {{ $attributes->class(['bama-brand-logo', 'bama-brand-logo--'.$variant]) }}>
    <img src="{{ $logoSrc }}" alt="{{ $alt }}" width="{{ $mark ? 1042 : 870 }}" height="{{ $mark ? 1042 : 260 }}">
</span>
