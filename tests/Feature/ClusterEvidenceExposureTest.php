<?php

use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use App\Services\ClusterEvidenceService;
use App\Services\ClusteringService;
use App\Services\IdeaGeneratorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

const EXPOSURE_CATEGORY = 'Evidence Exposure Category';

/**
 * Splits the eligible feedback into two problem clusters and records the exact
 * collection the DSS was given for each, plus the flat union it received.
 */
function exposureClusteringSpy(): object
{
    return new class
    {
        /** @var array<string, array<int, int>> */
        public array $groupedIds = [];

        /** @var array<int, int> */
        public array $unionIds = [];

        public function group($feedbacks): Collection
        {
            $groups = $feedbacks->groupBy(
                fn (Feedback $feedback): string => str_contains(strtolower($feedback->title), 'laboratory')
                    ? 'laboratory_monitoring'
                    : 'request_tracking'
            );

            $this->groupedIds = $groups
                ->map(fn (Collection $group): array => $group->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all())
                ->all();

            $this->unionIds = $feedbacks->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();

            return $groups;
        }

        public function label(string $clusterKey): string
        {
            return $clusterKey === 'laboratory_monitoring' ? 'Laboratory Equipment Monitoring' : 'Request Tracking';
        }

        public function explanation(string $clusterKey): string
        {
            return 'Reports share the same institutional problem profile.';
        }
    };
}

function exposureGeneratorFake(): object
{
    return new class
    {
        public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore, ?string $clusterKey = null): array
        {
            // generate() passes the cluster label, not the raw cluster key.
            $laboratory = $groupName === 'Laboratory Equipment Monitoring';

            return [
                'title' => $laboratory
                    ? 'Laboratory Equipment Availability System'
                    : 'Campus Request Tracking System',
                'description' => $laboratory
                    ? 'DSS generated description for laboratory equipment availability.'
                    : 'DSS generated description for campus request tracking.',
                'general_objective' => 'To improve the documented institutional service.',
                'specific_objectives' => ['To process every request through one recorded channel.'],
                'explanation' => [
                    'summary' => 'Generated from the eligible problem cluster.',
                    'factors' => [
                        'reports' => $reports,
                        'votes' => $votes,
                        'frequency_score' => $frequencyScore,
                        'impact_score' => $impactScore,
                        'top_affected_group' => 'Students',
                    ],
                    'reasoning' => [],
                ],
                'top_group' => 'Students',
                'project_name' => $laboratory ? 'LabTrack' : 'RequestHub',
                'concept' => [
                    'primary' => $laboratory ? 'Equipment monitoring' : 'Request tracking',
                    'primary_key' => $groupName,
                    'score' => 3.0,
                    'evidence' => [],
                ],
                'cluster_key' => $groupName,
            ];
        }
    };
}

function exposureFeedback(string $title, string $keyword): Feedback
{
    return Feedback::query()->create([
        'title' => $title,
        'description' => "A {$keyword} problem reported by a member of the campus community.",
        'impact' => 'Students and staff lose time because of the problem.',
        'category' => EXPOSURE_CATEGORY,
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
        'is_capstone_worthy' => true,
    ]);
}

/**
 * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: object}
 */
function exposureSetup(): array
{
    Cache::flush();

    $spy = exposureClusteringSpy();
    app()->instance(ClusteringService::class, $spy);
    app()->instance(IdeaGeneratorService::class, exposureGeneratorFake());

    collect(range(1, 3))->each(function (int $index): void {
        exposureFeedback("Request tracking gap number {$index} in the service office", 'request tracking');
        exposureFeedback("Laboratory workstation inspection problem number {$index}", 'laboratory');
    });

    $generated = app(CategoryIdeaGenerationService::class)->generate(EXPOSURE_CATEGORY);

    $ideas = collect($generated['ideas'])->keyBy('title');

    return [$ideas, $generated, $spy];
}

test('each idea exposes the report ids of the cluster the dss scored for that idea', function (): void {
    [$ideas, $generated, $spy] = exposureSetup();

    expect($generated['qualifying'])->toBeTrue()
        ->and($ideas)->toHaveCount(2);
    $requestIdea = $ideas['Campus Request Tracking System'];
    $laboratoryIdea = $ideas['Laboratory Equipment Availability System'];

    // Each idea carries exactly its own cluster's collection.
    expect($requestIdea['evidence']['feedback_ids'])
        ->toBe($spy->groupedIds['request_tracking'])
        ->and($laboratoryIdea['evidence']['feedback_ids'])
        ->toBe($spy->groupedIds['laboratory_monitoring']);

    // Neither idea exposes the flat union the DSS was handed.
    $union = $spy->unionIds;

    expect($union)->toHaveCount(6)
        ->and($requestIdea['evidence']['feedback_ids'])->not->toBe($union)
        ->and($laboratoryIdea['evidence']['feedback_ids'])->not->toBe($union)
        ->and($requestIdea['evidence']['feedback_ids'])->not->toBe($laboratoryIdea['evidence']['feedback_ids'])
        ->and(count(array_intersect(
            $requestIdea['evidence']['feedback_ids'],
            $laboratoryIdea['evidence']['feedback_ids']
        )))->toBe(0);

    // The exposed ids are exactly the reports each cluster's counts describe.
    expect($requestIdea['evidence']['feedback_ids'])->toHaveCount($requestIdea['reports_count'])
        ->and($laboratoryIdea['evidence']['feedback_ids'])->toHaveCount($laboratoryIdea['reports_count']);
});

test('the additive evidence key carries only the documented fields and changes nothing else', function (): void {
    [$ideas] = exposureSetup();

    $idea = $ideas['Campus Request Tracking System'];

    expect(array_keys($idea['evidence']))->toBe(['feedback_ids', 'reports_count', 'support_count'])
        ->and($idea['evidence']['reports_count'])->toBe($idea['reports_count'])
        ->and($idea['evidence']['support_count'])->toBe($idea['support_count'])
        ->and($idea['evidence']['feedback_ids'])->each->toBeInt();

    // Every key the DSS already produced is still present and unchanged.
    foreach ([
        'group', 'cluster_label', 'cluster_explanation', 'title', 'description',
        'project_name', 'concept', 'cluster_key', 'score', 'priority', 'seriousness',
        'general_objective', 'specific_objectives', 'explanation', 'evaluation',
        'severity_score', 'severity_level', 'severity_explanation', 'confidence_score',
        'confidence_level', 'confidence_explanation', 'confidence_breakdown',
        'comparison', 'reports_count', 'support_count', 'affected_groups',
    ] as $key) {
        expect($idea)->toHaveKey($key);
    }
});

test('the generation cache key is versioned so stale payloads cannot omit the evidence', function (): void {
    [$ideas, $generated] = exposureSetup();

    expect($generated['cache_key'])->toStartWith('ideas_v6_');

    // A repeat generation reuses the same versioned key and still carries evidence.
    $again = app(CategoryIdeaGenerationService::class)->generate(EXPOSURE_CATEGORY);

    expect($again['cache_key'])->toBe($generated['cache_key'])
        ->and($again['ideas'][0]['evidence']['feedback_ids'])->not->toBeEmpty()
        ->and($ideas->first()['evidence']['feedback_ids'])->not->toBeEmpty();
});

test('votes and comments do not change the evidence key but a new report does', function (): void {
    [$ideas] = exposureSetup();

    $service = app(ClusterEvidenceService::class);
    $title = 'Campus Request Tracking System';
    $idea = $ideas[$title];

    $baseline = $service->evidenceKey(
        $service->buildPackage($idea, EXPOSURE_CATEGORY),
        '1',
        'llama3.2:latest',
        'en'
    );

    FeedbackVote::query()->create([
        'feedback_id' => $idea['evidence']['feedback_ids'][0],
        'user_id' => User::factory()->create()->id,
    ]);
    FeedbackComment::query()->create([
        'feedback_id' => $idea['evidence']['feedback_ids'][0],
        'user_id' => User::factory()->create()->id,
        'body' => 'This also happens in the other building.',
    ]);

    $recomputed = app(CategoryIdeaGenerationService::class)->generate(EXPOSURE_CATEGORY);
    $refreshed = collect($recomputed['ideas'])->keyBy('title')[$title];

    expect($service->evidenceKey(
        $service->buildPackage($refreshed, EXPOSURE_CATEGORY),
        '1',
        'llama3.2:latest',
        'en'
    ))->toBe($baseline);

    // A new approved report joins the same cluster and changes the key.
    exposureFeedback('Request tracking gap number 4 in the service office', 'request tracking');

    $widened = app(CategoryIdeaGenerationService::class)->generate(EXPOSURE_CATEGORY);
    $widerIdea = collect($widened['ideas'])->keyBy('title')[$title];

    expect($widerIdea['reports_count'])->toBe($idea['reports_count'] + 1)
        ->and($service->evidenceKey(
            $service->buildPackage($widerIdea, EXPOSURE_CATEGORY),
            '1',
            'llama3.2:latest',
            'en'
        ))->not->toBe($baseline);
});
