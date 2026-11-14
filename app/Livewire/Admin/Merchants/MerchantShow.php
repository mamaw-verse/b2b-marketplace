<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Merchants;

use App\Actions\Staff\RevokeStaffAction;
use App\Livewire\Concerns\HasDocumentViewer;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopStaff;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Merchant Details')]
final class MerchantShow extends Component
{
    use HasDocumentViewer;

    public Shop $shop;

    public ?int $previewProductId = null;

    public ?int $confirmingStaffId = null;

    public function mount(Shop $shop): void
    {
        Gate::authorize('merchant.manage');

        $this->shop = $shop->load([
            'user',
            'onboarding',
            'subscriptions',
            'certificationApplications',
            'latestSubscription',
            'certificates.media',
            'categories.parent',
            'documents',
        ]);
    }

    public function openPreview(int $productId): void
    {
        Gate::authorize('merchant.manage');

        $this->previewProductId = $productId;
        unset($this->previewProduct);

        Flux::modal('product-preview')->show();
    }

    /**
     * Product loaded for the preview modal.
     */
    #[Computed]
    public function previewProduct(): ?Product
    {
        if (! $this->previewProductId) {
            return null;
        }

        return Product::with(['shop', 'variants', 'gallery', 'categories.parent'])
            ->find($this->previewProductId);
    }

    /**
     * All products for this merchant, ordered by published-first then name.
     * Includes soft-deleted so admin has full visibility.
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::with(['variants', 'categories'])
            ->withTrashed()
            ->where('shop_id', $this->shop->id)
            ->orderByRaw("status = 'published' desc")
            ->orderBy('name')
            ->get();
    }

    public function suspend(): void
    {
        Gate::authorize('merchant.manage');

        $this->shop->suspend();

        $this->dispatch('toast', message: 'Merchant suspended successfully.');
    }

    public function reactivate(): void
    {
        Gate::authorize('merchant.manage');

        $this->shop->reactivate();

        $this->dispatch('toast', message: 'Merchant reactivated successfully.');
    }

    /** @return Collection<int, ShopStaff> */
    #[Computed]
    public function staffMembers(): Collection
    {
        return $this->shop->staff()->with(['user', 'inviter'])->orderByDesc('created_at')->get();
    }

    public function confirmRemoveStaff(int $staffId): void
    {
        Gate::authorize('merchant.manage');
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
        $this->shop->staff()->findOrFail($staffId);
        $this->confirmingStaffId = $staffId;
        Flux::modal('admin-remove-staff-confirm')->show();
    }

    public function removeStaff(RevokeStaffAction $action): void
    {
        Gate::authorize('merchant.manage');
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
        $staff = $this->shop->staff()->findOrFail($this->confirmingStaffId);
        $action->execute($staff, auth()->user());
        $this->confirmingStaffId = null;
        unset($this->staffMembers);
        Flux::modal('admin-remove-staff-confirm')->close();
    }

    public function render(): View
    {
        return view('livewire.admin.merchants.merchant-show');
    }
}
