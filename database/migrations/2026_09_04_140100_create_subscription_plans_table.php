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
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->char('country_scope', 2)->nullable();
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('PHP');
            $table->string('billing_period', 20)->default('yearly');
            $table->smallInteger('trial_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_subscription_plans_country_scope ON subscription_plans (country_scope)');
        DB::statement('CREATE INDEX idx_subscription_plans_active ON subscription_plans (is_active)');
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
