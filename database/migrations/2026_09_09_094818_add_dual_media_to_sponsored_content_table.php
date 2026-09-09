<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsored_content', function (Blueprint $table): void {
            // Add wide and tall creative columns (nullable — at least one is required at app level)
            $table->foreignId('wide_media_id')->nullable()->after('media_id')->constrained('media')->nullOnDelete();
            $table->foreignId('tall_media_id')->nullable()->after('wide_media_id')->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sponsored_content', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('wide_media_id');
            $table->dropConstrainedForeignId('tall_media_id');
        });
    }
};
