<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackCommentRequest;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\AdviserReview;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\IdeaEvaluation;
use App\Services\CategoryIdeaGenerationService;
use App\Services\ClusteringService;
use App\Services\ConfidenceService;
use App\Services\OllamaService;
use App\Services\SeverityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FeedbackController extends Controller
{
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

    public function markCapstoneWorthy(int $id): RedirectResponse
    {
        $feedback = Feedback::findOrFail($id);

        abort_unless($feedback->status === 'approved', 422, 'Only approved reports can be marked as capstone ideas.');

        $feedback->update([
            'is_capstone_worthy' => true,
            'capstone_marked_by' => auth()->id(),
            'capstone_marked_at' => now(),
        ]);

        app(CategoryIdeaGenerationService::class)->generateForInstitutionalValidation($feedback);

        return back()->with('success', 'Marked as a capstone idea.');
    }

    private function buildHomeViewData(): array
    {
        $thresholds = $this->ideaGenerationThresholds();
        $cacheKey = $this->homeCacheKey(Auth::id(), $thresholds);

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

    /**
     * @param  array{votes: int, reports: int}  $thresholds
     */
    private function homeCacheKey(?int $userId, array $thresholds): string
    {
        return 'home-page-data:'.($userId ?? 'guest').":votes-{$thresholds['votes']}:reports-{$thresholds['reports']}";
    }

    private function forgetHomeCacheForCurrentUser(): void
    {
        Cache::forget($this->homeCacheKey(Auth::id(), $this->ideaGenerationThresholds()));
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
            ? (filled($request->category_other) ? $request->category_other : 'Other')
            : $request->category;
        $finalDepartment = $request->department === 'Other'
            ? $request->department_other
            : $request->department;
        $finalProcess = $request->current_process === 'Other'
            ? ($request->current_process_other ?? 'Other')
            : $request->current_process;
        $affectedGroups = collect($request->affected_group)
            ->filter(fn ($group): bool => is_string($group) && $group !== '' && $group !== 'Other: ')
            ->values()
            ->toArray();

        $isFlagged = $this->isDuplicateSubmission($request, $finalCategory)
            || $this->isRapidSubmission($request);

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $uploadType = $this->detectedUploadType($attachment);

            if ($uploadType !== null) {
                [$extension, $attachmentType] = $uploadType;
                $safeName = Str::slug(pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'attachment';
                $filename = $safeName.'-'.Str::uuid().'.'.$extension;

                $attachmentPath = Storage::disk('public')->putFileAs('attachments', $attachment, $filename);
            }
        }

        $isAuthorizedOfficeSubmission =
            auth()->user()->is_office_head &&
            in_array($finalCategory, auth()->user()->reviewableCategories(), true);

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
            'is_capstone_worthy' => $isAuthorizedOfficeSubmission,
            'capstone_marked_by' => $isAuthorizedOfficeSubmission ? auth()->id() : null,
            'capstone_marked_at' => $isAuthorizedOfficeSubmission ? now() : null,
        ]);

        // Persist supporting evidence files after feedback is created.
        if ($request->hasFile('evidence')) {
            $captions = $request->input('evidence_captions', []);

            foreach ($request->file('evidence') as $index => $file) {
                if (! $file->isValid()) {
                    continue;
                }

                $uploadType = $this->detectedUploadType($file);

                if ($uploadType === null) {
                    continue;
                }

                [$extension, $fileType] = $uploadType;
                $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'evidence';
                $filename = $safeName.'-'.Str::uuid().'.'.$extension;

                $path = Storage::disk('public')->putFileAs('evidence', $file, $filename);

                \App\Models\FeedbackEvidence::query()->create([
                    'feedback_id' => $created->id,
                    'user_id' => $request->has('is_anonymous') ? null : Auth::id(),
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $fileType,
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

    /**
     * @return array{0: string, 1: string}|null
     */
    private function detectedUploadType(UploadedFile $file): ?array
    {
        return match ($file->getMimeType()) {
            'image/jpeg' => ['jpg', 'image'],
            'image/png' => ['png', 'image'],
            'image/webp' => ['webp', 'image'],
            'application/pdf' => ['pdf', 'pdf'],
            default => null,
        };
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

    /**
     * Student-facing index of problems already identified as capstone opportunities.
     *
     * Reads existing DSS output only; it never runs clustering, idea generation,
     * or evaluation persistence.
     */
    public function capstoneOpportunities(): View
    {
        $opportunities = $this->approvedFeedbackQuery()
            ->where('is_capstone_worthy', true)
            ->with('capstoneMarkedBy')
            ->orderByDesc('capstone_marked_at')
            ->orderByDesc('id')
            ->paginate(9);

        $dssIdeasByCategory = IdeaEvaluation::query()
            ->select('category')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('MAX(overall_score) as top_score')
            ->groupBy('category')
            ->orderByDesc('total')
            ->orderBy('category')
            ->get();

        return view('capstone-opportunities.index', compact('opportunities', 'dssIdeasByCategory'));
    }

    public function priorityIndex(Request $request): View
    {
        $query = $this->approvedFeedbackQuery()
            ->where('is_priority', true)
            ->where('is_capstone_worthy', true)
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
            ->where('is_capstone_worthy', true)
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
        $generated = app(CategoryIdeaGenerationService::class)->generate($category);

        if (! $generated['qualifying']) {
            return view('low-data', [
                'minimumReports' => $generated['thresholds']['reports'],
                'minimumVotes' => $generated['thresholds']['votes'],
            ]);
        }

        $feedbacks = $generated['feedbacks'];
        $suggestedIdeas = $generated['ideas'];

        $topIdea = $suggestedIdeas[0] ?? null;
        $otherIdeas = array_slice($suggestedIdeas, 1);
        $topReview = $topIdea
            ? AdviserReview::query()
                ->where('idea_title', $topIdea['title'])
                ->where('category', $category)
                ->latest()
                ->first()
            : null;

        $suggestedIdeas = $this->attachAiEnhancements($suggestedIdeas, $category);
        $topIdea = $suggestedIdeas[0] ?? null;
        $otherIdeas = array_slice($suggestedIdeas, 1);

        return view('problems.category', compact('feedbacks', 'category', 'topIdea', 'otherIdeas', 'topReview'));
    }

    public function enhanceIdea(Request $request, string $category): RedirectResponse
    {
        abort_unless(config('services.ollama.enhance_enabled'), 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'general_objective' => ['required', 'string'],
            'specific_objectives' => ['required', 'array'],
            'specific_objectives.*' => ['required', 'string'],
        ]);

        $evaluation = IdeaEvaluation::query()
            ->where('idea_title', $validated['title'])
            ->where('category', $category)
            ->firstOrFail();

        $enhanced = app(OllamaService::class)->enhance([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'general_objective' => $validated['general_objective'],
            'specific_objectives' => $validated['specific_objectives'],
        ]);

        $evaluation->update([
            'ai_title' => $enhanced['title'],
            'ai_description' => $enhanced['description'],
            'ai_general_objective' => $enhanced['general_objective'],
            'ai_specific_objectives' => $enhanced['specific_objectives'],
            'ai_enhanced_at' => now(),
        ]);

        return redirect()->to(
            route('feedback.category', ['category' => $category, 'idea' => $validated['title']])
                .'#idea-'.Str::slug($validated['title'])
        )->with('info', 'Enhancing wording with AI — refresh in a few seconds to see the result.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $ideas
     * @return array<int, array<string, mixed>>
     */
    private function attachAiEnhancements(array $ideas, string $category): array
    {
        $evaluations = IdeaEvaluation::query()
            ->where('category', $category)
            ->whereIn('idea_title', collect($ideas)->pluck('title'))
            ->get()
            ->keyBy('idea_title');

        return collect($ideas)->map(function (array $idea) use ($evaluations): array {
            $evaluation = $evaluations->get($idea['title']);

            if ($evaluation?->ai_enhanced_at !== null) {
                $idea['ai'] = [
                    'title' => $evaluation->ai_title,
                    'description' => $evaluation->ai_description,
                    'general_objective' => $evaluation->ai_general_objective,
                    'specific_objectives' => $evaluation->ai_specific_objectives ?? [],
                ];
            }

            return $idea;
        })->all();
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

        app(CategoryIdeaGenerationService::class)->generateForInstitutionalValidation($feedback);

        return back()->with('success', 'Feedback approved.');
    }

    public function reject(int $id): RedirectResponse
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update([
            'status' => 'rejected',
            'is_capstone_worthy' => false,
            'capstone_marked_by' => null,
            'capstone_marked_at' => null,
        ]);

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
            $this->forgetHomeCacheForCurrentUser();

            return back()->with('success', 'Support removed.');
        }

        FeedbackVote::query()->create([
            'feedback_id' => $feedback->id,
            'user_id' => Auth::id(),
        ]);
        $this->forgetHomeCacheForCurrentUser();

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
        $validated = $request->validate([
            'idea_title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'comment' => 'required|string',
            'recommendation' => 'required|in:Recommended,Needs Revision,Not Recommended',
            'feasibility' => 'required|integer|min:1|max:5',
            'impact' => 'required|integer|min:1|max:5',
            'complexity' => 'required|integer|min:1|max:5',
            'innovation' => 'required|integer|min:1|max:5',
        ]);

        AdviserReview::query()->create([
            'idea_title' => $validated['idea_title'],
            'category' => $validated['category'],
            'comment' => $validated['comment'],
            'recommendation' => $validated['recommendation'],
            'user_id' => auth()->id(),
        ]);

        $evaluation = IdeaEvaluation::query()
            ->where('idea_title', $validated['idea_title'])
            ->where('category', $validated['category'])
            ->first();

        if ($evaluation) {
            $evaluation->adviser_feasibility = $validated['feasibility'];
            $evaluation->adviser_impact = $validated['impact'];
            $evaluation->adviser_complexity = $validated['complexity'];
            $evaluation->adviser_innovation = $validated['innovation'];

            $adviserScore = ($validated['impact'] * 0.35)
                + ($validated['feasibility'] * 0.25)
                + ($validated['complexity'] * 0.20)
                + ($validated['innovation'] * 0.20);
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
        return app(CategoryIdeaGenerationService::class)->averageFrequencyScore($feedbacks);
    }

    private function averageImpactScore(Collection $feedbacks): float
    {
        return app(CategoryIdeaGenerationService::class)->averageImpactScore($feedbacks);
    }

    private function ideaCandidateCategories(): Collection
    {
        $thresholds = $this->ideaGenerationThresholds();
        $clustering = app(ClusteringService::class);

        return Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->has('votes', '>=', $thresholds['votes'])
            ->withCount('votes')
            ->get()
            ->groupBy('category')
            ->filter(function (Collection $categoryFeedbacks) use ($clustering, $thresholds): bool {
                return $clustering->group($categoryFeedbacks)
                    ->contains(fn (Collection $clusterFeedbacks): bool => $clusterFeedbacks->count() >= $thresholds['reports']
                        && $clusterFeedbacks->sum('votes_count') >= $thresholds['votes']);
            })
            ->map(fn (Collection $categoryFeedbacks): Feedback => $categoryFeedbacks->first())
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * @return array{votes: int, reports: int}
     */
    private function ideaGenerationThresholds(): array
    {
        return app(CategoryIdeaGenerationService::class)->thresholds();
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
        return app(CategoryIdeaGenerationService::class)->similarityScore($first, $second);
    }

    private function normalizedText(?string $value): string
    {
        return app(CategoryIdeaGenerationService::class)->normalizedText($value);
    }
}
