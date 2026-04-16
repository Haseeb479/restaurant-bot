<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('whatsapp_number')->unique();
            $table->string('wa_phone_id')->unique();
            $table->string('owner_phone');
            $table->string('owner_password');
            $table->string('city');
            $table->text('address');
            $table->text('delivery_areas')->nullable();
            $table->decimal('delivery_charge', 8, 2)->default(0);
            $table->decimal('minimum_order', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_open')->default(true);
            $table->string('plan')->default('trial');
            $table->timestamp('plan_expires_at')->nullable();
            $table->text('greeting_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};