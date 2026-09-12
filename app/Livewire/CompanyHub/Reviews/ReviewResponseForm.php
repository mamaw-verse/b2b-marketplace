<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Reviews;

use App\Models\Review;
use App\Models\ReviewResponse;
use Illuminate\View\View;
use Livewire\Component;

final class ReviewResponseForm extends Component
{
    public Review $review;

    public string $body = '';

    public bool $editing = false;

    public function mount(Review $review): void
    {
        $this->review = $review;

        if ($review->response) {
            $this->body = $review->response->body;
        }
    }

    public function submit(): void
    {
        $this->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        if ($this->review->response) {
            $this->review->response->update([
                'body' => $this->body,
            ]);
        } else {
            ReviewResponse::create([
                'review_id' => $this->review->id,
                'responder_user_id' => auth()->id(),
                'body' => $this->body,
            ]);
        }

        $this->editing = false;
        $this->review->refresh();
    }

    public function render(): View
    {
        return view('livewire.company-hub.reviews.review-response-form');
    }
}
