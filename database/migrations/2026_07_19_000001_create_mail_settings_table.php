<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete()->unique();
            $table->boolean('enabled')->default(false);
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('scheme')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->string('from_address');
            $table->string('from_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
