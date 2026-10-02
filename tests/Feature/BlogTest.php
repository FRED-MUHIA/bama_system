<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_publish_edit_and_delete_posts(): void
    {
        $owner = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'status' => 'Active']);
        $data = ['title' => 'Business guide', 'content' => 'Useful advice <script>alert(1)</script>', 'meta_title' => 'Business SEO title', 'meta_description' => 'A useful business guide.', 'seo_tags' => 'business, growth, business'];
        $this->actingAs($owner)->get(route('platform.blogs.create'))->assertOk();
        $this->post(route('platform.blogs.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $post = BlogPost::firstOrFail();
        $this->assertSame('business-guide', $post->slug);
        $this->assertSame('business, growth', $post->seo_tags);
        $this->get(route('platform.blogs.index'))->assertOk()->assertSee('Business guide');
        $this->get(route('platform.blogs.edit', $post))->assertOk();
        auth()->logout();
        $this->get('/blog')->assertOk()->assertDontSee('Business guide');
        $this->get('/blog/business-guide')->assertNotFound();
        $this->actingAs($owner)->put(route('platform.blogs.update', $post), $data + ['is_published' => 1])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/blog')->assertOk()->assertSee('Business guide');
        $this->get('/blog/business-guide')->assertOk()
            ->assertSee('<meta name="description" content="A useful business guide.">', false)
            ->assertSee('<meta name="keywords" content="business, growth">', false)
            ->assertSee('<title>Business SEO title</title>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($owner)->put(route('platform.blogs.update', $post), $data + ['is_published' => 0])->assertSessionHasNoErrors();
        $this->get('/blog/business-guide')->assertNotFound();
        $this->delete(route('platform.blogs.destroy', $post))->assertRedirect(route('platform.blogs.index'));
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    public function test_management_is_restricted_and_duplicate_slugs_are_rejected(): void
    {
        $this->get('/owner/blogs')->assertRedirect();
        $user = User::factory()->create(['role' => 'super_admin']);
        $data = ['title' => 'Test post', 'content' => 'Content'];
        $this->actingAs($user)->post(route('platform.blogs.store'), $data)->assertSessionHasNoErrors();
        $post = BlogPost::firstOrFail();
        $this->post(route('platform.blogs.store'), $data)->assertSessionHasErrors('slug');
        $this->post(route('platform.blogs.store'), ['title' => '', 'content' => ''])->assertSessionHasErrors(['title', 'content']);
        $user->update(['role' => 'admin']);
        $this->get('/owner/blogs')->assertForbidden();
        $this->get(route('platform.blogs.create'))->assertForbidden();
        $this->get(route('platform.blogs.edit', $post))->assertForbidden();
        $this->post(route('platform.blogs.store'), $data)->assertForbidden();
        $this->put(route('platform.blogs.update', $post), $data)->assertForbidden();
        $this->delete(route('platform.blogs.destroy', $post))->assertForbidden();
    }
}
