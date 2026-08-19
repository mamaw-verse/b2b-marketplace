<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->string('title', 255)->nullable();
            $table->foreignId('rfq_id')->nullable()->constrained('rfqs')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_conversation_threads_conversation ON conversation_threads(conversation_id)');
        DB::statement('CREATE INDEX idx_conversation_threads_rfq ON conversation_threads(rfq_id) WHERE rfq_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_threads');
    }
};
