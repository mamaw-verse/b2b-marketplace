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
        Schema::create('dispute_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('disputes')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_dispute_items_dispute ON dispute_items(dispute_id)');
        DB::statement('CREATE INDEX idx_dispute_items_order_item ON dispute_items(order_item_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_items');
    }
};
