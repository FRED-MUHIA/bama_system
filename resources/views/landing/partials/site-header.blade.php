    <header class="bama-header sticky top-0 z-50">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 text-black no-underline">
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="bama-logo">
            </a>
            <div class="hidden items-center gap-5 text-sm font-bold text-black lg:flex">
                <a href="{{ route('landing') }}" class="hover:text-[#00A651]">Home</a>
                @foreach($headerLinks as $link)
                    @if(is_array($link) && ($link['label'] ?? '') && ($link['url'] ?? ''))
                        <a href="{{ $marketingUrl($link['url']) }}" class="hover:text-[#00A651]">{{ $link['label'] }}</a>
                    @endif
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                @if(data_get($headerContent, 'login_label') || data_get($headerContent, 'login_url'))
                    <a href="{{ $marketingUrl(data_get($headerContent, 'login_url', route('login')) ) }}" class="hidden rounded-lg border border-zinc-300 px-4 py-2 text-sm font-black md:inline-flex">{{ data_get($headerContent, 'login_label', 'Login') }}</a>
                @endif
                @if(data_get($headerContent, 'cta_label'))
                    <a href="{{ $marketingUrl(data_get($headerContent, 'cta_url'), route('register.account')) }}" class="rounded-lg bg-[#00A651] px-4 py-2 text-sm font-black text-white">{{ data_get($headerContent, 'cta_label', 'Start Free Trial') }}</a>
                @endif
            </div>
        </nav>
    </header>
