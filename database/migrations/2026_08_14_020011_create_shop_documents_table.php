<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('media_id');
            $table->boolean('is_verified')->default(false);

            $table->primary(['shop_id', 'media_id']);
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_documents');
    }
};
