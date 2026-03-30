<?php

namespace App\Http\Controllers;

use App\Models\{Feedback, AdviserReview, IdeaEvaluation};
use Illuminate\Http\Request;

class AdviserController extends Controller
{
    public function index()
{
    // TEMP: get categories with enough data
    $categories = \App\Models\Feedback::where('status', 'approved')
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
        ->select('category')
        ->selectRaw('COUNT(*) as total_reports')
        ->groupBy('category')
        ->havingRaw('COUNT(*) >= 3')
        ->get();
 
    // ── Attach review status to each category ──
    $pendingCategories = $readyCategories->filter(function ($cat) use ($adviserId) {
        // A category is "pending" if adviser hasn't reviewed ANY idea in it
        return !AdviserReview::where('category', $cat->category)
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
}
