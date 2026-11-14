<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureShopOwner
{
    /**
     * Restrict owner-only shop management pages to users with an active shop.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->can('manageShop') ?? false, 403);

        return $next($request);
    }
}
