<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('media_id');

            $table->primary(['product_id', 'media_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_documents');
    }
};
