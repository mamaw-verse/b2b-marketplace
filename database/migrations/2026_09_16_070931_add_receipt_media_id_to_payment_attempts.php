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
            // FK to the media record for the uploaded payment receipt.
            // Nullable — only present on manual payment attempts.
            // manual_receipt_path is retained for backwards-compat but
            // receipt_media_id is the canonical reference going forward.
            $table->foreignId('receipt_media_id')
                ->nullable()
                ->after('manual_receipt_path')
                ->constrained('media')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('receipt_media_id');
        });
    }
};
