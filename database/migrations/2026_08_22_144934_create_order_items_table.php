<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 30);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['order_id', 'sort_order'], 'idx_order_items_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
