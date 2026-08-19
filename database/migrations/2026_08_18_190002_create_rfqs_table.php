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
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE rfqs ADD COLUMN status rfq_status NOT NULL DEFAULT \'open\'');
        DB::statement('ALTER TABLE rfqs ADD COLUMN buyer_snapshot JSONB NOT NULL DEFAULT \'{}\'');

        DB::statement('CREATE INDEX idx_rfqs_user ON rfqs(user_id)');
        DB::statement('CREATE INDEX idx_rfqs_status ON rfqs(status)');
        DB::statement('CREATE INDEX idx_rfqs_last_activity ON rfqs(last_activity_at) WHERE status = \'open\'');
    }

    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
