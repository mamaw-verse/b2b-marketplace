<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_onboarding_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('onboarding_id');
            $table->unsignedBigInteger('media_id');

            $table->primary(['onboarding_id', 'media_id']);
            $table->foreign('onboarding_id')->references('id')->on('shop_onboardings')->onDelete('cascade');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_onboarding_documents');
    }
};
