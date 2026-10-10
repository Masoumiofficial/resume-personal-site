<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real testimonials only (decision D6). A testimonial can be public only when
 * consent_confirmed = true AND is_visible = true (enforced in the model scope).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_role')->nullable();
            $table->string('company')->nullable();
            $table->string('company_url', 500)->nullable();
            $table->foreignId('avatar_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('quote');
            $table->foreignId('related_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->boolean('consent_confirmed')->default(false);
            $table->date('received_at')->nullable();
            $table->boolean('is_visible')->default(false);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
