<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\IdeaGenerated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryIdeaGenerationService
{
    private const MINIMUM_VOTES_FOR_IDEA_GENERATION = 10;

    private const MINIMUM_REPORTS_FOR_IDEA_GENERATION = 3;

    /**
     * The clusters within one category that currently qualify for idea generation.
     *
     * This is the single source of truth for cluster qualification. It is shared
     * by generate() and by the read-only visibility helper so a stored
     * IdeaEvaluation is only ever presented as a current opportunity under
     * exactly the rules that produced it.
     *
     * An institutionally validated (capstone-worthy) report qualifies its own
     * cluster regardless of the report and vote thresholds, so the established
     * institutional path is preserved.
     *
     * @param  Collection<int, Feedback>  $feedbacks  Already eligibility-filtered feedback.
     * @param  array{votes: int, reports: int}  $thresholds
     * @return Collection<string, Collection<int, Feedback>>
     */
    private function qualifyingGroups(Collection $feedbacks, array $thresholds): Collection
    {
        return app(ClusteringService::class)
            ->group($feedbacks)
            ->filter(fn (Collection $groupFeedbacks): bool => ($groupFeedbacks->count() >= $thresholds['reports']
                && $groupFeedbacks->sum('votes_count') >= $thresholds['votes'])
                || $groupFeedbacks->contains(fn (Feedback $feedback): bool => $feedback->is_capstone_worthy));
    }

    /**
     * Category names that currently hold at least one qualifying DSS cluster.
     *
     * Read-only visibility support. It reuses the same eligibility and cluster
     * qualification rules as generate() but never clusters for generation,
     * never persists an IdeaEvaluation, never notifies, and never reads or
     * writes the generation cache.
     *
     * IdeaEvaluation rows are intentionally retained category-level artifacts,
     * so this answers "does this category still qualify?" without deleting
     * anything. The DSS decides; the AI only explains.
     *
     * @return Collection<int, string>
     */
    public function qualifyingCategories(): Collection
    {
        $thresholds = $this->thresholds();

        return Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->withCount('votes')
            ->get()
            ->filter(fn (Feedback $feedback): bool => $feedback->is_capstone_worthy || $feedback->votes_count >= $thresholds['votes'])
            ->groupBy(fn (Feedback $feedback): string => (string) $feedback->category)
            ->filter(fn (Collection $categoryFeedbacks): bool => $this->qualifyingGroups($categoryFeedbacks, $thresholds)->isNotEmpty())
            ->keys()
            ->values();
    }

    /**
     * Resolve the exact current provenance for one office-backed evaluation.
     *
     * @return array{office_cluster_key: string, office_source_feedback_ids: array<int, int>, office_provenance_fingerprint: string}|null
     */
    public function currentOfficeProvenance(IdeaEvaluation $evaluation): ?array
    {
        if ($evaluation->office_id === null
            || $evaluation->office_cluster_key === null
            || ! is_array($evaluation->office_source_feedback_ids)
            || $evaluation->office_provenance_fingerprint === null) {
            return null;
        }

        $thresholds = $this->thresholds();
        $feedbacks = Feedback::query()
            ->where('category', $evaluation->category)
            ->where('office_id', $evaluation->office_id)
            ->where('status', 'approved')
            ->notFlagged()
            ->withCount(['votes', 'comments', 'evidence'])
            ->latest()
            ->get()
            ->filter(fn (Feedback $feedback): bool => $feedback->is_capstone_worthy || $feedback->votes_count >= $thresholds['votes'])
            ->values();

        foreach ($this->qualifyingGroups($feedbacks, $thresholds) as $clusterKey => $clusterFeedbacks) {
            if ($clusterKey !== $evaluation->office_cluster_key) {
                continue;
            }

            $sourceIds = app(OfficeOpportunityProvenanceService::class)->sourceIds($clusterFeedbacks);
            $expectedIds = array_map('intval', $evaluation->office_source_feedback_ids);
            sort($expectedIds);

            if ($sourceIds !== array_values($expectedIds)) {
                continue;
            }

            $reports = $clusterFeedbacks->count();
            $votes = $clusterFeedbacks->sum('votes_count');
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
            $currentEvaluation = $this->evaluateIdea($reports, $votes, $frequencyScore, $impactScore, $dominantProcess);

            if (! $this->evaluationMatches($evaluation, $currentEvaluation)) {
                continue;
            }

            $snapshot = app(OfficeOpportunityProvenanceService::class)->snapshot(
                $evaluation->idea_title,
                $evaluation->category,
                (int) $evaluation->office_id,
                (string) $clusterKey,
                $clusterFeedbacks,
                $currentEvaluation,
                $thresholds
            );

            if (hash_equals($evaluation->office_provenance_fingerprint, $snapshot['office_provenance_fingerprint'])) {
                return $snapshot;
            }
        }

        return null;
    }

    /**
     * Run the category-level DSS pipeline: eligibility, clustering, severity,
     * confidence, evaluation, solution-concept idea generation, intra-run
     * deduplication, IdeaEvaluation persistence and idea notifications.
     *
     * Generated ideas are cached under the same 30-minute category cache key
     * used by the category page, so an approval-triggered run and a later page
     * view share one result.
     *
     * @return array{qualifying: bool, feedbacks: Collection<int, Feedback>, ideas: array<int, array<string, mixed>>, thresholds: array{votes: int, reports: int}, cache_key: string|null}
     */
    public function generate(string $category, ?int $officeId = null, bool $includeAllOffices = true): array
    {
        $thresholds = $this->thresholds();

        $feedbacks = Feedback::query()
            ->where('category', $category)
            ->where('status', 'approved')
            ->notFlagged()
            ->when($officeId !== null, fn ($query) => $query->where('office_id', $officeId), fn ($query) => $includeAllOffices ? $query : $query->whereNull('office_id'))
            ->with('user')
            ->withCount(['votes', 'comments', 'evidence'])
            ->latest()
            ->get()
            ->filter(fn (Feedback $feedback): bool => $feedback->is_capstone_worthy || $feedback->votes_count >= $thresholds['votes'])
            ->values();

        $clustering = app(ClusteringService::class);

        $qualifyingGroups = $this->qualifyingGroups($feedbacks, $thresholds);

        if ($qualifyingGroups->isEmpty()) {
            return [
                'qualifying' => false,
                'feedbacks' => collect(),
                'ideas' => [],
                'thresholds' => $thresholds,
                'cache_key' => null,
            ];
        }

        $feedbacks = $qualifyingGroups->flatten()->values();

        $voteSignature = md5($feedbacks
            ->map(fn (Feedback $feedback): string => "{$feedback->id}:{$feedback->votes_count}")
            ->implode('|'));
        $capstoneSignature = md5($feedbacks
            ->filter(fn (Feedback $feedback): bool => $feedback->is_capstone_worthy)
            ->pluck('id')
            ->sort()
            ->implode(','));
        $sourceSignature = app(OfficeOpportunityProvenanceService::class)->sourceFingerprint($feedbacks);
        $officeScope = $officeId !== null ? "office_{$officeId}" : ($includeAllOffices ? 'all_offices' : 'community');
        // ideas_v6 keys on concept-level idempotent presentation: the same
        // cluster and concept always resolve to the same title, so a cached
        // pre-fix result that rotated project names can never be returned.
        // The version also advances for the additive 'evidence' key below, so a
        // payload cached before that key existed is never returned as if it
        // carried the cluster's report ids.
        $cacheKey = "ideas_v6_{$category}_{$officeScope}_{$feedbacks->count()}_{$feedbacks->max('id')}_{$voteSignature}_cw-{$capstoneSignature}_src-{$sourceSignature}_votes-{$thresholds['votes']}_reports-{$thresholds['reports']}";

        $ideas = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($category, $clustering, $qualifyingGroups, $thresholds): array {
            $ideas = [];

            foreach ($qualifyingGroups as $groupName => $groupFeedbacks) {
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
                $clusterLabel = $clustering->label($groupName);

                $ideaData = app(IdeaGeneratorService::class)->generate(
                    $clusterLabel,
                    $category,
                    $groupFeedbacks,
                    $reports,
                    $votes,
                    $frequencyScore,
                    $impactScore,
                    // The raw cluster key is the authoritative problem profile.
                    $groupName
                );

                if ($this->matchesGeneratedIdea($ideaData['title'], $ideas)) {
                    continue;
                }

                $officeId = $this->officeScopeId($groupFeedbacks);
                $provenance = $officeId === null
                    ? []
                    : app(OfficeOpportunityProvenanceService::class)->snapshot(
                        $ideaData['title'],
                        $category,
                        $officeId,
                        (string) $groupName,
                        $groupFeedbacks,
                        $evaluation,
                        $thresholds
                    );

                $isNew = ! IdeaEvaluation::query()
                    ->where('idea_title', $ideaData['title'])
                    ->where('category', $category)
                    ->when($officeId !== null, fn ($query) => $query->where('office_id', $officeId), fn ($query) => $query->whereNull('office_id'))
                    ->exists();

                IdeaEvaluation::query()->updateOrCreate(
                    [
                        'idea_title' => $ideaData['title'],
                        'category' => $category,
                        'office_id' => $officeId,
                    ],
                    array_merge($evaluation, $provenance)
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
                    'cluster_label' => $clusterLabel,
                    'cluster_explanation' => $clustering->explanation($groupName),
                    'title' => $ideaData['title'],
                    'description' => $ideaData['description'],
                    // Presented to the student: the generator's own project brand
                    // and solution concept. Passed through unchanged.
                    'project_name' => $ideaData['project_name'],
                    'concept' => $ideaData['concept'],
                    'cluster_key' => $ideaData['cluster_key'],
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
                    'office_id' => $this->officeScopeId($groupFeedbacks),
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
                    // The exact feedback collection the DSS scored for this
                    // cluster. Purely additive: every other key above is
                    // unchanged, so existing consumers that read named keys
                    // only are unaffected. The raw cluster key is never
                    // included here; this is the cluster's own report ids.
                    'evidence' => [
                        'feedback_ids' => $groupFeedbacks->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                        'reports_count' => $reports,
                        'support_count' => $votes,
                    ],
                ];
            }

            usort($ideas, fn (array $left, array $right): int => $right['score'] <=> $left['score']);

            return $this->distinctGeneratedIdeas($ideas);
        });

        return [
            'qualifying' => true,
            'feedbacks' => $feedbacks,
            'ideas' => $ideas,
            'thresholds' => $thresholds,
            'cache_key' => $cacheKey,
        ];
    }

    /**
     * Generate DSS ideas for an institutionally validated problem.
     *
     * Runs only when the record is approved, not flagged, and capstone-worthy,
     * so pending, rejected, and flagged submissions never generate ideas while
     * a later category page view reuses the same cached result.
     *
     * @return array{qualifying: bool, feedbacks: Collection<int, Feedback>, ideas: array<int, array<string, mixed>>, thresholds: array{votes: int, reports: int}, cache_key: string|null}|null
     */
    /**
     * Run the shared DSS pipeline for an institutionally validated report.
     *
     * A report filed by the current representative of its own office is
     * qualified here first, so an office report that was stored before the
     * rule existed, or stored before the representative was recorded, still
     * enters the same pipeline with its office scope intact.
     */
    public function generateForInstitutionalValidation(Feedback $feedback): ?array
    {
        app(OfficeSubmissionQualificationService::class)->qualify($feedback);

        if (! $this->isInstitutionallyValidated($feedback)) {
            return null;
        }

        return $this->generate($feedback->category, $feedback->office_id ?? null, false);
    }

    public function isInstitutionallyValidated(Feedback $feedback): bool
    {
        return $feedback->status === 'approved'
            && ! (bool) $feedback->is_flagged
            && (bool) $feedback->is_capstone_worthy;
    }

    /**
     * @return array{votes: int, reports: int}
     */
    public function thresholds(): array
    {
        return [
            'votes' => (int) Setting::get('minimum_votes_for_idea_generation', self::MINIMUM_VOTES_FOR_IDEA_GENERATION),
            'reports' => (int) Setting::get('minimum_reports_for_idea_generation', self::MINIMUM_REPORTS_FOR_IDEA_GENERATION),
        ];
    }

    public function averageFrequencyScore(Collection $feedbacks): float
    {
        return round((float) $feedbacks->map(fn (Feedback $feedback): int => match ($feedback->frequency) {
            'Rarely' => 1,
            'Sometimes' => 2,
            'Often' => 3,
            'Everyday' => 4,
            default => 1,
        })->avg(), 2);
    }

    public function averageImpactScore(Collection $feedbacks): float
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

    public function evaluateIdea(int $reports, int $votes, float $frequencyScore, float $impactScore, ?string $currentProcess = null): array
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

    private function officeScopeId(Collection $feedbacks): ?int
    {
        $officeId = $feedbacks->first()?->office_id ?? null;

        return $officeId !== null && $officeId !== '' ? (int) $officeId : null;
    }

    /**
     * @param  array<string, int|float|string>  $currentEvaluation
     */
    private function evaluationMatches(IdeaEvaluation $evaluation, array $currentEvaluation): bool
    {
        foreach (['feasibility', 'impact', 'complexity', 'innovation', 'overall_score'] as $field) {
            if (abs((float) $evaluation->{$field} - (float) $currentEvaluation[$field]) > 0.00001) {
                return false;
            }
        }

        return $evaluation->recommendation === $currentEvaluation['recommendation'];
    }

    public function similarityScore(string $first, string $second): float
    {
        if ($first === '' || $second === '') {
            return 0.0;
        }

        similar_text($first, $second, $percentage);

        return $percentage;
    }

    public function normalizedText(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower($value ?? '')) ?? '');
    }

    /**
     * @param  array<int, array<string, mixed>>  $ideas
     * @return array<int, array<string, mixed>>
     */
    private function distinctGeneratedIdeas(array $ideas): array
    {
        $distinctIdeas = [];

        foreach ($ideas as $idea) {
            $title = $this->normalizedText((string) ($idea['title'] ?? ''));
            $isDuplicate = collect($distinctIdeas)->contains(
                fn (array $existingIdea): bool => $this->similarityScore(
                    $title,
                    $this->normalizedText((string) ($existingIdea['title'] ?? ''))
                ) >= 85.0
            );

            if (! $isDuplicate) {
                $distinctIdeas[] = $idea;
            }
        }

        return $distinctIdeas;
    }

    /**
     * @param  array<int, array<string, mixed>>  $ideas
     */
    private function matchesGeneratedIdea(string $title, array $ideas): bool
    {
        $normalizedTitle = $this->normalizedText($title);

        return collect($ideas)->contains(
            fn (array $idea): bool => $this->similarityScore(
                $normalizedTitle,
                $this->normalizedText((string) ($idea['title'] ?? ''))
            ) >= 85.0
        );
    }
}
