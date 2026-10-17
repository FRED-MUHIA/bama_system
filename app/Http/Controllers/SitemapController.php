<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\MarketingPage;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $pages = Schema::hasTable('marketing_pages')
            ? MarketingPage::query()->get()->keyBy('slug') : collect();
        $urls = collect([['loc' => route('landing'), 'lastmod' => $pages->get('home')?->updated_at]]);

        foreach (MarketingPage::publicIndustries() as $industry) {
            $urls->push(['loc' => route('industries.show', $industry['slug']), 'lastmod' => $pages->get($industry['slug'])?->updated_at]);
        }
        foreach ($pages as $page) {
            if ($page->is_published && ! $page->isBuiltIn()) {
                $urls->push(['loc' => $page->publicUrl(), 'lastmod' => $page->updated_at]);
            }
        }
        $urls->push(['loc' => route('blog.index'), 'lastmod' => null]);
        if (Schema::hasTable('blog_posts')) {
            foreach (BlogPost::published()->select('slug', 'updated_at')->cursor() as $post) {
                $urls->push(['loc' => route('blog.show', $post->slug), 'lastmod' => $post->updated_at]);
            }
        }

        return response()->view('seo.sitemap', ['urls' => $urls->unique('loc')])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        return response("User-agent: *\nAllow: /\n\nSitemap: ".route('sitemap')."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
