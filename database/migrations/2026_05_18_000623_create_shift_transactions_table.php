<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->enum('type', ['sale', 'expense', 'refund', 'cash_in', 'cash_out']);
            $table->decimal('amount', 15, 2);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_transactions');
    }
};