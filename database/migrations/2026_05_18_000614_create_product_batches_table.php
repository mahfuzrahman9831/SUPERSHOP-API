<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('batch_no', 100);
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->index('batch_no');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};