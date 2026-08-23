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
        Schema::create('fulfilments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();

            // Tracking info (all optional)
            $table->string('carrier', 120)->nullable();
            $table->string('tracking_code', 255)->nullable();
            $table->string('tracking_url', 500)->nullable();

            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE fulfilments ADD COLUMN status fulfilment_status NOT NULL DEFAULT 'pending'");

        DB::statement('CREATE INDEX idx_fulfilments_order ON fulfilments(order_id)');
        DB::statement('CREATE UNIQUE INDEX idx_fulfilments_order_unique ON fulfilments(order_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfilments');
    }
};
