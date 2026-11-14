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
        DB::statement("DO \$\$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'shop_staff_status') THEN CREATE TYPE shop_staff_status AS ENUM ('invited', 'active', 'revoked', 'left'); END IF; END \$\$");

        Schema::create('shop_staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email', 255);
            $table->char('token_hash', 64)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('invited_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('invited');
            $table->timestamps();
            $table->index('shop_id');
        });

        DB::statement('ALTER TABLE shop_staff ALTER COLUMN status DROP DEFAULT');
        DB::statement('ALTER TABLE shop_staff ALTER COLUMN status TYPE shop_staff_status USING status::shop_staff_status');
        DB::statement("ALTER TABLE shop_staff ALTER COLUMN status SET DEFAULT 'invited'");
        DB::statement("CREATE UNIQUE INDEX shop_staff_active_user_unique ON shop_staff (user_id) WHERE status = 'active'");
        DB::statement("CREATE UNIQUE INDEX shop_staff_pending_email_unique ON shop_staff (shop_id, lower(email)) WHERE status = 'invited'");

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('updated_by_id');
        });
        Schema::dropIfExists('shop_staff');
        DB::statement('DROP TYPE IF EXISTS shop_staff_status');
    }
};
