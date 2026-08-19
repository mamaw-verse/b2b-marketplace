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
        Schema::table('shop_certificates', function (Blueprint $table) {
            $table->smallInteger('last_alert_threshold_days')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_certificates', function (Blueprint $table) {
            $table->dropColumn('last_alert_threshold_days');
        });
    }
};
