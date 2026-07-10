<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('min_quantity', 10, 2)->default(0);
            $table->string('unit')->default('piece'); // piece, gram, kg, ml, liter
            $table->timestamps();

            $table->unique('ingredient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
