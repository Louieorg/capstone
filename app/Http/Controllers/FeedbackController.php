<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackCommentRequest;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\AdviserReview;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\IdeaEvaluation;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\IdeaGenerated;
use App\Services\ClusteringService;
use App\Services\ConfidenceService;
use App\Services\IdeaGeneratorService;
use App\Services\SeverityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    private const MINIMUM_VOTES_FOR_IDEA_GENERATION = 10;

    private const MINIMUM_REPORTS_FOR_IDEA_GENERATION = 3;

    public function create(): View
    {
        return view('submit');
    }

    public function home(): View
    {
        $viewData = $this->buildHomeViewData();

        return view('home', $viewData);
    }

    public function landing(): View
    {
        $viewData = $this->buildHomeViewData();

        return view('welcome', $viewData);
    }

    private function buildHomeViewData(): array
    {
        $thresholds = $this->ideaGenerationThresholds();
        $cacheKey = 'home-page-data:'.(Auth::id() ?? 'guest').":votes-{$thresholds['votes']}:reports-{$thresholds['reports']}";

        return Cache::remember($cacheKey, now()->addSeconds(30), fn (): array => $this->buildHomeViewDataUncached());
    }

    private function buildHomeViewDataUncached(): array
    {
        $approvedFeedbacks = $this->approvedFeedbackQuery()
            ->latest()
            ->get();

        $contexts = $this->buildFeedbackContexts($approvedFeedbacks);
        $feed = $this->decorateFeedbackCollection($approvedFeedbacks->take(12)->values(), $contexts);
        $trending = $this->decorateFeedbackCollection(
            $approvedFeedbacks
                ->sortByDesc(fn (Feedback $feedback): float => $this->trendingScore($feedback, $contexts))
                ->take(5)
                ->values(),
            $contexts
        );

        $totalProblems = $approvedFeedbacks->count();
        $ideaCandidates = $this->ideaCandidateCategories()->count();
        $totalCategories = $approvedFeedbacks->pluck('category')->unique()->count();
        $categories = $approvedFeedbacks
            ->groupBy('category')
            ->map(fn (Collection $group): array => [
                'name' => (string) $group->first()->category,
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values();
        $generatedIdeas = IdeaEvaluation::query()
            ->latest()
            ->take(4)
            ->get();

        return compact(
            'feed',
            'trending',
            'totalProblems',
            'ideaCandidates',
            'totalCategories',
            'categories',
            'generatedIdeas'
        );
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {

        if (! auth()->check()) {
            return redirect()->back()->with('showLogin', true);
        }

        $keywords = collect(explode(' ', strtolower($request->title.' '.$request->description)))
            ->filter(fn (string $word): bool => strlen($word) > 3)
            ->unique();

        $similarProblems = Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->where('category', $request->category)
            ->get()
            ->filter(function (Feedback $feedback) use ($keywords): bool {
                $text = strtolower($feedback->title.' '.$feedback->description);
                $matchCount = 0;

                foreach ($keywords as $word) {
                    if (str_contains($text, $word)) {
                        $matchCount++;
                    }
                }

                return $matchCount >= 2;
            })
            ->take(3);

        if ($similarProblems->count() > 0 && ! $request->has('force_submit')) {
            return back()
                ->withInput()
                ->with('similarProblems', $similarProblems);
        }

        $finalCategory = $request->category === 'Other'
            ? $request->category_other
            : $request->category;
        $finalDepartment = $request->department === 'Other'
            ? $request->department_other
            : $request->department;
        $finalProcess = $request->current_process === 'Other'
            ? ($request->current_process_other ?? 'Other')
            : $request->current_process;
        $affectedGroups = collect($request->affected_group)
            ->filter(fn (string $group): bool => $group !== '' && $group !== 'Other: ')
            ->values()
            ->toArray();

        $isFlagged = $this->isDuplicateSubmission($request, $finalCategory)
            || $this->isRapidSubmission($request);

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $extension = strtolower($attachment->getClientOriginalExtension());
            $safeName = Str::slug(pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'attachment';
            $filename = $safeName.'-'.Str::uuid().'.'.$extension;

            $attachmentPath = Storage::disk('public')->putFileAs('attachments', $attachment, $filename);
            $attachmentType = in_array($extension, ['jpg', 'jpeg', 'png'], true) ? 'image' : 'pdf';
        }

        $created = Feedback::create([
            'user_id' => $request->has('is_anonymous') ? null : Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'impact' => $request->impact,
            'category' => $finalCategory,
            'category_other' => $request->category_other,
            'department' => filled($finalDepartment) ? $finalDepartment : null,
            'frequency' => $request->frequency,
            'current_process' => $finalProcess,
            'current_process_other' => $request->current_process_other,
            'affected_users' => $request->affected_users,
            'affected_group' => $affectedGroups,
            'is_anonymous' => $request->has('is_anonymous'),
            'is_flagged' => $isFlagged,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        // Persist supporting evidence files after feedback is created.
        if ($request->hasFile('evidence')) {
            $captions = $request->input('evidence_captions', []);

            foreach ($request->file('evidence') as $index => $file) {
                if (! $file->isValid()) {
                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());
                $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'evidence';
                $filename = $safeName.'-'.Str::uuid().'.'.$extension;

                $path = Storage::disk('public')->putFileAs('evidence', $file, $filename);

                \App\Models\FeedbackEvidence::query()->create([
                    'feedback_id' => $created->id,
                    'user_id' => $request->has('is_anonymous') ? null : Auth::id(),
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? 'image' : 'pdf',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'caption' => $captions[$index] ?? null,
                ]);
            }
        }

        return redirect()->route('feedback.submitted')
            ->with(
                $isFlagged ? 'warning' : 'success',
                $isFlagged
                    ? 'Problem submitted for admin review. It will be excluded from idea generation until approved as valid.'
                    : 'Problem submitted successfully.'
            );
    }

    public function submitted(): View
    {
        return view('feedback.submitted');
    }

    public function index(Request $request): View
    {
        $query = $this->approvedFeedbackQuery();

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('impact', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $sort = (string) $request->get('sort', 'trending');
        $perPage = 9;

        if (in_array($sort, ['newest', 'supported'], true)) {
            $contextFeedbacks = (clone $query)
                ->withoutEagerLoads()
                ->get();
            $contexts = $this->buildFeedbackContexts($contextFeedbacks);
            $paginatedQuery = clone $query;

            if ($sort === 'supported') {
                $paginatedQuery
                    ->orderByDesc('votes_count')
                    ->orderBy('id');
            } else {
                $paginatedQuery
                    ->orderByDesc('created_at')
                    ->orderBy('id');
            }

            $paginated = $paginatedQuery->paginate($perPage)->withQueryString();
            $paginated->setCollection(
                $this->decorateFeedbackCollection($paginated->getCollection(), $contexts)
            );
        } else {
            $feedbacks = $query->get();
            $contexts = $this->buildFeedbackContexts($feedbacks);
            $decorated = $this->decorateFeedbackCollection($feedbacks, $contexts);

            $sorted = (match ($sort) {
                'severity' => $decorated->sortByDesc('severity_score'),
                default => $decorated->sortByDesc(fn (Feedback $feedback): float => $this->trendingScore($feedback, $contexts)),
            })->values();

            $page = max(1, (int) $request->integer('page', 1));
            $paginated = new LengthAwarePaginator(
                $sorted->forPage($page, $perPage),
                $sorted->count(),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        }

        $categories = Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $recentIdeas = IdeaEvaluation::query()->latest()->take(4)->get();

        return view('problems.index', [
            'feedbacks' => $paginated,
            'categories' => $categories,
            'recentIdeas' => $recentIdeas,
            'activeSort' => $sort,
        ]);
    }

    public function priorityIndex(Request $request): View
    {
        $query = $this->approvedFeedbackQuery()
            ->where('is_priority', true)
            ->with('takenBy');

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('impact', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $problems = $query->latest()->paginate(12)->withQueryString();
        $categories = Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->where('is_priority', true)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('priority.index', compact('problems', 'categories'));
    }

    public function show(Feedback $feedback): View
    {
        abort_unless($feedback->status === 'approved' && ! $feedback->is_flagged, 404);

        $categoryFeedbacks = $this->approvedFeedbackQuery()
            ->where('category', $feedback->category)
            ->get();
        $contexts = $this->buildFeedbackContexts($categoryFeedbacks);
        $feedback = $this->decorateFeedbackCollection($categoryFeedbacks, $contexts)->firstWhere('id', $feedback->id);
        $clusterFeedbacks = $categoryFeedbacks
            ->filter(fn (Feedback $item): bool => in_array($item->id, $feedback->cluster_feedback_ids ?? [], true))
            ->values();
        $relatedProblems = $this->buildRelatedProblems($feedback, $clusterFeedbacks);
        $evidenceFiles = \App\Models\FeedbackEvidence::query()->where('feedback_id', $feedback->id)->latest()->get();

        $analysis = [
            'category' => $feedback->category,
            'severity_level' => $feedback->severity_level,
            'severity_score' => $feedback->severity_score,
            'confidence_level' => $feedback->confidence_level,
            'confidence_score' => $feedback->confidence_score,
            'affected_groups' => $feedback->affected_groups,
            'recurring_reports' => $feedback->recurring_report_count,
            'support_count' => $feedback->cluster_support_count,
            'why_it_matters' => $feedback->why_it_matters,
            'timeline' => $feedback->timeline,
            'evidence_files' => $evidenceFiles->count(),
        ];
        $comments = FeedbackComment::query()
            ->with('user')
            ->where('feedback_id', $feedback->id)
            ->latest()
            ->get();

        return view('feedback.show', compact('feedback', 'relatedProblems', 'analysis', 'comments', 'evidenceFiles'));
    }

    public function storeComment(StoreFeedbackCommentRequest $request, Feedback $feedback): RedirectResponse
    {
        abort_unless($feedback->status === 'approved' && ! $feedback->is_flagged, 404);

        FeedbackComment::query()->create([
            'feedback_id' => $feedback->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
        ]);

        return redirect()
            ->route('feedback.show', $feedback)
            ->with('success', 'Supporting experience added.');
    }

    public function summary(): View
    {
        $categories = Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->select('category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('problems.summary', compact('categories'));
    }

    public function showCategory(string $category): View
    {
        $thresholds = $this->ideaGenerationThresholds();
        $feedbacks = Feedback::query()
            ->where('category', $category)
            ->where('status', 'approved')
            ->notFlagged()
            ->with('user')
            ->withCount(['votes', 'comments', 'evidence'])
            ->latest()
            ->get()
            ->filter(fn (Feedback $feedback): bool => $feedback->votes_count >= $thresholds['votes'])
            ->values();

        if ($feedbacks->count() < $thresholds['reports']) {
            return view('low-data', [
                'minimumReports' => $thresholds['reports'],
                'minimumVotes' => $thresholds['votes'],
            ]);
        }

        $voteSignature = md5($feedbacks
            ->map(fn (Feedback $feedback): string => "{$feedback->id}:{$feedback->votes_count}")
            ->implode('|'));
        $cacheKey = "ideas_{$category}_{$feedbacks->count()}_{$feedbacks->max('id')}_{$voteSignature}_votes-{$thresholds['votes']}_reports-{$thresholds['reports']}";

        $suggestedIdeas = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($feedbacks, $category): array {
            $groups = app(ClusteringService::class)->group($feedbacks);
            $ideas = [];

            foreach ($groups as $groupName => $groupFeedbacks) {
                $reports = $groupFeedbacks->count();
                $votes = $groupFeedbacks->sum('votes_count');
                $frequencyScore = $this->averageFrequencyScore($groupFeedbacks);
                $impactScore = $this->averageImpactScore($groupFeedbacks);
                $dominantProcess = $groupFeedbacks
                    ->pluck('current_process')
                    ->filter()
                    ->groupBy(fn (string $process): string => $process)
                    ->map->count()
                    ->sortDesc()
                    ->keys()
                    ->first();

                $severity = app(SeverityService::class)->compute($reports, $votes, $frequencyScore, $impactScore, $dominantProcess);
                $confidence = app(ConfidenceService::class)->compute($reports, $votes, $frequencyScore, $impactScore, $groupFeedbacks);
                $evaluation = $this->evaluateIdea($reports, $votes, $frequencyScore, $impactScore, $dominantProcess);
                $ideaData = app(IdeaGeneratorService::class)->generate(
                    $groupName,
                    $category,
                    $groupFeedbacks,
                    $reports,
                    $votes,
                    $frequencyScore,
                    $impactScore
                );

                $isNew = ! IdeaEvaluation::query()
                    ->where('idea_title', $ideaData['title'])
                    ->where('category', $category)
                    ->exists();

                IdeaEvaluation::query()->updateOrCreate(
                    ['idea_title' => $ideaData['title'], 'category' => $category],
                    $evaluation
                );

                if ($isNew) {
                    $contributingUserIds = $groupFeedbacks->pluck('user_id')->filter()->unique();
                    $contributingUsers = User::query()
                        ->whereIn('id', $contributingUserIds)
                        ->get();

                    foreach ($contributingUsers as $user) {
                        $user->notify(new IdeaGenerated($ideaData['title'], $category));
                    }
                }

                $processGap = match (true) {
                    str_contains(strtolower($dominantProcess ?? ''), 'no solution') => 5.0,
                    str_contains(strtolower($dominantProcess ?? ''), 'manual') => 3.5,
                    str_contains(strtolower($dominantProcess ?? ''), 'wait') => 3.0,
                    str_contains(strtolower($dominantProcess ?? ''), 'verbally') => 2.5,
                    str_contains(strtolower($dominantProcess ?? ''), 'broken') => 3.0,
                    str_contains(strtolower($dominantProcess ?? ''), 'email') => 2.0,
                    default => 1.5,
                };

                $ideaScore = round(
                    ($severity['score'] * 0.40) +
                    ($confidence['score'] * 0.30) +
                    ($evaluation['impact'] * 0.20) +
                    ($processGap * 0.10),
                    2
                );

                $priority = match (true) {
                    $ideaScore >= 3.5 => 'High',
                    $ideaScore >= 2.5 => 'Medium',
                    default => 'Low',
                };

                $sevReasons = array_filter([
                    $reports >= 5 ? 'frequently reported' : null,
                    $votes >= 20 ? 'strong user concern' : null,
                    $frequencyScore >= 3 ? 'occurs often' : null,
                    $impactScore >= 3 ? 'affects many users' : null,
                    ($severity['process_bonus'] ?? 0) > 0 ? 'no adequate existing solution' : null,
                ]);

                $ideas[] = [
                    'group' => $groupName,
                    'title' => $ideaData['title'],
                    'description' => $ideaData['description'],
                    'score' => $ideaScore,
                    'priority' => $priority,
                    'seriousness' => match ($priority) {
                        'High' => 'Critical Issue',
                        'Medium' => 'Moderate Issue',
                        default => 'Minor Issue',
                    },
                    'general_objective' => $ideaData['general_objective'],
                    'specific_objectives' => $ideaData['specific_objectives'],
                    'explanation' => $ideaData['explanation'],
                    'evaluation' => $evaluation,
                    'severity_score' => $severity['score'],
                    'severity_level' => $severity['level'],
                    'severity_explanation' => count($sevReasons)
                        ? 'This problem is severe because it is '.implode(', ', $sevReasons).'.'
                        : 'This problem has low reported impact.',
                    'confidence_score' => $confidence['score'],
                    'confidence_level' => $confidence['level'],
                    'confidence_explanation' => $confidence['explanation'],
                    'confidence_breakdown' => [
                        'source_diversity' => $confidence['diversity_score'] ?? 0,
                        'frequency_consistency' => $confidence['consistency_score'] ?? 0,
                        'sample_size' => $confidence['sample_score'] ?? 0,
                        'community_validation' => $confidence['validation_score'] ?? 0,
                    ],
                    'impact_simulation' => $ideaData['impact_simulation'],
                    'comparison' => [
                        'score' => $ideaScore,
                        'feasibility' => $evaluation['feasibility'],
                        'impact' => $evaluation['impact'],
                        'complexity' => $evaluation['complexity'],
                        'innovation' => $evaluation['innovation'],
                        'severity' => $severity['level'],
                        'confidence' => $confidence['level'],
                    ],
                    'reports_count' => $reports,
                    'support_count' => $votes,
                    'affected_groups' => $groupFeedbacks->pluck('affected_group')->flatten()->filter()->unique()->values()->all(),
                ];
            }

            usort($ideas, fn (array $left, array $right): int => $right['score'] <=> $left['score']);

            return $ideas;
        });

        $topIdea = $suggestedIdeas[0] ?? null;
        $otherIdeas = array_slice($suggestedIdeas, 1);
        $topReview = $topIdea
            ? AdviserReview::query()
                ->where('idea_title', $topIdea['title'])
                ->where('category', $category)
                ->latest()
                ->first()
            : null;

        return view('problems.category', compact('feedbacks', 'category', 'topIdea', 'otherIdeas', 'topReview'));
    }

    public function admin(): View
    {
        $totalFeedback = Feedback::count();
        $categoryData = Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->select('category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->get();
        $totalCategories = Feedback::query()
            ->select('category')
            ->notFlagged()
            ->distinct()
            ->count();
        $topCategory = Feedback::query()
            ->select('category')
            ->notFlagged()
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();
        $recentFeedback = Feedback::query()->latest()->take(5)->get();
        $pendingFeedback = Feedback::query()->where('status', 'pending')->latest()->get();
        $topProblems = Feedback::query()
            ->withCount('votes')
            ->where('status', 'approved')
            ->notFlagged()
            ->orderByDesc('votes_count')
            ->take(5)
            ->get();
        $ideaCandidates = $this->ideaCandidateCategories();
        $monthlyReports = Feedback::query()
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->notFlagged()
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        $impactLevels = Feedback::query()
            ->select('affected_users')
            ->notFlagged()
            ->selectRaw('COUNT(*) as total')
            ->groupBy('affected_users')
            ->get();
        $affectedGroupData = Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->select('affected_group')
            ->get()
            ->flatMap(fn (Feedback $feedback): array => is_array($feedback->affected_group) ? $feedback->affected_group : [])
            ->filter(fn ($group): bool => is_string($group) && ! empty(trim($group)))
            ->countBy()
            ->map(fn (int $count, string $group): object => (object) [
                'affected_group' => $group,
                'total' => $count,
            ])
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

    public function manageFeedback(Request $request): View
    {
        $query = Feedback::query()
            ->with('user')
            ->withCount(['votes', 'comments']);

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $status = (string) $request->get('status', 'all');

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($request->boolean('flagged')) {
            $query->where('is_flagged', true);
        }

        $sort = (string) $request->get('sort', 'newest');

        match ($sort) {
            'oldest' => $query->oldest(),
            'most_supported' => $query->orderByDesc('votes_count'),
            default => $query->latest(),
        };

        $feedbacks = $query->paginate(15)->withQueryString();

        $categories = Feedback::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $counts = [
            'all' => Feedback::count(),
            'pending' => Feedback::where('status', 'pending')->count(),
            'approved' => Feedback::where('status', 'approved')->count(),
            'rejected' => Feedback::where('status', 'rejected')->count(),
            'flagged' => Feedback::where('is_flagged', true)->count(),
        ];

        return view('admin.feedback.index', compact('feedbacks', 'categories', 'counts', 'status', 'sort'));
    }

    public function approve(int $id): RedirectResponse
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->status = 'approved';
        $feedback->is_flagged = false;
        $feedback->save();

        return back()->with('success', 'Feedback approved.');
    }

    public function reject(int $id): RedirectResponse
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update(['status' => 'rejected']);

        return back()->with('success', 'Feedback rejected.');
    }

    public function vote(int $id): RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->back()->with('showLogin', true);
        }

        $feedback = Feedback::findOrFail($id);
        $existingVote = FeedbackVote::query()
            ->where('feedback_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingVote) {
            $existingVote->delete();

            return back()->with('success', 'Support removed.');
        }

        FeedbackVote::query()->create([
            'feedback_id' => $feedback->id,
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Problem supported.');
    }

    public function similarProblems(Request $request): JsonResponse
    {
        $keywords = collect(explode(' ', strtolower((string) $request->title)))
            ->filter(fn (string $word): bool => strlen($word) > 3)
            ->unique();
        $stopWords = ['the', 'and', 'for', 'with', 'this', 'that', 'from', 'have', 'has'];
        $keywords = $keywords->reject(fn (string $word): bool => in_array($word, $stopWords, true));

        $feedbacks = Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->when($request->category, function (Builder $query) use ($request): void {
                $query->where('category', $request->category);
            })
            ->withCount('votes')
            ->latest()
            ->get();

        $similar = $feedbacks
            ->map(function (Feedback $feedback) use ($keywords): Feedback {
                $text = strtolower($feedback->title.' '.$feedback->description);
                $matchCount = 0;

                foreach ($keywords as $word) {
                    if (str_contains($text, $word)) {
                        $matchCount++;
                    }
                }

                $feedback->match_score = $matchCount;

                return $feedback;
            })
            ->filter(fn (Feedback $feedback): bool => $feedback->match_score >= 2)
            ->sortByDesc('match_score')
            ->take(5)
            ->values();

        return response()->json($similar);
    }

    public function storeReview(Request $request): RedirectResponse
    {
        AdviserReview::query()->create([
            'idea_title' => $request->idea_title,
            'category' => $request->category,
            'comment' => $request->comment,
            'recommendation' => $request->recommendation,
            'user_id' => auth()->id(),
        ]);

        $evaluation = IdeaEvaluation::query()
            ->where('idea_title', $request->idea_title)
            ->where('category', $request->category)
            ->first();

        if ($evaluation) {
            $evaluation->adviser_feasibility = $request->feasibility;
            $evaluation->adviser_impact = $request->impact;
            $evaluation->adviser_complexity = $request->complexity;
            $evaluation->adviser_innovation = $request->innovation;

            $adviserScore = ($request->impact * 0.35)
                + ($request->feasibility * 0.25)
                + ($request->complexity * 0.20)
                + ($request->innovation * 0.20);
            $finalScore = ($evaluation->overall_score * 0.7) + ($adviserScore * 0.3);

            $evaluation->final_score = round($finalScore, 2);
            $evaluation->save();
        }

        return back()->with('success', 'Review submitted with evaluation.');
    }

    private function approvedFeedbackQuery(): Builder
    {
        return Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->with('user')
            ->withCount(['votes', 'comments'])
            ->when(Auth::check(), function (Builder $query): void {
                $query->with(['votes' => function (HasMany $voteQuery): void {
                    $voteQuery->where('user_id', Auth::id());
                }]);
            });
    }

    private function decorateFeedbackCollection(Collection $feedbacks, array $contexts): Collection
    {
        return $feedbacks->map(function (Feedback $feedback) use ($contexts): Feedback {
            $context = $contexts[$feedback->id] ?? [];

            foreach ($context as $key => $value) {
                $feedback->setAttribute($key, $value);
            }

            $feedback->setAttribute('has_supported', $feedback->relationLoaded('votes') && $feedback->votes->isNotEmpty());

            return $feedback;
        });
    }

    private function buildFeedbackContexts(Collection $feedbacks): array
    {
        $contexts = [];

        foreach ($feedbacks->groupBy('category') as $categoryFeedbacks) {
            $clusters = app(ClusteringService::class)->group($categoryFeedbacks);

            foreach ($clusters as $clusterName => $clusterFeedbacks) {
                $reports = $clusterFeedbacks->count();
                $supportCount = $clusterFeedbacks->sum('votes_count');
                $commentCount = $clusterFeedbacks->sum('comments_count');
                $frequencyScore = $this->averageFrequencyScore($clusterFeedbacks);
                $impactScore = $this->averageImpactScore($clusterFeedbacks);
                $dominantProcess = $clusterFeedbacks
                    ->pluck('current_process')
                    ->filter()
                    ->groupBy(fn (string $process): string => $process)
                    ->map->count()
                    ->sortDesc()
                    ->keys()
                    ->first();
                $severity = app(SeverityService::class)->compute($reports, $supportCount, $frequencyScore, $impactScore, $dominantProcess);
                $confidence = app(ConfidenceService::class)->compute($reports, $supportCount, $frequencyScore, $impactScore, $clusterFeedbacks);
                $affectedGroups = $clusterFeedbacks
                    ->pluck('affected_group')
                    ->flatten()
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                $timeline = $this->buildTimeline($clusterFeedbacks);
                $whyItMatters = Str::of($severity['level'].' severity and '.$confidence['level'].' confidence')
                    ->append(' because ')
                    ->append($reports > 1 ? "{$reports} related reports" : 'this report')
                    ->append($supportCount > 0 ? ", {$supportCount} support signals" : '')
                    ->append(count($affectedGroups) > 0 ? ', and '.count($affectedGroups).' affected groups' : '')
                    ->append(' are pointing to the same institutional friction.')
                    ->value();

                foreach ($clusterFeedbacks as $feedback) {
                    $contexts[$feedback->id] = [
                        'cluster_name' => Str::headline((string) $clusterName),
                        'recurring_report_count' => $reports,
                        'cluster_support_count' => $supportCount,
                        'cluster_comment_count' => $commentCount,
                        'severity_score' => $severity['score'],
                        'severity_level' => $severity['level'],
                        'confidence_score' => $confidence['score'],
                        'confidence_level' => $confidence['level'],
                        'affected_groups' => $affectedGroups,
                        'why_it_matters' => $whyItMatters,
                        'timeline' => $timeline,
                        'cluster_feedback_ids' => $clusterFeedbacks->pluck('id')->all(),
                    ];
                }
            }
        }

        return $contexts;
    }

    private function buildTimeline(Collection $feedbacks): array
    {
        $thresholds = $this->ideaGenerationThresholds();
        $runningReports = 0;
        $runningSupport = 0;
        $thresholdMarked = false;
        $timeline = [];

        foreach ($feedbacks->sortBy('created_at')->groupBy(fn (Feedback $feedback): string => $feedback->created_at->format('F Y')) as $month => $items) {
            $runningReports += $items->count();
            $runningSupport += $items->sum('votes_count');

            $timeline[] = [
                'label' => $month,
                'reports' => $items->count(),
                'supports' => $items->sum('votes_count'),
                'threshold_reached' => ! $thresholdMarked
                    && $runningReports >= $thresholds['reports']
                    && $runningSupport >= $thresholds['votes'],
                'idea_generated' => $runningReports >= $thresholds['reports']
                    && $runningSupport >= $thresholds['votes'],
            ];

            if (($timeline[array_key_last($timeline)]['threshold_reached'] ?? false) === true) {
                $thresholdMarked = true;
            }
        }

        return $timeline;
    }

    private function buildRelatedProblems(Feedback $feedback, Collection $clusterFeedbacks): Collection
    {
        return $clusterFeedbacks
            ->where('id', '!=', $feedback->id)
            ->map(function (Feedback $item) use ($feedback): Feedback {
                $item->setAttribute('similarity', round($this->similarityScore(
                    $this->normalizedText($feedback->title.' '.$feedback->description),
                    $this->normalizedText($item->title.' '.$item->description)
                )));

                return $item;
            })
            ->sortByDesc('similarity')
            ->take(3)
            ->values();
    }

    private function trendingScore(Feedback $feedback, array $contexts): float
    {
        $context = $contexts[$feedback->id] ?? [];
        $recencyScore = max(0, 30 - $feedback->created_at->diffInDays(now()));

        return ($feedback->votes_count * 3)
            + (($context['recurring_report_count'] ?? 1) * 2)
            + (($context['severity_score'] ?? 0) * 2)
            + (($context['confidence_score'] ?? 0) * 2)
            + $recencyScore;
    }

    private function averageFrequencyScore(Collection $feedbacks): float
    {
        return round((float) $feedbacks->map(fn (Feedback $feedback): int => match ($feedback->frequency) {
            'Rarely' => 1,
            'Sometimes' => 2,
            'Often' => 3,
            'Everyday' => 4,
            default => 1,
        })->avg(), 2);
    }

    private function averageImpactScore(Collection $feedbacks): float
    {
        return round((float) $feedbacks->map(function (Feedback $feedback): float {
            $base = match ($feedback->affected_users) {
                'Less than 50' => 1,
                '50-200' => 2,
                '200-500' => 3,
                'More than 500' => 4,
                default => 1,
            };
            $groupCount = is_array($feedback->affected_group) ? count($feedback->affected_group) : 1;
            $bonus = min(1, ($groupCount - 1) * 0.5);

            return $base + $bonus;
        })->avg(), 2);
    }

    private function evaluateIdea(int $reports, int $votes, float $frequencyScore, float $impactScore, ?string $currentProcess = null): array
    {
        $impact = min(5, round($impactScore + ($votes / 20)));
        $feasibility = match (true) {
            $frequencyScore >= 3.5 => 4,
            $frequencyScore >= 2.5 => 3,
            $frequencyScore >= 1.5 => 3,
            default => 2,
        };

        if (str_contains(strtolower($currentProcess ?? ''), 'no solution')) {
            $feasibility = min(5, $feasibility + 1);
        }

        $complexity = match (true) {
            $reports >= 8 => 4,
            $reports >= 5 => 3,
            $reports >= 3 => 2,
            default => 2,
        };

        $noSolution = str_contains(strtolower($currentProcess ?? ''), 'no solution')
            || str_contains(strtolower($currentProcess ?? ''), 'manual');
        $innovation = match (true) {
            $noSolution && $impactScore >= 3 => 5,
            $noSolution => 4,
            $impactScore >= 3 => 3,
            default => 2,
        };

        $overall = round(
            ($impact * 0.35) +
            ($feasibility * 0.25) +
            ($complexity * 0.20) +
            ($innovation * 0.20),
            2
        );

        return [
            'feasibility' => $feasibility,
            'impact' => $impact,
            'complexity' => $complexity,
            'innovation' => $innovation,
            'overall_score' => $overall,
            'recommendation' => match (true) {
                $overall >= 4.0 => 'Highly Recommended',
                $overall >= 3.0 => 'Recommended',
                default => 'Needs Improvement',
            },
        ];
    }

    private function ideaCandidateCategories(): EloquentCollection
    {
        $thresholds = $this->ideaGenerationThresholds();

        return Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->has('votes', '>=', $thresholds['votes'])
            ->select('category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->having('total', '>=', $thresholds['reports'])
            ->orderByDesc('total')
            ->get();
    }

    /**
     * @return array{votes: int, reports: int}
     */
    private function ideaGenerationThresholds(): array
    {
        return [
            'votes' => (int) Setting::get('minimum_votes_for_idea_generation', self::MINIMUM_VOTES_FOR_IDEA_GENERATION),
            'reports' => (int) Setting::get('minimum_reports_for_idea_generation', self::MINIMUM_REPORTS_FOR_IDEA_GENERATION),
        ];
    }

    private function isDuplicateSubmission(StoreFeedbackRequest $request, string $category): bool
    {
        $title = $this->normalizedText((string) $request->title);
        $description = $this->normalizedText((string) $request->description);

        return Feedback::query()
            ->notFlagged()
            ->where('category', $category)
            ->get(['title', 'description'])
            ->contains(function (Feedback $feedback) use ($title, $description): bool {
                return $this->similarityScore($title, $this->normalizedText($feedback->title)) >= 80.0
                    || $this->similarityScore($description, $this->normalizedText($feedback->description)) >= 80.0;
            });
    }

    private function isRapidSubmission(StoreFeedbackRequest $request): bool
    {
        if (! $request->user()) {
            return false;
        }

        return Feedback::query()
            ->where('user_id', $request->user()->id)
            ->where('created_at', '>=', now()->subMinute())
            ->count() >= 3;
    }

    private function similarityScore(string $first, string $second): float
    {
        if ($first === '' || $second === '') {
            return 0.0;
        }

        similar_text($first, $second, $percentage);

        return $percentage;
    }

    private function normalizedText(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower($value ?? '')) ?? '');
    }
}
