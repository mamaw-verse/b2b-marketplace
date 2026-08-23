<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'order_status') THEN CREATE TYPE order_status AS ENUM ('draft', 'pending_payment', 'paid', 'completed', 'cancelled', 'refunded'); END IF; END $$");

        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'fulfilment_status') THEN CREATE TYPE fulfilment_status AS ENUM ('pending', 'shipped', 'delivered', 'returned_to_seller'); END IF; END $$");

        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_attempt_status') THEN CREATE TYPE payment_attempt_status AS ENUM ('pending', 'succeeded', 'failed'); END IF; END $$");

        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_term_type') THEN CREATE TYPE payment_term_type AS ENUM ('full'); END IF; END $$");

        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ledger_entry_type') THEN CREATE TYPE ledger_entry_type AS ENUM ('escrow_hold', 'payout', 'refund'); END IF; END $$");

        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'dispute_type') THEN CREATE TYPE dispute_type AS ENUM ('return_refund', 'refund_only'); END IF; END $$");

        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'dispute_status') THEN CREATE TYPE dispute_status AS ENUM ('open', 'awaiting_return', 'return_received', 'escalated', 'resolved_refunded', 'resolved_denied', 'withdrawn'); END IF; END $$");
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
