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
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE subscriptions ADD COLUMN status subscription_status NOT NULL DEFAULT 'pending_payment'");
        DB::statement('ALTER TABLE subscriptions ADD COLUMN trial_ends_at TIMESTAMP NULL');
        DB::statement('ALTER TABLE subscriptions ADD COLUMN current_period_start TIMESTAMP NULL');
        DB::statement('ALTER TABLE subscriptions ADD COLUMN current_period_end TIMESTAMP NULL');
        DB::statement('ALTER TABLE subscriptions ADD COLUMN last_renewal_alert_threshold_days SMALLINT NULL');

        DB::statement('CREATE INDEX idx_subscriptions_shop_status ON subscriptions (shop_id, status)');
        DB::statement('CREATE INDEX idx_subscriptions_status_period_end ON subscriptions (status, current_period_end)');
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
