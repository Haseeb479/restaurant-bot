<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('menu_file')->nullable()->after('menu_image');
            $table->string('menu_file_name')->nullable()->after('menu_file');
            $table->string('menu_file_type')->nullable()->after('menu_file_name'); // 'image', 'pdf', 'excel', 'document'
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['menu_file', 'menu_file_name', 'menu_file_type']);
        });
    }
};
