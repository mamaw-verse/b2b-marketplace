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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('note', 255)->nullable();
            // No updated_at — ledger entries are append-only and never modified.
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('ALTER TABLE ledger_entries ADD COLUMN type ledger_entry_type NOT NULL');

        DB::statement('CREATE INDEX idx_ledger_entries_order ON ledger_entries(order_id)');
        DB::statement('CREATE INDEX idx_ledger_entries_type ON ledger_entries(type)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
