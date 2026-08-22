<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('template_categories')) {
            Schema::create('template_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('signatories')) {
            Schema::create('signatories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('title')->nullable();
                $table->string('signature_path')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('business_templates')) {
            Schema::create('business_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('template_category_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('type')->default('General');
                $table->string('default_subject')->nullable();
                $table->longText('content');
                $table->string('output_format')->default('PDF');
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('source_template_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('document_verifications')) {
            Schema::create('document_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('document_type');
                $table->unsignedBigInteger('document_id');
                $table->string('document_number');
                $table->string('hash')->unique();
                $table->string('public_token')->unique();
                $table->timestamp('verified_at')->nullable();
                $table->string('verified_by_ip')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('document_media')) {
            Schema::create('document_media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->string('file_path');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->unsignedInteger('file_size')->nullable();
                $table->string('disk')->default('public');
                $table->text('caption')->nullable();
                $table->timestamps();
            });
        }

        Schema::whenTableHasColumn('letters', 'content', function (Blueprint $table) {
            $table->string('content_type')->default('text')->after('content');
        });

        Schema::whenTableHasColumn('letter_templates', 'content', function (Blueprint $table) {
            $table->string('content_type')->default('text')->after('content');
        });

        if (Schema::hasTable('letter_templates') && ! Schema::hasColumn('letter_templates', 'template_category_id')) {
            Schema::table('letter_templates', function (Blueprint $table) {
                $table->foreignId('template_category_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
                $table->boolean('is_system')->default(false)->after('is_active');
                $table->unsignedBigInteger('source_template_id')->nullable()->after('is_system');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_media');
        Schema::dropIfExists('document_verifications');
        Schema::dropIfExists('business_templates');
        Schema::dropIfExists('signatories');
        Schema::dropIfExists('template_categories');

        if (Schema::hasTable('letter_templates')) {
            Schema::table('letter_templates', function (Blueprint $table) {
                $table->dropForeign(['template_category_id']);
                $table->dropColumn(['template_category_id', 'is_system', 'source_template_id']);
            });
        }
    }
};
