<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sourcing_requests', function (Blueprint $table): void {
            // Free text on purpose: buyers write "500 kg", "2 containers", "PHP 50,000 total", etc.
            $table->string('quantity', 100)->nullable()->after('product_description');
            $table->string('target_budget', 100)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sourcing_requests', function (Blueprint $table): void {
            $table->dropColumn(['quantity', 'target_budget']);
        });
    }
};
