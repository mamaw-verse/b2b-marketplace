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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX idx_conversations_pair ON conversations(buyer_user_id, shop_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
