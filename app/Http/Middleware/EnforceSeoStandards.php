<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Seo\IndexingPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends `X-Robots-Tag` for every non-public response (private areas, auth,
 * invitation tokens, and any non-production deployment).
 */
final class EnforceSeoStandards
{
    public function __construct(private readonly IndexingPolicy $policy) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $directive = $this->policy->robotsDirective($request);

        if ($directive !== null && ! $response->headers->has('X-Robots-Tag')) {
            $response->headers->set('X-Robots-Tag', $directive);
        }

        return $response;
    }
}
