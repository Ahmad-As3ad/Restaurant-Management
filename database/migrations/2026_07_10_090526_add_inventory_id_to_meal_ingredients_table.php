<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_ingredients', function (Blueprint $table) {
            $table->foreignId('inventory_id')->nullable()->constrained('inventory')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('meal_ingredients', function (Blueprint $table) {
            $table->dropForeign(['inventory_id']);
            $table->dropColumn('inventory_id');
        });
    }
};
