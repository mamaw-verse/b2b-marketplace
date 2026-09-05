<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsored_content_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sponsored_content_id')->constrained('sponsored_content')->cascadeOnDelete();
            $table->string('event_type'); // impression|click
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['sponsored_content_id', 'event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsored_content_events');
    }
};
