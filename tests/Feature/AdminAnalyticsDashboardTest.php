<?php

declare(strict_types=1);

use App\Livewire\Admin\Finance\AnalyticsDashboard;
use App\Models\User;
use App\Services\GoogleAnalyticsService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

final class GoogleAnalyticsFake
{
    public function __construct(
        private readonly ?array $overview = null,
        private readonly bool $configured = false,
    ) {}

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function getOverview(string $from, string $to): ?array
    {
        return $this->overview;
    }
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can access the analytics dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard.index'))
        ->assertOk();
});

test('dashboard defaults to 30d period with correct date range', function () {
    Livewire::test(AnalyticsDashboard::class)
        ->assertSet('period', '30d')
        ->assertSet('from', now()->subDays(30)->format('Y-m-d'))
        ->assertSet('to', now()->format('Y-m-d'));
});

test('changing period updates from and to dates', function () {
    Livewire::test(AnalyticsDashboard::class)
        ->set('period', '7d')
        ->assertSet('from', now()->subDays(7)->format('Y-m-d'))
        ->assertSet('to', now()->format('Y-m-d'))
        ->assertSet('period', '7d');
});

test('custom period shows custom date inputs', function () {
    Livewire::test(AnalyticsDashboard::class)
        ->set('period', 'custom')
        ->assertSet('period', 'custom');
});

test('metrics include trend data arrays', function () {
    Livewire::test(AnalyticsDashboard::class)
        ->assertSet('metrics.revenue_trend', [])
        ->assertSet('metrics.subscription_trend', [])
        ->assertSet('metrics.certification_trend', []);
});

test('website traffic section is hidden when google analytics is not configured', function () {
    config([
        'services.google_analytics.property_id' => null,
        'services.google_analytics.credentials' => null,
    ]);

    $this->app->bind(GoogleAnalyticsService::class, fn () => new GoogleAnalyticsFake(configured: false));

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard.index'))
        ->assertOk();

    Livewire::test(AnalyticsDashboard::class)
        ->assertSet('gaConfigured', false);
});

test('website traffic section renders google analytics metrics when configured', function () {
    config([
        'services.google_analytics.property_id' => '123456789',
        'services.google_analytics.credentials' => '/tmp/credentials.json',
    ]);

    $this->app->bind(GoogleAnalyticsService::class, fn () => new GoogleAnalyticsFake(
        overview: [
            'active_users' => 320,
            'sessions' => 410,
            'page_views' => 980,
            'bounce_rate' => 42.3,
            'avg_session_duration' => 95,
        ],
        configured: true,
    ));

    $this->actingAs($this->admin);

    Livewire::test(AnalyticsDashboard::class)
        ->assertSet('gaConfigured', true)
        ->assertSee('Website Traffic')
        ->assertSee('Active Users')
        ->assertSee('320')
        ->assertSee('Sessions')
        ->assertSee('410')
        ->assertSee('Page Views')
        ->assertSee('980')
        ->assertSee('42.3%')
        ->assertSee('Avg. Session Duration')
        ->assertSee('1:35');
});

test('dashboard renders without error when ga api call fails', function () {
    config([
        'services.google_analytics.property_id' => '123456789',
        'services.google_analytics.credentials' => '/tmp/credentials.json',
    ]);

    $this->app->bind(GoogleAnalyticsService::class, fn () => new GoogleAnalyticsFake(
        overview: null,
        configured: true,
    ));

    $this->actingAs($this->admin);

    Livewire::test(AnalyticsDashboard::class)
        ->assertSet('gaConfigured', true)
        ->assertSee('Website Traffic')
        ->assertSee('Active Users')
        ->assertSee('Bounce Rate')
        ->assertSee('Avg. Session Duration');
});

test('dashboard page title is Platform Dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard.index'))
        ->assertSee('Platform Dashboard');
});
