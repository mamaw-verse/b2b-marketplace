<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Public applications no longer require a shop: the form moved from the
        // seller portal to a public storefront page, so shop_id becomes nullable
        // and the one-application-per-shop constraint is lifted (public visitors
        // can apply without a shop, and multiple interested parties may apply).
        Schema::table('certification_applications', function (Blueprint $table): void {
            $table->dropUnique(['shop_id']);
        });

        Schema::table('certification_applications', function (Blueprint $table): void {
            $table->dropForeign(['shop_id']);
        });

        Schema::table('certification_applications', function (Blueprint $table): void {
            $table->foreignId('shop_id')->nullable()->change();
        });

        Schema::table('certification_applications', function (Blueprint $table): void {
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->index('shop_id');
            $table->string('email')->nullable();
            $table->string('company_name')->nullable();
            $table->string('document_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('certification_applications', function (Blueprint $table): void {
            $table->dropIndex(['shop_id']);
            $table->dropColumn(['email', 'company_name', 'document_path']);
            $table->dropForeign(['shop_id']);
            $table->foreignId('shop_id')->nullable(false)->change();
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->unique('shop_id');
        });
    }
};
