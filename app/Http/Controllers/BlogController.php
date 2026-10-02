<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index()
    {
        return view('blog.index', ['posts' => BlogPost::published()->orderByDesc('published_at')->orderByDesc('id')->paginate(9)]);
    }

    public function show(string $slug)
    {
        return view('blog.show', ['post' => BlogPost::published()->where('slug', $slug)->firstOrFail()]);
    }

    public function manage()
    {
        return view('platform.blogs.index', ['posts' => BlogPost::latest()->paginate(20)]);
    }

    public function create()
    {
        return view('platform.blogs.form', ['blog' => new BlogPost]);
    }

    public function store(Request $request)
    {
        $blog = BlogPost::create($this->validated($request));

        return redirect()->route('platform.blogs.edit', $blog)->with('status', 'Blog post created.');
    }

    public function edit(BlogPost $blog)
    {
        return view('platform.blogs.form', compact('blog'));
    }

    public function update(Request $request, BlogPost $blog)
    {
        $blog->update($this->validated($request, $blog));

        return redirect()->route('platform.blogs.edit', $blog)->with('status', 'Blog post updated.');
    }

    public function destroy(BlogPost $blog)
    {
        $blog->delete();

        return redirect()->route('platform.blogs.index')->with('status', 'Blog post deleted.');
    }

    private function validated(Request $request, ?BlogPost $blog = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string', 'max:200000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'seo_tags' => ['nullable', 'string', 'max:1000'],
            'is_published' => ['sometimes', 'boolean'],
        ]);
        $data['slug'] = Str::slug(($data['slug'] ?? '') ?: $data['title']);
        validator($data, ['slug' => ['required', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($blog?->id)]])->validate();
        $data['seo_tags'] = collect(explode(',', $data['seo_tags'] ?? ''))->map(fn ($tag) => trim($tag))->filter()->unique()->implode(', ');
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published'] ? ($blog?->published_at ?? now()) : $blog?->published_at;

        return $data;
    }
}
