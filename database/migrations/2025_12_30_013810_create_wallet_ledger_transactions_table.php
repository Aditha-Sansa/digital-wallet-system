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
        Schema::create('wallet_ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->decimal('amount', 18, 2);
            $table->enum('type', ['credit', 'debit']);
            $table->string('source');
            $table->uuid('reference_id');
            $table->char('idempotency_key', 64)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger_transactions');
    }
};
