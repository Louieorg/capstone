<?php

namespace Tests\Feature;

use App\Models\CategoryAssignment;
use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\OfficeConfirmationRequest;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use App\Services\IdeaGeneratorService;
use App\Services\OfficeConfirmationService;
use App\Services\OfficeSubmissionQualificationService;
use Illuminate\Support\Facades\Cache;

function representativeOfficeDssGeneratorFake(string $title): object
{
    return new class($title)
    {
        public function __construct(private string $title) {}

        public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore, ?string $clusterKey = null): array
        {
            return [
                'title' => $this->title,
                'description' => 'Generated from an office representative report using the existing DSS pipeline.',
                'project_name' => 'Representative Office Project',
                'concept' => 'Office Equipment Monitoring',
                'cluster_key' => $clusterKey ?? 'office_dss_cluster',
                'general_objective' => 'To improve the represented office service.',
                'specific_objectives' => ['To reuse the existing DSS generation pipeline.'],
                'explanation' => [
                    'summary' => 'Generated from the eligible office report.',
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

function representativeOfficePayload(Office $office, array $overrides = []): array
{
    return array_merge([
        'title' => 'Laboratory equipment maintenance requests are tracked manually',
        'category' => 'Office Representative Facilities',
        'department' => 'CICS',
        'description' => 'Laboratory personnel record every equipment maintenance request by hand and there is no centralized request status.',
        'impact' => 'Students wait for equipment that is under maintenance because no request status is published.',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students', 'Staff'],
        'office_id' => $office->id,
        'force_submit' => '1',
    ], $overrides);
}

test('a current office representative submission qualifies without community votes', function (): void {
    Cache::flush();

    $representative = User::factory()->create(['role' => 'user']);
    $office = Office::query()->create([
        'name' => 'Representative Qualification Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);

    $this->actingAs($representative)
        ->post(route('feedback.store'), representativeOfficePayload($office))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()->where('category', 'Office Representative Facilities')->latest('id')->firstOrFail();

    expect($feedback->office_id)->toBe($office->id)
        ->and((bool) $feedback->is_capstone_worthy)->toBeTrue()
        ->and($feedback->capstone_marked_by)->toBe($representative->id)
        ->and($feedback->capstone_marked_at)->not->toBeNull()
        ->and($feedback->votes()->count())->toBe(0);
});

test('a user who does not represent the selected office cannot associate a submission with it', function (): void {
    Cache::flush();

    $representative = User::factory()->create(['role' => 'user']);
    $office = Office::query()->create([
        'name' => 'Other Representative Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);
    $otherUser = User::factory()->create(['role' => 'user']);

    $this->actingAs($otherUser)
        ->post(route('feedback.store'), representativeOfficePayload($office, [
            'title' => 'Tampered office report attempt',
        ]))
        ->assertSessionHasErrors('office_id');

    expect(Feedback::query()->where('title', 'Tampered office report attempt')->exists())->toBeFalse()
        ->and(app(OfficeSubmissionQualificationService::class)
            ->isRepresentativeSubmission($otherUser, $office->id))->toBeFalse();
});

test('a representative cannot qualify a report against an office they no longer represent', function (): void {
    Cache::flush();

    $representative = User::factory()->create(['role' => 'user']);
    $successor = User::factory()->create(['role' => 'user']);
    $office = Office::query()->create([
        'name' => 'Reassigned Representative Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);

    $this->actingAs($representative)
        ->post(route('feedback.store'), representativeOfficePayload($office))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()->where('category', 'Office Representative Facilities')->latest('id')->firstOrFail();
    $office->update(['representative_user_id' => $successor->id]);
    $feedback->update(['is_capstone_worthy' => false, 'capstone_marked_by' => null, 'capstone_marked_at' => null, 'status' => 'approved']);

    expect(app(OfficeSubmissionQualificationService::class)->qualifies($feedback->fresh()))->toBeFalse()
        ->and(app(OfficeSubmissionQualificationService::class)->qualify($feedback->fresh()))->toBeFalse()
        ->and($feedback->fresh()->is_capstone_worthy)->toBeFalse();
});

test('an inactive office cannot qualify a representative submission', function (): void {
    Cache::flush();

    $representative = User::factory()->create(['role' => 'user']);
    $office = Office::query()->create([
        'name' => 'Deactivated Office',
        'representative_user_id' => $representative->id,
        'is_active' => false,
    ]);

    expect(app(OfficeSubmissionQualificationService::class)
        ->isRepresentativeSubmission($representative, $office->id))->toBeFalse();

    $this->actingAs($representative)
        ->post(route('feedback.store'), representativeOfficePayload($office))
        ->assertSessionHasErrors('office_id');

    expect(Feedback::query()->where('category', 'Office Representative Facilities')->exists())->toBeFalse();
});

test('representing an office grants no authority over an unassigned category', function (): void {
    Cache::flush();

    $representative = User::factory()->create(['role' => 'user']);
    $office = Office::query()->create([
        'name' => 'Authority Scope Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);

    expect(app(OfficeSubmissionQualificationService::class)
        ->isRepresentativeSubmission($representative, $office->id))->toBeTrue()
        ->and($representative->reviewableCategories())->toBe([])
        ->and($representative->isOfficeReviewer())->toBeFalse();

    $this->actingAs($representative)
        ->post(route('feedback.store'), representativeOfficePayload($office, [
            'category' => 'Unassigned Authority Category',
        ]))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()->where('category', 'Unassigned Authority Category')->latest('id')->firstOrFail();

    $this->actingAs($representative)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertForbidden();

    expect(IdeaEvaluation::query()->where('category', 'Unassigned Authority Category')->count())->toBe(0);
});

test('a community submission with a null office keeps the community qualification path', function (): void {
    Cache::flush();

    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)->post(route('feedback.store'), [
        'title' => 'Community laboratory equipment request status is unknown',
        'category' => 'Community Representative Facilities',
        'description' => 'Students manually follow up on laboratory equipment maintenance requests because no request status is published.',
        'impact' => 'Students repeat follow ups while equipment remains under maintenance.',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ])->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()->where('category', 'Community Representative Facilities')->latest('id')->firstOrFail();

    expect($feedback->office_id)->toBeNull()
        ->and((bool) $feedback->is_capstone_worthy)->toBeFalse()
        ->and($feedback->capstone_marked_by)->toBeNull()
        ->and(app(OfficeSubmissionQualificationService::class)->qualifies($feedback))->toBeFalse();

    $generated = app(CategoryIdeaGenerationService::class)->generate('Community Representative Facilities');

    expect($generated['qualifying'])->toBeFalse()
        ->and(IdeaEvaluation::query()->where('category', 'Community Representative Facilities')->count())->toBe(0);
});

test('an approved office representative report generates an office scoped idea evaluation', function (): void {
    Cache::flush();

    $representative = User::factory()->create(['role' => 'user']);
    $admin = User::factory()->create(['role' => 'admin']);
    $office = Office::query()->create([
        'name' => 'Generation Scope Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);

    $this->app->instance(IdeaGeneratorService::class, representativeOfficeDssGeneratorFake('Representative Office Laboratory Idea'));

    $this->actingAs($representative)
        ->post(route('feedback.store'), representativeOfficePayload($office))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()->where('category', 'Office Representative Facilities')->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('feedback.approve', $feedback->id))
        ->assertSessionHas('success');

    expect($feedback->fresh()->status)->toBe('approved')
        ->and((bool) $feedback->fresh()->is_flagged)->toBeFalse();

    $evaluation = IdeaEvaluation::query()
        ->where('idea_title', 'Representative Office Laboratory Idea')
        ->firstOrFail();

    expect($evaluation->office_id)->toBe($office->id)
        ->and($evaluation->office_source_feedback_ids)->toContain($feedback->id)
        ->and($evaluation->office_cluster_key)->not->toBeNull()
        ->and($evaluation->office_provenance_fingerprint)->not->toBeNull();
});

test('office scoped supporting evidence resolves the actual source report', function (): void {
    Cache::flush();

    $representative = User::factory()->create(['role' => 'user']);
    $office = Office::query()->create([
        'name' => 'Evidence Scope Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);

    $this->app->instance(IdeaGeneratorService::class, representativeOfficeDssGeneratorFake('Evidence Office Laboratory Idea'));

    $this->actingAs($representative)
        ->post(route('feedback.store'), representativeOfficePayload($office))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()->where('category', 'Office Representative Facilities')->latest('id')->firstOrFail();
    $feedback->update(['status' => 'approved']);

    Cache::flush();

    $generated = app(CategoryIdeaGenerationService::class)->generate('Office Representative Facilities');

    expect($generated['qualifying'])->toBeTrue();

    $evaluation = IdeaEvaluation::query()
        ->where('category', 'Office Representative Facilities')
        ->whereNotNull('office_id')
        ->firstOrFail();

    expect($evaluation->office_source_feedback_ids)->toBe([$feedback->id]);

    $response = $this->get(route('feedback.category', [
        'category' => 'Office Representative Facilities',
        'scope' => 'office',
    ]));

    $response->assertOk()
        ->assertSeeText('1 report')
        ->assertSeeText('Laboratory personnel record every equipment maintenance request by hand and there is no centralized request status.');

    expect($response->viewData('feedbacks')->pluck('id')->all())->toBe([$feedback->id]);
});

test('office confirmation semantics are unchanged by office dss qualification', function (): void {
    Cache::flush();

    $representative = User::factory()->create(['role' => 'user']);
    $office = Office::query()->create([
        'name' => 'Confirmation Semantics Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);

    $this->app->instance(IdeaGeneratorService::class, representativeOfficeDssGeneratorFake('Confirmation Semantics Idea'));

    $this->actingAs($representative)
        ->post(route('feedback.store'), representativeOfficePayload($office))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()->where('category', 'Office Representative Facilities')->latest('id')->firstOrFail();
    $feedback->update(['status' => 'approved']);

    Cache::flush();

    app(CategoryIdeaGenerationService::class)->generate('Office Representative Facilities');

    $evaluation = IdeaEvaluation::query()
        ->where('idea_title', 'Confirmation Semantics Idea')
        ->firstOrFail();

    $otherStudent = User::factory()->create(['role' => 'user']);

    expect($evaluation->office_id)->toBe($office->id)
        ->and(OfficeConfirmationRequest::query()->count())->toBe(0)
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $representative->id))->toBe('available')
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $otherStudent->id))->toBe('available');

    $this->get(route('feedback.category', [
        'category' => 'Office Representative Facilities',
        'scope' => 'office',
    ]))->assertOk()
        ->assertSeeText('AVAILABLE')
        ->assertSeeText('Request office confirmation');

    $this->actingAs($otherStudent)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertRedirect();

    $request = OfficeConfirmationRequest::query()->latest('id')->firstOrFail();

    expect($request->status)->toBe(OfficeConfirmationRequest::STATUS_PENDING)
        ->and($request->active_opportunity_id)->toBeNull()
        ->and($request->office_id)->toBe($office->id)
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $otherStudent->id))->toBe('available');

    $this->actingAs($otherStudent)
        ->patch(route('office.confirmations.confirm', $request))
        ->assertForbidden();

    expect($request->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_PENDING)
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $otherStudent->id))->toBe('available');

    $this->actingAs($representative)
        ->patch(route('office.confirmations.confirm', $request))
        ->assertRedirect();

    $unrelatedStudent = User::factory()->create(['role' => 'user']);

    expect($request->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and($request->fresh()->active_opportunity_id)->toBe($evaluation->id)
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $otherStudent->id))->toBe('office-confirmed')
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $unrelatedStudent->id))->toBe('taken');
});

test('office category assignment authority rules still govern office review actions', function (): void {
    Cache::flush();

    $reviewer = User::factory()->create([
        'role' => 'office_academic',
        'is_office_head' => true,
    ]);
    $representative = User::factory()->create(['role' => 'user']);
    $office = Office::query()->create([
        'name' => 'Review Authority Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);

    $this->actingAs($representative)
        ->post(route('feedback.store'), representativeOfficePayload($office))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()->where('category', 'Office Representative Facilities')->latest('id')->firstOrFail();

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertForbidden();

    expect(IdeaEvaluation::query()->where('category', 'Office Representative Facilities')->count())->toBe(0);

    CategoryAssignment::query()->create([
        'category' => 'Office Representative Facilities',
        'office' => 'office_academic',
    ]);

    $this->actingAs($reviewer)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertSessionHas('success');

    expect($feedback->fresh()->status)->toBe('approved')
        ->and(IdeaEvaluation::query()
            ->where('category', 'Office Representative Facilities')
            ->whereNotNull('office_id')
            ->where('office_id', $office->id)
            ->count())->toBe(1);
});
