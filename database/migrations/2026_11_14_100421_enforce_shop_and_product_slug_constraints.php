<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('shops')->whereNull('slug')->exists() || DB::table('products')->whereNull('slug')->exists()) {
            throw new RuntimeException('Cannot enforce slug constraints while shops or products still have NULL slugs. Run `php artisan slugs:backfill` first.');
        }

        DB::statement('ALTER TABLE shops ALTER COLUMN slug SET NOT NULL');
        DB::statement('ALTER TABLE products ALTER COLUMN slug SET NOT NULL');
        DB::statement('CREATE UNIQUE INDEX shops_slug_unique ON shops (slug)');
        DB::statement('CREATE UNIQUE INDEX products_slug_unique ON products (slug)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS shops_slug_unique');
        DB::statement('DROP INDEX IF EXISTS products_slug_unique');
        DB::statement('ALTER TABLE shops ALTER COLUMN slug DROP NOT NULL');
        DB::statement('ALTER TABLE products ALTER COLUMN slug DROP NOT NULL');
    }
};
