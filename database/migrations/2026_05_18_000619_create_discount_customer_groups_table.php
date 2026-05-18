<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_customer_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('customer_group_id')->constrained('customer_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->index('discount_id');
            $table->index('customer_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_customer_groups');
    }
};