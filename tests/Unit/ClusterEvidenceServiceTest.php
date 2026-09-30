<?php

use App\Models\ClusterExplanation;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\User;
use App\Services\ClusterEvidenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// The suite's shared test case is bound to the Feature directory only
// (tests/Pest.php), so this file binds it for itself: the evidence service
// resolves the cluster through Eloquent and asserts its own table.
uses(TestCase::class, RefreshDatabase::class);

// Booting the application leaves Eloquent's static connection resolver pointing
// at the container this file tears down. DB-free unit tests such as
// IdeaGeneratorServiceTest depend on that resolver being null, so it is
// restored here instead of leaking into whatever file runs next.
afterEach(function (): void {
    Model::unsetConnectionResolver();
});

function evidenceService(): ClusterEvidenceService
{
    return app(ClusterEvidenceService::class);
}

function evidenceFeedback(array $overrides = []): Feedback
{
    static $sequence = 0;
    $sequence++;

    return Feedback::query()->create(array_merge([
        'title' => "Campus request tracking problem number {$sequence}",
        'description' => "Students repeatedly follow up in person about request status number {$sequence}.",
        'impact' => "Students lose class time chasing request updates number {$sequence}.",
        'category' => 'Evidence Category',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
    ], $overrides));
}

/**
 * @param  iterable<int, Feedback>  $feedbacks
 * @return array<string, mixed>
 */
function evidenceIdea(iterable $feedbacks, array $overrides = []): array
{
    $feedbacks = collect($feedbacks);

    return array_merge([
        'title' => 'Campus Request Tracking System',
        'cluster_label' => 'Request Tracking',
        'evidence' => [
            'feedback_ids' => $feedbacks->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'reports_count' => $feedbacks->count(),
            'support_count' => 0,
        ],
    ], $overrides);
}

function keyFor(ClusterEvidenceService $service, array $package, string $language = 'en'): string
{
    return $service->evidenceKey($package, '1', 'llama3.2:latest', $language);
}

test('the package exposes exactly the whitelisted fields and nothing else', function (): void {
    $service = evidenceService();

    $feedback = evidenceFeedback();
    $package = $service->buildPackage(evidenceIdea(collect([$feedback])), 'Evidence Category');

    expect($package)->not->toBeNull()
        ->and(array_keys($package))->toBe([
            'schema_version',
            'category',
            'cluster_label',
            'report_count',
            'evidence_shown',
            'evidence_total',
            'evidence',
        ])
        ->and($package['schema_version'])->toBe('1')
        ->and($package['category'])->toBe('Evidence Category')
        ->and($package['cluster_label'])->toBe('Request Tracking')
        ->and(array_keys($package['evidence'][0]))->toBe([
            'title',
            'description',
            'impact',
            'frequency',
            'affected_users',
            'affected_group',
            'current_process',
        ]);
});

test('the package carries no report id, user reference, internal state, or DSS score', function (): void {
    $service = evidenceService();

    $user = User::factory()->create(['name' => 'Test Person']);
    $feedback = evidenceFeedback([
        'user_id' => $user->id,
        'is_capstone_worthy' => true,
        'capstone_marked_by' => $user->id,
        'department' => 'CICS',
    ]);

    $package = $service->buildPackage(evidenceIdea(collect([$feedback])), 'Evidence Category');
    $encoded = json_encode($package);

    $keys = [];
    $values = [];

    $walk = function (array $node) use (&$walk, &$keys, &$values): void {
        foreach ($node as $key => $value) {
            $keys[] = (string) $key;

            if (is_array($value)) {
                $walk($value);
            } else {
                $values[] = (string) $value;
            }
        }
    };
    $walk($package);

    $forbiddenKeys = [
        'id', 'user_id', 'is_anonymous', 'department', 'group', 'cluster_key',
        'is_flagged', 'status', 'is_capstone_worthy', 'capstone_marked_by',
        'capstone_marked_at', 'reviewed_by', 'reviewed_at', 'is_priority',
        'priority_status', 'attachment_path', 'attachment_type', 'created_at',
        'updated_at', 'translated_at', 'votes_count', 'comments_count',
        'evidence_count', 'score', 'severity', 'severity_level',
        'severity_score', 'confidence', 'confidence_level', 'confidence_score',
        'recommendation', 'idea_title', 'title_en', 'description_en', 'impact_en',
    ];

    foreach ($keys as $key) {
        expect($forbiddenKeys)->not->toContain($key);
    }

    // A report id can legitimately equal a small count such as report_count,
    // so the id comparison runs only over free-text evidence values.
    $textValues = collect($package['evidence'])
        ->flatMap(fn (array $item): array => array_values($item))
        ->flatten()
        ->filter(fn ($value): bool => ! is_numeric($value))
        ->map(fn ($value): string => (string) $value);

    foreach ($textValues as $value) {
        expect($value)->not->toContain('_unclassified_')
            ->and($value)->not->toContain('CICS')
            ->and($value)->not->toContain($user->name)
            ->and($value)->not->toContain('approved')
            ->and($value)->not->toMatch('/\b\d{4,}\b/');
    }

    // The report and user ids never appear as a standalone value. Count fields
    // are compared separately because a small id can equal a small count.
    $topLevelScalars = collect($package)
        ->map(fn ($value) => is_array($value) ? null : $value)
        ->filter()
        ->all();

    expect($topLevelScalars)->toBe([
        'schema_version' => '1',
        'category' => 'Evidence Category',
        'cluster_label' => 'Request Tracking',
        'report_count' => 1,
        'evidence_shown' => 1,
        'evidence_total' => 1,
    ]);

    $itemScalars = collect($package['evidence'][0])->map(fn ($value) => $value)->all();

    expect($itemScalars)->not->toContain((string) $feedback->id)
        ->and($itemScalars)->not->toContain((string) $user->id)
        ->and(array_keys($package['evidence'][0]))->not->toContain('id');

    expect($encoded)->not->toContain('"id"')
        ->and($encoded)->not->toContain('user_id')
        ->and($encoded)->not->toContain('is_anonymous');
});

// Twenty mutually distinct report titles, so the 85% duplicate rule has
// nothing to collapse and the item cap is what limits the package.
const DISTINCT_TITLES = [
    'Campus network drops during evening class hours',
    'Laboratory workstations are unavailable without physical inspection',
    'Enrolment schedules clash across shared rooms',
    'Request status is never communicated after submission',
    'Historical maintenance records cannot be retrieved quickly',
    'Internet access fails intermittently in the library',
    'Equipment under repair remains visible to nobody',
    'Building maintenance requests are handled verbally only',
    'Room reservations overlap because bookings are manual',
    'Student transcripts are stored in physical folders only',
    'Consultation queues exceed the available service window',
    'Computer availability cannot be checked before arrival',
    'Laboratory access requests pass through several offices',
    'Campus announcements are distributed inconsistently',
    'Inspection results are recorded after the fact',
    'Connectivity in the research wing is persistently weak',
    'Signage for relocated offices is not updated',
    'Document archiving depends on one staff member',
    'Follow up on submitted concerns is difficult',
    'Graduation checklist verification takes repeated trips',
];

test('a large cluster is capped but still reports its true size', function (): void {
    $service = evidenceService();

    $feedbacks = collect(DISTINCT_TITLES)->map(
        fn (string $title, int $index): Feedback => evidenceFeedback([
            'title' => $title,
            'description' => str_repeat("Inspection detail {$index}. ", 11),
            'impact' => str_repeat("Impact detail {$index}. ", 7),
        ])
    );

    $package = $service->buildPackage(evidenceIdea($feedbacks), 'Evidence Category');

    expect($package['evidence_shown'])->toBe(ClusterEvidenceService::MAX_ITEMS)
        ->and($package['evidence_total'])->toBe(20)
        ->and($package['report_count'])->toBe(20)
        ->and($package['evidence'])->toHaveCount(ClusterEvidenceService::MAX_ITEMS);

    foreach ($package['evidence'] as $item) {
        expect(strlen($item['title']))->toBeLessThanOrEqual(ClusterEvidenceService::MAX_TITLE_LENGTH)
            ->and(strlen($item['description']))->toBeLessThanOrEqual(ClusterEvidenceService::MAX_DESCRIPTION_LENGTH)
            ->and(strlen($item['impact']))->toBeLessThanOrEqual(ClusterEvidenceService::MAX_IMPACT_LENGTH)
            ->and(strlen($item['frequency']))->toBeLessThanOrEqual(ClusterEvidenceService::MAX_SHORT_VALUE_LENGTH)
            ->and(strlen($item['affected_users']))->toBeLessThanOrEqual(ClusterEvidenceService::MAX_SHORT_VALUE_LENGTH)
            ->and(strlen($item['current_process']))->toBeLessThanOrEqual(ClusterEvidenceService::MAX_SHORT_VALUE_LENGTH)
            ->and(count($item['affected_group']))->toBeLessThanOrEqual(ClusterEvidenceService::MAX_AFFECTED_GROUPS);
    }

    expect(strlen((string) json_encode($package)))->toBeLessThanOrEqual(ClusterEvidenceService::MAX_PACKAGE_LENGTH);
});

test('affected groups are a sorted unique list within their caps', function (): void {
    $service = evidenceService();

    $feedback = evidenceFeedback([
        'affected_group' => ['Staff', 'Students', 'Staff', 'Faculty', 'Visitors', 'Alumni', 'Interns'],
    ]);

    $package = $service->buildPackage(evidenceIdea(collect([$feedback])), 'Evidence Category');
    $groups = $package['evidence'][0]['affected_group'];

    expect($groups)->toBe(['Alumni', 'Faculty', 'Interns', 'Staff'])
        ->and($groups)->toHaveCount(ClusterEvidenceService::MAX_AFFECTED_GROUPS);

    foreach ($groups as $group) {
        expect(strlen($group))->toBeLessThanOrEqual(ClusterEvidenceService::MAX_AFFECTED_GROUP_LENGTH);
    }
});

test('ordering is deterministic and independent of the order reports were loaded in', function (): void {
    $service = evidenceService();

    $frequencies = ['Everyday', 'Often', 'Everyday', 'Sometimes', 'Everyday', 'Rarely'];
    $users = ['Less than 50', '200-500', 'More than 500', '50-200', '50-200', 'More than 500'];

    $feedbacks = collect(range(0, 5))->map(fn (int $index): Feedback => evidenceFeedback([
        'title' => DISTINCT_TITLES[$index],
        'frequency' => $frequencies[$index],
        'affected_users' => $users[$index],
    ]));

    $baseline = $service->buildPackage(evidenceIdea($feedbacks), 'Evidence Category');
    $expectedOrder = collect($baseline['evidence'])->pluck('title')->all();

    // Three Everyday reports rank highest, then title order breaks their tie.
    expect($expectedOrder[0])->toBe(DISTINCT_TITLES[2])
        ->and($expectedOrder[1])->toBe(DISTINCT_TITLES[4])
        ->and($expectedOrder[2])->toBe(DISTINCT_TITLES[0])
        ->and($expectedOrder[3])->toBe(DISTINCT_TITLES[1]);

    foreach (range(1, 5) as $seed) {
        $shuffled = $feedbacks->shuffle($seed)->values();

        $package = $service->buildPackage(evidenceIdea($shuffled), 'Evidence Category');

        expect(collect($package['evidence'])->pluck('title')->all())->toBe($expectedOrder);
    }
});

test('identical and near duplicate titles collapse while the cluster size is preserved', function (): void {
    $service = evidenceService();

    $first = evidenceFeedback(['title' => 'Students cannot track the status of their campus requests']);
    $exact = evidenceFeedback(['title' => 'Students cannot track the status of their campus requests']);
    $near = evidenceFeedback(['title' => 'Students cannot track the status of their campus request']);
    $distinct = evidenceFeedback(['title' => 'Graduation checklist verification takes repeated trips']);

    $package = $service->buildPackage(
        evidenceIdea(collect([$first, $exact, $near, $distinct])),
        'Evidence Category'
    );

    $titles = collect($package['evidence'])->pluck('title')->all();

    // All four share a frequency and reach, so the normalized title breaks the
    // tie: "graduation" sorts first, and within the repeated pair
    // "request" sorts before "requests", so the singular title is the one
    // kept and the exact and near duplicates collapse into it.
    expect($titles)->toBe([
        'Graduation checklist verification takes repeated trips',
        'Students cannot track the status of their campus request',
    ])
        ->and($package['evidence_total'])->toBe(4)
        ->and($package['report_count'])->toBe(4);
});

test('anonymous and named reports with identical content are treated identically', function (): void {
    $service = evidenceService();

    $shared = [
        'title' => 'Campus network connectivity fails during class hours',
        'description' => 'Students lose connection during online sessions.',
        'impact' => 'Class activities are interrupted.',
        'frequency' => 'Often',
        'affected_users' => '200-500',
        'affected_group' => ['Students'],
        'current_process' => 'Report verbally to staff',
    ];

    $named = User::factory()->create();
    $namedFeedback = evidenceFeedback($shared + ['user_id' => $named->id, 'is_anonymous' => false]);
    $anonymousFeedback = evidenceFeedback($shared + ['user_id' => null, 'is_anonymous' => true]);

    $namedPackage = $service->buildPackage(evidenceIdea(collect([$namedFeedback])), 'Evidence Category');
    $anonymousPackage = $service->buildPackage(evidenceIdea(collect([$anonymousFeedback])), 'Evidence Category');

    expect($namedFeedback->id)->not->toBe($anonymousFeedback->id)
        ->and($anonymousPackage)->toBe($namedPackage)
        ->and(keyFor($service, $anonymousPackage))->toBe(keyFor($service, $namedPackage))
        ->and(json_encode($anonymousPackage))->not->toContain('is_anonymous');
});

test('changed evidence or a changed prompt, model or language resolves to a different key', function (): void {
    $service = evidenceService();

    $feedback = evidenceFeedback();
    $idea = evidenceIdea(collect([$feedback]));
    $package = $service->buildPackage($idea, 'Evidence Category');
    $baseline = keyFor($service, $package);

    // Identical inputs always resolve to the same key, and input key order
    // is not part of the identity.
    expect(keyFor($service, $package))->toBe($baseline)
        ->and(keyFor($service, array_reverse($package, true)))->toBe($baseline)
        ->and(keyFor($service, $package, 'fil'))->not->toBe($baseline)
        ->and($service->evidenceKey($package, '2', 'llama3.2:latest', 'en'))->not->toBe($baseline)
        ->and($service->evidenceKey($package, '1', 'llama3.1', 'en'))->not->toBe($baseline);

    // Changed evidence.
    $feedback->update(['description' => 'A materially different description of the same problem.']);
    expect(keyFor($service, $service->buildPackage($idea, 'Evidence Category')))->not->toBe($baseline);

    $feedback->update(['frequency' => 'Everyday']);
    expect(keyFor($service, $service->buildPackage($idea, 'Evidence Category')))->not->toBe($baseline);

    $feedback->update(['affected_group' => ['Students', 'Faculty']]);
    expect(keyFor($service, $service->buildPackage($idea, 'Evidence Category')))->not->toBe($baseline);

    // Changed DSS context carried on the idea.
    $relabelled = $service->buildPackage(
        evidenceIdea(collect([$feedback]), ['cluster_label' => 'Request Tracking Plus']),
        'Evidence Category'
    );
    expect(keyFor($service, $relabelled))->not->toBe($baseline);

    $recounted = $service->buildPackage(evidenceIdea(collect([$feedback]), [
        'evidence' => ['feedback_ids' => [$feedback->id], 'reports_count' => 7, 'support_count' => 0],
    ]), 'Evidence Category');
    expect(keyFor($service, $recounted))->not->toBe($baseline);

    // Changed category.
    expect(keyFor($service, $service->buildPackage($idea, 'Another Category')))->not->toBe($baseline);
});

test('votes and comments leave the key unchanged but a new report changes it', function (): void {
    $service = evidenceService();

    $first = evidenceFeedback();
    $second = evidenceFeedback();

    $idea = evidenceIdea(collect([$first, $second]));
    $baseline = keyFor($service, $service->buildPackage($idea, 'Evidence Category'));

    FeedbackVote::query()->create([
        'feedback_id' => $first->id,
        'user_id' => User::factory()->create()->id,
    ]);
    FeedbackComment::query()->create([
        'feedback_id' => $first->id,
        'user_id' => User::factory()->create()->id,
        'body' => 'This also happens in the other building.',
    ]);

    expect(keyFor($service, $service->buildPackage($idea, 'Evidence Category')))->toBe($baseline);

    $third = evidenceFeedback();
    $widened = evidenceIdea(collect([$first, $second, $third]));

    expect(keyFor($service, $service->buildPackage($widened, 'Evidence Category')))->not->toBe($baseline);
});

test('the package is dropped when the cluster cannot be resolved exactly', function (): void {
    $service = evidenceService();

    $approved = evidenceFeedback();
    $pending = evidenceFeedback(['status' => 'pending']);
    $flagged = evidenceFeedback(['is_flagged' => true]);

    // An unapproved report invalidates the whole package.
    expect($service->buildPackage(evidenceIdea(collect([$approved, $pending])), 'Evidence Category'))->toBeNull();

    // A flagged report invalidates the whole package.
    expect($service->buildPackage(evidenceIdea(collect([$approved, $flagged])), 'Evidence Category'))->toBeNull();

    // A missing report invalidates the whole package.
    $missingIdea = evidenceIdea(collect([$approved]));
    $missingIdea['evidence']['feedback_ids'][] = $approved->id + 9999;
    expect($service->buildPackage($missingIdea, 'Evidence Category'))->toBeNull();

    // An absent or empty evidence key produces no package.
    expect($service->buildPackage(['title' => 'No Evidence Key'], 'Evidence Category'))->toBeNull();
    expect($service->buildPackage(evidenceIdea(collect()), 'Evidence Category'))->toBeNull();
    expect($service->buildPackage(['evidence' => ['feedback_ids' => []]], 'Evidence Category'))->toBeNull();
});

test('a cluster needs at least two reports to be describable as shared experience', function (): void {
    $service = evidenceService();

    $single = $service->buildPackage(evidenceIdea(collect([evidenceFeedback()])), 'Evidence Category');
    $pair = $service->buildPackage(evidenceIdea(collect([evidenceFeedback(), evidenceFeedback()])), 'Evidence Category');

    expect($service->isSynthesizable($single))->toBeFalse()
        ->and($service->isSynthesizable($pair))->toBeTrue()
        ->and($service->isSynthesizable(['report_count' => 1]))->toBeFalse()
        ->and($service->isSynthesizable([]))->toBeFalse();
});

test('the evidence service stays free of any AI, HTTP or queue dependency', function (): void {
    $source = (string) file_get_contents(app_path('Services/ClusterEvidenceService.php'));

    expect($source)->not->toContain('OllamaService')
        ->and($source)->not->toContain('Http::')
        ->and($source)->not->toContain('dispatch(')
        ->and($source)->not->toContain('App\Jobs')
        ->and($source)->not->toContain('ShouldQueue');
});

test('the cluster explanations table exists with the specified columns', function (): void {
    expect(Schema::hasTable('cluster_explanations'))->toBeTrue();

    foreach ([
        'id', 'evidence_hash', 'category', 'cluster_label', 'language',
        'prompt_version', 'model', 'summary', 'patterns', 'experiences',
        'status', 'attempts', 'last_attempted_at', 'generated_at',
        'created_at', 'updated_at',
    ] as $column) {
        expect(Schema::hasColumn('cluster_explanations', $column))->toBeTrue("missing column {$column}");
    }

    $indexes = collect(Schema::getIndexes('cluster_explanations'))
        ->filter(fn (array $index): bool => $index['unique'] === true
            && collect($index['columns'])->contains('evidence_hash'));

    expect($indexes)->not->toBeEmpty();

    $row = ClusterExplanation::query()->create([
        'evidence_hash' => hash('sha256', 'a known evidence hash'),
        'category' => 'Evidence Category',
        'cluster_label' => 'Request Tracking',
        'status' => 'complete',
        'summary' => 'Students repeatedly follow up in person for request status.',
        'patterns' => ['Updates are shared verbally.'],
        'experiences' => [['title' => 'Repeated follow ups', 'body' => 'Students queue at the office.']],
        'generated_at' => now(),
    ]);

    $fresh = $row->fresh();

    expect($fresh->evidence_hash)->toBe(hash('sha256', 'a known evidence hash'))
        ->and($fresh->status)->toBe('complete')
        ->and($fresh->language)->toBe('en')
        ->and($fresh->prompt_version)->toBe('1')
        ->and($fresh->attempts)->toBe(0)
        ->and($fresh->patterns)->toBe(['Updates are shared verbally.'])
        ->and($fresh->experiences[0]['title'])->toBe('Repeated follow ups')
        ->and($fresh->generated_at)->toBeInstanceOf(Illuminate\Support\Carbon::class);
});

test('the idea evaluations table is untouched by the new migration', function (): void {
    $columns = Schema::getColumnListing('idea_evaluations');

    expect($columns)->toContain('idea_title')
        ->and($columns)->toContain('category')
        ->and($columns)->toContain('feasibility')
        ->and($columns)->toContain('impact')
        ->and($columns)->toContain('complexity')
        ->and($columns)->toContain('innovation')
        ->and($columns)->toContain('overall_score')
        ->and($columns)->toContain('recommendation')
        ->and($columns)->toContain('adviser_id')
        ->and($columns)->toContain('adviser_feasibility')
        ->and($columns)->toContain('adviser_impact')
        ->and($columns)->toContain('adviser_complexity')
        ->and($columns)->toContain('adviser_innovation')
        ->and($columns)->toContain('final_score')
        ->and($columns)->toContain('ai_title')
        ->and($columns)->toContain('ai_description')
        ->and($columns)->toContain('ai_general_objective')
        ->and($columns)->toContain('ai_specific_objectives')
        ->and($columns)->toContain('ai_enhanced_at')
        ->and($columns)->not->toContain('evidence_hash')
        ->and($columns)->not->toContain('cluster_label');
});
