<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Onboarding certificates can be saved incrementally (wizard "Save Progress").
     * Both the file upload and expiry date are only required at submission time,
     * so we relax the NOT NULL constraints here to support partial saves.
     */
    public function up(): void
    {
        Schema::table('shop_onboarding_certificates', function (Blueprint $table) {
            $table->unsignedBigInteger('media_id')->nullable()->change();
            $table->date('expiry_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shop_onboarding_certificates', function (Blueprint $table) {
            $table->unsignedBigInteger('media_id')->nullable(false)->change();
            $table->date('expiry_date')->nullable(false)->change();
        });
    }
};
