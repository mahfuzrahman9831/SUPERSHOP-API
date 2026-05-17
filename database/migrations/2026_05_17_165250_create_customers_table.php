<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->string('name', 100);
            $table->string('phone', 20)->unique();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('total_due', 15, 2)->default(0);
            $table->decimal('loyalty_points', 15, 2)->default(0);
            $table->boolean('is_vip')->default(false);
            $table->boolean('is_blacklisted')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('phone');
            $table->index('customer_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};