<?php

declare(strict_types=1);

use App\Livewire\Admin\CategoryCreate;
use App\Livewire\Admin\CategoryEdit;
use App\Livewire\Admin\CategoryManager;
use App\Livewire\CompanyHub\ShopProfileForm;
use App\Livewire\Storefront\ProductDirectory;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;
use Laravel\Scout\EngineManager;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

// ---------------------------------------------------------------------------
// Model
// ---------------------------------------------------------------------------

test('a category can have children nested under a top-level group', function () {
    $group = Category::factory()->create(['name' => 'Foods']);
    $child = Category::factory()->childOf($group)->create(['name' => 'Meat & Poultry']);

    expect($child->parent->is($group))->toBeTrue()
        ->and($group->children)->toHaveCount(1)
        ->and($group->children->first()->is($child))->toBeTrue()
        ->and($group->isGroup())->toBeTrue()
        ->and($child->isGroup())->toBeFalse();
});

test('roots and leaves scopes reflect the two-level hierarchy', function () {
    $group = Category::factory()->create();
    $child = Category::factory()->childOf($group)->create();
    $standalone = Category::factory()->create();

    expect(Category::roots()->pluck('id')->all())
        ->toContain($group->id, $standalone->id)
        ->not->toContain($child->id);

    // A group is not selectable; a root without children still is.
    expect(Category::leaves()->pluck('id')->all())
        ->toContain($child->id, $standalone->id)
        ->not->toContain($group->id);
});

test('display name prefixes the parent group for nested categories', function () {
    $group = Category::factory()->create(['name' => 'Foods']);
    $child = Category::factory()->childOf($group)->create(['name' => 'Seafood']);

    expect($group->display_name)->toBe('Foods')
        ->and($child->fresh('parent')->display_name)->toBe('Foods · Seafood');
});

// ---------------------------------------------------------------------------
// Admin API
// ---------------------------------------------------------------------------

test('admin api lists categories with their parent', function () {
    $group = Category::factory()->create(['name' => 'Foods']);
    $child = Category::factory()->childOf($group)->create(['name' => 'Seafood']);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/categories')
        ->assertOk();

    $childData = collect($response->json('data'))->firstWhere('id', $child->id);

    expect($childData['parent']['name'])->toBe('Foods');
});

test('admin api creates a child category under a top-level group', function () {
    $group = Category::factory()->create(['name' => 'Foods']);

    $this->actingAs($this->admin, 'sanctum')
        ->postJson('/api/v1/admin/categories', [
            'name' => 'Seafood',
            'parent_id' => $group->id,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('categories', [
        'name' => 'Seafood',
        'slug' => 'seafood',
        'parent_id' => $group->id,
    ]);
});

test('admin api rejects creating a child under a non-root category', function () {
    $group = Category::factory()->create();
    $child = Category::factory()->childOf($group)->create();

    $response = $this->actingAs($this->admin, 'sanctum')
        ->postJson('/api/v1/admin/categories', [
            'name' => 'Too Deep',
            'parent_id' => $child->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    expect($response->json('error.fields'))->toHaveKey('parent_id');
});

test('admin api rejects self-parenting and nesting a group', function () {
    $group = Category::factory()->create(['name' => 'Foods']);
    $other = Category::factory()->create(['name' => 'Services']);
    $child = Category::factory()->childOf($other)->create();

    // A category cannot be its own parent.
    $response = $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/v1/admin/categories/{$group->id}", [
            'name' => 'Foods',
            'parent_id' => $group->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    expect($response->json('error.fields'))->toHaveKey('parent_id');

    // A group with subcategories cannot be nested under another group.
    $withChild = Category::factory()->create(['name' => 'Non-Foods']);
    Category::factory()->childOf($withChild)->create();

    $response = $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/v1/admin/categories/{$withChild->id}", [
            'name' => 'Non-Foods',
            'parent_id' => $group->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    expect($response->json('error.fields'))->toHaveKey('parent_id');
});

test('admin api cannot delete a group that still has subcategories', function () {
    $group = Category::factory()->create();
    Category::factory()->childOf($group)->create();

    $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/v1/admin/categories/{$group->id}")
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'CATEGORY_HAS_CHILDREN');

    $this->assertDatabaseHas('categories', ['id' => $group->id]);
});

test('admin api deletes a leaf category', function () {
    $leaf = Category::factory()->create();

    $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/v1/admin/categories/{$leaf->id}")
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    $this->assertDatabaseMissing('categories', ['id' => $leaf->id]);
});

// ---------------------------------------------------------------------------
// Admin Livewire
// ---------------------------------------------------------------------------

test('admin create form only accepts top-level groups as parents', function () {
    $group = Category::factory()->create();
    $child = Category::factory()->childOf($group)->create();

    Livewire::actingAs($this->admin)
        ->test(CategoryCreate::class)
        ->set('name', 'Too Deep')
        ->set('parent_id', $child->id)
        ->call('save')
        ->assertHasErrors('parent_id');
});

test('admin edit form cannot nest a group under another category', function () {
    $group = Category::factory()->create(['name' => 'Foods']);
    Category::factory()->childOf($group)->create();
    $other = Category::factory()->create(['name' => 'Services']);

    Livewire::actingAs($this->admin)
        ->test(CategoryEdit::class, ['category' => $group])
        ->set('parent_id', $other->id)
        ->call('save')
        ->assertHasErrors('parent_id');

    expect($group->fresh()->parent_id)->toBeNull();
});

test('admin manager refuses to delete a group with subcategories', function () {
    $group = Category::factory()->create();
    Category::factory()->childOf($group)->create();

    Livewire::actingAs($this->admin)
        ->test(CategoryManager::class)
        ->call('confirmDelete', $group->id)
        ->assertSet('deletingCategoryId', null);

    $this->assertDatabaseHas('categories', ['id' => $group->id]);
});

// ---------------------------------------------------------------------------
// Public API + storefront filtering
// ---------------------------------------------------------------------------

test('public categories endpoint returns the nested tree', function () {
    $group = Category::factory()->create(['name' => 'Foods']);
    $child = Category::factory()->childOf($group)->create(['name' => 'Seafood']);

    $response = $this->getJson('/api/v1/categories')->assertOk();

    $groupData = collect($response->json('data'))->firstWhere('id', $group->id);

    expect($response->json('data'))->toHaveCount(1)
        ->and(collect($groupData['children'])->pluck('id'))->toContain($child->id);
});

test('product directory filters by group across subcategories', function () {
    Config::set('marketplace.features.subscriptions_enabled', false);

    $shop = Shop::factory()->create(['status' => 'active', 'country_code' => 'PH']);
    $group = Category::factory()->create(['name' => 'Foods']);
    $child = Category::factory()->childOf($group)->create(['name' => 'Seafood']);
    $other = Category::factory()->childOf(Category::factory()->create())->create();

    $match = Product::factory()->published()->create(['shop_id' => $shop->id]);
    $match->categories()->attach($child->id);

    $miss = Product::factory()->published()->create(['shop_id' => $shop->id]);
    $miss->categories()->attach($other->id);

    // Filtering by the group matches products tagged with its subcategories.
    $ids = Livewire::test(ProductDirectory::class)
        ->set('categoryId', $group->id)
        ->instance()->products->pluck('id');

    expect($ids)->toContain($match->id)->not->toContain($miss->id);

    // Filtering by the subcategory is an exact match.
    $ids = Livewire::test(ProductDirectory::class)
        ->set('categoryId', $child->id)
        ->instance()->products->pluck('id');

    expect($ids)->toContain($match->id)->not->toContain($miss->id);
});

test('postgres search category filter matches products via parent group name', function () {
    Config::set('scout.driver', 'pgsql');
    Config::set('marketplace.features.subscriptions_enabled', false);
    app(EngineManager::class)->forgetEngines();

    $shop = Shop::factory()->create(['status' => 'active']);
    $group = Category::factory()->create(['name' => 'Foods']);
    $child = Category::factory()->childOf($group)->create(['name' => 'Seafood']);

    $product = Product::factory()->published()->create(['shop_id' => $shop->id, 'name' => 'Tuna Flakes']);
    $product->categories()->attach($child->id);

    expect(Product::search('Tuna')->where('categories', 'Foods')->get()->pluck('id'))
        ->toContain($product->id);
});

// ---------------------------------------------------------------------------
// Seller surfaces
// ---------------------------------------------------------------------------

test('seller shop profile form exposes categories grouped under top-level groups', function () {
    $seller = User::factory()->create(['email_verified_at' => now()]);
    $seller->assignRole('seller');
    Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $group = Category::factory()->create();
    $child = Category::factory()->childOf($group)->create();
    Category::factory()->childOf(Category::factory()->create())->create();

    $groups = Livewire::actingAs($seller)
        ->test(ShopProfileForm::class)
        ->instance()->categoryGroups;

    expect($groups->pluck('id')->all())
        ->toContain($group->id)
        ->not->toContain($child->id)
        ->and($groups->firstWhere('id', $group->id)->children->pluck('id'))
        ->toContain($child->id);
});

// ---------------------------------------------------------------------------
// Seeder
// ---------------------------------------------------------------------------

test('category seeder creates the canonical five-group taxonomy', function () {
    $this->seed(CategorySeeder::class);

    $groups = Category::roots()->orderBy('name')->get();

    expect($groups->pluck('name')->all())->toBe([
        'Agriculture & Raw Materials',
        'Business & Community',
        'Foods',
        'Non-Foods',
        'Services',
    ]);

    expect(Category::whereNotNull('parent_id')->count())->toBe(53);
    expect(Category::count())->toBe(58);

    $meat = Category::where('slug', 'meat-poultry')->sole();

    expect($meat->parent->slug)->toBe('foods')
        ->and($meat->variant_attributes_schema)->toHaveKey('attr_cut');

    // Groups never carry a variant schema — schemas live on leaf categories.
    expect(Category::where('slug', 'foods')->sole()->variant_attributes_schema)->toBeEmpty();
});

test('categories present in canonical sort order', function () {
    $this->seed(CategorySeeder::class);

    // Groups follow the seeded display order, not alphabetical.
    expect(Category::roots()->orderBy('sort_order')->pluck('name')->all())->toBe([
        'Foods',
        'Non-Foods',
        'Services',
        'Agriculture & Raw Materials',
        'Business & Community',
    ]);

    // Children inherit display order via the self-ordering relation.
    $foods = Category::where('slug', 'foods')->sole();

    expect($foods->children->pluck('name')->take(3)->all())->toBe([
        'Meat & Poultry',
        'Seafood',
        'Fresh Produce',
    ]);

    // Admin-set ordering wins over alphabetical ties.
    Category::factory()->create(['name' => 'AAA Out of Order', 'slug' => 'aaa-out', 'sort_order' => 99]);

    expect(Category::roots()->orderBy('sort_order')->orderBy('name')->pluck('name')->last())
        ->toBe('AAA Out of Order');
});

test('category seeder rebuilds the table and clears stale rows', function () {
    $this->seed(CategorySeeder::class);

    // A stale/legacy row outside the canonical tree is wiped on re-seed.
    Category::factory()->create(['name' => 'Legacy Category', 'slug' => 'legacy-category']);

    $this->seed(CategorySeeder::class);

    expect(Category::count())->toBe(58);
    expect(Category::where('slug', 'meat-poultry')->count())->toBe(1);
    $this->assertDatabaseMissing('categories', ['slug' => 'legacy-category']);
});
