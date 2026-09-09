<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_onboardings', function (Blueprint $table): void {
            $table->string('dti_sec_number', 100)->nullable()->change();
            $table->string('tax_identification_number', 50)->nullable()->change();
        });

        Schema::table('shops', function (Blueprint $table): void {
            $table->string('dti_sec_number', 100)->nullable()->change();
            $table->string('tax_identification_number', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shop_onboardings', function (Blueprint $table): void {
            $table->string('dti_sec_number', 100)->nullable(false)->change();
            $table->string('tax_identification_number', 50)->nullable(false)->change();
        });

        Schema::table('shops', function (Blueprint $table): void {
            $table->string('dti_sec_number', 100)->nullable(false)->change();
            $table->string('tax_identification_number', 50)->nullable(false)->change();
        });
    }
};
