<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('amount', 15, 2);
            $table->enum('frequency', ['weekly', 'monthly', 'yearly']);
            $table->date('next_billing_date');
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('next_billing_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};