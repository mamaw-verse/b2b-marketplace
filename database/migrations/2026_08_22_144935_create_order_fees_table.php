<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('label', 255);
            $table->decimal('amount', 12, 2);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['order_id', 'sort_order'], 'idx_order_fees_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fees');
    }
};
