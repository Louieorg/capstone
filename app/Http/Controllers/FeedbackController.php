<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\FeedbackVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\FeedbackApproved;
use App\Models\User;
use App\Models\AdviserReview;
use App\Models\IdeaEvaluation;
use App\Services\ClusteringService;
use App\Services\SeverityService;
use App\Services\ConfidenceService;
use App\Services\IdeaGeneratorService;
use Illuminate\Support\Facades\Cache;

class FeedbackController extends Controller
{
    public function create()
    {
        return view('submit');
    }

    public function home()
{
    $trending = Feedback::withCount('votes')
        ->where('status', 'approved')
        ->orderByDesc('votes_count')
        ->take(5)
        ->get();

    // total approved problems
    $totalProblems = Feedback::where('status', 'approved')->count();

    // categories that have enough reports for capstone ideas
    $ideaCandidates = Feedback::where('status', 'approved')
        ->select('category')
        ->groupBy('category')
        ->havingRaw('COUNT(*) >= 3')
        ->get()
        ->count();

    // total distinct categories
    $totalCategories = Feedback::select('category')
        ->distinct()
        ->count();

    return view('landing', compact(
        'trending',
        'totalProblems',
        'ideaCandidates',
        'totalCategories'
    ));
}

    public function store(Request $request)
    {
        $request->validate([
    'title'           => 'required|min:5',
    'category'        => 'required',
    'description'     => 'required|min:10',
    'impact'          => 'required|min:10',
    'frequency'       => 'required',
    'current_process' => 'required',
    'affected_users'  => 'required',
    'affected_group'  => 'required|array|min:1',   // ← now an array
 
    // Only required when "Other" is selected
    'category_other'       => 'required_if:category,Other|nullable|string|max:100',
    'current_process_other'=> 'required_if:current_process,Other|nullable|string|max:100',
]);

        if (!auth()->check()) {
    return redirect()->back()->with('showLogin', true);
}

        // Duplicate Problem Detection
        $keywords = collect(explode(' ', strtolower($request->title . ' ' . $request->description)))
    ->filter(fn($word) => strlen($word) > 3)
    ->unique();

$similarProblems = Feedback::where('status', 'approved')
    ->where('category', $request->category) // 🔥 IMPORTANT FILTER
    ->get()
    ->filter(function ($feedback) use ($keywords) {

        $text = strtolower($feedback->title . ' ' . $feedback->description);

        $matchCount = 0;

        foreach ($keywords as $word) {
            if (str_contains($text, $word)) {
                $matchCount++;
            }
        }

        // require at least 2 matching keywords
        return $matchCount >= 2;
    })
    ->take(3);

        if ($similarProblems->count() > 0 && !$request->has('force_submit')) {
            return back()
                ->withInput()
                ->with('similarProblems', $similarProblems);
        }

        // Resolve final category value
$finalCategory = $request->category === 'Other'
    ? $request->category_other
    : $request->category;
 
// Resolve final current_process value
$finalProcess = $request->current_process === 'Other'
    ? ($request->current_process_other ?? 'Other')
    : $request->current_process;
 
// Filter out empty "Other:" entries from the group array
$affectedGroups = collect($request->affected_group)
    ->filter(fn($g) => $g !== '' && $g !== 'Other: ')
    ->values()
    ->toArray();
 
Feedback::create([
    'user_id'               => $request->has('is_anonymous') ? null : Auth::id(),
    'title'                 => $request->title,
    'description'           => $request->description,
    'impact'                => $request->impact,
    'category'              => $finalCategory,           // ← resolved
    'category_other'        => $request->category_other, // ← stored for admin
    'frequency'             => $request->frequency,
    'current_process'       => $finalProcess,            // ← resolved
    'current_process_other' => $request->current_process_other,
    'affected_users'        => $request->affected_users,
    'affected_group'        => json_encode($affectedGroups), // ← JSON array
    'is_anonymous'          => $request->has('is_anonymous'),
]);

        // SUCCESS MESSAGE FOR TOAST
        return redirect()->route('feedback.submitted')
            ->with('success', 'Problem submitted successfully.');
    }

    public function submitted()
    {
        return view('feedback.submitted');
    }

    public function index(Request $request)
{
    $query = Feedback::where('status', 'approved');

    if ($request->search) {
        $query->where('description', 'like', '%' . $request->search . '%');
    }

    if ($request->category) {
        $query->where('category', $request->category);
    }

    $feedbacks = $query
        ->withCount('votes')
        ->with(['votes' => function ($query) {
            $query->where('user_id', auth()->id());
        }])
        ->latest()
        ->paginate(5);

    // ✅ ADD THIS BACK
    $categories = Feedback::where('status', 'approved')
        ->select('category')
        ->distinct()
        ->pluck('category');

    return view('problems.index', compact('feedbacks', 'categories'));
}

    public function summary()
    {
        $categories = Feedback::where('status', 'approved')
            ->select('category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('problems.summary', compact('categories'));
    }

    public function showCategory($category)
{
    $feedbacks = Feedback::where('category', $category)
        ->where('status', 'approved')
        ->withCount('votes')
        ->latest()
        ->get();
 
    if ($feedbacks->count() < 3) {
        return view('low-data');
    }
 
    // Cache key includes count + latest ID so it auto-invalidates
    // when new feedback is approved in this category.
    $cacheKey = "ideas_{$category}_{$feedbacks->count()}_{$feedbacks->max('id')}";
 
    $suggestedIdeas = Cache::remember($cacheKey, now()->addMinutes(30), function () use (
        $feedbacks, $category
    ) {
        $groups = app(ClusteringService::class)->group($feedbacks);
        $ideas  = [];
 
        foreach ($groups as $groupName => $groupFeedbacks) {
            $reports       = $groupFeedbacks->count();
            $votes         = $groupFeedbacks->sum('votes_count');
            $frequencyScore = $groupFeedbacks->map(fn($f) => match ($f->frequency) {
                'Rarely'    => 1,
                'Sometimes' => 2,
                'Often'     => 3,
                'Everyday'  => 4,
                default     => 1,
            })->avg();
            $impactScore = $groupFeedbacks->map(function ($f) {
    $base = match ($f->affected_users) {
        'Less than 50'  => 1,
        '50-200'        => 2,
        '200-500'       => 3,
        'More than 500' => 4,
        default         => 1,
    };
 
    // Bonus: +0.5 for each extra affected group beyond the first (max +1)
    $groupCount = is_array($f->affected_group) ? count($f->affected_group) : 1;
    $bonus = min(1, ($groupCount - 1) * 0.5);
 
    return $base + $bonus;
})->avg();
 
            // Get the dominant current_process across feedbacks in this group
            $dominantProcess = $groupFeedbacks
                ->pluck('current_process')
                ->filter()
                ->groupBy(fn($p) => $p)
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first();
 
            $severity   = app(SeverityService::class)->compute($reports, $votes, $frequencyScore, $impactScore, $dominantProcess);
            $confidence = app(ConfidenceService::class)->compute($reports, $votes, $frequencyScore, $impactScore);
            $evaluation = $this->evaluateIdea($reports, $votes, $frequencyScore, $impactScore);
            $ideaData   = app(IdeaGeneratorService::class)->generate(
                $groupName, $category, $groupFeedbacks,
                $reports, $votes, $frequencyScore, $impactScore
            );
 
            // Persist evaluation (outside cache closure is fine — idempotent)
            IdeaEvaluation::updateOrCreate(
                ['idea_title' => $ideaData['title'], 'category' => $category],
                $evaluation
            );
 
            $reportScore = min(10, $reports * 2);
            $voteScore   = min(10, $votes * 0.2);
            $ideaScore   = round(
                ($reportScore * 0.3) + ($voteScore * 0.3) +
                ($frequencyScore * 0.2) + ($impactScore * 0.2)
            );
 
            $priority = match(true) {
                $ideaScore >= 7 => 'High',
                $ideaScore >= 4 => 'Medium',
                default         => 'Low',
            };
 
            // Build severity/confidence explanation strings
            $sevReasons  = array_filter([
                $reports >= 5       ? 'frequently reported' : null,
                $votes >= 20        ? 'strong user concern' : null,
                $frequencyScore >= 3 ? 'occurs often' : null,
                $impactScore >= 3   ? 'affects many users' : null,
                ($severity['process_bonus'] ?? 0) > 0 ? 'no adequate existing solution' : null,
            ]);
            $confReasons = array_filter([
                $reports >= 5       ? 'based on multiple reports' : null,
                $votes >= 20        ? 'validated by users' : null,
                $frequencyScore >= 3 ? 'consistent occurrence' : null,
                $impactScore >= 3   ? 'clear impact on users' : null,
            ]);
 
            $ideas[] = [
                'group'                  => $groupName,
                'title'                  => $ideaData['title'],
                'description'            => $ideaData['description'],
                'score'                  => $ideaScore,
                'priority'               => $priority,
                'seriousness'            => $ideaScore >= 7 ? 'Critical Issue' : ($ideaScore >= 4 ? 'Moderate Issue' : 'Minor Issue'),
                'general_objective'      => $ideaData['general_objective'],
                'specific_objectives'    => $ideaData['specific_objectives'],
                'explanation'            => $ideaData['explanation'],
                'evaluation'             => $evaluation,
                'severity_score'         => $severity['score'],
                'severity_level'         => $severity['level'],
                'severity_explanation'   => count($sevReasons)
                                               ? 'This problem is severe because it is ' . implode(', ', $sevReasons) . '.'
                                               : 'This problem has low reported impact.',
                'confidence_score'       => $confidence['score'],
                'confidence_level'       => $confidence['level'],
                'confidence_explanation' => count($confReasons)
                                               ? 'This recommendation is reliable because it is ' . implode(', ', $confReasons) . '.'
                                               : 'This recommendation has limited supporting data.',
                'impact_simulation'      => $ideaData['impact_simulation'],
                'comparison'             => [
                    'score'       => $ideaScore,
                    'feasibility' => $evaluation['feasibility'],
                    'impact'      => $evaluation['impact'],
                    'complexity'  => $evaluation['complexity'],
                    'innovation'  => $evaluation['innovation'],
                    'severity'    => $severity['level'],
                    'confidence'  => $confidence['level'],
                ],
            ];
        }
 
        usort($ideas, fn($a, $b) => $b['score'] <=> $a['score']);
        return $ideas;
    });
 
    $topIdea    = $suggestedIdeas[0] ?? null;
    $otherIdeas = array_slice($suggestedIdeas, 1);
 
    $topReview = $topIdea
        ? AdviserReview::where('idea_title', $topIdea['title'])
                       ->where('category', $category)
                       ->latest()->first()
        : null;
 
    return view('problems.category', compact(
        'feedbacks', 'category', 'topIdea', 'otherIdeas', 'topReview'
    ));
}

    public function admin()
    {
        $totalFeedback = Feedback::count();

        $categoryData = Feedback::where('status', 'approved')
            ->select('category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->get();

        $totalCategories = Feedback::select('category')
            ->distinct()
            ->count();

        $topCategory = Feedback::select('category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        $recentFeedback = Feedback::latest()
            ->take(5)
            ->get();

        $pendingFeedback = Feedback::where('status', 'pending')
            ->latest()
            ->get();

        $topProblems = Feedback::withCount('votes')
            ->where('status', 'approved')
            ->orderByDesc('votes_count')
            ->take(5)
            ->get();

        $ideaCandidates = Feedback::where('status', 'approved')
            ->select('category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->having('total', '>=', 3)
            ->get();

        $monthlyReports = Feedback::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $impactLevels = Feedback::select('affected_users')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('affected_users')
            ->get();

        // ── Affected groups — extract from JSON and count ──
        $affectedGroupData = Feedback::where('status', 'approved')
            ->select('affected_group')
            ->get()
            ->flatMap(function ($feedback) {
                // affected_group is cast to array, so this safely extracts all groups
                return $feedback->affected_group ?? [];
            })
            ->countBy()
            ->map(function ($count, $group) {
                return (object)[
                    'affected_group' => $group,
                    'total' => $count
                ];
            })
            ->values();    

        return view('admin.dashboard', compact(
            'totalFeedback',
            'totalCategories',
            'topCategory',
            'recentFeedback',
            'pendingFeedback',
            'categoryData',
            'topProblems',
            'ideaCandidates',
            'monthlyReports',
            'impactLevels',
            'affectedGroupData'
        ));
    }

    public function approve($id)
{
    $feedback = Feedback::findOrFail($id);

    $feedback->status = 'approved';
    $feedback->save();

    if ($feedback->user_id) {
        $user = User::find($feedback->user_id);
        $user->notify(new FeedbackApproved($feedback));
    }

    return back();
}

    public function reject($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update(['status' => 'rejected']);

        return back()->with('success', 'Feedback rejected.');
    }

    public function vote($id)
    {
        if (!auth()->check()) {
    return redirect()->back()->with('showLogin', true);
}
        $feedback = Feedback::findOrFail($id);

        $existingVote = FeedbackVote::where('feedback_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingVote) {
            $existingVote->delete();
            return back()->with('success', 'Vote removed.');
        } else {
            FeedbackVote::create([
                'feedback_id' => $id,
                'user_id' => Auth::id()
            ]);

            return back()->with('success', 'Vote recorded.');
        }
    }

public function similarProblems(Request $request)
{
    // STEP 1: Clean and extract keywords
    $keywords = collect(explode(' ', strtolower($request->title)))
        ->filter(fn($word) => strlen($word) > 3)
        ->unique();

    // OPTIONAL: remove common useless words
    $stopWords = ['the','and','for','with','this','that','from','have','has'];
    $keywords = $keywords->reject(fn($word) => in_array($word, $stopWords));

    // STEP 2: Get candidate problems (filtered by category if available)
    $feedbacks = Feedback::where('status', 'approved')
        ->when($request->category, function ($query) use ($request) {
            $query->where('category', $request->category);
        })
        ->withCount('votes')
        ->latest()
        ->get();

    // STEP 3: Score similarity
    $similar = $feedbacks->map(function ($feedback) use ($keywords) {

        $text = strtolower($feedback->title . ' ' . $feedback->description);

        $matchCount = 0;

        foreach ($keywords as $word) {
            if (str_contains($text, $word)) {
                $matchCount++;
            }
        }

        // attach score
        $feedback->match_score = $matchCount;

        return $feedback;
    })
    ->filter(fn($f) => $f->match_score >= 2) // 🔥 require at least 2 matches
    ->sortByDesc('match_score') // 🔥 best matches first
    ->take(5)
    ->values();

    return response()->json($similar);
}

public function storeReview(Request $request)
{
    AdviserReview::create([
        'idea_title' => $request->idea_title,
        'category' => $request->category,
        'comment' => $request->comment,
        'recommendation' => $request->recommendation,
        'user_id' => auth()->id()
    ]);

    // GET SYSTEM EVALUATION
    $evaluation = IdeaEvaluation::where('idea_title', $request->idea_title)
        ->where('category', $request->category)
        ->first();

    if ($evaluation) {

        // SAVE ADVISER SCORES
        $evaluation->adviser_feasibility = $request->feasibility;
        $evaluation->adviser_impact = $request->impact;
        $evaluation->adviser_complexity = $request->complexity;
        $evaluation->adviser_innovation = $request->innovation;

        // COMPUTE ADVISER SCORE
        $adviserScore =
            ($request->impact * 0.35) +
            ($request->feasibility * 0.25) +
            ($request->complexity * 0.20) +
            ($request->innovation * 0.20);

        // FINAL SCORE (SYSTEM + ADVISER)
        $finalScore =
            ($evaluation->overall_score * 0.7) +
            ($adviserScore * 0.3);

        $evaluation->final_score = round($finalScore, 2);

        $evaluation->save();
    }

    return back()->with('success', 'Review submitted with evaluation.');
}

private function evaluateIdea($reports, $votes, $frequencyScore, $impactScore)
{
    // Convert to 1–5 scale

    $impact = min(5, round($impactScore + ($votes / 20)));

    $feasibility = 5 - min(4, round($frequencyScore)); 
    // frequent problems = harder to solve

    $complexity = min(5, round($reports / 2));

    $innovation = 3;
    if ($reports < 3) $innovation = 5;
    elseif ($reports < 5) $innovation = 4;

    // FINAL SCORE
    $overall =
        ($impact * 0.35) +
        ($feasibility * 0.25) +
        ($complexity * 0.20) +
        ($innovation * 0.20);

    // RECOMMENDATION
    if ($overall >= 4.0) {
        $recommendation = 'Highly Recommended';
    } elseif ($overall >= 3.0) {
        $recommendation = 'Recommended';
    } else {
        $recommendation = 'Needs Improvement';
    }

    return [
        'feasibility' => $feasibility,
        'impact' => $impact,
        'complexity' => $complexity,
        'innovation' => $innovation,
        'overall_score' => round($overall, 2),
        'recommendation' => $recommendation
    ];
}



}