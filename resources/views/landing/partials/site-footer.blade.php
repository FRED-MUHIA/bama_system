    <footer class="border-t border-zinc-200 bg-[#F1F3EE] px-5 py-10 text-zinc-700">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1.2fr_2fr]">
            <div>
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="bama-logo">
                <p class="mt-4 max-w-sm leading-7">{{ data_get($footerContent, 'body', 'Enterprise SaaS for ERP, CRM, finance, projects, documents, and industry operations.') }}</p>
                <p class="mt-3 text-sm">{{ data_get($footerContent, 'email', 'sales@bama.co.ke') }}<br>{{ data_get($footerContent, 'phone', '+254 700 000 000') }}</p>
            </div>
            <div class="grid gap-8 sm:grid-cols-4">
                @foreach($footerColumns as $column)
                    @php
                        $heading = is_array($column) ? ($column['heading'] ?? '') : '';
                        $links = is_array($column) ? ($column['links'] ?? []) : [];
                    @endphp
                    @continue(! $heading)
                    <div>
                        <h3 class="font-black text-black">{{ $heading }}</h3>
                        <div class="mt-3 grid gap-2 text-sm">
                            @foreach($links as $link)
                                @php
                                    $label = is_array($link) ? ($link['label'] ?? '') : $link;
                                    $url = is_array($link) ? $marketingUrl($link['url'] ?? route('landing'), route('landing')) : route('landing');
                                @endphp
                                @if($label)
                                    <a href="{{ $url }}" class="hover:text-[#00A651]">{{ $label }}</a>
                                @endif
                            @endforeach
                        </div>
                        @if(strtolower($heading) === 'legal')
                            <div class="mt-5" data-bama-install-card hidden>
                                <button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#00A651] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#007A3B] focus:outline-none focus:ring-2 focus:ring-[#00A651] focus:ring-offset-2" data-bama-install hidden>
                                    <i class="bi bi-download"></i>
                                    <span>Download App</span>
                                </button>
                                <p class="mt-2 text-sm text-zinc-600" data-bama-ios-install hidden>On iPhone or iPad, tap Share, then Add to Home Screen.</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </footer>
