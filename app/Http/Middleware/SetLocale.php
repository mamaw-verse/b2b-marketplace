<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
        } else {
            // Fall back to the default locale for this country instance
            $instanceLocale = collect(config('marketplace.portals', []))
                ->firstWhere('country_code', config('marketplace.instance_country'))['locale']
                ?? config('app.locale');

            App::setLocale($instanceLocale);
        }

        return $next($request);
    }
}
