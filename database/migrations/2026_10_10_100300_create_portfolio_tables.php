<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public visibility rule (shared with articles and services):
 *   status = 'published' AND published_at <= now()
 * Scheduled publishing therefore needs no cron.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 190)->unique();
            $table->string('summary', 500)->nullable();
            $table->mediumText('body')->nullable();           // Markdown
            $table->text('problem')->nullable();
            $table->text('solution')->nullable();
            $table->string('role')->nullable();
            $table->json('technologies')->nullable();
            $table->text('outcome')->nullable();
            // An outcome is shown publicly only with evidence or an explicit "verified" tick.
            $table->string('outcome_evidence_url', 500)->nullable();
            $table->boolean('outcome_verified')->default(false);
            $table->string('project_url', 500)->nullable();
            $table->string('client_name')->nullable();        // only with the client's permission
            $table->unsignedSmallInteger('year')->nullable();
            $table->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0)->index();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 190)->unique();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('project_category', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'project_category_id']);
        });

        // Gallery.
        Schema::create('project_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('caption', 500)->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_media');
        Schema::dropIfExists('project_category');
        Schema::dropIfExists('project_categories');
        Schema::dropIfExists('projects');
    }
};
