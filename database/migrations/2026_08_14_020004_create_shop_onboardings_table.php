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
        Schema::create('shop_onboardings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('registered_business_name', 255);
            $table->string('storefront_name', 255);
            $table->string('dti_sec_number', 100);
            $table->string('tax_identification_number', 50);
            $table->string('phone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('address_1', 255)->nullable();
            $table->string('address_2', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('province', 120)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->char('country_code', 2)->default('PH');
            $table->string('product_categories', 500)->nullable();
            $table->string('fda_lto_number', 100)->nullable();
            $table->smallInteger('average_production_lead_time')->nullable();
            $table->jsonb('value_chain_positions')->default('[]');
            $table->jsonb('capabilities')->default('[]');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index('user_id', 'idx_onboardings_user');
        });

        DB::statement('ALTER TABLE shop_onboardings ADD COLUMN business_structure business_structure NOT NULL');
        DB::statement("ALTER TABLE shop_onboardings ADD COLUMN export_readiness_status export_readiness NOT NULL DEFAULT 'never_exported'");
        DB::statement("ALTER TABLE shop_onboardings ADD COLUMN status onboarding_status NOT NULL DEFAULT 'pending'");
        DB::statement('ALTER TABLE shop_onboardings ADD COLUMN geo_location geography(Point, 4326)');

        DB::statement('CREATE INDEX idx_onboardings_status ON shop_onboardings (status)');
        DB::statement('CREATE INDEX idx_onboardings_geo ON shop_onboardings USING GIST (geo_location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_onboardings');
    }
};
