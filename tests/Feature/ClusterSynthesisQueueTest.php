<?php

use App\Jobs\SynthesizeClusterExplanation;
use App\Models\ClusterExplanation;
use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Services\CategoryIdeaGenerationService;
use App\Services\ClusterEvidenceService;
use App\Services\ClusteringService;
use App\Services\IdeaGeneratorService;
use App\Services\OllamaService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

const AI4_CATEGORY = 'Cluster Synthesis Category';

/**
 * The canned answer of the faked model: already validator-clean, so a test can
 * tell "the model answered" apart from "the answer was accepted and stored".
 *
 * @return array<string, mixed>
 */
function ai4Result(): array
{
    return [
        'summary' => 'Students follow up in person because they cannot see whether a request was received or resolved.',
        'patterns' => [
            'Status updates are shared verbally instead of being published.',
            'The same concern is raised again because nothing is tracked.',
        ],
        'experiences' => [
            [
                'title' => 'Queuing for an answer',
                'body' => 'Students wait during office hours to ask staff whether their request was received or already resolved.',
            ],
        ],
    ];
}

function ai4HttpOk(): void
{
    Http::fake([
        '*' => Http::response(['response' => json_encode(ai4Result())], 200),
    ]);
}

function ai4HttpDown(): void
{
    Http::fake(['*' => Http::response(null, 503)]);
}

function ai4Feedback(string $title, string $description, string $impact, array $groups): Feedback
{
    return Feedback::query()->create([
        'title' => $title,
        'description' => $description,
        'impact' => $impact,
        'category' => AI4_CATEGORY,
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => $groups,
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
        'is_capstone_worthy' => true,
    ]);
}

/**
 * One qualifying DSS cluster of three reports, decided by a clustering double
 * so its membership is known, beside the generator double the other category
 * page tests already use.
 *
 * @return array<int, int> The live cluster's report ids.
 */
function ai4Cluster(): array
{
    Cache::flush();

    app()->instance(ClusteringService::class, new class
    {
        public function group($feedbacks): Collection
        {
            return collect(['request_follow_up' => $feedbacks]);
        }

        public function label(string $clusterKey): string
        {
            return 'Request Follow-Up';
        }

        public function explanation(string $clusterKey): string
        {
            return 'Reports share the same request follow-up problem.';
        }
    });

    app()->instance(IdeaGeneratorService::class, new class
    {
        public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore, ?string $clusterKey = null): array
        {
            return [
                'title' => 'Request Follow-Up Tracking System',
                'description' => 'DSS generated description for request follow-up tracking.',
                'general_objective' => 'To publish the status of every request.',
                'specific_objectives' => ['To record every request and its status.'],
                'explanation' => [
                    'summary' => 'Reports were grouped and scored by the DSS.',
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
                'project_name' => 'FollowUpHub',
                'concept' => [
                    'primary' => 'Request tracking',
                    'primary_key' => 'request_follow_up',
                    'score' => 4.0,
                    'evidence' => [],
                ],
                'cluster_key' => 'request_follow_up',
            ];
        }
    });

    ai4Feedback(
        'Request status is unclear after submission',
        'Students follow up in person because status updates are never published.',
        'Students lose class time queuing for an answer.',
        ['Students'],
    );

    ai4Feedback(
        'Facility concerns are handled without a record',
        'Concerns reach the office by talking to staff in person.',
        'Nothing is tracked, so the same concern is raised again.',
        ['Students', 'Staff'],
    );

    ai4Feedback(
        'No way to check whether the office received a request',
        'Applicants return to the counter the next day to ask again.',
        'Applicants travel twice for one request.',
        ['Students', 'Faculty'],
    );

    return Feedback::query()->where('category', AI4_CATEGORY)->orderBy('id')->pluck('id')->all();
}

/**
 * The content address the current evidence resolves to, plus the profile that
 * produced it, computed the same way the page computes it.
 *
 * @return array{0: string, 1: array{model: string, prompt_version: string, language: string}}
 */
function ai4Hash(): array
{
    $generated = app(CategoryIdeaGenerationService::class)->generate(AI4_CATEGORY);
    $evidence = app(ClusterEvidenceService::class);
    $package = $evidence->buildPackage($generated['ideas'][0], AI4_CATEGORY);
    $profile = app(OllamaService::class)->synthesisProfile();

    return [
        $evidence->evidenceKey($package, $profile['prompt_version'], $profile['model'], $profile['language']),
        $profile,
    ];
}

function ai4Visit(): void
{
    test()->get(route('feedback.category', ['category' => AI4_CATEGORY]))->assertOk();
}

/**
 * Fake the queue so the page's job can be inspected without running it.
 */
function ai4QueueFake(): void
{
    Queue::fake();
}

/**
 * Everything the page has tried to queue since ai4QueueFake().
 *
 * @return array<int, SynthesizeClusterExplanation>
 */
function ai4Pushed(): array
{
    return Queue::pushed(SynthesizeClusterExplanation::class)->all();
}

/**
 * @param  array<string, mixed>  $overrides
 */
function ai4AttemptRow(array $overrides = []): ClusterExplanation
{
    [$hash] = ai4Hash();

    return ClusterExplanation::query()->create(array_merge([
        'evidence_hash' => $hash,
        'category' => AI4_CATEGORY,
        'cluster_label' => 'Request Follow-Up',
        'status' => 'rejected',
        'attempts' => 1,
        'last_attempted_at' => now()->subHours(7),
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function ai4CompleteRow(array $overrides = []): ClusterExplanation
{
    [$hash] = ai4Hash();
    $result = ai4Result();

    return ClusterExplanation::query()->create(array_merge([
        'evidence_hash' => $hash,
        'category' => AI4_CATEGORY,
        'cluster_label' => 'Request Follow-Up',
        'language' => 'en',
        'prompt_version' => '1',
        'model' => (string) config('services.ollama.synthesis.model'),
        'summary' => $result['summary'],
        'patterns' => $result['patterns'],
        'experiences' => $result['experiences'],
        'status' => 'complete',
        'attempts' => 1,
        'generated_at' => now(),
    ], $overrides));
}

test('the category page queues one synthesis job for the top qualifying cluster', function (): void {
    $ids = ai4Cluster();

    ai4QueueFake();
    ai4Visit();

    $pushed = ai4Pushed();

    expect($pushed)->toHaveCount(1)
        ->and($pushed[0])->toBeInstanceOf(SynthesizeClusterExplanation::class);

    $job = $pushed[0];

    expect($job->category)->toBe(AI4_CATEGORY)
        ->and($job->queue)->toBe('ai-synthesis')
        ->and($job->cluster['evidence']['feedback_ids'])->toBe($ids)
        ->and($job->cluster['evidence']['reports_count'])->toBe(3)
        ->and($job->cluster['cluster_label'])->toBe('Request Follow-Up')
        ->and($job->evidenceHash)->toMatch('/^[0-9a-f]{64}$/');

    // Only the cluster's identity travels with the job: no score, level,
    // qualification flag or DSS wording can reach the model from here.
    expect(array_keys($job->cluster))->toBe(['cluster_label', 'evidence'])
        ->and(array_keys($job->cluster['evidence']))->toBe(['feedback_ids', 'reports_count']);
});

test('the queued job is reserved and addressed by the evidence hash', function (): void {
    ai4Cluster();
    [$hash] = ai4Hash();

    ai4QueueFake();
    ai4Visit();

    $pushed = ai4Pushed();

    expect($pushed)->toHaveCount(1);
    $job = $pushed[0];

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job->uniqueId())->toBe($hash)
        ->and($job->evidenceHash)->toBe($hash)
        ->and($job->uniqueFor)->toBe(300)
        ->and($job->tries)->toBe(2)
        ->and($job->timeout)->toBe(180);
});

test('the same evidence hash is never queued twice while the first generation is pending', function (): void {
    ai4Cluster();

    ai4QueueFake();
    ai4Visit();
    ai4Visit();

    // The first queueing reserves the hash, so the second visit cannot spend a
    // second job on identical evidence.
    expect(ai4Pushed())->toHaveCount(1);
});

test('a completed explanation is displayed instead of another generation', function (): void {
    ai4Cluster();
    ai4CompleteRow();

    ai4QueueFake();
    ai4Visit();

    expect(ai4Pushed())->toHaveCount(0);
});

test('the retry gate decides whether an unfinished generation is queued again', function (int $attempts, int $hoursAgo, bool $shouldQueue): void {
    ai4Cluster();
    ai4AttemptRow(['attempts' => $attempts, 'last_attempted_at' => now()->subHours($hoursAgo)]);

    ai4QueueFake();
    ai4Visit();

    expect(ai4Pushed())->toHaveCount($shouldQueue ? 1 : 0);
})->with([
    'under the ceiling and outside the window' => [1, 7, true],
    'inside the window' => [1, 5, false],
    'at the ceiling' => [3, 24, false],
    'above the ceiling' => [4, 24, false],
]);

test('an attempt row that never recorded a time is retried', function (): void {
    ai4Cluster();
    ai4AttemptRow(['attempts' => 1, 'last_attempted_at' => null]);

    ai4QueueFake();
    ai4Visit();

    expect(ai4Pushed())->toHaveCount(1);
});

/**
 * The job the page would have queued, built here so its handle() can be run
 * directly. It carries the same reduced cluster reference the page builds, so
 * these tests also prove that reference still addresses the same evidence.
 */
function ai4Job(string $evidenceHash): SynthesizeClusterExplanation
{
    $idea = app(CategoryIdeaGenerationService::class)->generate(AI4_CATEGORY)['ideas'][0];

    return new SynthesizeClusterExplanation(AI4_CATEGORY, [
        'cluster_label' => (string) $idea['cluster_label'],
        'evidence' => [
            'feedback_ids' => array_map(fn (mixed $id): int => (int) $id, $idea['evidence']['feedback_ids']),
            'reports_count' => (int) $idea['evidence']['reports_count'],
        ],
    ], $evidenceHash);
}

test('a job whose evidence no longer matches stores nothing', function (): void {
    ai4Cluster();
    ai4HttpOk();

    $job = new SynthesizeClusterExplanation(AI4_CATEGORY, [
        'cluster_label' => 'Request Follow-Up',
        'evidence' => [
            'feedback_ids' => Feedback::query()->where('category', AI4_CATEGORY)->orderBy('id')->pluck('id')->all(),
            'reports_count' => 3,
        ],
    ], str_repeat('f', 64));

    $job->handle(app(ClusterEvidenceService::class), app(OllamaService::class));

    expect(ClusterExplanation::query()->count())->toBe(0);

    // It does not even spend the model call on evidence it cannot address.
    Http::assertSentCount(0);
});

test('a cluster that stops resolving exactly is left undescribed', function (): void {
    $ids = ai4Cluster();
    [$hash] = ai4Hash();
    ai4HttpOk();

    Feedback::query()->where('id', $ids[0])->update(['status' => 'pending']);

    ai4Job($hash)->handle(app(ClusterEvidenceService::class), app(OllamaService::class));

    expect(ClusterExplanation::query()->count())->toBe(0);

    Http::assertNothingSent();
});

test('a successful synthesis stores the explanation against its evidence hash', function (): void {
    ai4Cluster();
    [$hash, $profile] = ai4Hash();
    ai4HttpOk();

    ai4Job($hash)->handle(app(ClusterEvidenceService::class), app(OllamaService::class));

    $explanation = ClusterExplanation::query()->first();
    $result = ai4Result();

    expect($explanation)->not->toBeNull()
        ->and($explanation->evidence_hash)->toBe($hash)
        ->and($explanation->category)->toBe(AI4_CATEGORY)
        ->and($explanation->cluster_label)->toBe('Request Follow-Up')
        ->and($explanation->language)->toBe('en')
        ->and($explanation->prompt_version)->toBe($profile['prompt_version'])
        ->and($explanation->model)->toBe($profile['model'])
        ->and($explanation->status)->toBe('complete')
        ->and($explanation->summary)->toBe($result['summary'])
        ->and($explanation->patterns)->toBe($result['patterns'])
        ->and($explanation->experiences)->toBe($result['experiences'])
        ->and($explanation->generated_at)->not->toBeNull()
        ->and($explanation->last_attempted_at)->not->toBeNull()
        ->and((int) $explanation->attempts)->toBe(1)
        ->and(ClusterExplanation::query()->count())->toBe(1);

    // The answer went through the production validator, not a stub.
    Http::assertSentCount(1);
});

test('an already complete evidence hash is never generated again', function (): void {
    ai4Cluster();
    [$hash] = ai4Hash();
    ai4HttpOk();

    ai4Job($hash)->handle(app(ClusterEvidenceService::class), app(OllamaService::class));
    ai4Job($hash)->handle(app(ClusterEvidenceService::class), app(OllamaService::class));

    expect(ClusterExplanation::query()->count())->toBe(1)
        ->and((int) ClusterExplanation::query()->value('attempts'))->toBe(1);

    Http::assertSentCount(1);
});

test('a rejected generation stores no completed explanation', function (): void {
    ai4Cluster();
    [$hash] = ai4Hash();
    ai4HttpDown();

    ai4Job($hash)->handle(app(ClusterEvidenceService::class), app(OllamaService::class));

    $explanation = ClusterExplanation::query()->first();

    expect(ClusterExplanation::query()->count())->toBe(1)
        ->and($explanation->evidence_hash)->toBe($hash)
        ->and($explanation->status)->toBe('rejected')
        ->and($explanation->summary)->toBeNull()
        ->and($explanation->patterns)->toBeNull()
        ->and($explanation->experiences)->toBeNull()
        ->and($explanation->generated_at)->toBeNull()
        ->and((int) $explanation->attempts)->toBe(1)
        ->and($explanation->last_attempted_at)->not->toBeNull();
});

test('an output that fails the validator is not stored as complete', function (): void {
    ai4Cluster();
    [$hash] = ai4Hash();

    // A summary too short to satisfy the production length rule.
    Http::fake([
        '*' => Http::response(['response' => json_encode(array_replace(ai4Result(), ['summary' => 'Too short.']))], 200),
    ]);

    ai4Job($hash)->handle(app(ClusterEvidenceService::class), app(OllamaService::class));

    expect(ClusterExplanation::query()->where('status', 'complete')->count())->toBe(0)
        ->and(ClusterExplanation::query()->where('status', 'rejected')->count())->toBe(1)
        ->and(ClusterExplanation::query()->value('summary'))->toBeNull();
});

test('the category page shows a completed explanation to a guest', function (): void {
    ai4Cluster();
    ai4CompleteRow();

    $result = ai4Result();
    $response = test()->get(route('feedback.category', ['category' => AI4_CATEGORY]))->assertOk();

    $response
        ->assertSeeText('What People Are Experiencing')
        ->assertSeeText('AI-generated')
        ->assertSeeText($result['summary'])
        ->assertSeeText($result['patterns'][0])
        ->assertSeeText($result['experiences'][0]['title'])
        ->assertSeeText($result['experiences'][0]['body'])
        ->assertSeeText('The DSS decided this problem qualifies; AI only summarizes the evidence.')
        ->assertSee('class="co-synth-card"', false)
        // The DSS result and its own evidence are unaffected by the section.
        ->assertSeeText('Supporting Evidence')
        ->assertSeeText('Request Follow-Up Tracking System');
});

test('generated content is escaped', function (): void {
    ai4Cluster();
    ai4CompleteRow(['summary' => 'Students say <script>alert(1)</script> about their requests here']);

    test()->get(route('feedback.category', ['category' => AI4_CATEGORY]))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

test('the category page shows nothing invented while a generation is missing', function (): void {
    ai4Cluster();
    Queue::fake();

    test()->get(route('feedback.category', ['category' => AI4_CATEGORY]))
        ->assertOk()
        ->assertDontSeeText('What People Are Experiencing')
        ->assertDontSeeText('AI-generated')
        ->assertDontSeeText('The DSS decided this problem qualifies')
        ->assertDontSee('class="co-synth-card"', false)
        ->assertSeeText('Supporting Evidence')
        ->assertSeeText('Request Follow-Up Tracking System');
});

test('a rejected generation leaves the page complete and the section absent', function (): void {
    ai4Cluster();
    ai4AttemptRow();
    Queue::fake();

    test()->get(route('feedback.category', ['category' => AI4_CATEGORY]))
        ->assertOk()
        ->assertDontSeeText('What People Are Experiencing')
        ->assertDontSee('class="co-synth-card"', false)
        ->assertSeeText('Supporting Evidence')
        ->assertSeeText('Request Follow-Up Tracking System');
});

test('ai synthesis never changes anything the dss decided', function (): void {
    ai4Cluster();
    [$hash] = ai4Hash();
    $job = ai4Job($hash);

    $evaluationsBefore = IdeaEvaluation::query()->orderBy('id')->get()->toArray();
    $feedbackBefore = Feedback::query()->orderBy('id')->get()->toArray();

    ai4HttpOk();
    $job->handle(app(ClusterEvidenceService::class), app(OllamaService::class));

    expect(IdeaEvaluation::query()->orderBy('id')->get()->toArray())->toBe($evaluationsBefore)
        ->and(Feedback::query()->orderBy('id')->get()->toArray())->toBe($feedbackBefore)
        ->and(ClusterExplanation::query()->count())->toBe(1);
});

test('the page request stores the generation instead of calling the model', function (): void {
    ai4Cluster();
    Config::set('queue.default', 'database');
    ai4HttpOk();

    test()->get(route('feedback.category', ['category' => AI4_CATEGORY]))->assertOk();

    // The work really is waiting on the database queue and the explanation
    // really is not there yet: the page was served without waiting for anyone.
    expect(DB::table('jobs')->where('queue', 'ai-synthesis')->count())->toBe(1)
        ->and(ClusterExplanation::query()->count())->toBe(0);

    Http::assertNothingSent();
});

test('the queued generation runs on the worker and the next visit shows it', function (): void {
    ai4Cluster();
    [$hash] = ai4Hash();
    Config::set('queue.default', 'database');
    ai4HttpOk();

    test()->get(route('feedback.category', ['category' => AI4_CATEGORY]))
        ->assertOk()
        ->assertDontSeeText('What People Are Experiencing');

    Http::assertNothingSent();

    $this->artisan('queue:work', [
        '--queue' => 'ai-synthesis',
        '--stop-when-empty' => true,
        '--sleep' => 0,
    ])->run();

    expect(ClusterExplanation::query()->where('status', 'complete')->count())->toBe(1)
        ->and(ClusterExplanation::query()->where('evidence_hash', $hash)->count())->toBe(1)
        ->and(DB::table('jobs')->count())->toBe(0);

    Http::assertSentCount(1);

    test()->get(route('feedback.category', ['category' => AI4_CATEGORY]))
        ->assertOk()
        ->assertSeeText('What People Are Experiencing')
        ->assertSeeText(ai4Result()['summary']);

    // A finished explanation is never queued a second time.
    expect(DB::table('jobs')->count())->toBe(0);
});
