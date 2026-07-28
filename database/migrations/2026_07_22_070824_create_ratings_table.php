<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('meal_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned(); 
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'meal_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
