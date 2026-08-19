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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('featured_image_path', 500)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->index('shop_id', 'idx_products_shop');
        });

        DB::statement("ALTER TABLE products ADD COLUMN status listing_status NOT NULL DEFAULT 'archived'");

        DB::statement('CREATE INDEX idx_products_status ON products (status) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
