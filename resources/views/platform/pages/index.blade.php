@extends('layouts.platform')
@section('title', 'Page Builder')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <p class="owner-title-eyebrow mb-1">Website Content</p>
        <h2 class="h4 mb-0">Marketing pages</h2>
    </div>
    @if($migrationMissing ?? false)
        <button class="btn btn-owner" type="button" disabled><i class="bi bi-plus-lg"></i> New Page</button>
    @else
        <a class="btn btn-owner" href="{{ route('platform.pages.create') }}"><i class="bi bi-plus-lg"></i> New Page</a>
    @endif
</div>

@if($migrationMissing ?? false)
    <div class="alert alert-warning">
        The page builder database table is missing. Run <code>php artisan migrate --force</code> on this environment, then refresh this page.
    </div>
@else
    <div class="alert alert-info">
        Header, footer, logo, favicon, and homepage content are edited from the <strong>Home</strong> page record.
    </div>
@endif

<div class="owner-card p-3">
    <div class="table-responsive">
        <table class="table owner-table align-middle mb-0">
            <thead><tr><th>Page</th><th>URL</th><th>Status</th><th>Updated</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td>
                        <strong>{{ $page->title }}</strong>
                        <small class="d-block text-muted">{{ $page->meta_description ?: 'No SEO description yet.' }}</small>
                    </td>
                    <td>
                        <a href="{{ $page->slug === 'home' ? route('landing') : route('marketing.pages.show', $page->slug) }}" target="_blank">
                            {{ $page->slug === 'home' ? '/' : '/pages/'.$page->slug }}
                        </a>
                    </td>
                    <td><span class="badge {{ $page->is_published ? 'badge-owner' : 'text-bg-light' }}">{{ $page->is_published ? 'Published' : 'Draft' }}</span></td>
                    <td>{{ $page->updated_at?->format('d M Y, H:i') }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-owner" href="{{ route('platform.pages.edit', $page) }}"><i class="bi bi-pencil-square"></i> Edit</a>
                        @if($page->slug !== 'home')
                            <form method="post" action="{{ route('platform.pages.destroy', $page) }}" class="d-inline" onsubmit="return confirm('Delete this page?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No pages yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
