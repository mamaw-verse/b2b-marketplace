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
        // Make dlt_merchant_transaction_id nullable so manual attempts can omit it,
        // keeping a synthetic internal reference in manual_reference_number instead.
        DB::statement('ALTER TABLE payment_attempts ALTER COLUMN dlt_merchant_transaction_id DROP NOT NULL');
        DB::statement('ALTER TABLE payment_attempts ALTER COLUMN dlt_merchant_transaction_id SET DEFAULT NULL');

        // Drop the UNIQUE constraint on dlt_merchant_transaction_id — manual attempts
        // don't have a DLT transaction ID, so we can't enforce uniqueness globally.
        // DLT uniqueness is still enforced at the application layer for DLT attempts.
        DB::statement('ALTER TABLE payment_attempts DROP CONSTRAINT IF EXISTS payment_attempts_dlt_merchant_transaction_id_unique');
        DB::statement('DROP INDEX IF EXISTS idx_payment_attempts_dlt_txn');

        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->string('payment_method', 20)->default('dlt')->after('subscription_id');
            $table->string('manual_reference_number', 100)->nullable()->after('payment_method');
            $table->text('manual_receipt_path')->nullable()->after('manual_reference_number');
        });

        // Re-create a partial unique index only for DLT transaction IDs.
        DB::statement('
            CREATE UNIQUE INDEX idx_payment_attempts_dlt_txn
            ON payment_attempts (dlt_merchant_transaction_id)
            WHERE dlt_merchant_transaction_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_payment_attempts_dlt_txn');

        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->dropColumn(['payment_method', 'manual_reference_number', 'manual_receipt_path']);
        });

        // Restore NOT NULL and the original unique constraint.
        DB::statement("UPDATE payment_attempts SET dlt_merchant_transaction_id = '' WHERE dlt_merchant_transaction_id IS NULL");
        DB::statement('ALTER TABLE payment_attempts ALTER COLUMN dlt_merchant_transaction_id SET NOT NULL');
        DB::statement('ALTER TABLE payment_attempts ALTER COLUMN dlt_merchant_transaction_id DROP DEFAULT');
        DB::statement('CREATE UNIQUE INDEX idx_payment_attempts_dlt_txn ON payment_attempts (dlt_merchant_transaction_id)');
        DB::statement('ALTER TABLE payment_attempts ADD CONSTRAINT payment_attempts_dlt_merchant_transaction_id_unique UNIQUE USING INDEX idx_payment_attempts_dlt_txn');
    }
};
