<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Support\Seo\Visibility;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCanonicalSlug
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $parameter, string $routeName): Response
    {
        $route = $request->route();
        $model = $route instanceof Route ? $route->parameter($parameter) : null;
        $requestedValue = $route instanceof Route ? $route->originalParameter($parameter) : null;

        if (
            $model instanceof Model
            && is_string($model->getAttribute('slug'))
            && (string) $requestedValue !== $model->getAttribute('slug')
            && $this->isVisible($model)
        ) {
            $url = route($routeName, $model);
            $query = $request->getQueryString();

            return redirect()->to($query === null ? $url : $url.'?'.$query, 301);
        }

        return $next($request);
    }

    private function isVisible(Model $model): bool
    {
        if ($model instanceof Product) {
            return Visibility::isProductVisible($model);
        }

        if ($model instanceof Shop) {
            return Visibility::isShopVisible($model);
        }

        // Category pages are public for every category; the slug is the only identity.
        return $model instanceof Category;
    }
}
