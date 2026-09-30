<?php

use App\Models\CategoryAssignment;
use App\Models\Feedback;
use App\Models\FeedbackVote;
use App\Models\IdeaEvaluation;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use App\Services\ClusteringService;
use App\Services\IdeaGeneratorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

function officeDssUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'office_academic',
        'is_office_head' => true,
        'office_department' => 'CICS',
    ], $attributes));
}

function officeDssAssignment(string $category, string $office = 'office_academic'): CategoryAssignment
{
    return CategoryAssignment::query()->create([
        'category' => $category,
        'office' => $office,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function officeDssPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Laboratory equipment status is tracked manually',
        'category' => 'Office Review Facilities',
        'department' => 'CICS',
        'description' => 'Laboratory personnel inspect every workstation by hand to learn which computers are functional and which units are under maintenance.',
        'impact' => 'Students spend class time locating working computers while staff repeat manual inspections every session.',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students', 'Staff'],
        'force_submit' => '1',
    ], $overrides);
}

function officeDssClusteringSpy(): object
{
    return new class
    {
        public ?Collection $processedFeedbacks = null;

        public function group($feedbacks): Collection
        {
            $this->processedFeedbacks = $feedbacks;

            return collect(['office dss cluster' => $feedbacks]);
        }

        public function label(string $clusterKey): string
        {
            return 'Office DSS Cluster';
        }

        public function explanation(string $clusterKey): string
        {
            return 'Reports share the same institutional problem profile.';
        }
    };
}

function officeDssGeneratorFake(string $title): object
{
    return new class($title)
    {
        public function __construct(private string $title) {}

        public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore, ?string $clusterKey = null): array
        {
            return [
                'title' => $this->title,
                'description' => 'Generated from an institutionally validated problem using the existing DSS pipeline.',
                'project_name' => 'Office DSS Project',
                'concept' => 'Equipment Monitoring System',
                'cluster_key' => $clusterKey ?? 'office_dss_cluster',
                'general_objective' => 'To improve the affected institutional service.',
                'specific_objectives' => ['To reuse the existing DSS generation pipeline.'],
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
            ];
        }
    };
}

/**
 * @param  array<string, mixed>  $overrides
 */
function officeDssFeedback(array $overrides = []): Feedback
{
    return Feedback::query()->create(array_merge([
        'title' => 'Submitted institutional problem',
        'description' => 'Students experience recurring delays that need a coordinated solution.',
        'impact' => 'This affects student transactions and creates repeated follow ups.',
        'category' => 'Office Review Facilities',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'status' => 'approved',
    ], $overrides));
}

function officeDssAddVotes(Feedback $feedback, int $votes): void
{
    User::factory()
        ->count($votes)
        ->create()
        ->each(function (User $user) use ($feedback): void {
            FeedbackVote::query()->create([
                'feedback_id' => $feedback->id,
                'user_id' => $user->id,
            ]);
        });
}

test('an authorized office head submission is institutionally validated but generates no dss idea while pending', function (): void {
    Cache::flush();

    $officeUser = officeDssUser();
    officeDssAssignment('Office Review Facilities');

    $clusteringSpy = officeDssClusteringSpy();
    $this->app->instance(ClusteringService::class, $clusteringSpy);
    $this->app->instance(IdeaGeneratorService::class, officeDssGeneratorFake('Office Validated Laboratory Idea'));

    $this->actingAs($officeUser)
        ->post(route('feedback.store'), officeDssPayload())
        ->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()
        ->where('category', 'Office Review Facilities')
        ->latest('id')
        ->firstOrFail();

    expect($feedback->status)->toBe('pending')
        ->and((bool) $feedback->is_capstone_worthy)->toBeTrue()
        ->and($feedback->capstone_marked_by)->toBe($officeUser->id)
        ->and($feedback->capstone_marked_at)->not->toBeNull()
        ->and(IdeaEvaluation::query()->count())->toBe(0)
        ->and($clusteringSpy->processedFeedbacks)->toBeNull();
});

test('office approval and rejection reject transitions from non-pending states without changing review data or dss output', function (string $action, string $status): void {
    $officeUser = officeDssUser();
    officeDssAssignment('Office Review Facilities');

    $reviewer = User::factory()->create();
    $capstoneMarker = User::factory()->create();
    $feedback = officeDssFeedback([
        'status' => $status,
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => now()->subDays(2),
        'is_capstone_worthy' => true,
        'capstone_marked_by' => $capstoneMarker->id,
        'capstone_marked_at' => now()->subDay(),
    ]);
    $stateBefore = $feedback->only([
        'status',
        'reviewed_by',
        'reviewed_at',
        'is_capstone_worthy',
        'capstone_marked_by',
        'capstone_marked_at',
    ]);
    $ideaEvaluationCount = IdeaEvaluation::query()->count();

    $generationService = Mockery::mock(CategoryIdeaGenerationService::class);
    $generationService->shouldNotReceive('generateForInstitutionalValidation');
    $this->app->instance(CategoryIdeaGenerationService::class, $generationService);

    $this->actingAs($officeUser)
        ->patch(route("office.review.{$action}", $feedback->id), [
            'status' => 'pending',
            'reviewed_by' => $officeUser->id,
            'is_capstone_worthy' => false,
            'capstone_marked_by' => $officeUser->id,
        ])
        ->assertUnprocessable();

    expect($feedback->fresh()->only([
        'status',
        'reviewed_by',
        'reviewed_at',
        'is_capstone_worthy',
        'capstone_marked_by',
        'capstone_marked_at',
    ]))->toEqual($stateBefore)
        ->and(IdeaEvaluation::query()->count())->toBe($ideaEvaluationCount);
})->with([
    'approving an already-approved report' => ['approve', 'approved'],
    'approving a rejected report' => ['approve', 'rejected'],
    'rejecting an already-approved report' => ['reject', 'approved'],
    'rejecting an already-rejected report' => ['reject', 'rejected'],
]);

test('office approval runs the existing dss pipeline and persists an idea evaluation', function (): void {
    Cache::flush();

    $officeUser = officeDssUser();
    officeDssAssignment('Office Review Facilities');

    $clusteringSpy = officeDssClusteringSpy();
    $this->app->instance(ClusteringService::class, $clusteringSpy);
    $this->app->instance(IdeaGeneratorService::class, officeDssGeneratorFake('Office Validated Laboratory Idea'));

    $this->actingAs($officeUser)->post(route('feedback.store'), officeDssPayload());

    $feedback = Feedback::query()
        ->where('category', 'Office Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($officeUser)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    $feedback->refresh();

    expect($feedback->status)->toBe('approved')
        ->and((bool) $feedback->is_flagged)->toBeFalse()
        ->and($clusteringSpy->processedFeedbacks)->not->toBeNull()
        ->and($clusteringSpy->processedFeedbacks)->toHaveCount(1)
        ->and($clusteringSpy->processedFeedbacks->first()->id)->toBe($feedback->id)
        ->and($clusteringSpy->processedFeedbacks->first()->votes_count)->toBe(0);

    $this->assertDatabaseHas('idea_evaluations', [
        'idea_title' => 'Office Validated Laboratory Idea',
        'category' => 'Office Review Facilities',
    ]);
});

test('admin approval of an institutionally validated submission also runs the dss pipeline', function (): void {
    Cache::flush();

    $officeUser = officeDssUser();
    $admin = User::factory()->create(['role' => 'admin']);
    officeDssAssignment('Office Review Facilities');

    $this->app->instance(ClusteringService::class, officeDssClusteringSpy());
    $this->app->instance(IdeaGeneratorService::class, officeDssGeneratorFake('Admin Approved Laboratory Idea'));

    $this->actingAs($officeUser)->post(route('feedback.store'), officeDssPayload());

    $feedback = Feedback::query()
        ->where('category', 'Office Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('feedback.approve', $feedback->id))
        ->assertSessionHas('success');

    expect($feedback->refresh()->status)->toBe('approved');

    $this->assertDatabaseHas('idea_evaluations', [
        'idea_title' => 'Admin Approved Laboratory Idea',
        'category' => 'Office Review Facilities',
    ]);
});

test('institutional validation does not inflate community evidence or dss scores', function (): void {
    Cache::flush();

    $officeUser = officeDssUser();
    officeDssAssignment('Facilities');

    $this->actingAs($officeUser)->post(route('feedback.store'), officeDssPayload([
        'category' => 'Facilities',
        'title' => 'Laboratory computer availability is checked manually',
        'description' => 'Laboratory staff inspect each workstation by hand to determine which computers are functional, occupied, or under maintenance.',
    ]));

    $feedback = Feedback::query()
        ->where('category', 'Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($officeUser)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    $feedback->refresh();

    $evaluation = IdeaEvaluation::query()
        ->where('category', 'Facilities')
        ->firstOrFail();

    $service = app(CategoryIdeaGenerationService::class);
    $expected = $service->evaluateIdea(
        1,
        0,
        $service->averageFrequencyScore(collect([$feedback])),
        $service->averageImpactScore(collect([$feedback])),
        $feedback->current_process
    );

    expect($feedback->votes()->count())->toBe(0)
        ->and(Feedback::query()->where('category', 'Facilities')->count())->toBe(1)
        ->and((float) $evaluation->feasibility)->toBe((float) $expected['feasibility'])
        ->and((float) $evaluation->impact)->toBe((float) $expected['impact'])
        ->and((float) $evaluation->complexity)->toBe((float) $expected['complexity'])
        ->and((float) $evaluation->innovation)->toBe((float) $expected['innovation'])
        ->and((float) $evaluation->overall_score)->toBe((float) $expected['overall_score'])
        ->and($evaluation->recommendation)->toBe($expected['recommendation']);
});

test('an office head submitting a category that is not assigned to the office is not institutionally validated', function (): void {
    Cache::flush();

    $officeUser = officeDssUser();

    $this->actingAs($officeUser)->post(route('feedback.store'), officeDssPayload([
        'category' => 'Unassigned Office Category',
    ]));

    $feedback = Feedback::query()
        ->where('category', 'Unassigned Office Category')
        ->latest('id')
        ->firstOrFail();

    expect((bool) $feedback->is_capstone_worthy)->toBeFalse()
        ->and($feedback->capstone_marked_by)->toBeNull()
        ->and($feedback->capstone_marked_at)->toBeNull()
        ->and(IdeaEvaluation::query()->count())->toBe(0);

    $this->actingAs($officeUser)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertForbidden();

    expect(IdeaEvaluation::query()->count())->toBe(0);
});

test('a normal community submission does not receive institutional validation or a dss idea', function (): void {
    Cache::flush();

    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)->post(route('feedback.store'), officeDssPayload([
        'category' => 'Community Facilities Review',
    ]));

    $feedback = Feedback::query()
        ->where('category', 'Community Facilities Review')
        ->latest('id')
        ->firstOrFail();

    expect((bool) $feedback->is_capstone_worthy)->toBeFalse()
        ->and($feedback->capstone_marked_by)->toBeNull()
        ->and($feedback->capstone_marked_at)->toBeNull()
        ->and(IdeaEvaluation::query()->count())->toBe(0);

    $this->get(route('feedback.category', ['category' => 'Community Facilities Review']))
        ->assertOk()
        ->assertSeeText('Not Enough Data Yet');

    expect(IdeaEvaluation::query()->count())->toBe(0);
});

test('rejecting an office submission clears institutional validation and capstone visibility', function (): void {
    Cache::flush();

    $officeUser = officeDssUser();
    officeDssAssignment('Office Review Facilities');

    $this->actingAs($officeUser)->post(route('feedback.store'), officeDssPayload());

    $feedback = Feedback::query()
        ->where('category', 'Office Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($officeUser)
        ->patch(route('office.review.reject', $feedback->id))
        ->assertSessionHas('success');

    $feedback->refresh();

    expect($feedback->status)->toBe('rejected')
        ->and((bool) $feedback->is_capstone_worthy)->toBeFalse()
        ->and($feedback->capstone_marked_by)->toBeNull()
        ->and($feedback->capstone_marked_at)->toBeNull()
        ->and(IdeaEvaluation::query()->count())->toBe(0);

    $this->get(route('capstone.opportunities'))
        ->assertOk()
        ->assertDontSeeText('Laboratory equipment status is tracked manually');
});

test('a rejected submission that is later approved does not regain institutional validation', function (): void {
    Cache::flush();

    $officeUser = officeDssUser();
    $admin = User::factory()->create(['role' => 'admin']);
    officeDssAssignment('Office Review Facilities');

    $this->actingAs($officeUser)->post(route('feedback.store'), officeDssPayload());

    $feedback = Feedback::query()
        ->where('category', 'Office Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($officeUser)->patch(route('office.review.reject', $feedback->id));
    $this->actingAs($admin)->patch(route('feedback.approve', $feedback->id));

    $feedback->refresh();

    expect($feedback->status)->toBe('approved')
        ->and((bool) $feedback->is_capstone_worthy)->toBeFalse()
        ->and($feedback->capstone_marked_by)->toBeNull()
        ->and($feedback->capstone_marked_at)->toBeNull()
        ->and(IdeaEvaluation::query()->count())->toBe(0);
});

test('admin marking an approved problem capstone-worthy runs the shared dss pipeline', function (): void {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);

    $clusteringSpy = officeDssClusteringSpy();
    $this->app->instance(ClusteringService::class, $clusteringSpy);
    $this->app->instance(IdeaGeneratorService::class, officeDssGeneratorFake('Manually Marked Laboratory Idea'));

    $feedback = officeDssFeedback([
        'title' => 'Approved problem waiting for capstone validation',
        'category' => 'Admin Capstone Review',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.priority.mark-capstone', $feedback->id))
        ->assertSessionHas('success');

    expect($clusteringSpy->processedFeedbacks)->not->toBeNull();

    $this->assertDatabaseHas('idea_evaluations', [
        'idea_title' => 'Manually Marked Laboratory Idea',
        'category' => 'Admin Capstone Review',
    ]);
});

test('office reviewers marking an approved problem capstone-worthy runs the shared dss pipeline', function (): void {
    Cache::flush();

    $officeUser = officeDssUser();
    officeDssAssignment('Office Review Facilities');

    $this->app->instance(ClusteringService::class, officeDssClusteringSpy());
    $this->app->instance(IdeaGeneratorService::class, officeDssGeneratorFake('Office Manually Marked Idea'));

    $feedback = officeDssFeedback([
        'title' => 'Approved office problem waiting for capstone validation',
    ]);

    $this->actingAs($officeUser)
        ->patch(route('office.review.mark-capstone', $feedback->id))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('idea_evaluations', [
        'idea_title' => 'Office Manually Marked Idea',
        'category' => 'Office Review Facilities',
    ]);
});

test('viewing the category page after automatic generation does not duplicate the idea evaluation', function (): void {
    Cache::flush();

    $officeUser = officeDssUser();
    officeDssAssignment('Office Review Facilities');

    $this->app->instance(ClusteringService::class, officeDssClusteringSpy());
    $this->app->instance(IdeaGeneratorService::class, officeDssGeneratorFake('Office Validated Laboratory Idea'));

    $this->actingAs($officeUser)->post(route('feedback.store'), officeDssPayload());

    $feedback = Feedback::query()
        ->where('category', 'Office Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($officeUser)->patch(route('office.review.approve', $feedback->id));

    expect(IdeaEvaluation::query()->count())->toBe(1);

    $this->get(route('feedback.category', ['category' => 'Office Review Facilities']))
        ->assertOk()
        ->assertSeeText('Office Validated Laboratory Idea');

    $this->get(route('feedback.category', ['category' => 'Office Review Facilities']))
        ->assertOk()
        ->assertSeeText('Office Validated Laboratory Idea');

    expect(IdeaEvaluation::query()->count())->toBe(1);
});

test('community problems still generate ideas once three reports each reach ten votes', function (): void {
    Cache::flush();

    $this->app->instance(ClusteringService::class, officeDssClusteringSpy());
    $this->app->instance(IdeaGeneratorService::class, officeDssGeneratorFake('Community Threshold Idea'));

    $reporters = User::factory()->count(3)->create(['role' => 'user']);

    $reporters->each(function (User $reporter, int $index): void {
        $feedback = officeDssFeedback([
            'user_id' => $reporter->id,
            'title' => "Community service window delay number {$index}",
            'description' => "Students experience repeated waiting delays at service window {$index} during enrollment periods.",
            'category' => 'Community Threshold Review',
        ]);

        officeDssAddVotes($feedback, 10);
    });

    $this->get(route('feedback.category', ['category' => 'Community Threshold Review']))
        ->assertOk()
        ->assertSeeText('Community Threshold Idea');

    expect(IdeaEvaluation::query()->count())->toBe(1);
});

test('community problems below three reports still do not generate ideas', function (): void {
    Cache::flush();

    $this->app->instance(ClusteringService::class, officeDssClusteringSpy());
    $this->app->instance(IdeaGeneratorService::class, officeDssGeneratorFake('Should Not Appear Idea'));

    $reporters = User::factory()->count(2)->create(['role' => 'user']);

    $reporters->each(function (User $reporter, int $index): void {
        $feedback = officeDssFeedback([
            'user_id' => $reporter->id,
            'title' => "Community review bottleneck number {$index}",
            'description' => "Students experience repeated waiting delays in review window {$index} during enrollment periods.",
            'category' => 'Community Below Threshold Review',
        ]);

        officeDssAddVotes($feedback, 10);
    });

    $this->get(route('feedback.category', ['category' => 'Community Below Threshold Review']))
        ->assertOk()
        ->assertSeeText('Not Enough Data Yet')
        ->assertDontSeeText('Should Not Appear Idea');

    expect(IdeaEvaluation::query()->count())->toBe(0);
});
