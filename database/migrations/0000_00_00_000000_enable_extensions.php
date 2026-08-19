<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Enable required PostgreSQL extensions.
     *
     * PostGIS: geospatial data types and functions (geo_location columns).
     * pgcrypto: gen_random_uuid() for optional UUID generation.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS pgcrypto;');
        DB::statement('DROP EXTENSION IF EXISTS postgis;');
    }
};
