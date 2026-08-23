<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\DltPaymentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::subscribe(\App\Listeners\PostOrderStatusMessage::class);
        \Illuminate\Support\Facades\Event::subscribe(\App\Listeners\PostDisputeStatusMessage::class);

        \Illuminate\Support\Facades\Gate::define('accessHub', function (\App\Models\User $user) {
            return $user->shop !== null
                && $user->shop->status === 'active'
                && $user->shop->deleted_at === null;
        });

        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth-limiter', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => [
                            'code' => 'RATE_LIMITED',
                            'message' => 'Too many attempts. Please try again later.',
                        ],
                    ], 429);
                });
        });

        RateLimiter::for('search-limiter', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => [
                            'code' => 'RATE_LIMITED',
                            'message' => 'Too many search requests. Please try again later.',
                        ],
                    ], 429);
                });
        });
    }
}
