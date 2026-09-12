<?php

declare(strict_types=1);

use App\Livewire\Admin\CertificationQueue;
use App\Livewire\Admin\Finance\AnalyticsDashboard;
use App\Livewire\Admin\Rfq\RfqList;
use App\Livewire\Admin\SourcingQueue;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\SourcingRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

// ---------------------------------------------------------------------------
// AnalyticsDashboard: open counts in metrics
// ---------------------------------------------------------------------------

test('dashboard metrics include open_rfq_count for pending, in_progress, and null admin_status recipients', function () {
    RfqRecipient::factory()->create(['admin_status' => 'pending']);
    RfqRecipient::factory()->create(['admin_status' => 'in_progress']);
    RfqRecipient::factory()->create(['admin_status' => null]);
    RfqRecipient::factory()->create(['admin_status' => 'resolved']);

    Livewire::actingAs($this->admin)
        ->test(AnalyticsDashboard::class)
        ->assertSet('metrics.open_rfq_count', 3);
});

test('dashboard metrics include open_sourcing_count for pending and in_progress sourcing requests', function () {
    SourcingRequest::factory()->create(['status' => 'pending']);
    SourcingRequest::factory()->inProgress()->create();
    SourcingRequest::factory()->create(['status' => 'completed']);

    Livewire::actingAs($this->admin)
        ->test(AnalyticsDashboard::class)
        ->assertSet('metrics.open_sourcing_count', 2);
});

test('dashboard metrics include expiring_cert_count for expiring_soon and expired certificates', function () {
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);

    ShopCertificate::factory()->expiringSoon()->create(['shop_id' => $shop->id]);
    ShopCertificate::factory()->expired()->create(['shop_id' => $shop->id]);
    ShopCertificate::factory()->create(['shop_id' => $shop->id, 'status' => 'active']);

    Livewire::actingAs($this->admin)
        ->test(AnalyticsDashboard::class)
        ->assertSet('metrics.expiring_cert_count', 2);
});

test('dashboard open_rfq_count is zero when no open rfqs exist', function () {
    RfqRecipient::factory()->create(['admin_status' => 'resolved']);

    Livewire::actingAs($this->admin)
        ->test(AnalyticsDashboard::class)
        ->assertSet('metrics.open_rfq_count', 0);
});

// ---------------------------------------------------------------------------
// #[Url] binding: query param pre-selects status filter on arrival
// ---------------------------------------------------------------------------

test('rfq list statusFilter is pre-selected from status query param', function () {
    Livewire::actingAs($this->admin)
        ->withQueryParams(['status' => 'pending'])
        ->test(RfqList::class)
        ->assertSet('statusFilter', 'pending');
});

test('rfq list statusFilter defaults to all when no query param', function () {
    Livewire::actingAs($this->admin)
        ->test(RfqList::class)
        ->assertSet('statusFilter', 'all');
});

test('sourcing queue statusFilter is pre-selected from status query param', function () {
    Livewire::actingAs($this->admin)
        ->withQueryParams(['status' => 'pending'])
        ->test(SourcingQueue::class)
        ->assertSet('statusFilter', 'pending');
});

test('sourcing queue statusFilter defaults to empty when no query param', function () {
    Livewire::actingAs($this->admin)
        ->test(SourcingQueue::class)
        ->assertSet('statusFilter', '');
});

test('certification queue statusFilter is pre-selected from status query param', function () {
    Livewire::actingAs($this->admin)
        ->withQueryParams(['status' => 'expiring_soon'])
        ->test(CertificationQueue::class)
        ->assertSet('statusFilter', 'expiring_soon');
});

test('certification queue statusFilter defaults to empty when no query param', function () {
    Livewire::actingAs($this->admin)
        ->test(CertificationQueue::class)
        ->assertSet('statusFilter', '');
});
