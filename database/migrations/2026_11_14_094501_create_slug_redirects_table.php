<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('slug_redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('sluggable_type', 100);
            $table->unsignedBigInteger('sluggable_id');
            $table->string('slug', 100);
            $table->timestamps();
            $table->unique(['sluggable_type', 'slug']);
            $table->index(['sluggable_type', 'sluggable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
    }
};
