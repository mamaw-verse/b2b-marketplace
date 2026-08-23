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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');

            // Resolution fields
            $table->string('resolution_type', 30)->nullable();
            $table->decimal('resolution_amount', 12, 2)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('escalated_at')->nullable();

            // Return tracking (optional, for return_refund type)
            $table->string('return_carrier', 120)->nullable();
            $table->string('return_tracking_code', 255)->nullable();

            $table->timestamps();
        });

        DB::statement('ALTER TABLE disputes ADD COLUMN type dispute_type NOT NULL');
        DB::statement("ALTER TABLE disputes ADD COLUMN status dispute_status NOT NULL DEFAULT 'open'");

        DB::statement('CREATE INDEX idx_disputes_order ON disputes(order_id)');
        DB::statement("CREATE INDEX idx_disputes_status ON disputes(status) WHERE status NOT IN ('resolved_refunded', 'resolved_denied', 'withdrawn')");
        DB::statement('CREATE INDEX idx_disputes_opened_by ON disputes(opened_by_user_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
