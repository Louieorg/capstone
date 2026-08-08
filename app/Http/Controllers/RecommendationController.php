<?php

namespace App\Http\Controllers;

use App\Models\IdeaEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function index(Request $request): View
    {
        $query = IdeaEvaluation::query();

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

        $review = (string) $request->get('review', 'all');

        if ($review === 'reviewed') {
            $query->whereNotNull('final_score');
        } elseif ($review === 'pending') {
            $query->whereNull('final_score');
        }

        $sort = (string) $request->get('sort', 'newest');

        match ($sort) {
            'oldest' => $query->oldest(),
            'overall_score' => $query->orderByDesc('overall_score'),
            'final_score' => $query->orderByDesc('final_score'),
            default => $query->latest(),
        };

        $evaluations = $query->paginate(15)->withQueryString();

        $categories = IdeaEvaluation::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $counts = [
            'all' => IdeaEvaluation::count(),
            'reviewed' => IdeaEvaluation::whereNotNull('final_score')->count(),
            'pending' => IdeaEvaluation::whereNull('final_score')->count(),
        ];

        return view('admin.recommendations.index', compact('evaluations', 'categories', 'counts', 'review', 'sort'));
    }
}
