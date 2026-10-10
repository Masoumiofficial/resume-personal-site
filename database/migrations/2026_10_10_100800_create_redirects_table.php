<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 301/302/410 rules for the WordPress migration and later URL changes.
 * Decoded Persian paths can exceed index limits, so uniqueness is enforced on
 * from_hash = sha256(normalized from_path) and from_path itself is TEXT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->char('from_hash', 64)->unique();
            $table->text('from_path');
            $table->text('to_url')->nullable();                     // NULL for 410 Gone
            $table->unsignedSmallInteger('status_code')->default(301); // 301 | 302 | 410
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
