<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rfq_recipients', function (Blueprint $table) {
            $table->string('admin_status')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('managed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfq_recipients', function (Blueprint $table) {
            $table->dropForeign(['managed_by']);
            $table->dropColumn(['admin_status', 'admin_notes', 'managed_by']);
        });
    }
};
