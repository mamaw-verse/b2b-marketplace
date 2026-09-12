<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $shop_id
 * @property string $name
 * @property string|null $description
 * @property string|null $featured_image_path
 * @property string $status
 * @property array<string, array<string, mixed>> $variant_attributes_schema
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'name',
        'description',
        'featured_image_path',
        'status',
        'moderation_status',
        'variant_attributes_schema',
        'average_rating',
        'total_reviews',
    ];

    public function searchableAs(): string
    {
        return 'products';
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'description' => $this->description ?? '',
            'shop_name' => $this->shop->storefront_name ?? '',
            'shop_id' => (string) $this->shop_id,
            'categories' => $this->categories->pluck('name')->toArray(),
            'price_from' => (float) $this->variants()->min('price'),
            'currency' => $this->variants()->value('currency') ?? 'PHP',
            'average_rating' => (float) ($this->average_rating ?? 0),
            'total_reviews' => (int) ($this->total_reviews ?? 0),
            'created_at' => $this->created_at->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        $visible = $this->status === 'published'
            && $this->moderation_status === 'active'
            && $this->deleted_at === null
            && $this->shop?->status === 'active';

        if ($visible && config('marketplace.features.subscriptions_enabled')) {
            $visible = $this->shop?->hasActiveOrTrialingSubscription();
        }

        return (bool) $visible;
    }

    /**
     * Scope products that are visible in the marketplace.
     * When subscriptions are enabled, only products from shops with an
     * active or trialing subscription are returned.
     */
    public function scopeMarketplaceVisible(Builder $query): Builder
    {
        $query
            ->where('products.status', 'published')
            ->where('products.moderation_status', 'active')
            ->whereHas('shop', fn (Builder $shopQuery) => $shopQuery
                ->where('status', 'active'));

        if (config('marketplace.features.subscriptions_enabled')) {
            $query->whereHas('shop', fn (Builder $shopQuery) => $shopQuery
                ->whereHas('subscriptions', fn (Builder $subscriptionQuery) => $subscriptionQuery
                    ->whereIn('status', ['active', 'trialing'])));
        }

        return $query;
    }

    /**
     * The shop that owns this product.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Product variants (SKUs).
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Categories assigned to this product.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    /**
     * Documents attached to this product.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'product_documents');
    }

    /**
     * Gallery images for this product, ordered by sort_order.
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(ProductGallery::class)->orderBy('sort_order');
    }

    /**
     * Reviews for this product.
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Scope a query to only include published products.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    protected function casts(): array
    {
        return [
            'variant_attributes_schema' => 'array',
        ];
    }

    /**
     * Get the full S3 URL for the featured image.
     */
    protected function featuredImageUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->featured_image_path) {
                return null;
            }

            return str_starts_with($this->featured_image_path, 'http')
                ? $this->featured_image_path
                : Storage::disk('s3')->url($this->featured_image_path);
        });
    }
}
