<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payout_status') THEN CREATE TYPE payout_status AS ENUM ('pending', 'processing', 'completed', 'failed'); END IF; END $$");
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payout_disbursement_status') THEN CREATE TYPE payout_disbursement_status AS ENUM ('pending', 'succeeded', 'failed'); END IF; END $$");
    }

    public function down(): void
    {
        DB::statement('DROP TYPE IF EXISTS payout_disbursement_status');
        DB::statement('DROP TYPE IF EXISTS payout_status');
    }
};
