<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->restrictOnDelete();
            $table->string('reviewable_type', 50);
            $table->bigInteger('reviewable_id');
            $table->smallInteger('rating');
            $table->string('title', 255)->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->unique(['order_id', 'reviewer_user_id', 'reviewable_type', 'reviewable_id'], 'reviews_unique_per_order');
            $table->index(['reviewable_type', 'reviewable_id'], 'idx_reviews_reviewable');
            $table->index('order_id', 'idx_reviews_order');
            $table->index('reviewer_user_id', 'idx_reviews_reviewer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
