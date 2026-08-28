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
        Schema::create('fulfilment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfilment_id')->constrained('fulfilments')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_fulfilment_items_fulfilment ON fulfilment_items(fulfilment_id)');
        DB::statement('CREATE INDEX idx_fulfilment_items_order_item ON fulfilment_items(order_item_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfilment_items');
    }
};
