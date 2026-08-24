<?php

use App\Models\MarketingPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketing_pages')) {
            Schema::create('marketing_pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('title');
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->json('sections')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        MarketingPage::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'BAMA Business Cloud',
                'meta_title' => 'BAMA Business Cloud',
                'meta_description' => 'Run your entire business from one unified BAMA cloud platform.',
                'sections' => MarketingPage::defaultSections('home'),
                'is_published' => true,
                'published_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_pages');
    }
};
