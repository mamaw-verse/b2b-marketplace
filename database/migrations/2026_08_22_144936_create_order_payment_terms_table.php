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
        Schema::create('order_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE order_payment_terms ADD COLUMN type payment_term_type NOT NULL DEFAULT 'full'");

        DB::statement('CREATE INDEX idx_order_payment_terms_order ON order_payment_terms(order_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payment_terms');
    }
};
