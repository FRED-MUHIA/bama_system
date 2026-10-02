@extends('blog.layout', ['blogTitle' => $post->meta_title ?: $post->title, 'blogDescription' => $post->meta_description ?: ($post->excerpt ?: \Illuminate\Support\Str::limit($post->content, 160))])
@push('seo')
<link rel="canonical" href="{{ route('blog.show', $post->slug) }}">
@if($post->seo_tags)<meta name="keywords" content="{{ $post->seo_tags }}">@endif
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $post->meta_title ?: $post->title }}">
<meta property="og:description" content="{{ $post->meta_description ?: ($post->excerpt ?: \Illuminate\Support\Str::limit($post->content, 160)) }}">
<meta property="og:url" content="{{ route('blog.show', $post->slug) }}">
<meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
@endpush
@section('blog-content')
<article class="mx-auto max-w-3xl px-5 py-16">
<a href="{{ route('blog.index') }}" class="font-bold text-[#007A3B]">&larr; All articles</a>
<p class="mt-8 text-sm text-zinc-500">{{ $post->published_at->format('d M Y') }}</p>
<h1 class="mt-4 text-4xl font-black text-black sm:text-5xl">{{ $post->title }}</h1>
@if($post->excerpt)<p class="mt-6 text-xl leading-8 text-zinc-600">{{ $post->excerpt }}</p>@endif
<div class="mt-10 whitespace-pre-wrap break-words text-lg leading-8 text-zinc-700">{{ $post->content }}</div>
@if($post->seo_tags)<div class="mt-10 flex flex-wrap gap-2">@foreach(explode(',', $post->seo_tags) as $tag)<span class="rounded-full bg-[#EAF8F0] px-4 py-2 text-sm text-[#007A3B]">{{ trim($tag) }}</span>@endforeach</div>@endif
</article>
@endsection
