<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('invoice_no', 100)->unique();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'sent', 'approved', 'converted', 'expired', 'cancelled'])->default('draft');
            $table->date('expiry_date')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('converted_sale_id')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('invoice_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};