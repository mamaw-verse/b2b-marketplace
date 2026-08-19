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
        Schema::create('rfq_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->text('decline_reason')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE rfq_recipients ADD COLUMN status rfq_recipient_status NOT NULL DEFAULT 'received'");

        DB::statement('CREATE INDEX idx_rfq_recipients_rfq ON rfq_recipients(rfq_id)');
        DB::statement('CREATE INDEX idx_rfq_recipients_shop ON rfq_recipients(shop_id)');
        DB::statement("CREATE INDEX idx_rfq_recipients_status ON rfq_recipients(status) WHERE status <> 'no_bid'");
        DB::statement('CREATE UNIQUE INDEX idx_rfq_recipients_rfq_shop ON rfq_recipients(rfq_id, shop_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_recipients');
    }
};
