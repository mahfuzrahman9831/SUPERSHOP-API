<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('batch_no', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('received_quantity', 15, 2)->default(0);
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->unsignedBigInteger('stock_layer_id')->nullable();
            $table->timestamps();

            $table->index('purchase_id');
            $table->index('product_id');
            $table->index('stock_layer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};