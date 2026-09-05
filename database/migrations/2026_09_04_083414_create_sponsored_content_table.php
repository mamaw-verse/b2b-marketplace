<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsored_content', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sponsored_content_tier_id')->constrained('sponsored_content_tiers')->restrictOnDelete();
            $table->string('title');
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->string('destination_url');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsored_content');
    }
};
