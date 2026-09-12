<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table): void {
            // Tracks how many /sync recovery calls have been made for this attempt.
            $table->unsignedSmallInteger('sync_attempts')->default(0)->after('failed_at');

            // When the next /sync poll should be attempted (exponential backoff).
            // Null means not yet scheduled or already terminal.
            $table->timestamp('next_sync_at')->nullable()->after('sync_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->dropColumn(['sync_attempts', 'next_sync_at']);
        });
    }
};
