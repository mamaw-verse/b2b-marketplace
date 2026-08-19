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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('conversation_threads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_messages_thread ON messages(thread_id, created_at)');
        DB::statement('CREATE INDEX idx_messages_sender ON messages(sender_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
