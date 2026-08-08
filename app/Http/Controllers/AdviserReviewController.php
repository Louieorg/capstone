<?php

namespace App\Http\Controllers;

use App\Models\AdviserReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdviserReviewController extends Controller
{
    private const RECOMMENDATIONS = ['Recommended', 'Needs Revision', 'Not Recommended'];

    public function index(Request $request): View
    {
        $query = AdviserReview::query()->with('user');

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

        if ($request->filled('recommendation') && in_array($request->recommendation, self::RECOMMENDATIONS, true)) {
            $query->where('recommendation', $request->recommendation);
        }

        if ($request->filled('adviser')) {
            $query->where('user_id', $request->adviser);
        }

        $sort = (string) $request->get('sort', 'newest');

        match ($sort) {
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $reviews = $query->paginate(15)->withQueryString();

        $categories = AdviserReview::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $advisers = User::query()
            ->where('role', 'adviser')
            ->orderBy('name')
            ->get(['id', 'name']);

        $counts = [
            'all' => AdviserReview::count(),
            'recommended' => AdviserReview::where('recommendation', 'Recommended')->count(),
            'needs_revision' => AdviserReview::where('recommendation', 'Needs Revision')->count(),
            'not_recommended' => AdviserReview::where('recommendation', 'Not Recommended')->count(),
        ];

        return view('admin.adviser-reviews.index', compact('reviews', 'categories', 'advisers', 'counts', 'sort'));
    }
}
