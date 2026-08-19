<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_onboarding_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_id');
            $table->string('certification_body_other', 150)->nullable();
            $table->string('registration_number', 100);
            $table->date('expiry_date');
            $table->unsignedBigInteger('media_id');
            $table->timestamps();

            $table->foreign('onboarding_id')->references('id')->on('shop_onboardings')->onDelete('cascade');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('restrict');
            $table->index('onboarding_id', 'idx_onboarding_certs_onboarding');
            $table->index('expiry_date', 'idx_onboarding_certs_expiry');
        });

        DB::statement('ALTER TABLE shop_onboarding_certificates ADD COLUMN certification_body halal_cert_body NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_onboarding_certificates');
    }
};
