@extends('layouts.platform')
@section('title', 'Blogs')
@section('content')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <p class="text-muted">Manage articles and their search engine details.</p>
    <div><a class="btn btn-outline-secondary" href="{{ route('blog.index') }}">View blog</a> <a class="btn btn-owner" href="{{ route('platform.blogs.create') }}">New blog post</a></div>
</div>
<div class="owner-card p-3 table-responsive">
<table class="table owner-table align-middle"><thead><tr><th>Title</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead><tbody>
@forelse($posts as $post)
<tr><td><strong>{{ $post->title }}</strong><small class="d-block text-muted">{{ $post->meta_description ?: 'No meta description yet.' }}</small></td><td>{{ $post->is_published ? 'Published' : 'Draft' }}</td><td>{{ $post->published_at?->format('d M Y') ?? '—' }}</td><td>
<a class="btn btn-sm btn-owner" href="{{ route('platform.blogs.edit', $post) }}">Edit</a>
@if($post->is_published)<a class="btn btn-sm btn-outline-secondary" href="{{ route('blog.show', $post->slug) }}">View</a>@endif
<form class="d-inline" method="post" action="{{ route('platform.blogs.destroy', $post) }}" onsubmit="return confirm('Delete this blog post?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
</td></tr>
@empty
<tr><td colspan="4" class="text-muted py-4">No blog posts yet. Create your first article to get started.</td></tr>
@endforelse
</tbody></table>
{{ $posts->links() }}
</div>
@endsection
