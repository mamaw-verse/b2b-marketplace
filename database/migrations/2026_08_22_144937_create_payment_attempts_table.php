<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_payment_term_id')->constrained('order_payment_terms')->cascadeOnDelete();

            // DLT gateway fields
            $table->string('dlt_merchant_transaction_id', 50)->unique();
            $table->string('dlt_quick_link_token', 255)->nullable();
            $table->string('dlt_payment_url', 500)->nullable();
            $table->string('dlt_payment_status', 30)->nullable();
            $table->string('dlt_amount', 30)->nullable();

            // Audit
            $table->jsonb('gateway_response')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE payment_attempts ADD COLUMN status payment_attempt_status NOT NULL DEFAULT 'pending'");

        DB::statement('CREATE INDEX idx_payment_attempts_term ON payment_attempts(order_payment_term_id)');
        DB::statement('CREATE UNIQUE INDEX idx_payment_attempts_dlt_txn ON payment_attempts(dlt_merchant_transaction_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
