@extends('layouts.platform')
@section('title', $blog->exists ? 'Edit blog post' : 'New blog post')
@section('content')
<form method="post" action="{{ $blog->exists ? route('platform.blogs.update', $blog) : route('platform.blogs.store') }}" class="owner-card p-4">
@csrf
@if($blog->exists) @method('PUT') @endif
<div class="row g-4"><div class="col-lg-8">
<label for="title" class="form-label">Title</label><input id="title" name="title" class="form-control mb-3" required maxlength="255" value="{{ old('title', $blog->title) }}">
<label for="slug" class="form-label">URL slug</label><input id="slug" name="slug" class="form-control" maxlength="255" value="{{ old('slug', $blog->slug) }}"><div class="form-text mb-3">Leave blank to generate from the title. Public URL: /blog/your-slug</div>
<label for="excerpt" class="form-label">Short summary</label><textarea id="excerpt" name="excerpt" class="form-control mb-3" rows="3" maxlength="1000">{{ old('excerpt', $blog->excerpt) }}</textarea>
<label for="content" class="form-label">Article content</label><textarea id="content" name="content" class="form-control" rows="18" required>{{ old('content', $blog->content) }}</textarea><div class="form-text">Use plain text. Paragraphs and line breaks are preserved.</div>
</div><div class="col-lg-4">
<h2 class="h5">Search engine details</h2>
<label for="meta_title" class="form-label">SEO title</label><input id="meta_title" name="meta_title" class="form-control" maxlength="255" value="{{ old('meta_title', $blog->meta_title) }}"><div class="form-text mb-3">Defaults to the article title.</div>
<label for="meta_description" class="form-label">Meta description</label><textarea id="meta_description" name="meta_description" class="form-control" rows="4" maxlength="500">{{ old('meta_description', $blog->meta_description) }}</textarea><div class="form-text mb-3">Aim for about 150–160 characters. Defaults to the summary when empty.</div>
<label for="seo_tags" class="form-label">SEO tags</label><input id="seo_tags" name="seo_tags" class="form-control" maxlength="1000" placeholder="business, accounting, growth" value="{{ old('seo_tags', $blog->seo_tags) }}"><div class="form-text mb-4">Separate tags with commas.</div>
<input type="hidden" name="is_published" value="0"><div class="form-check mb-4"><input class="form-check-input" id="is_published" type="checkbox" name="is_published" value="1" @checked(old('is_published', $blog->is_published))><label class="form-check-label" for="is_published">Publish on the website</label></div>
<button class="btn btn-owner" type="submit">Save blog post</button> <a href="{{ route('platform.blogs.index') }}" class="btn btn-outline-secondary">Back</a>
</div></div></form>
@endsection
