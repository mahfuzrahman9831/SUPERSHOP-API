<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('quantity_in', 15, 2);
            $table->decimal('quantity_remaining', 15, 2);
            $table->timestamp('created_at')->nullable();

            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('purchase_item_id');
            $table->index(['product_id', 'warehouse_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_layers');
    }
};