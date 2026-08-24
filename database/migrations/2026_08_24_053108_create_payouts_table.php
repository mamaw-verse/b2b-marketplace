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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->char('currency', 3)->default('PHP');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('total_transaction_fees', 12, 2);
            $table->decimal('total_commission_fees', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->integer('orders_count');
            $table->foreignId('released_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE payouts ADD COLUMN status payout_status NOT NULL DEFAULT 'pending'");

        Schema::table('payouts', function (Blueprint $table) {
            $table->index('shop_id', 'idx_payouts_shop');
            $table->index('status', 'idx_payouts_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
