<?php

namespace App\Http\Controllers;

use App\Models\AdviserReview;
use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdviserController extends Controller
{
    public function index()
    {
        // TEMP: get categories with enough data
        $categories = \App\Models\Feedback::where('status', 'approved')
            ->notFlagged()
            ->select('category')
            ->groupBy('category')
            ->havingRaw('COUNT(*) >= 3')
            ->pluck('category');

        return view('adviser.dashboard', compact('categories'));
    }

    public function dashboard()
    {
        $adviserId = auth()->id();

        // ── Categories that have enough data for ideas (≥3 reports) ──
        $readyCategories = Feedback::where('status', 'approved')
            ->notFlagged()
            ->select('category')
            ->selectRaw('COUNT(*) as total_reports')
            ->groupBy('category')
            ->havingRaw('COUNT(*) >= 3')
            ->get();

        // ── Attach review status to each category ──
        $pendingCategories = $readyCategories->filter(function ($cat) use ($adviserId) {
            // A category is "pending" if adviser hasn't reviewed ANY idea in it
            return ! AdviserReview::where('category', $cat->category)
                ->where('user_id', $adviserId)
                ->exists();
        })->values();

        $reviewedCategories = $readyCategories->filter(function ($cat) use ($adviserId) {
            return AdviserReview::where('category', $cat->category)
                ->where('user_id', $adviserId)
                ->exists();
        })->values();

        // ── Recent reviews by this adviser ──
        $recentReviews = AdviserReview::where('user_id', $adviserId)
            ->latest()
            ->take(10)
            ->get();

        // ── Recommendation breakdown (for the summary stat) ──
        $recommendationStats = AdviserReview::where('user_id', $adviserId)
            ->select('recommendation')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('recommendation')
            ->pluck('total', 'recommendation');

        // ── Total ideas reviewed ──
        $totalReviewed = AdviserReview::where('user_id', $adviserId)->count();

        // ── Evaluations for reviewed categories (scores) ──
        $evaluations = IdeaEvaluation::whereIn(
            'category',
            $reviewedCategories->pluck('category')
        )->get()->keyBy('category');

        return view('adviser.dashboard', compact(
            'pendingCategories',
            'reviewedCategories',
            'recentReviews',
            'recommendationStats',
            'totalReviewed',
            'evaluations',
            'readyCategories'
        ));
    }

    public function evaluations(Request $request): View
    {
        $adviserId = auth()->id();

        $query = AdviserReview::query()->where('user_id', $adviserId);

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('idea_title', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('recommendation')) {
            $query->where('recommendation', $request->recommendation);
        }

        $sort = (string) $request->get('sort', 'newest');

        match ($sort) {
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $reviews = $query->paginate(15)->withQueryString();

        $categories = AdviserReview::query()
            ->where('user_id', $adviserId)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $counts = [
            'all' => AdviserReview::where('user_id', $adviserId)->count(),
            'recommended' => AdviserReview::where('user_id', $adviserId)->where('recommendation', 'Recommended')->count(),
            'needs_revision' => AdviserReview::where('user_id', $adviserId)->where('recommendation', 'Needs Revision')->count(),
            'not_recommended' => AdviserReview::where('user_id', $adviserId)->where('recommendation', 'Not Recommended')->count(),
        ];

        return view('adviser.evaluations.index', compact('reviews', 'categories', 'counts', 'sort'));
    }
}
