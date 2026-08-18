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
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('shop_onboardings', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
        });
    }
};
