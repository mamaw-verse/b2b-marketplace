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
        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->foreignId('subscription_id')
                ->nullable()
                ->after('order_payment_term_id')
                ->constrained('subscriptions')
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE payment_attempts ALTER COLUMN order_payment_term_id DROP NOT NULL');
        DB::statement('CREATE INDEX idx_payment_attempts_subscription_id ON payment_attempts(subscription_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_payment_attempts_subscription_id');
        DB::statement('ALTER TABLE payment_attempts ALTER COLUMN order_payment_term_id SET NOT NULL');

        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('subscription_id');
        });
    }
};
