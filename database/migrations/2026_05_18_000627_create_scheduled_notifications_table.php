<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('scheduled_at');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();

            $table->index('scheduled_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};