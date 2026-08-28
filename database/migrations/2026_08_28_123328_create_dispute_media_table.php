<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispute_media', function (Blueprint $table) {
            $table->foreignId('dispute_id')->constrained()->onDelete('cascade');
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->primary(['dispute_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_media');
    }
};
