<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contact form inbox. Hard-deleted (privacy); proposed retention 12 months.
 * ip_hash is an HMAC of the IP keyed with APP_KEY — the raw IP is never stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status', 16)->default('new')->index(); // new | read | replied | archived | spam
            $table->char('ip_hash', 64)->nullable()->index();
            $table->string('user_agent')->nullable();               // truncated
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index('created_at');                            // retention clean-up
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
