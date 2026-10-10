<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Services and prices (decisions D4, D7). Prices are whole TOMAN in BIGINT —
 * never floats. cta_type=external allows a later Zarinpal / EtehadWP link
 * without a schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 190)->unique();
            $table->text('summary')->nullable();
            $table->string('icon', 32)->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_group_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug', 190)->nullable()->unique(); // only when has_page
            $table->text('summary')->nullable();
            $table->mediumText('body')->nullable();            // Markdown, for the dedicated page
            $table->unsignedBigInteger('price_from')->nullable();     // toman; NULL = «تماس بگیرید»
            $table->unsignedBigInteger('price_original')->nullable(); // toman; shown struck through
            $table->string('price_unit', 16)->default('once'); // once | monthly | hourly
            $table->string('price_note')->nullable();
            $table->string('cta_type', 16)->default('contact'); // contact | external
            $table->string('cta_url', 500)->nullable();
            $table->boolean('has_page')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0)->index();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            // SEO (dedicated page)
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_groups');
    }
};
