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
        Schema::create('seller_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained('shops')->cascadeOnDelete();
            $table->string('bank_name', 150);
            $table->string('bank_code', 30);
            $table->string('account_holder_name', 255);
            $table->text('account_number')->comment('Encrypted at rest, plaintext max 50 chars');
            $table->string('branch', 150)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE seller_payout_accounts ADD COLUMN verification_status payout_account_verification_status NOT NULL DEFAULT 'pending'");

        DB::statement('CREATE INDEX idx_seller_payout_accounts_status ON seller_payout_accounts (verification_status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_payout_accounts');
    }
};
