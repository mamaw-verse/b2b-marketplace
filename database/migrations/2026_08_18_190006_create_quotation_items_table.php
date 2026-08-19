<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 30);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quotation_id', 'sort_order'], 'idx_quotation_items_quotation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
