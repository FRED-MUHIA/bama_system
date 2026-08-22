<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\DocumentMedia;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\Signatory;
use App\Models\User;
use App\Services\LetterService;
use App\Support\ActiveBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LetterTemplateCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_letter_catalog_is_additive_and_includes_procurement_templates(): void
    {
        $business = ActiveBusiness::current();

        LetterTemplate::create([
            'business_id' => $business->id,
            'name' => 'Payment Request',
            'type' => 'Financial',
            'default_subject' => 'Custom subject',
            'content' => 'Custom wording that must not be replaced.',
            'content_type' => 'text',
            'output_format' => 'PDF',
            'is_active' => true,
            'is_system' => false,
        ]);

        (new LetterService())->ensureDefaultTemplates();

        $this->assertDatabaseHas('letter_templates', [
            'business_id' => $business->id,
            'name' => 'RFQ Cover Letter',
            'type' => 'Procurement',
            'is_system' => true,
        ]);

        $this->assertSame(1, LetterTemplate::where('name', 'Payment Request')->count());
        $this->assertSame('Custom wording that must not be replaced.', LetterTemplate::where('name', 'Payment Request')->value('content'));
        $this->assertGreaterThanOrEqual(28, LetterTemplate::count());
    }

    public function test_existing_letter_pdf_generation_still_outputs_a_pdf(): void
    {
        CompanySetting::create([
            'business_id' => ActiveBusiness::id(),
            'company_name' => 'BAMA',
            'phone' => '+254 700 000 000',
            'email' => 'admin@bama.co.ke',
            'address' => 'Nairobi, Kenya',
        ]);

        Signatory::create([
            'business_id' => ActiveBusiness::id(),
            'name' => 'Zacharia Mugai',
            'title' => 'Managing Director',
            'is_default' => true,
            'is_active' => true,
        ]);

        $letter = Letter::create([
            'business_id' => ActiveBusiness::id(),
            'letter_number' => 'LTR-2026-0001',
            'prefix' => 'LTR',
            'type' => 'General',
            'subject' => 'General Correspondence',
            'content' => "Dear {{client_name}},\n\nThis is a regression PDF check.\n\n{{prepared_by}}",
            'content_type' => 'text',
            'status' => 'Draft',
        ]);

        $pdf = (new LetterService())->pdf($letter)->output();

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_letter_editor_image_upload_stores_media_and_returns_editor_location(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The GD extension is required to generate fake test images.');
        }

        Storage::fake('public');

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->postJson(route('letters.images.store'), [
            'file' => UploadedFile::fake()->image('site-photo.jpg', 900, 600),
        ]);

        $response->assertOk()
            ->assertJsonPath('location', '/storage/'.DocumentMedia::firstOrFail()->file_path);

        $media = DocumentMedia::firstOrFail();
        Storage::disk('public')->assertExists($media->file_path);
        $this->assertSame(ActiveBusiness::id(), $media->business_id);
        $this->assertSame(Letter::class, $media->model_type);
        $this->assertSame('site-photo.jpg', $media->file_name);
    }

    public function test_letter_form_loads_professional_rich_editor_controls(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'editor@example.com',
            'role' => 'admin',
            'is_active' => true,
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->get(route('letters.create'));

        $response->assertOk()
            ->assertSee('Add Media')
            ->assertDontSee('Rich Text Mode')
            ->assertDontSee('TinyMCE')
            ->assertSee('letterEditor')
            ->assertSee('data-editor-command="bold"', false)
            ->assertSee('data-editor-command="insertUnorderedList"', false)
            ->assertSee('data-editor-command="insertTable"', false)
            ->assertSee('letterLineHeight');
    }
}
