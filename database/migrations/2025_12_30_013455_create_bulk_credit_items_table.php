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
        Schema::create('bulk_credit_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('activity_id');
            $table->uuid('user_id')->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->char('row_hash', 64);
            $table->enum('status', [
                'PENDING',
                'SUCCESS',
                'FAILED',
            ])->default('PENDING')->index();
            $table->string('error_info')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'row_hash']);
            $table->index(['activity_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_credit_items');
    }
};
