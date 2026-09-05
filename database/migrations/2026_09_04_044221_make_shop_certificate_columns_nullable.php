<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE shop_certificates ALTER COLUMN certification_body DROP NOT NULL');
        DB::statement('ALTER TABLE shop_certificates ALTER COLUMN registration_number DROP NOT NULL');
        DB::statement('ALTER TABLE shop_certificates ALTER COLUMN expiry_date DROP NOT NULL');
        DB::statement('ALTER TABLE shop_certificates ALTER COLUMN media_id DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shop_certificates ALTER COLUMN certification_body SET NOT NULL');
        DB::statement('ALTER TABLE shop_certificates ALTER COLUMN registration_number SET NOT NULL');
        DB::statement('ALTER TABLE shop_certificates ALTER COLUMN expiry_date SET NOT NULL');
        DB::statement('ALTER TABLE shop_certificates ALTER COLUMN media_id SET NOT NULL');
    }
};
