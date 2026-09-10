<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('password_reset_requests')) {
            Schema::create('password_reset_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
                $table->string('restaurant_name');
                $table->string('email');
                $table->string('phone');
                $table->string('status')->default('pending'); // pending, resolved, rejected
                $table->string('resolved_password')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
