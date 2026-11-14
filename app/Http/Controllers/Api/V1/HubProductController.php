<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductDocumentRequest;
use App\Http\Requests\StoreProductGalleryRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Jobs\IndexProductJob;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class HubProductController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
    ) {}

    /**
     * GET /api/v1/hub/products — List own shop's products.
     */
    public function index(Request $request): JsonResponse
    {
        $shop = $this->getActiveShopOrFail($request);

        $products = $shop->products()
            ->with(['variants', 'categories'])
            ->latest()
            ->cursorPaginate($request->integer('limit', 20));

        return response()->json(['data' => $products]);
    }

    /**
     * POST /api/v1/hub/products — Create a new product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $shop = $this->getActiveShopOrFail($request);
        Gate::authorize('createProduct', $shop);

        $validated = $request->validated();

        $product = $shop->products()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => 'archived',
            'updated_by_id' => $request->user()->id,
        ]);

        if (! empty($validated['category_ids'])) {
            $product->categories()->sync($validated['category_ids']);
        }

        $product->load('categories');

        return response()->json(['data' => $product], 201);
    }

    /**
     * PUT /api/v1/hub/products/{product} — Update a product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validated();

        $product->update([
            'name' => $validated['name'] ?? $product->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $product->description,
            'updated_by_id' => $request->user()->id,
        ]);

        if (array_key_exists('category_ids', $validated)) {
            $product->categories()->sync($validated['category_ids'] ?? []);
        }

        $product->load('categories');

        return response()->json(['data' => $product]);
    }

    /**
     * POST /api/v1/hub/products/{product}/publish — Publish a product.
     */
    public function publish(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('publish', $product);

        $publishedVariantCount = $product->variants()
            ->where('status', 'published')
            ->count();

        if ($publishedVariantCount === 0) {
            throw ValidationException::withMessages([
                'variants' => ['Product must have at least one published variant before publishing.'],
            ]);
        }

        $product->update(['status' => 'published', 'updated_by_id' => $request->user()->id]);

        IndexProductJob::dispatch($product)->afterCommit();

        return response()->json(['data' => $product]);
    }

    /**
     * POST /api/v1/hub/products/{product}/variants — Add a variant.
     */
    public function storeVariant(StoreProductVariantRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validated();

        $variant = $product->variants()->create([
            ...$validated,
            'currency' => $validated['currency'] ?? 'PHP',
            'status' => $validated['status'] ?? 'archived',
        ]);
        $product->update(['updated_by_id' => $request->user()->id]);

        return response()->json(['data' => $variant], 201);
    }

    /**
     * PUT /api/v1/hub/products/{product}/variants/{variant} — Update a variant.
     */
    public function updateVariant(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): JsonResponse
    {
        Gate::authorize('update', $product);

        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        $variant->update($request->validated());
        $product->update(['updated_by_id' => $request->user()->id]);

        return response()->json(['data' => $variant]);
    }

    /**
     * POST /api/v1/hub/products/{product}/gallery — Upload a gallery image.
     */
    public function storeGallery(StoreProductGalleryRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $media = $this->mediaUploadService->store(
            $request->file('image'),
            $request->user()->id,
            'Product gallery image',
        );

        $galleryEntry = $product->gallery()->create([
            'media_id' => $media->id,
            'sort_order' => $request->integer('sort_order', 0),
        ]);
        $product->update(['updated_by_id' => $request->user()->id]);

        return response()->json(['data' => $galleryEntry->load('media')], 201);
    }

    /**
     * POST /api/v1/hub/products/{product}/documents — Attach a document.
     */
    public function storeDocument(StoreProductDocumentRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $media = $this->mediaUploadService->store(
            $request->file('document'),
            $request->user()->id,
            $request->input('description', ''),
        );

        $product->documents()->attach($media->id);
        $product->update(['updated_by_id' => $request->user()->id]);

        return response()->json(['data' => $media], 201);
    }

    /**
     * Get the authenticated user's active shop or abort with 403.
     */
    private function getActiveShopOrFail(Request $request): Shop
    {
        $shop = $request->user()->activeShop;

        if (! $shop || $shop->status !== 'active') {
            abort(403, 'You must have an active shop to manage products.');
        }

        return $shop;
    }
}
