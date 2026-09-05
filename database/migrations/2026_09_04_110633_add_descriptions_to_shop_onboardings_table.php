<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_onboardings', function (Blueprint $table) {
            $table->text('company_description')->nullable();
            $table->text('products_description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shop_onboardings', function (Blueprint $table) {
            $table->dropColumn(['company_description', 'products_description']);
        });
    }
};
