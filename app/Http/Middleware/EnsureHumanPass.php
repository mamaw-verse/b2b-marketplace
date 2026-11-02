<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\HumanPass;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureHumanPass
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('humancheck.enabled', true)) {
            return $next($request);
        }

        if (HumanPass::valid($request)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax() || $request->hasHeader('X-Livewire')) {
            return response()->json([
                'code' => 'HUMAN_CHECK_REQUIRED',
                'message' => 'Human verification required.',
            ], 403);
        }

        return redirect()->back()->withErrors([
            'human_check' => __('Please complete the security check.'),
        ]);
    }
}
