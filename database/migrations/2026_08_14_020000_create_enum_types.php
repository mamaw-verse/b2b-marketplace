<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'onboarding_status') THEN CREATE TYPE onboarding_status AS ENUM ('pending', 'requires_revision', 'approved', 'rejected'); END IF; END $$");
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'business_structure') THEN CREATE TYPE business_structure AS ENUM ('sole_proprietorship', 'partnership', 'corporation'); END IF; END $$");
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'export_readiness') THEN CREATE TYPE export_readiness AS ENUM ('never_exported', 'exporting_regionally', 'exporting_globally'); END IF; END $$");
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'shop_status') THEN CREATE TYPE shop_status AS ENUM ('active', 'inactive', 'suspended'); END IF; END $$");
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'halal_cert_body') THEN CREATE TYPE halal_cert_body AS ENUM ('IDCP', 'HDIP', 'PRIME_CERTIFICATION', 'OTHER_NCMF'); END IF; END $$");
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'shop_cert_status') THEN CREATE TYPE shop_cert_status AS ENUM ('active', 'expiring_soon', 'expired'); END IF; END $$");
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'listing_status') THEN CREATE TYPE listing_status AS ENUM ('published', 'archived'); END IF; END $$");
    }

    public function down(): void
    {
        DB::statement('DROP TYPE IF EXISTS listing_status');
        DB::statement('DROP TYPE IF EXISTS shop_cert_status');
        DB::statement('DROP TYPE IF EXISTS halal_cert_body');
        DB::statement('DROP TYPE IF EXISTS shop_status');
        DB::statement('DROP TYPE IF EXISTS export_readiness');
        DB::statement('DROP TYPE IF EXISTS business_structure');
        DB::statement('DROP TYPE IF EXISTS onboarding_status');
    }
};
