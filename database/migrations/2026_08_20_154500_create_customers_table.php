<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->index();
            $table->text('address')->nullable();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->string('tag')->default('New'); // 'VIP', 'Frequent', 'New'
            $table->boolean('opt_in_marketing')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
