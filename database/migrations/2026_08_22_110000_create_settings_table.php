<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-wide key/value settings store.
 *
 * Added so the super-admin master password can actually be changed from the
 * Admin → Settings page and survive a deploy (previously updateSettings() was a
 * no-op stub and the password lived only in ADMIN_PASSWORD / an `admin123`
 * fallback). Values are stored already-hashed where they are credentials.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
