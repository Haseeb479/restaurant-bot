<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `city` and `address` were created NOT NULL with no default, but every code path
 * treats them as optional: both signup forms render them without `required`,
 * RestaurantController::register validates `city` as `nullable` and never
 * validates `address` at all, and AdminController::storeRestaurant validates
 * neither. Leaving either blank therefore threw a QueryException (HTTP 500) on
 * the public self-service signup and the admin create-restaurant page.
 *
 * The columns are genuinely optional business info, so the schema is loosened to
 * match the intended behaviour rather than forcing both forms to require them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('city')->nullable()->change();
            $table->text('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing NULLs must be backfilled before the NOT NULL constraint can
        // be restored, otherwise this reversal fails on a populated table.
        DB::table('restaurants')->whereNull('city')->update(['city' => '']);
        DB::table('restaurants')->whereNull('address')->update(['address' => '']);

        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('city')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });
    }
};
