<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reviews;

use App\Jobs\RecalculateRatingsJob;
use App\Models\ReviewReport;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Review Reports')]
final class ReviewReportQueue extends Component
{
    use WithPagination;

    public function dismiss(int $reportId): void
    {
        $report = ReviewReport::findOrFail($reportId);

        $report->update([
            'status' => 'dismissed',
            'resolved_by_id' => auth()->id(),
            'resolved_at' => now(),
        ]);
    }

    public function action(int $reportId): void
    {
        $report = ReviewReport::with('review')->findOrFail($reportId);

        $report->update([
            'status' => 'actioned',
            'resolved_by_id' => auth()->id(),
            'resolved_at' => now(),
        ]);

        $review = $report->review;
        $review->update(['is_hidden' => true]);

        RecalculateRatingsJob::dispatch($review->reviewable_type, $review->reviewable_id);
    }

    public function render(): View
    {
        $reports = ReviewReport::where('status', 'pending')
            ->with(['review.reviewer', 'reporter'])
            ->latest()
            ->paginate(20);

        return view('livewire.admin.reviews.review-report-queue', [
            'reports' => $reports,
        ]);
    }
}
