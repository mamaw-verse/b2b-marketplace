<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_share_stats', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('product_id');
            $t->string('channel', 20);
            $t->date('shared_on');
            $t->unsignedInteger('count')->default(0);
            $t->timestamps();
            $t->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $t->unique(['product_id', 'channel', 'shared_on']);
            $t->index(['product_id', 'shared_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_share_stats');
    }
};
