<?php

declare(strict_types=1);

namespace App\Livewire\Admin\PayoutAccounts;

use App\Actions\RejectPayoutAccountAction;
use App\Actions\VerifyPayoutAccountAction;
use App\Models\SellerPayoutAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Payout Accounts')]
final class PayoutAccountQueue extends Component
{
    use WithPagination;

    public string $statusFilter = 'pending';

    public string $search = '';

    public ?int $rejectingAccountId = null;

    public string $rejectionReason = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function verifyAccount(int $accountId): void
    {
        $account = SellerPayoutAccount::findOrFail($accountId);

        app(VerifyPayoutAccountAction::class)->execute($account, Auth::user());
    }

    public function openRejectModal(int $accountId): void
    {
        $this->rejectingAccountId = $accountId;
        $this->rejectionReason = '';

        \Flux\Flux::modal('reject-payout-account')->show();
    }

    public function rejectAccount(): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'max:1000'],
        ]);

        $account = SellerPayoutAccount::findOrFail($this->rejectingAccountId);

        app(RejectPayoutAccountAction::class)->execute($account, $this->rejectionReason);

        $this->rejectingAccountId = null;
        $this->rejectionReason = '';

        \Flux\Flux::modal('reject-payout-account')->close();
    }

    /**
     * @return LengthAwarePaginator<SellerPayoutAccount>
     */
    #[Computed]
    public function accounts(): LengthAwarePaginator
    {
        return SellerPayoutAccount::with(['shop'])
            ->when($this->statusFilter, fn ($q) => $q->where('verification_status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->whereHas(
                'shop',
                fn ($sq) => $sq->where('storefront_name', 'ilike', "%{$this->search}%")
                    ->orWhere('registered_business_name', 'ilike', "%{$this->search}%"),
            ))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.payout-accounts.payout-account-queue');
    }
}
