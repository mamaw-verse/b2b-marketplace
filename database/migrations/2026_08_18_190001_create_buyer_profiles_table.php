<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('business_name', 255);
            $table->string('billing_address_1', 255)->nullable();
            $table->string('billing_address_2', 255)->nullable();
            $table->string('billing_city', 120)->nullable();
            $table->string('billing_province', 120)->nullable();
            $table->string('billing_zip_code', 20)->nullable();
            $table->char('billing_country_code', 2)->default('PH');
            $table->string('shipping_address_1', 255)->nullable();
            $table->string('shipping_address_2', 255)->nullable();
            $table->string('shipping_city', 120)->nullable();
            $table->string('shipping_province', 120)->nullable();
            $table->string('shipping_zip_code', 20)->nullable();
            $table->char('shipping_country_code', 2)->default('PH');
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_profiles');
    }
};
