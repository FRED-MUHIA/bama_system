@props([
    'title',
    'subtitle' => null,
    'kicker' => null,
])

<div {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="page-header-main">
        @if($kicker)
            <div class="page-kicker">{{ $kicker }}</div>
        @endif
        <h1 class="page-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="page-actions">
            {{ $actions }}
        </div>
    @endisset
</div>
