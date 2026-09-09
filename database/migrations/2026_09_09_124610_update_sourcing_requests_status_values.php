<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sourcing_requests')
            ->where('status', 'new')
            ->update(['status' => 'pending']);

        DB::statement("ALTER TABLE sourcing_requests ALTER COLUMN status SET DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('sourcing_requests')
            ->where('status', 'pending')
            ->update(['status' => 'new']);

        DB::statement("ALTER TABLE sourcing_requests ALTER COLUMN status SET DEFAULT 'new'");
    }
};
