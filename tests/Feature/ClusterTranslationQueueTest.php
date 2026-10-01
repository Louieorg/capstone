<?php

use App\Jobs\TranslateClusterExplanationToFilipino;
use App\Models\ClusterExplanation;
use App\Models\Feedback;
use App\Services\CategoryIdeaGenerationService;
use App\Services\ClusterEvidenceService;
use App\Services\ClusteringService;
use App\Services\ClusterTranslationService;
use App\Services\IdeaGeneratorService;
use App\Services\OllamaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

const AI5_CATEGORY = 'Filipino Translation Category';

/**
 * A validator-clean Filipino rendering of ai5Result(), so a test can tell
 * "the model answered" apart from "the answer was accepted and stored".
 *
 * @return array<string, mixed>
 */
function ai5FilipinoResult(): array
{
    return [
        'summary' => 'Paulit-ulit na personal na nagfo-follow up ang mga estudyante dahil hindi nila nakikita kung natanggap o naresolba na ang kanilang kahilingan.',
        'patterns' => [
            'Ang mga update sa status ay sinasabi nang pasalita sa halip na ilathala para sa lahat.',
            'Ang parehong alalahanin ay muling ibinabangon dahil walang naitatalang talaan.',
        ],
        'experiences' => [
            [
                'title' => 'Pumipila para sa kasagutan',
                'body' => 'Naghihintay ang mga estudyante sa oras ng klase upang tanungin ang mga kawani kung natanggap o naresolba na ang kanilang kahilingan.',
            ],
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function ai5EnglishResult(): array
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

function ai5Feedback(string $title, string $description, string $impact, array $groups): Feedback
{
    return Feedback::query()->create([
        'title' => $title,
        'description' => $description,
        'impact' => $impact,
        'category' => AI5_CATEGORY,
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
 * One qualifying DSS cluster of three reports, beside the generator double
 * the other category page tests already use.
 *
 * @return array<int, int> The live cluster's report ids.
 */
function ai5Cluster(): array
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
                ],
                'top_group' => 'Students',
                'current_process' => 'Report verbally to staff',
                'department' => 'Registrar',
                'concept' => [
                    'concept' => 'Request Follow-Up Tracking System',
                    'category' => $category,
                    'scope_kind' => 'wide',
                ],
                'project_name' => 'TrackPoint',
                'seriousness' => 'Medium',
                'severity_score' => 3,
                'severity_level' => 'Medium',
                'confidence_score' => 3,
                'confidence_level' => 'Medium',
                'evaluation' => ['overall_score' => 3.5, 'recommendation' => 'Recommended'],
                'cluster_label' => 'Request Follow-Up',
                'cluster_explanation' => 'Reports share the same request follow-up problem.',
                'cluster_key' => 'request_follow_up',
            ];
        }
    });

    ai5Feedback(
        'Request status is unclear after submission',
        'Students follow up in person because status updates are never published.',
        'Students lose class time queuing for an answer.',
        ['Students'],
    );

    ai5Feedback(
        'Facility concerns are handled without a record',
        'Concerns reach the office by talking to staff in person.',
        'Nothing is tracked, so the same concern is raised again.',
        ['Students', 'Staff'],
    );

    ai5Feedback(
        'No way to check whether the office received a request',
        'Applicants return to the counter the next day to ask again.',
        'Applicants travel twice for one request.',
        ['Students', 'Faculty'],
    );

    return Feedback::query()->where('category', AI5_CATEGORY)->orderBy('id')->pluck('id')->all();
}

/**
 * The content address the current evidence resolves to, computed the same
 * way the page computes it.
 */
function ai5EnglishHash(): string
{
    $generated = app(CategoryIdeaGenerationService::class)->generate(AI5_CATEGORY);
    $evidence = app(ClusterEvidenceService::class);
    $package = $evidence->buildPackage($generated['ideas'][0], AI5_CATEGORY);
    $profile = app(OllamaService::class)->synthesisProfile();

    return $evidence->evidenceKey($package, $profile['prompt_version'], $profile['model'], $profile['language']);
}

/**
 * A stored completed English synthesis for the live cluster.
 */
function ai5EnglishRow(array $overrides = []): ClusterExplanation
{
    return ClusterExplanation::query()->create(array_merge([
        'evidence_hash' => ai5EnglishHash(),
        'category' => AI5_CATEGORY,
        'cluster_label' => 'Request Follow-Up',
        'language' => 'en',
        'prompt_version' => '1',
        'model' => (string) config('services.ollama.synthesis.model'),
        'summary' => ai5EnglishResult()['summary'],
        'patterns' => ai5EnglishResult()['patterns'],
        'experiences' => ai5EnglishResult()['experiences'],
        'status' => 'complete',
        'attempts' => 1,
        'generated_at' => now(),
    ], $overrides));
}

/**
 * Everything the page has tried to queue for Filipino translation.
 *
 * @return array<int, TranslateClusterExplanationToFilipino>
 */
function ai5Pushed(): array
{
    return Queue::pushed(TranslateClusterExplanationToFilipino::class)->all();
}

/**
 * Visit the category page in Filipino mode.
 */
function ai5VisitFilipino(): void
{
    test()->get(route('feedback.category', ['category' => AI5_CATEGORY, 'synth_lang' => 'fil']))->assertOk();
}

test('filipino is queued only when a completed english synthesis exists', function (): void {
    ai5Cluster();
    Queue::fake();
    ai5VisitFilipino();

    // Without completed English there is nothing to render, so nothing to queue.
    expect(ai5Pushed())->toHaveCount(0);
});

test('filipino is queued for a completed english synthesis', function (): void {
    ai5Cluster();
    $english = ai5EnglishRow();
    Queue::fake();
    ai5VisitFilipino();

    $pushed = ai5Pushed();

    expect($pushed)->toHaveCount(1)
        ->and($pushed[0])->toBeInstanceOf(TranslateClusterExplanationToFilipino::class)
        ->and($pushed[0]->englishId)->toBe($english->id)
        ->and($pushed[0]->queue)->toBe('ai-filipino');

    // Only the English row's identity travels with the job: no scores, no
    // raw reports, no DSS wording the model could reinterpret.
    expect(array_keys(get_object_vars($pushed[0])))->not->toContain('evidence');
});

test('rejected english synthesis never dispatches a translation', function (): void {
    ai5Cluster();
    ai5EnglishRow(['status' => 'rejected', 'summary' => null, 'generated_at' => null]);
    Queue::fake();
    ai5VisitFilipino();

    expect(ai5Pushed())->toHaveCount(0);
});

test('the same translation hash is never queued twice at once', function (): void {
    ai5Cluster();
    ai5EnglishRow();
    Queue::fake();
    ai5VisitFilipino();
    ai5VisitFilipino();

    // The unique reservation held by the first queueing blocks the second.
    expect(ai5Pushed())->toHaveCount(1);
});

test('a completed translation is displayed instead of queued again', function (): void {
    ai5Cluster();
    $english = ai5EnglishRow();

    Http::fake(['*' => Http::response(['response' => json_encode(ai5FilipinoResult())], 200)]);

    $translation = app(ClusterTranslationService::class);
    $source = $translation->canonicalSource($english);
    $profile = app(OllamaService::class)->translationProfile();
    $hash = $translation->translationKey($source, $profile['prompt_version'], $profile['model'], $profile['language']);

    (new TranslateClusterExplanationToFilipino(AI5_CATEGORY, $english->id, $hash))->handle(
        $translation,
        app(OllamaService::class)
    );

    Queue::fake();
    $response = test()->get(route('feedback.category', ['category' => AI5_CATEGORY, 'synth_lang' => 'fil']));

    $response->assertOk()->assertSeeText('Pumipila para sa kasagutan');
    expect(ai5Pushed())->toHaveCount(0);
});

test('the page never waits for the model', function (): void {
    ai5Cluster();
    ai5EnglishRow();

    Http::fake(['*' => Http::response(null, 503)]);
    Queue::fake();

    test()->get(route('feedback.category', ['category' => AI5_CATEGORY, 'synth_lang' => 'fil']))
        ->assertOk()
        ->assertSeeText('Filipino translation is being prepared')
        ->assertSeeText('Supporting Evidence');
});

test('english stays available when the translation is rejected', function (): void {
    ai5Cluster();
    $english = ai5EnglishRow();

    Http::fake(['*' => Http::response(['response' => json_encode(['summary' => 'too short'])], 200)]);
    $translation = app(ClusterTranslationService::class);
    $source = $translation->canonicalSource($english);
    $profile = app(OllamaService::class)->translationProfile();
    $hash = $translation->translationKey($source, $profile['prompt_version'], $profile['model'], $profile['language']);

    (new TranslateClusterExplanationToFilipino(AI5_CATEGORY, $english->id, $hash))->handle(
        $translation,
        app(OllamaService::class)
    );

    expect(ClusterExplanation::query()->where('evidence_hash', $hash)->value('status'))->toBe('rejected');

    Queue::fake();

    test()->get(route('feedback.category', ['category' => AI5_CATEGORY, 'synth_lang' => 'fil']))
        ->assertOk()
        ->assertSeeText(ai5EnglishResult()['summary']);
});
