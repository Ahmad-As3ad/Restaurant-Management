<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_meal_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_meal_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->enum('action', ['added', 'removed']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_meal_ingredients');
    }
};
