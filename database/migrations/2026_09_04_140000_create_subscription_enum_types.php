<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'subscription_status') THEN CREATE TYPE subscription_status AS ENUM ('pending_payment', 'trialing', 'active', 'lapsed', 'cancelled'); END IF; END $$");
    }

    public function down(): void
    {
        DB::statement('DROP TYPE IF EXISTS subscription_status');
    }
};
