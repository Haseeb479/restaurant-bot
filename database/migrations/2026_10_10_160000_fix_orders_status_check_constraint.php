<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Drop Postgres check constraints so 'served', 'ready', and all statuses/payment methods work seamlessly
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check;');
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check;');
            DB::statement('ALTER TABLE orders ALTER COLUMN status TYPE VARCHAR(50);');
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_method TYPE VARCHAR(50);');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending';");
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method VARCHAR(50) DEFAULT 'cash_on_delivery';");
        }
    }

    public function down(): void
    {
        // No-op to avoid breaking dynamic statuses
    }
};
