@extends('blog.layout', ['blogTitle' => 'Blog | Bama Business Cloud', 'blogDescription' => 'Ideas, guides, and updates to help you run and grow your business.'])
@push('seo')<link rel="canonical" href="{{ $posts->currentPage() > 1 ? $posts->url($posts->currentPage()) : route('blog.index') }}">@endpush
@section('blog-content')
<section class="bg-[#EAF8F0] px-5 py-16"><div class="mx-auto max-w-7xl"><p class="font-bold text-[#007A3B]">THE BAMA BLOG</p><h1 class="mt-3 text-4xl font-black text-black sm:text-5xl">Ideas for better business.</h1><p class="mt-5 max-w-2xl text-lg text-zinc-600">Practical guides, fresh perspectives, and the latest updates to help your business grow.</p></div></section>
<section class="mx-auto max-w-7xl px-5 py-12"><div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
@forelse($posts as $post)
<article class="rounded-2xl border border-zinc-200 bg-white p-7"><time class="text-sm text-zinc-500" datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d M Y') }}</time><h2 class="mt-4 text-2xl font-bold"><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2><p class="mt-4 leading-7 text-zinc-600">{{ $post->excerpt ?: \Illuminate\Support\Str::limit($post->content, 180) }}</p><a class="mt-6 inline-block font-bold text-[#007A3B]" href="{{ route('blog.show', $post->slug) }}">Read article &rarr;</a></article>
@empty
<p class="py-12 text-lg text-zinc-600">Our first articles are on the way. Check back soon.</p>
@endforelse
</div><div class="mt-8">{{ $posts->links('pagination::tailwind') }}</div></section>
@endsection
