<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resume sections. Dates are DATE + date_precision ('year' | 'month' | 'day')
 * because most history is only known to the year («۱۳۹۲ تاکنون»).
 * end_date NULL means "present". Skills deliberately have NO percentage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->string('organization');
            $table->string('organization_url', 500)->nullable();
            $table->foreignId('logo_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('role');
            $table->string('employment_type', 32)->nullable(); // founder | full_time | part_time | freelance | contract
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('date_precision', 8)->default('year');
            $table->string('location')->nullable();
            $table->text('summary')->nullable();
            $table->json('highlights')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->string('institution');
            $table->string('degree')->nullable();
            $table->string('field')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('date_precision', 8)->default('year');
            $table->text('description')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('group', 32)->index();            // development | data_ai | design | management | language | tools
            $table->string('level_label', 64)->nullable();   // optional words, e.g. «روزمره» or «B2» — never a percentage
            $table->unsignedTinyInteger('years')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16)->index();             // certificate | course | award
            $table->string('title');
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('end_at')->nullable();
            $table->string('date_precision', 8)->default('month');
            $table->string('credential_url', 500)->nullable();
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('educations');
        Schema::dropIfExists('experiences');
    }
};
