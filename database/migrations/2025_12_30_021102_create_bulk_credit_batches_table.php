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
        Schema::create('bulk_credit_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('activity_id')->index();
            $table->unsignedInteger('total_chunks');
            $table->unsignedInteger('processed_chunks')->default(0);
            $table->unsignedInteger('successful_chunks')->default(0);
            $table->unsignedInteger('failed_chunks')->default(0);
            $table->enum('status', [
                'PENDING',
                'PROCESSING',
                'COMPLETED',
                'PARTIALLY_COMPLETED',
                'FAILED',
            ])->default('PENDING')->index();
            $table->timestamps();

            $table->unique(['activity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_credit_batches');
    }
};
