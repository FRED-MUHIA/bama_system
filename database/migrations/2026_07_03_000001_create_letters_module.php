<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('letter_templates')) {
            Schema::create('letter_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('type')->default('General');
                $table->string('default_subject')->nullable();
                $table->text('content');
                $table->string('output_format')->default('PDF');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('letters')) {
            Schema::create('letters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('letter_template_id')->nullable()->constrained()->nullOnDelete();
                $table->string('letter_number')->unique();
                $table->string('prefix')->default('LTR');
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('receipt_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('warranty_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type')->default('General');
                $table->string('subject');
                $table->longText('content');
                $table->string('status')->default('Draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->string('recipient')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('portal_published_at')->nullable();
                $table->string('delivery_status')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('letter_attachments')) {
            Schema::create('letter_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('letter_id')->constrained()->cascadeOnDelete();
                $table->foreignId('document_id')->nullable()->constrained('project_documents')->nullOnDelete();
                $table->string('document_type')->nullable();
                $table->string('title')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('letter_versions')) {
            Schema::create('letter_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('letter_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->string('subject');
                $table->longText('content');
                $table->string('status');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_versions');
        Schema::dropIfExists('letter_attachments');
        Schema::dropIfExists('letters');
        Schema::dropIfExists('letter_templates');
    }
};
