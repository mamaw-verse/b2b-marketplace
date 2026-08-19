<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    /**
     * GET /api/v1/search/companies — Search shops via Typesense/Scout.
     */
    public function companies(Request $request): JsonResponse
    {
        $query = $request->string('q', '*')->toString();
        $limit = $request->integer('limit', 20);

        if ($cursor = $request->query('cursor')) {
            $request->merge(['page' => (int) base64_decode($cursor)]);
        }

        $search = Shop::search($query);

        if ($request->filled('category')) {
            $search->where('categories', $request->string('category')->toString());
        }

        if ($request->filled('province')) {
            $search->where('province', $request->string('province')->toString());
        }

        $startTime = microtime(true);
        $results = $search->paginate($limit);
        $searchTimeMs = round((microtime(true) - $startTime) * 1000);

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'found' => count($results->items()),
                'search_time_ms' => $searchTimeMs,
                'next_cursor' => $results->nextPageUrl() ? base64_encode((string) ($results->currentPage() + 1)) : null,
            ],
        ]);
    }

    /**
     * GET /api/v1/search/products — Search products via Typesense/Scout.
     */
    public function products(Request $request): JsonResponse
    {
        $query = $request->string('q', '*')->toString();
        $limit = $request->integer('limit', 20);

        if ($cursor = $request->query('cursor')) {
            $request->merge(['page' => (int) base64_decode($cursor)]);
        }

        $search = Product::search($query);

        if ($request->filled('category')) {
            $search->where('categories', $request->string('category')->toString());
        }

        if ($request->filled('shop_id')) {
            $search->where('shop_id', $request->string('shop_id')->toString());
        }

        $startTime = microtime(true);
        $results = $search->paginate($limit);
        $searchTimeMs = round((microtime(true) - $startTime) * 1000);

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'found' => count($results->items()),
                'search_time_ms' => $searchTimeMs,
                'next_cursor' => $results->nextPageUrl() ? base64_encode((string) ($results->currentPage() + 1)) : null,
            ],
        ]);
    }

    /**
     * GET /api/v1/companies/{shop} — Public company profile.
     */
    public function showCompany(Shop $shop): JsonResponse
    {
        if ($shop->status !== 'active') {
            abort(404);
        }

        $shop->load(['categories', 'certificates', 'products' => function ($query) {
            $query->where('status', 'published')->limit(10);
        }, 'products.variants']);

        return response()->json(['data' => $shop]);
    }

    /**
     * GET /api/v1/products/{product} — Public product detail.
     */
    public function showProduct(Product $product): JsonResponse
    {
        if ($product->status !== 'published') {
            abort(404);
        }

        $product->load(['variants', 'gallery', 'documents', 'categories', 'shop']);

        return response()->json(['data' => $product]);
    }

    /**
     * GET /api/v1/categories — Public category list.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

        return response()->json(['data' => $categories]);
    }
}
