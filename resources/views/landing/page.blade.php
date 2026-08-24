@extends('layouts.marketing', [
    'title' => $page->meta_title ?: $page->title,
    'metaDescription' => $page->meta_description,
])

@section('body')
<main class="bama-page min-h-screen">
    <header class="bama-header sticky top-0 z-50">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 text-black no-underline">
                <img src="{{ asset('images/bama-logo-cropped.png') }}" alt="BAMA" class="bama-logo">
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('landing') }}" class="hidden px-3 py-2 text-sm font-bold text-black hover:text-[#00A651] sm:inline-flex">Home</a>
                <a href="{{ route('register.account') }}" class="rounded-lg bg-[#00A651] px-4 py-2 text-sm font-black text-white">Start Free Trial</a>
            </div>
        </nav>
    </header>

    @include('landing.partials.page-blocks', ['blocks' => $blocks])

    <footer class="border-t border-zinc-200 bg-[#F1F3EE] px-5 py-8 text-center text-sm text-black">
        <a href="{{ route('landing') }}" class="font-black text-[#00A651]">BAMA</a>
    </footer>
</main>
@endsection
