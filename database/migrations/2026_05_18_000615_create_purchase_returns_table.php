<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('user_id')->constrained('users');
            $table->string('invoice_no', 100)->unique();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'completed'])->default('pending');
            $table->timestamps();

            $table->index('purchase_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};