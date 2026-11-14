<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureCanonicalSlug;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureHumanPass;
use App\Http\Middleware\EnsureShopOwner;
use App\Http\Middleware\SetLocale;
use App\Support\HumanPass;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            HumanPass::COOKIE_NAME,
        ]);
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'feature' => EnsureFeatureEnabled::class,
            'human' => EnsureHumanPass::class,
            'canonical.slug' => EnsureCanonicalSlug::class,
            'shop.owner' => EnsureShopOwner::class,
        ]);
        $middleware->appendToPriorityList(SubstituteBindings::class, EnsureCanonicalSlug::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => $e->getMessage(),
                        'fields' => $e->errors(),
                    ],
                ], $e->status);
            }
        });
    })->create();
