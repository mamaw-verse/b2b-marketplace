<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certification_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->date('expiry_date');
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['status', 'expiry_date']);
        });

        Schema::table('shops', function (Blueprint $table): void {
            $table->boolean('is_halal_certified')->default(false);
            $table->date('certified_expiry_date')->nullable();
            $table->smallInteger('last_certification_alert_threshold_days')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn([
                'is_halal_certified',
                'certified_expiry_date',
                'last_certification_alert_threshold_days',
            ]);
        });

        Schema::dropIfExists('certification_applications');
    }
};
