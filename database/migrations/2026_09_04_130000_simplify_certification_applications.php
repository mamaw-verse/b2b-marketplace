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
        Schema::table('certification_applications', function (Blueprint $table): void {
            $table->dropForeign(['media_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status', 'expiry_date']);
            $table->dropColumn(['expiry_date', 'media_id', 'admin_notes', 'reviewed_by', 'reviewed_at']);
            $table->string('status')->default('interested')->change();
            $table->foreignId('addressed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('addressed_at')->nullable();
            $table->unique('shop_id');
        });

        DB::table('certification_applications')
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->update(['status' => 'interested']);

        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn(['is_halal_certified', 'certified_expiry_date', 'last_certification_alert_threshold_days']);
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->boolean('is_halal_certified')->default(false);
            $table->date('certified_expiry_date')->nullable();
            $table->smallInteger('last_certification_alert_threshold_days')->nullable();
        });

        Schema::table('certification_applications', function (Blueprint $table): void {
            $table->date('expiry_date')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->restrictOnDelete();
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->dropForeign(['addressed_by']);
            $table->dropUnique(['shop_id']);
            $table->dropColumn(['addressed_by', 'addressed_at']);
            $table->index(['status', 'expiry_date']);
        });
    }
};
