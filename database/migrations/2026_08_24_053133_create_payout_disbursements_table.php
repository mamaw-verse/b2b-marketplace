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
        Schema::create('payout_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained('payouts')->cascadeOnDelete();
            $table->string('dlt_reference_id', 100)->nullable();
            $table->string('dlt_transaction_id', 50)->nullable();
            $table->decimal('amount', 12, 2);
            $table->jsonb('gateway_response')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE payout_disbursements ADD COLUMN status payout_disbursement_status NOT NULL DEFAULT 'pending'");

        Schema::table('payout_disbursements', function (Blueprint $table) {
            $table->index('payout_id', 'idx_payout_disbursements_payout');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_disbursements');
    }
};
