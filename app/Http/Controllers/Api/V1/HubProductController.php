<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\IndexProductJob;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
    public function store(Request $request): JsonResponse
    {
        $shop = $this->getActiveShopOrFail($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $product = $shop->products()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => 'archived',
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
    public function update(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $product->update([
            'name' => $validated['name'] ?? $product->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $product->description,
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
        Gate::authorize('update', $product);

        $publishedVariantCount = $product->variants()
            ->where('status', 'published')
            ->count();

        if ($publishedVariantCount === 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'variants' => ['Product must have at least one published variant before publishing.'],
            ]);
        }

        $product->update(['status' => 'published']);

        IndexProductJob::dispatch($product);

        return response()->json(['data' => $product]);
    }

    /**
     * POST /api/v1/hub/products/{product}/variants — Add a variant.
     */
    public function storeVariant(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_variants')->where('product_id', $product->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'attributes' => ['nullable', 'array'],
            'price' => ['required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'quantity_breaks' => ['nullable', 'array'],
            'quantity_breaks.*.min_qty' => ['required_with:quantity_breaks', 'integer', 'min:1'],
            'quantity_breaks.*.price' => ['required_with:quantity_breaks', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'available_quantity' => ['nullable', 'integer', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'pack_size' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['archived', 'published'])],
        ]);

        $variant = $product->variants()->create([
            ...$validated,
            'currency' => $validated['currency'] ?? 'PHP',
            'status' => $validated['status'] ?? 'archived',
        ]);

        return response()->json(['data' => $variant], 201);
    }

    /**
     * PUT /api/v1/hub/products/{product}/variants/{variant} — Update a variant.
     */
    public function updateVariant(Request $request, Product $product, ProductVariant $variant): JsonResponse
    {
        Gate::authorize('update', $product);

        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        $validated = $request->validate([
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('product_variants')->where('product_id', $product->id)->ignore($variant->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'attributes' => ['nullable', 'array'],
            'price' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'minimum_order_quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'quantity_breaks' => ['nullable', 'array'],
            'quantity_breaks.*.min_qty' => ['required_with:quantity_breaks', 'integer', 'min:1'],
            'quantity_breaks.*.price' => ['required_with:quantity_breaks', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'available_quantity' => ['nullable', 'integer', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'pack_size' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['archived', 'published'])],
        ]);

        $variant->update($validated);

        return response()->json(['data' => $variant]);
    }

    /**
     * POST /api/v1/hub/products/{product}/gallery — Upload a gallery image.
     */
    public function storeGallery(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $media = $this->mediaUploadService->store(
            $request->file('image'),
            $request->user()->id,
            'Product gallery image',
        );

        $galleryEntry = $product->gallery()->create([
            'media_id' => $media->id,
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return response()->json(['data' => $galleryEntry->load('media')], 201);
    }

    /**
     * POST /api/v1/hub/products/{product}/documents — Attach a document.
     */
    public function storeDocument(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $request->validate([
            'document' => ['required', 'file', 'max:20480'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $media = $this->mediaUploadService->store(
            $request->file('document'),
            $request->user()->id,
            $request->input('description', ''),
        );

        $product->documents()->attach($media->id);

        return response()->json(['data' => $media], 201);
    }

    /**
     * Get the authenticated user's active shop or abort with 403.
     */
    private function getActiveShopOrFail(Request $request): \App\Models\Shop
    {
        $shop = $request->user()->shop;

        if (! $shop || $shop->status !== 'active') {
            abort(403, 'You must have an active shop to manage products.');
        }

        return $shop;
    }
}
