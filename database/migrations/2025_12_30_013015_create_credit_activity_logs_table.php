<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('activity_id')->unique();
            $table->string('file_path');
            $table->unsignedInteger('total_records')->nullable();
            $table->unsignedInteger('valid_records')->default(0);
            $table->unsignedInteger('invalid_records')->default(0);
            $table->unsignedInteger('successful_records')->default(0);
            $table->unsignedInteger('failed_records')->default(0);
            $table->enum('process_status', [
                'UPLOADED',
                'PROCESSING',
                'COMPLETED',
                'PARTIALLY_COMPLETED',
                'FAILED',
            ])->default('FAILED')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_activity_logs');
    }
};
