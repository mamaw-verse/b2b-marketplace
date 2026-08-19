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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignId('rfq_recipient_id')->constrained('rfq_recipients')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->char('currency', 3)->default('PHP');
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE quotations ADD COLUMN status quotation_status NOT NULL DEFAULT 'draft'");
        DB::statement('ALTER TABLE quotations ADD COLUMN buyer_snapshot JSONB');

        DB::statement('CREATE INDEX idx_quotations_rfq ON quotations(rfq_id)');
        DB::statement('CREATE INDEX idx_quotations_rfq_recipient ON quotations(rfq_recipient_id)');
        DB::statement('CREATE INDEX idx_quotations_status ON quotations(status)');
        DB::statement("CREATE INDEX idx_quotations_valid_until ON quotations(valid_until) WHERE status = 'submitted'");
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
