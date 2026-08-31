<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('account_email_reuse_holds')) {
            return;
        }

        Schema::create('account_email_reuse_holds', function (Blueprint $table) {
            $table->id();
            $table->string('email_hash', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->default('self_deleted');
            $table->timestamp('release_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_email_reuse_holds');
    }
};
