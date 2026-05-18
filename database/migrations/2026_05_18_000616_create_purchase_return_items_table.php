<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedBigInteger('stock_layer_id')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();

            $table->index('purchase_return_id');
            $table->index('product_id');
            $table->index('stock_layer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};