<?php

declare(strict_types=1);

use App\Jobs\RecalculateRatingsJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\ReviewResponse;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->buyer = User::factory()->create();
    $this->seller = User::factory()->create();
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
    $this->product = Product::factory()->create(['shop_id' => $this->shop->id, 'status' => 'published']);
    $this->order = Order::factory()->completed()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);
});

test('buyer can create product review for completed order', function () {
    Queue::fake();

    $response = $this->actingAs($this->buyer)->postJson("/api/v1/orders/{$this->order->id}/reviews", [
        'reviewable_type' => 'product',
        'reviewable_id' => $this->product->id,
        'rating' => 4,
        'title' => 'Great product',
        'body' => 'Very good quality halal product.',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.rating', 4);
    $response->assertJsonPath('data.title', 'Great product');

    $this->assertDatabaseHas('reviews', [
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 4,
    ]);

    Queue::assertPushed(RecalculateRatingsJob::class);
});

test('buyer can create shop review for completed order', function () {
    Queue::fake();

    $response = $this->actingAs($this->buyer)->postJson("/api/v1/orders/{$this->order->id}/reviews", [
        'reviewable_type' => 'shop',
        'reviewable_id' => $this->shop->id,
        'rating' => 5,
        'title' => 'Excellent seller',
        'body' => 'Fast delivery and great communication.',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.rating', 5);

    $this->assertDatabaseHas('reviews', [
        'order_id' => $this->order->id,
        'reviewable_type' => Shop::class,
        'reviewable_id' => $this->shop->id,
        'rating' => 5,
    ]);
});

test('buyer cannot review non-completed order', function () {
    $pendingOrder = Order::factory()->pendingPayment()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);

    $response = $this->actingAs($this->buyer)->postJson("/api/v1/orders/{$pendingOrder->id}/reviews", [
        'reviewable_type' => 'product',
        'reviewable_id' => $this->product->id,
        'rating' => 3,
    ]);

    $response->assertStatus(403);
});

test('buyer cannot review same entity and order twice', function () {
    Queue::fake();

    Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 4,
    ]);

    $response = $this->actingAs($this->buyer)->postJson("/api/v1/orders/{$this->order->id}/reviews", [
        'reviewable_type' => 'product',
        'reviewable_id' => $this->product->id,
        'rating' => 5,
    ]);

    $response->assertStatus(422);
});

test('buyer can edit their own review', function () {
    $review = Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 3,
        'title' => 'OK',
    ]);

    $response = $this->actingAs($this->buyer)->putJson("/api/v1/reviews/{$review->id}", [
        'rating' => 5,
        'title' => 'Actually great!',
        'body' => 'Changed my mind after using it more.',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.rating', 5);
    $response->assertJsonPath('data.title', 'Actually great!');
});

test('buyer cannot edit another users review', function () {
    $otherBuyer = User::factory()->create();
    $otherOrder = Order::factory()->completed()->create([
        'buyer_user_id' => $otherBuyer->id,
        'shop_id' => $this->shop->id,
    ]);

    $review = Review::create([
        'order_id' => $otherOrder->id,
        'reviewer_user_id' => $otherBuyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 2,
    ]);

    $response = $this->actingAs($this->buyer)->putJson("/api/v1/reviews/{$review->id}", [
        'rating' => 5,
    ]);

    $response->assertStatus(403);
});

test('seller can respond to a review on their product', function () {
    $review = Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 4,
    ]);

    $response = $this->actingAs($this->seller)->postJson("/api/v1/reviews/{$review->id}/respond", [
        'body' => 'Thank you for your review! We appreciate your feedback.',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('review_responses', [
        'review_id' => $review->id,
        'responder_user_id' => $this->seller->id,
    ]);
});

test('seller cannot respond twice to the same review', function () {
    $review = Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Shop::class,
        'reviewable_id' => $this->shop->id,
        'rating' => 3,
    ]);

    ReviewResponse::create([
        'review_id' => $review->id,
        'responder_user_id' => $this->seller->id,
        'body' => 'First response',
    ]);

    $response = $this->actingAs($this->seller)->postJson("/api/v1/reviews/{$review->id}/respond", [
        'body' => 'Second response attempt',
    ]);

    // Should fail due to unique constraint on review_id
    $response->assertStatus(500);
});

test('seller can edit their response', function () {
    $review = Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 4,
    ]);

    $reviewResponse = ReviewResponse::create([
        'review_id' => $review->id,
        'responder_user_id' => $this->seller->id,
        'body' => 'Original response',
    ]);

    $response = $this->actingAs($this->seller)->putJson("/api/v1/review-responses/{$reviewResponse->id}", [
        'body' => 'Updated response text',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.body', 'Updated response text');
});

test('user can report a review not their own', function () {
    $review = Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 1,
        'body' => 'Inappropriate content here',
    ]);

    $reporter = User::factory()->create();

    $response = $this->actingAs($reporter)->postJson("/api/v1/reviews/{$review->id}/report", [
        'reason' => 'This review contains inappropriate language.',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('review_reports', [
        'review_id' => $review->id,
        'reporter_user_id' => $reporter->id,
        'status' => 'pending',
    ]);
});

test('admin can dismiss a report', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $review = Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 2,
    ]);

    $report = ReviewReport::create([
        'review_id' => $review->id,
        'reporter_user_id' => User::factory()->create()->id,
        'reason' => 'Fake report',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->postJson("/api/v1/admin/review-reports/{$report->id}/dismiss");

    $response->assertOk();
    $this->assertDatabaseHas('review_reports', [
        'id' => $report->id,
        'status' => 'dismissed',
    ]);
});

test('admin can action a report and review becomes hidden', function () {
    Queue::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $review = Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 1,
        'is_hidden' => false,
    ]);

    $report = ReviewReport::create([
        'review_id' => $review->id,
        'reporter_user_id' => User::factory()->create()->id,
        'reason' => 'Inappropriate content',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->postJson("/api/v1/admin/review-reports/{$report->id}/action");

    $response->assertOk();
    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'is_hidden' => true,
    ]);
    $this->assertDatabaseHas('review_reports', [
        'id' => $report->id,
        'status' => 'actioned',
    ]);

    Queue::assertPushed(RecalculateRatingsJob::class);
});

test('hidden reviews excluded from public listing', function () {
    Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 4,
        'is_hidden' => false,
    ]);

    $otherBuyer = User::factory()->create();
    $otherOrder = Order::factory()->completed()->create([
        'buyer_user_id' => $otherBuyer->id,
        'shop_id' => $this->shop->id,
    ]);

    Review::create([
        'order_id' => $otherOrder->id,
        'reviewer_user_id' => $otherBuyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 1,
        'is_hidden' => true,
    ]);

    $response = $this->getJson("/api/v1/products/{$this->product->id}/reviews");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.rating', 4);
});

test('RecalculateRatingsJob correctly updates average_rating and total_reviews', function () {
    Review::create([
        'order_id' => $this->order->id,
        'reviewer_user_id' => $this->buyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 4,
        'is_hidden' => false,
    ]);

    $otherBuyer = User::factory()->create();
    $otherOrder = Order::factory()->completed()->create([
        'buyer_user_id' => $otherBuyer->id,
        'shop_id' => $this->shop->id,
    ]);

    Review::create([
        'order_id' => $otherOrder->id,
        'reviewer_user_id' => $otherBuyer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $this->product->id,
        'rating' => 2,
        'is_hidden' => false,
    ]);

    $job = new RecalculateRatingsJob(Product::class, $this->product->id);
    $job->handle();

    $this->product->refresh();
    expect((float) $this->product->average_rating)->toEqual(3.00);
    expect($this->product->total_reviews)->toBe(2);
});

test('rating fields appear in shop toSearchableArray', function () {
    $this->shop->update([
        'average_rating' => 4.50,
        'total_reviews' => 10,
    ]);

    $this->shop->refresh();
    $searchArray = $this->shop->toSearchableArray();

    expect($searchArray)->toHaveKey('average_rating');
    expect($searchArray)->toHaveKey('total_reviews');
    expect($searchArray['average_rating'])->toEqual(4.50);
    expect($searchArray['total_reviews'])->toBe(10);
});

test('rate limit: 11th review creation in an hour is rejected', function () {
    Queue::fake();

    // Create 10 separate completed orders for the buyer to review
    for ($i = 1; $i <= 11; $i++) {
        $product = Product::factory()->create(['shop_id' => $this->shop->id, 'status' => 'published']);
        $order = Order::factory()->completed()->create([
            'buyer_user_id' => $this->buyer->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->actingAs($this->buyer)->postJson("/api/v1/orders/{$order->id}/reviews", [
            'reviewable_type' => 'product',
            'reviewable_id' => $product->id,
            'rating' => fake()->numberBetween(1, 5),
        ]);

        if ($i <= 10) {
            $response->assertStatus(201);
        } else {
            $response->assertStatus(429);
        }
    }
});
