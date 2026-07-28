<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->boolean('is_default')->default(true); 
            $table->timestamps();

            $table->unique(['meal_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_ingredients');
    }
};
