@props([
    'title',
    'href' => null,
])

<article {{ $attributes->merge(['class' => 'mobile-record-card']) }}>
    <div class="d-flex align-items-start justify-content-between gap-2 min-w-0">
        <div class="mobile-record-title">
            @if($href)
                <a href="{{ $href }}">{{ $title }}</a>
            @else
                {{ $title }}
            @endif
        </div>
        @isset($badge)
            <div class="flex-shrink-0">{{ $badge }}</div>
        @endisset
    </div>

    @isset($meta)
        <div class="mobile-record-meta">
            {{ $meta }}
        </div>
    @endisset

    @isset($actions)
        <div class="mobile-record-actions">
            {{ $actions }}
        </div>
    @endisset
</article>
