<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the deals table for time-based restaurant promotions.
 *
 * Deals are injected into the bot's AI system prompt so it can mention
 * them naturally to customers — only when they are currently valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('title');        // e.g. "Friday Zinger Deal"
            $table->text('description');    // e.g. "2 Zingers + fries for Rs.1800"

            $table->enum('discount_type', ['flat', 'percent', 'bundle'])
                  ->default('bundle');

            $table->decimal('discount_value', 8, 2)
                  ->default(0)
                  ->comment('Rs. amount, percent, or 0 for bundle deals');

            // Days this deal is valid (JSON array of lowercase day names)
            // e.g. ["friday","saturday"] or all days
            // Note: MySQL doesn't support DEFAULT on JSON columns — set value on create
            $table->json('valid_days')->nullable();

            // Time window (null = all day)
            $table->time('valid_from')->nullable()->comment('Start time, e.g. 17:00');
            $table->time('valid_until')->nullable()->comment('End time, e.g. 23:00');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
