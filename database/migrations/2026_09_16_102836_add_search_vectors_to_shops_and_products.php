<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Native Postgres search support for the `pgsql` Scout driver.
     *
     * Adds auto-maintained tsvector columns (no app-level sync jobs) plus
     * GIN full-text indexes and pg_trgm indexes for prefix/typo matching.
     * Reverting to Typesense later needs no schema change.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        DB::statement("
            ALTER TABLE shops ADD COLUMN IF NOT EXISTS search_vector tsvector
            GENERATED ALWAYS AS (
                to_tsvector('simple',
                    coalesce(storefront_name, '') || ' ' ||
                    coalesce(registered_business_name, '') || ' ' ||
                    coalesce(city, '') || ' ' ||
                    coalesce(province, '')
                )
            ) STORED
        ");
        DB::statement('CREATE INDEX IF NOT EXISTS idx_shops_search_vector ON shops USING GIN (search_vector)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_shops_storefront_trgm ON shops USING GIN (storefront_name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_shops_business_name_trgm ON shops USING GIN (registered_business_name gin_trgm_ops)');

        DB::statement("
            ALTER TABLE products ADD COLUMN IF NOT EXISTS search_vector tsvector
            GENERATED ALWAYS AS (
                to_tsvector('simple',
                    coalesce(name, '') || ' ' ||
                    coalesce(description, '')
                )
            ) STORED
        ");
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_search_vector ON products USING GIN (search_vector)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_name_trgm ON products USING GIN (name gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_products_name_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_products_search_vector');
        DB::statement('ALTER TABLE products DROP COLUMN IF EXISTS search_vector');

        DB::statement('DROP INDEX IF EXISTS idx_shops_business_name_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_shops_storefront_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_shops_search_vector');
        DB::statement('ALTER TABLE shops DROP COLUMN IF EXISTS search_vector');
    }
};
