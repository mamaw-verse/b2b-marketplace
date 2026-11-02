<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\DltDisbursementServiceInterface;
use App\Events\OrderReturned;
use App\Events\SubscriptionStatusChanged;
use App\Listeners\NotifyBuyerOrderReturned;
use App\Listeners\PostDisputeStatusMessage;
use App\Listeners\PostOrderStatusMessage;
use App\Listeners\ReindexProductsForSubscriptionChange;
use App\Models\User;
use App\Notifications\Channels\ArabicGreetingMailChannel;
use App\Search\PostgresSearchEngine;
use App\Services\DltDisbursementService;
use App\Services\DltPaymentService;
use App\Services\MockDltDisbursementService;
use App\Services\TurnstileVerifier;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Scout\EngineManager;
use Spatie\Permission\Models\Role;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TurnstileVerifier::class, function () {
            return new TurnstileVerifier(
                secret: (string) config('services.turnstile.secret'),
                expectedHostname: config('humancheck.expected_hostname'),
                expectedAction: config('humancheck.expected_action'),
            );
        });

        $this->app->singleton(DltPaymentService::class);

        // Route the notification "mail" channel through our channel that
        // prepends a shared Arabic salutation addressed to the recipient.
        $this->app->bind(
            MailChannel::class,
            ArabicGreetingMailChannel::class,
        );

        $this->app->singleton(
            DltDisbursementServiceInterface::class,
            fn () => config('services.dlt.disbursement_enabled', false)
                ? new DltDisbursementService
                : new MockDltDisbursementService,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(PostOrderStatusMessage::class);
        Event::subscribe(PostDisputeStatusMessage::class);
        Event::listen(
            SubscriptionStatusChanged::class,
            ReindexProductsForSubscriptionChange::class,
        );

        Event::listen(
            OrderReturned::class,
            NotifyBuyerOrderReturned::class,
        );

        Gate::define('accessHub', function (User $user) {
            return $user->shop !== null
                && $user->shop->status === 'active'
                && $user->shop->deleted_at === null;
        });

        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->registerSearchEngines();
        $this->shareLegalContactDetails();
    }

    /**
     * Register the native Postgres Scout engine.
     *
     * `SCOUT_DRIVER=pgsql` serves search from Postgres (tsvector + pg_trgm).
     * Flipping back to Typesense later is config-only
     * (`SCOUT_DRIVER=typesense` + `sail up --profile search` + `scout:import`).
     */
    protected function registerSearchEngines(): void
    {
        app(EngineManager::class)->extend('pgsql', fn () => new PostgresSearchEngine);
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

        Password::defaults(fn (): Password => Password::min(6)
            ->when(app()->isProduction(), fn (Password $password): Password => $password->uncompromised()));
    }

    /**
     * Share the admin profile phone with the privacy policy partial.
     *
     * The number is maintained on the admin user profile; the line stays
     * hidden until a phone is set, and nothing breaks when roles are
     * not seeded (e.g. fresh test databases).
     */
    protected function shareLegalContactDetails(): void
    {
        View::composer('partials.legal.privacy-body', function (\Illuminate\View\View $view): void {
            $adminPhone = Role::where('name', 'admin')->exists()
                ? User::role('admin')->whereNotNull('phone')->value('phone')
                : null;

            $view->with('adminPhone', $adminPhone);
        });
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

        RateLimiter::for('review-creation', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => [
                            'code' => 'RATE_LIMITED',
                            'message' => 'Too many reviews submitted. Please try again later.',
                        ],
                    ], 429);
                });
        });
    }
}
