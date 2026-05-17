<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_item_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignId('stock_layer_id')->constrained('product_stock_layers');
            $table->decimal('quantity', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->timestamp('created_at')->nullable();

            $table->index('sale_item_id');
            $table->index('stock_layer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item_layers');
    }
};