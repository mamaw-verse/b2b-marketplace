<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // S4.T9: buyer closing an RFQ cascades non-terminal recipients to 'closed'.
        DB::statement("ALTER TYPE rfq_recipient_status ADD VALUE IF NOT EXISTS 'closed' AFTER 'no_bid'");
    }

    public function down(): void
    {
        // PostgreSQL does not support removing enum values directly.
        // A full type rebuild would be needed to reverse this.
    }
};
