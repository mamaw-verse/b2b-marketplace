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
        Schema::create('shop_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->string('certification_body_other', 150)->nullable();
            $table->string('registration_number', 100);
            $table->date('expiry_date');
            $table->unsignedBigInteger('media_id');
            $table->boolean('is_verified')->default(true);
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('restrict');
            $table->index('shop_id', 'idx_shop_certs_shop');
        });

        DB::statement('ALTER TABLE shop_certificates ADD COLUMN certification_body halal_cert_body NOT NULL');
        DB::statement("ALTER TABLE shop_certificates ADD COLUMN status shop_cert_status NOT NULL DEFAULT 'active'");

        DB::statement("CREATE INDEX idx_shop_certs_expiry ON shop_certificates (expiry_date) WHERE status <> 'expired'");
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_certificates');
    }
};
