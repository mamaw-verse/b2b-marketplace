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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->restrictOnDelete();

            // Customer / billing / shipping snapshot (editable until paid)
            $table->string('customer_business_name', 255)->nullable();
            $table->string('customer_contact_name', 255)->nullable();
            $table->string('customer_contact_phone', 30)->nullable();
            $table->string('customer_contact_email', 255)->nullable();
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

            // Financial totals
            $table->char('currency', 3)->default('PHP');
            $table->decimal('items_total', 12, 2)->nullable();
            $table->decimal('fees_total', 12, 2)->nullable();
            $table->decimal('order_total', 12, 2)->nullable();

            // Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE orders ADD COLUMN status order_status NOT NULL DEFAULT 'draft'");

        DB::statement('CREATE INDEX idx_orders_buyer ON orders(buyer_user_id)');
        DB::statement('CREATE INDEX idx_orders_shop ON orders(shop_id)');
        DB::statement('CREATE INDEX idx_orders_quotation ON orders(quotation_id) WHERE quotation_id IS NOT NULL');
        DB::statement('CREATE INDEX idx_orders_status ON orders(status)');
        DB::statement('CREATE UNIQUE INDEX idx_orders_quotation_unique ON orders(quotation_id) WHERE quotation_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
