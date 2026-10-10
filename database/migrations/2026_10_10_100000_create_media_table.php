<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uploaded files. Created first because most content tables reference it.
 * mime is detected from file CONTENT at upload time, never from the extension.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->default('public');
            $table->string('path')->unique();               // random ULID-based name
            $table->string('original_name');                // display only, never used as a path
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('sha256', 64)->unique();            // blocks duplicate uploads
            $table->string('alt')->nullable();
            $table->text('caption')->nullable();
            $table->json('variants')->nullable();            // responsive WebP renditions
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('legacy_url')->nullable();          // original WordPress URL (for redirects)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
