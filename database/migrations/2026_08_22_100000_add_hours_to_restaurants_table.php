<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Free-text opening hours (e.g. "10 AM – 11 PM"). Shown by the bot in
            // the "we're closed" reply and injected into the AI system prompt.
            $table->string('hours')->nullable()->after('is_open');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('hours');
        });
    }
};
