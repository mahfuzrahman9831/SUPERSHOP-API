<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('value', 100);
            $table->string('barcode', 100)->unique()->nullable();
            $table->string('sku', 100)->unique()->nullable();
            $table->decimal('last_purchase_price', 15, 2)->default(0);
            $table->decimal('default_selling_price', 15, 2)->default(0);
            $table->decimal('stock_quantity', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};