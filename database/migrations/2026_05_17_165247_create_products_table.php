<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('barcode', 100)->unique()->nullable();
            $table->string('sku', 100)->unique()->nullable();
            $table->text('description')->nullable();
            $table->decimal('last_purchase_price', 15, 2)->default(0);
            $table->decimal('default_selling_price', 15, 2)->default(0);
            $table->decimal('min_selling_price', 15, 2)->default(0);
            $table->decimal('stock_quantity', 15, 2)->default(0);
            $table->decimal('low_stock_alert', 15, 2)->default(10);
            $table->enum('costing_method', ['fifo', 'lifo', 'avg'])->nullable()->default(null);
            $table->boolean('has_variants')->default(false);
            $table->boolean('has_serial')->default(false);
            $table->boolean('has_batch')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('barcode');
            $table->index('sku');
            $table->index('category_id');
            $table->index('brand_id');
            $table->index('tax_rate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};