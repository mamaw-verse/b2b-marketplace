<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TYPE order_status AS ENUM (
            'draft',
            'pending_payment',
            'paid',
            'completed',
            'cancelled',
            'refunded'
        )");

        DB::statement("CREATE TYPE fulfilment_status AS ENUM (
            'pending',
            'shipped',
            'delivered',
            'returned_to_seller'
        )");

        DB::statement("CREATE TYPE payment_attempt_status AS ENUM (
            'pending',
            'succeeded',
            'failed'
        )");

        DB::statement("CREATE TYPE payment_term_type AS ENUM ('full')");

        DB::statement("CREATE TYPE ledger_entry_type AS ENUM (
            'escrow_hold',
            'payout',
            'refund'
        )");

        DB::statement("CREATE TYPE dispute_type AS ENUM (
            'return_refund',
            'refund_only'
        )");

        DB::statement("CREATE TYPE dispute_status AS ENUM (
            'open',
            'awaiting_return',
            'return_received',
            'escalated',
            'resolved_refunded',
            'resolved_denied',
            'withdrawn'
        )");
    }

    public function down(): void
    {
        DB::statement('DROP TYPE IF EXISTS dispute_status');
        DB::statement('DROP TYPE IF EXISTS dispute_type');
        DB::statement('DROP TYPE IF EXISTS ledger_entry_type');
        DB::statement('DROP TYPE IF EXISTS payment_term_type');
        DB::statement('DROP TYPE IF EXISTS payment_attempt_status');
        DB::statement('DROP TYPE IF EXISTS fulfilment_status');
        DB::statement('DROP TYPE IF EXISTS order_status');
    }
};
