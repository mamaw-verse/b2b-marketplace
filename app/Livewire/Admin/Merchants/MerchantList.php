<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Merchants;

use App\Models\Shop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Businesses')]
final class MerchantList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $countryFilter = '';

    public string $subscriptionFilter = '';

    public string $certificationFilter = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'countryFilter', 'subscriptionFilter', 'certificationFilter'])) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function merchants(): LengthAwarePaginator
    {
        $query = Shop::with(['user', 'latestSubscription', 'certificates']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('storefront_name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('registered_business_name', 'ilike', '%'.$this->search.'%')
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', 'ilike', '%'.$this->search.'%'));
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->countryFilter) {
            $query->where('country_code', $this->countryFilter);
        }

        if ($this->subscriptionFilter) {
            if ($this->subscriptionFilter === 'none') {
                $query->whereDoesntHave('latestSubscription');
            } else {
                $query->whereHas('latestSubscription', function ($q) {
                    $q->where('status', $this->subscriptionFilter);
                });
            }
        }

        if ($this->certificationFilter) {
            $validCerts = fn ($q) => $q->whereIn('status', ['active', 'expiring_soon']);

            if ($this->certificationFilter === 'certified') {
                $query->whereHas('certificates', $validCerts);
            } elseif ($this->certificationFilter === 'not_certified') {
                $query->whereDoesntHave('certificates', $validCerts);
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.merchants.merchant-list');
    }
}
