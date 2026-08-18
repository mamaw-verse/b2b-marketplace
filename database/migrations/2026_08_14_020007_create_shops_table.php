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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
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
            $table->string('fda_lto_number', 100)->nullable();
            $table->smallInteger('average_production_lead_time')->nullable();
            $table->jsonb('value_chain_positions')->default('[]');
            $table->jsonb('capabilities')->default('[]');
            $table->unsignedBigInteger('user_id');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });

        DB::statement('CREATE UNIQUE INDEX shops_user_id_unique ON shops (user_id) WHERE deleted_at IS NULL');

        DB::statement('ALTER TABLE shops ADD COLUMN business_structure business_structure NOT NULL');
        DB::statement("ALTER TABLE shops ADD COLUMN export_readiness_status export_readiness NOT NULL DEFAULT 'never_exported'");
        DB::statement("ALTER TABLE shops ADD COLUMN status shop_status NOT NULL DEFAULT 'active'");
        DB::statement('ALTER TABLE shops ADD COLUMN geo_location geography(Point, 4326)');

        DB::statement('CREATE INDEX idx_shops_status ON shops (status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_shops_geo ON shops USING GIST (geo_location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
