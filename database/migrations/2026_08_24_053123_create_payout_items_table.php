<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained('payouts')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('ledger_entry_id')->constrained('ledger_entries')->restrictOnDelete();
            $table->decimal('escrowed_amount', 12, 2);
            $table->decimal('transaction_fee', 12, 2);
            $table->decimal('commission_fee', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->timestamp('created_at')->nullable();

            $table->unique('order_id');
            $table->index('payout_id', 'idx_payout_items_payout');
            $table->index('order_id', 'idx_payout_items_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_items');
    }
};
