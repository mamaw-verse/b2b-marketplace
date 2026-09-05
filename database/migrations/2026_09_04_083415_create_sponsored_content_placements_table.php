<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsored_content_placements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sponsored_content_id')->constrained('sponsored_content')->cascadeOnDelete();
            $table->string('placement_key'); // homepage|category_page|search_results
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsored_content_placements');
    }
};
