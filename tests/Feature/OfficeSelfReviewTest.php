<?php

use App\Models\CategoryAssignment;
use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\User;
use App\Services\ClusteringService;
use App\Services\IdeaGeneratorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Create an office reviewer for the academic affairs office.
 *
 * @param  array<string, mixed>  $attributes
 */
function selfReviewReviewer(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'office_academic',
        'is_office_head' => true,
        'office_department' => 'CICS',
    ], $attributes));
}

function selfReviewAssignment(string $category): CategoryAssignment
{
    return CategoryAssignment::query()->create([
        'category' => $category,
        'office' => 'office_academic',
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function selfReviewPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Laboratory equipment status is still tracked on paper',
        'category' => 'Self Review Facilities',
        'department' => 'CICS',
        'description' => 'Laboratory personnel inspect every workstation by hand to learn which computers are functional and which units are under maintenance.',
        'impact' => 'Students spend class time locating working computers while staff repeat manual inspections every session.',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ], $overrides);
}

function selfReviewClusteringSpy(): object
{
    return new class
    {
        public ?Collection $processedFeedbacks = null;

        public function group($feedbacks): Collection
        {
            $this->processedFeedbacks = $feedbacks;

            return collect(['self review cluster' => $feedbacks]);
        }

        public function label(string $clusterKey): string
        {
            return 'Self Review Cluster';
        }

        public function explanation(string $clusterKey): string
        {
            return 'Reports share the same institutional problem profile.';
        }
    };
}

function selfReviewGeneratorFake(string $title): object
{
    return new class($title)
    {
        public function __construct(private string $title) {}

        public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore, ?string $clusterKey = null): array
        {
            return [
                'title' => $this->title,
                'description' => 'Generated from an institutionally validated problem using the existing DSS pipeline.',
                'project_name' => 'Self Review Project',
                'concept' => 'Equipment Monitoring System',
                'cluster_key' => $clusterKey ?? 'self_review_cluster',
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

test('a lone office reviewer can approve their own submission', function (): void {
    $reviewer = selfReviewReviewer();
    selfReviewAssignment('Self Review Facilities');

    $this->actingAs($reviewer)->post(route('feedback.store'), selfReviewPayload());

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    expect($feedback->refresh()->status)->toBe('approved')
        ->and($feedback->reviewed_by)->toBe($reviewer->id);
});

test('a reviewer is blocked from approving rejecting or marking their own submission when a peer is available', function (): void {
    $reviewer = selfReviewReviewer();
    selfReviewReviewer();
    selfReviewAssignment('Self Review Facilities');

    $this->actingAs($reviewer)->post(route('feedback.store'), selfReviewPayload());

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertForbidden();

    $this->actingAs($reviewer)
        ->patch(route('office.review.reject', $feedback->id))
        ->assertForbidden();

    $this->actingAs($reviewer)
        ->patch(route('office.review.mark-capstone', $feedback->id))
        ->assertForbidden();

    $feedback->refresh();

    expect($feedback->status)->toBe('pending')
        ->and($feedback->reviewed_by)->toBeNull()
        ->and($feedback->reviewed_at)->toBeNull()
        ->and((bool) $feedback->is_capstone_worthy)->toBeTrue()
        ->and($feedback->capstone_marked_by)->toBe($reviewer->id)
        ->and(IdeaEvaluation::query()->count())->toBe(0);
});

test('the other reviewer can approve the submission and idea generation still runs', function (): void {
    Cache::flush();

    $submitter = selfReviewReviewer();
    $peerReviewer = selfReviewReviewer();
    selfReviewAssignment('Self Review Facilities');

    $clusteringSpy = selfReviewClusteringSpy();
    $this->app->instance(ClusteringService::class, $clusteringSpy);
    $this->app->instance(IdeaGeneratorService::class, selfReviewGeneratorFake('Self Review Peer Approved Idea'));

    $this->actingAs($submitter)->post(route('feedback.store'), selfReviewPayload());

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($peerReviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    $feedback->refresh();

    expect($feedback->status)->toBe('approved')
        ->and($feedback->reviewed_by)->toBe($peerReviewer->id)
        ->and($clusteringSpy->processedFeedbacks)->not->toBeNull();

    $this->assertDatabaseHas('idea_evaluations', [
        'idea_title' => 'Self Review Peer Approved Idea',
        'category' => 'Self Review Facilities',
    ]);
});

test('a reviewer with a different office role does not count as another eligible reviewer', function (): void {
    $reviewer = selfReviewReviewer();
    selfReviewReviewer(['role' => 'office_chief', 'office_department' => 'Registrar']);
    selfReviewAssignment('Self Review Facilities');

    $this->actingAs($reviewer)->post(route('feedback.store'), selfReviewPayload());

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    expect($feedback->refresh()->status)->toBe('approved')
        ->and($feedback->reviewed_by)->toBe($reviewer->id);
});

test('an unverified user with the same role does not count as another eligible reviewer', function (): void {
    $reviewer = selfReviewReviewer();
    selfReviewReviewer(['email_verified_at' => null]);
    selfReviewAssignment('Self Review Facilities');

    $this->actingAs($reviewer)->post(route('feedback.store'), selfReviewPayload());

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    expect($feedback->refresh()->status)->toBe('approved')
        ->and($feedback->reviewed_by)->toBe($reviewer->id);
});

test('an anonymous office head submission is blocked when another reviewer is available', function (): void {
    $reviewer = selfReviewReviewer();
    selfReviewReviewer();
    selfReviewAssignment('Self Review Facilities');

    $this->actingAs($reviewer)->post(route('feedback.store'), selfReviewPayload(['is_anonymous' => '1']));

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    expect($feedback->user_id)->toBeNull()
        ->and($feedback->capstone_marked_by)->toBe($reviewer->id);

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertForbidden();

    expect($feedback->refresh()->status)->toBe('pending');
});

test('an anonymous office head submission can be approved when they are the only reviewer', function (): void {
    $reviewer = selfReviewReviewer();
    selfReviewAssignment('Self Review Facilities');

    $this->actingAs($reviewer)->post(route('feedback.store'), selfReviewPayload(['is_anonymous' => '1']));

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    expect($feedback->refresh()->status)->toBe('approved');
});

test('the review queue replaces the actions with a notice when the reviewer is blocked', function (): void {
    $reviewer = selfReviewReviewer();
    selfReviewReviewer();
    selfReviewAssignment('Self Review Facilities');

    $this->actingAs($reviewer)->post(route('feedback.store'), selfReviewPayload());

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($reviewer)
        ->get(route('office.review.index'))
        ->assertOk()
        ->assertSeeText('This is your own submission. Another reviewer in your office must review it.')
        ->assertDontSeeText('You are the only reviewer for your office, so you can review your own submission.')
        ->assertDontSee(route('office.review.approve', $feedback->id))
        ->assertDontSee(route('office.review.reject', $feedback->id));
});

test('the review queue explains the lone reviewer case and marks the row self-reviewed afterwards', function (): void {
    $reviewer = selfReviewReviewer();
    selfReviewAssignment('Self Review Facilities');

    $this->actingAs($reviewer)->post(route('feedback.store'), selfReviewPayload());

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($reviewer)
        ->get(route('office.review.index'))
        ->assertOk()
        ->assertSeeText('You are the only reviewer for your office, so you can review your own submission.')
        ->assertSee(route('office.review.approve', $feedback->id));

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    $this->actingAs($reviewer)
        ->get(route('office.review.index', ['status' => 'approved']))
        ->assertOk()
        ->assertSeeText('Reviewed by')
        ->assertSeeText('Self-reviewed');
});

test('a non-head office reviewer cannot validate their own submission while a peer is available', function (): void {
    $reviewer = selfReviewReviewer(['is_office_head' => false]);
    selfReviewReviewer();
    selfReviewAssignment('Self Review Facilities');

    $this->actingAs($reviewer)->post(route('feedback.store'), selfReviewPayload());

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    expect((bool) $feedback->is_capstone_worthy)->toBeFalse();

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertForbidden();

    $this->actingAs($reviewer)
        ->patch(route('office.review.mark-capstone', $feedback->id))
        ->assertForbidden();

    $feedback->refresh();

    expect($feedback->status)->toBe('pending')
        ->and((bool) $feedback->is_capstone_worthy)->toBeFalse()
        ->and(IdeaEvaluation::query()->count())->toBe(0);
});

test('peer review and lone self review persist the same dss evaluation for the same problem', function (): void {
    Cache::flush();

    $this->app->instance(ClusteringService::class, selfReviewClusteringSpy());
    $this->app->instance(IdeaGeneratorService::class, selfReviewGeneratorFake('Self Review Comparable Idea'));

    $loneReviewer = selfReviewReviewer();
    selfReviewAssignment('Self Review Lone Category');
    $this->actingAs($loneReviewer)->post(route('feedback.store'), selfReviewPayload([
        'category' => 'Self Review Lone Category',
    ]));

    $loneFeedback = Feedback::query()
        ->where('category', 'Self Review Lone Category')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($loneReviewer)
        ->patch(route('office.review.approve', $loneFeedback->id))
        ->assertSessionHas('success');

    $submitter = selfReviewReviewer();
    $peerReviewer = selfReviewReviewer();
    selfReviewAssignment('Self Review Peer Category');
    $this->actingAs($submitter)->post(route('feedback.store'), selfReviewPayload([
        'category' => 'Self Review Peer Category',
    ]));

    $peerFeedback = Feedback::query()
        ->where('category', 'Self Review Peer Category')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($peerReviewer)
        ->patch(route('office.review.approve', $peerFeedback->id))
        ->assertSessionHas('success');

    $loneFeedback->refresh();
    $peerFeedback->refresh();

    expect($loneFeedback->title)->toBe($peerFeedback->title)
        ->and($loneFeedback->description)->toBe($peerFeedback->description)
        ->and($loneFeedback->frequency)->toBe($peerFeedback->frequency)
        ->and($loneFeedback->affected_users)->toBe($peerFeedback->affected_users)
        ->and($loneFeedback->affected_group)->toBe($peerFeedback->affected_group)
        ->and($loneFeedback->current_process)->toBe($peerFeedback->current_process);

    $selfApproved = IdeaEvaluation::query()->where('category', 'Self Review Lone Category')->firstOrFail();
    $peerApproved = IdeaEvaluation::query()->where('category', 'Self Review Peer Category')->firstOrFail();

    expect(IdeaEvaluation::query()->count())->toBe(2)
        ->and($selfApproved->idea_title)->toBe($peerApproved->idea_title)
        ->and((int) $selfApproved->feasibility)->toBe((int) $peerApproved->feasibility)
        ->and((int) $selfApproved->impact)->toBe((int) $peerApproved->impact)
        ->and((int) $selfApproved->complexity)->toBe((int) $peerApproved->complexity)
        ->and((int) $selfApproved->innovation)->toBe((int) $peerApproved->innovation)
        ->and((float) $selfApproved->overall_score)->toBe((float) $peerApproved->overall_score)
        ->and($selfApproved->recommendation)->toBe($peerApproved->recommendation);
});

test('a head who marks an anonymous student report capstone-worthy is not treated as its submitter', function (): void {
    Cache::flush();

    $head = selfReviewReviewer();
    selfReviewReviewer();
    selfReviewAssignment('Self Review Facilities');

    $this->app->instance(ClusteringService::class, selfReviewClusteringSpy());
    $this->app->instance(IdeaGeneratorService::class, selfReviewGeneratorFake('Self Review Anonymous Student Idea'));

    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)->post(route('feedback.store'), selfReviewPayload(['is_anonymous' => '1']));

    $feedback = Feedback::query()
        ->where('category', 'Self Review Facilities')
        ->latest('id')
        ->firstOrFail();

    expect($feedback->user_id)->toBeNull()
        ->and($feedback->capstone_marked_by)->toBeNull()
        ->and((bool) $feedback->is_capstone_worthy)->toBeFalse();

    $this->actingAs($head)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    $this->actingAs($head)
        ->patch(route('office.review.mark-capstone', $feedback->id))
        ->assertSessionHas('success');

    $feedback->refresh();

    expect((bool) $feedback->is_capstone_worthy)->toBeTrue()
        ->and($feedback->capstone_marked_by)->toBe($head->id);

    $this->actingAs($head)
        ->get(route('office.review.index', ['status' => 'approved']))
        ->assertOk()
        ->assertSeeText('Anonymous')
        ->assertSeeText('Marked as Capstone Idea')
        ->assertDontSeeText('This is your own submission. Another reviewer in your office must review it.')
        ->assertDontSeeText('You are the only reviewer for your office, so you can review your own submission.')
        ->assertDontSeeText('Self-reviewed');

    $this->actingAs($head)
        ->patch(route('office.review.mark-capstone', $feedback->id))
        ->assertRedirect()
        ->assertSessionHas('success');
});
