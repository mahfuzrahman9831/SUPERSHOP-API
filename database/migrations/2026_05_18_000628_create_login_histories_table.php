<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('device', 255)->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};