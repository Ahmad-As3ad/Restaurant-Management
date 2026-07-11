<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id('meal_id');
            $table->string('name')->unique();
            $table->decimal('price', 8, 2);
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('avg_rating', 3, 1)->default(0);
            $table->timestamps();
            $table->softDeletes(); // للحذف الناعم إذا احتاج

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
