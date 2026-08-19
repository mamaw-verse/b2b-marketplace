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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('sku', 100);
            $table->string('description', 500)->nullable();
            $table->jsonb('attributes')->default('{}');
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('PHP');
            $table->integer('minimum_order_quantity')->default(1);
            $table->jsonb('quantity_breaks')->default('[]');
            $table->string('unit', 30);
            $table->integer('available_quantity')->nullable();
            $table->smallInteger('lead_time_days')->nullable();
            $table->string('pack_size', 100)->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['product_id', 'sku']);
            $table->index('product_id', 'idx_variants_product');
        });

        DB::statement("ALTER TABLE product_variants ADD COLUMN status listing_status NOT NULL DEFAULT 'archived'");

        DB::statement('CREATE INDEX idx_variants_status ON product_variants (status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
