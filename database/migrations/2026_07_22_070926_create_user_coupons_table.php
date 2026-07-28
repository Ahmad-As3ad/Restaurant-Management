<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('usage_count')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'coupon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_coupons');
    }
};
