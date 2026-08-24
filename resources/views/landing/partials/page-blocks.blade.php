@foreach(($blocks ?? []) as $block)
    @php
        $type = $block['type'] ?? 'text';
        $items = $block['items'] ?? [];
    @endphp

    @if($type === 'hero')
        <section class="bg-black px-5 py-16 text-white md:py-20">
            <div class="mx-auto max-w-5xl">
                @if(!empty($block['eyebrow']))<p class="text-sm font-black uppercase text-[#79D9A3]">{{ $block['eyebrow'] }}</p>@endif
                <h1 class="mt-4 text-4xl font-black leading-tight md:text-6xl">{{ $block['title'] ?? 'Page title' }}</h1>
                @if(!empty($block['body']))<p class="mt-5 max-w-3xl text-lg leading-8 text-white/80">{!! nl2br(e($block['body'])) !!}</p>@endif
                @if(!empty($block['button_label']) && !empty($block['button_url']))
                    <a href="{{ $block['button_url'] }}" class="mt-8 inline-flex rounded-lg bg-[#00A651] px-6 py-3 text-sm font-black uppercase text-white">{{ $block['button_label'] }}</a>
                @endif
            </div>
        </section>
    @elseif($type === 'cards')
        <section class="bg-[#F7F8F5] px-5 py-12">
            <div class="mx-auto max-w-7xl">
                @if(!empty($block['eyebrow']))<p class="bama-eyebrow">{{ $block['eyebrow'] }}</p>@endif
                @if(!empty($block['title']))<h2 class="mt-3 text-3xl font-black">{{ $block['title'] }}</h2>@endif
                @if(!empty($block['body']))<p class="mt-3 max-w-3xl leading-7 text-black">{!! nl2br(e($block['body'])) !!}</p>@endif
                <div class="mt-6 grid gap-3 md:grid-cols-3">
                    @foreach($items as $item)
                        <article class="bama-card p-4">
                            <h3 class="font-black">{{ is_array($item) ? ($item['title'] ?? 'Item') : $item }}</h3>
                            @if(is_array($item) && !empty($item['copy']))<p class="mt-2 text-sm leading-6">{{ $item['copy'] }}</p>@endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @elseif($type === 'cta')
        <section class="px-5 py-14">
            <div class="mx-auto max-w-6xl rounded-lg bg-black p-8 text-center text-white">
                @if(!empty($block['eyebrow']))<p class="bama-eyebrow">{{ $block['eyebrow'] }}</p>@endif
                <h2 class="mt-3 text-3xl font-black">{{ $block['title'] ?? 'Ready to start?' }}</h2>
                @if(!empty($block['body']))<p class="mx-auto mt-3 max-w-2xl leading-7 text-white/80">{!! nl2br(e($block['body'])) !!}</p>@endif
                @if(!empty($block['button_label']) && !empty($block['button_url']))
                    <a href="{{ $block['button_url'] }}" class="mt-6 inline-flex rounded-lg bg-[#00A651] px-6 py-3 text-sm font-black uppercase text-white">{{ $block['button_label'] }}</a>
                @endif
            </div>
        </section>
    @else
        <section class="bg-[#F7F8F5] px-5 py-12">
            <div class="bama-card mx-auto max-w-4xl p-6">
                @if(!empty($block['eyebrow']))<p class="bama-eyebrow">{{ $block['eyebrow'] }}</p>@endif
                @if(!empty($block['title']))<h2 class="mt-3 text-3xl font-black">{{ $block['title'] }}</h2>@endif
                @if(!empty($block['body']))<p class="mt-4 leading-8">{!! nl2br(e($block['body'])) !!}</p>@endif
                @if(!empty($block['button_label']) && !empty($block['button_url']))
                    <a href="{{ $block['button_url'] }}" class="mt-6 inline-flex rounded-lg bg-[#00A651] px-5 py-3 text-sm font-black text-white">{{ $block['button_label'] }}</a>
                @endif
            </div>
        </section>
    @endif
@endforeach
