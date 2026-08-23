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
        Schema::table('conversation_threads', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('rfq_id')->constrained('orders')->nullOnDelete();
        });

        DB::statement('CREATE INDEX idx_conversation_threads_order ON conversation_threads(order_id) WHERE order_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('conversation_threads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
