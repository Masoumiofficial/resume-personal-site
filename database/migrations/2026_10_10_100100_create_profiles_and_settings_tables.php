<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row identity of the site owner.
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('display_name');
            $table->string('headline')->nullable();
            $table->text('short_bio')->nullable();
            $table->text('long_bio')->nullable();            // Markdown
            $table->foreignId('avatar_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('location')->nullable();
            $table->boolean('show_location')->default(true);
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('show_phone')->default(true);
            $table->json('social_links')->nullable();        // [{type, url}]
            $table->string('availability_note')->nullable(); // e.g. «پذیرش پروژهٔ جدید»; null = hidden
            $table->text('resume_summary')->nullable();
            $table->timestamps();
        });

        // Key/value site settings (title, footer, default meta, home sections…).
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('profiles');
    }
};
