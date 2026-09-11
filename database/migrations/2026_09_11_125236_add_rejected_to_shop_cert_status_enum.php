<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TYPE shop_cert_status ADD VALUE IF NOT EXISTS 'rejected' AFTER 'under_review'");
    }

    public function down(): void
    {
        // PostgreSQL does not support removing enum values directly.
        // A full type rebuild would be needed to reverse this.
    }
};
