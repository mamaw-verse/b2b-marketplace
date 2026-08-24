<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Reviews;

use App\Models\Review;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Reviews')]
final class ReviewList extends Component
{
    use WithPagination;

    public ?int $ratingFilter = null;

    public function updatedRatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $shop = $user->shop;

        $query = Review::query()
            ->where(function ($q) use ($shop) {
                // Reviews for the shop itself
                $q->where(function ($sub) use ($shop) {
                    $sub->where('reviewable_type', \App\Models\Shop::class)
                        ->where('reviewable_id', $shop->id);
                })
                // Reviews for the shop's products
                    ->orWhere(function ($sub) use ($shop) {
                        $sub->where('reviewable_type', \App\Models\Product::class)
                            ->whereIn('reviewable_id', $shop->products()->pluck('id'));
                    });
            })
            ->with(['reviewer', 'response', 'reviewable']);

        if ($this->ratingFilter) {
            $query->where('rating', $this->ratingFilter);
        }

        $reviews = $query->latest()->paginate(20);

        return view('livewire.company-hub.reviews.review-list', [
            'reviews' => $reviews,
        ]);
    }
}
