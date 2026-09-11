@props([
    'title' => null,
    'intro' => null,
    'variant' => 'card',
    'logoSrc' => null,
    'logoAlt' => 'BAMA',
    'showLogo' => true,
    'backUrl' => null,
])

<div {{ $attributes->class(['auth-layout', 'auth-layout--'.$variant]) }}>
    @if ($variant === 'bare')
        {{ $slot }}
    @else
        <section class="auth-layout-card">
            @if ($backUrl)
                <a href="{{ $backUrl }}" class="auth-layout-back" aria-label="Back">
                    <i class="bi bi-arrow-left"></i>
                </a>
            @endif

            @if ($showLogo)
                <div class="auth-layout-orb" aria-hidden="true">
                    <x-bama-logo variant="splash" mark alt="" />
                </div>
            @endif

            @if ($title || $intro)
                <header class="auth-layout-heading">
                    @if ($title)
                        <h1>{{ $title }}</h1>
                    @endif
                    @if ($intro)
                        <p>{{ $intro }}</p>
                    @endif
                </header>
            @endif

            <div class="auth-layout-content">
                {{ $slot }}
            </div>
        </section>
    @endif
</div>
