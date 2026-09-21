<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menu_item_variants')) {
            Schema::create('menu_item_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
                $table->string('name', 100);
                $table->decimal('price', 10, 2);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['menu_item_id', 'is_active']);
                $table->index(['menu_item_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_variants');
    }
};
