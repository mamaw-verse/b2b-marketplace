<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            // Soft link to the product variant this line item originated from.
            // Nullable — freeform items have no variant. SET NULL on delete so
            // catalogue cleanup is never blocked by historical quotation items.
            $table->foreignId('variant_id')
                ->nullable()
                ->after('quotation_id')
                ->constrained('product_variants')
                ->nullOnDelete();

            // Snapshot of the variant's key fields at the moment the quotation
            // was submitted. Null for freeform items and draft quotations.
            // Structure: {sku, product_name, unit_price, unit, moq, pack_size}
            $table->jsonb('variant_snapshot')->nullable()->after('variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn(['variant_id', 'variant_snapshot']);
        });
    }
};
