<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename', 255);
            $table->string('extension', 20);
            $table->string('storage_path', 500);
            $table->string('mime_type', 150);
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('uploaded_by_id');
            $table->timestamps();

            $table->foreign('uploaded_by_id')->references('id')->on('users')->onDelete('restrict');
            $table->index('uploaded_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
