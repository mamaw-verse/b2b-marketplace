<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DO $$ BEGIN CREATE TYPE media_scan_status AS ENUM ('pending', 'clean', 'infected'); EXCEPTION WHEN duplicate_object THEN null; END $$");
        DB::statement("ALTER TABLE media ADD COLUMN IF NOT EXISTS scan_status media_scan_status NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('media', function () {
            DB::statement('ALTER TABLE media DROP COLUMN IF EXISTS scan_status');
        });
        DB::statement('DROP TYPE IF EXISTS media_scan_status');
    }
};
