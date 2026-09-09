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
            // media_id is superseded by wide_media_id / tall_media_id.
            // Make it nullable so legacy rows and new rows created without it are valid.
            $table->foreignId('media_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sponsored_content', function (Blueprint $table): void {
            $table->foreignId('media_id')->nullable(false)->change();
        });
    }
};
