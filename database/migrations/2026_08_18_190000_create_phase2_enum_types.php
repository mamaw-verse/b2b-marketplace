<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'rfq_status') THEN CREATE TYPE rfq_status AS ENUM ('open', 'closed'); END IF; END $$");
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'rfq_recipient_status') THEN CREATE TYPE rfq_recipient_status AS ENUM ('received', 'reviewed', 'in_progress', 'no_bid'); END IF; END $$");
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'quotation_status') THEN CREATE TYPE quotation_status AS ENUM ('draft', 'submitted', 'awarded', 'rejected', 'withdrawn', 'not_awarded', 'expired'); END IF; END $$");
    }

    public function down(): void
    {
        DB::statement('DROP TYPE IF EXISTS quotation_status');
        DB::statement('DROP TYPE IF EXISTS rfq_recipient_status');
        DB::statement('DROP TYPE IF EXISTS rfq_status');
    }
};
