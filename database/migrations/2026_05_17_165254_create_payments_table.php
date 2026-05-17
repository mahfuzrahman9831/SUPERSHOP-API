<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payable_type', 100);
            $table->unsignedBigInteger('payable_id');
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->foreignId('account_id')->constrained('accounts');
            $table->enum('type', ['received', 'paid', 'refund', 'adjustment', 'reversal']);
            $table->decimal('amount', 15, 2);
            $table->string('transaction_id', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('paid_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index('paid_at');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};