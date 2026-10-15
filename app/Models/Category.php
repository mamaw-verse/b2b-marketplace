<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property int $sort_order
 * @property string $name
 * @property string|null $description
 * @property string $slug
 * @property array<string, array<string, mixed>> $variant_attributes_schema
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'sort_order',
        'name',
        'description',
        'slug',
        'variant_attributes_schema',
    ];

    /**
     * The parent group this category belongs to (null = top-level).
     *
     * @return BelongsTo<Category, Category>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Subcategories nested under this category.
     *
     * @return HasMany<Category>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Top-level category groups (no parent).
     *
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Selectable categories — any category with no children of its own.
     * These are what shops and products can be tagged with.
     *
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeLeaves(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    /**
     * Whether this category is a group containing subcategories.
     */
    public function isGroup(): bool
    {
        return $this->children()->exists();
    }

    /**
     * "Group · Subcategory" for nested categories, plain name otherwise.
     *
     * @return Attribute<string, never>
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => $this->parent?->name
            ? $this->parent->name.' · '.$this->name
            : $this->name);
    }

    /**
     * Shops with this category as a primary category.
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_categories');
    }

    /**
     * Products assigned to this category.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'variant_attributes_schema' => 'array',
        ];
    }
}
