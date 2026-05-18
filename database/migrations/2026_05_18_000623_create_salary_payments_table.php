<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('month', 7);
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('bonus', 15, 2)->default(0);
            $table->decimal('advance_deduction', 15, 2)->default(0);
            $table->decimal('total_salary', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};