<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE payment_attempts ALTER COLUMN dlt_payment_url TYPE TEXT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payment_attempts ALTER COLUMN dlt_payment_url TYPE VARCHAR(500)');
    }
};
