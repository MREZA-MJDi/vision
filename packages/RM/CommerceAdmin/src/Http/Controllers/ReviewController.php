<?php

namespace Rm\CommerceAdmin\Http\Controllers;

use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController
{
    private function model(): string
    {
        return (string) config('commerce-admin.models.review', ProductReview::class);
    }

    public function index(Request $request): View
    {
        $reviewModel = $this->model();
        $query = $reviewModel::query()->with(['product', 'user']);
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'pending');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('reviewer_name', 'like', '%' . $search . '%')
                    ->orWhere('reviewer_email', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%')
                    ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        if (in_array($status, ProductReview::STATUSES, true)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        $reviews = $query->orderByDesc('created_at')
            ->paginate((int) config('commerce-admin.items_per_page', 10))
            ->withQueryString();

        $stats = [
            'pending' => $reviewModel::query()->where('status', 'pending')->count(),
            'approved' => $reviewModel::query()->where('status', 'approved')->count(),
            'rejected' => $reviewModel::query()->where('status', 'rejected')->count(),
            'average' => round((float) $reviewModel::query()->where('status', 'approved')->avg('rating'), 2),
        ];

        return view('commerce-admin::reviews.index', compact('reviews', 'stats', 'search', 'status'));
    }

    public function updateStatus(Request $request, int $review): RedirectResponse
    {
        $reviewModel = $this->model();
        $validated = $request->validate([
            'status' => ['required', Rule::in(ProductReview::STATUSES)],
        ]);

        DB::transaction(function () use ($reviewModel, $review, $validated): void {
            $row = $reviewModel::query()->whereKey($review)->lockForUpdate()->firstOrFail();
            $row->status = $validated['status'];
            $row->approved_at = $validated['status'] === 'approved' ? now() : null;
            $row->save();
        });

        return redirect()->route('commerce-admin.reviews.index', array_filter([
            'status' => $request->query('status'),
            'q' => $request->query('q'),
        ]))->with('status', 'Review moderation status saved.');
    }
}
