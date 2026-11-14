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
        Schema::table('shops', function (Blueprint $table): void {
            $table->string('slug', 100)->nullable();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('slug', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('slug');
        });

        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn('slug');
        });
    }
};
