<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Restaurant this order belongs to
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();

            // Customer info
            $table->string('customer_phone');
            $table->string('customer_name')->nullable();
            $table->text('delivery_address');

            // Tracking
            $table->string('tracking_code')->unique(); // e.g. JC-2026-00042

            // Order status
            // pending → confirmed → preparing → out_for_delivery → delivered → cancelled
            $table->enum('status', [
                'pending',
                'confirmed',
                'preparing',
                'out_for_delivery',
                'delivered',
                'cancelled',
            ])->default('pending');

            // Payment
            $table->enum('payment_method', [
                'cash_on_delivery',
                'jazzcash',
                'easypaisa',
            ])->default('cash_on_delivery');

            $table->boolean('is_paid')->default(false);

            // Amounts
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('delivery_charge', 8, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            // Notifications
            $table->boolean('owner_notified')->default(false);     // owner got WhatsApp notification
            $table->boolean('customer_notified')->default(false);  // customer got tracking code

            // Notes from bot conversation
            $table->text('notes')->nullable();

            // Estimated delivery time in minutes
            $table->integer('estimated_minutes')->default(40);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};