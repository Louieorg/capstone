<?php

use App\Models\CategoryAssignment;
use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\OfficeConfirmationRequest;
use App\Models\SavedIdea;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use App\Services\OfficeConfirmationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

function confirmationTestOffice(array $attributes = []): Office
{
    $representativeUserId = $attributes['representative_user_id'] ?? null;

    if ($representativeUserId === null) {
        $representative = User::factory()->create(array_merge([
            'role' => 'user',
            'name' => 'Official Office Representative',
            'email' => fake()->unique()->safeEmail(),
        ], $attributes['representative'] ?? []));
        $representativeUserId = $representative->id;
    }

    return Office::query()->create(array_merge([
        'name' => 'Confirmation Test Office',
        'representative_user_id' => $representativeUserId,
        'contact_email' => 'office-contact@example.edu',
        'is_active' => true,
    ], collect($attributes)->except('representative')->all()));
}

function confirmationTestEvaluation(Office $office, string $category): IdeaEvaluation
{
    Feedback::query()->create([
        'title' => 'Office laboratory reports are tracked manually',
        'description' => 'Laboratory personnel manually inspect equipment and record each workstation issue on paper.',
        'impact' => 'Students lose access to working equipment while staff repeat manual inspections.',
        'category' => $category,
        'office_id' => $office->id,
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students', 'Staff'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
        'is_capstone_worthy' => true,
        'capstone_marked_at' => now(),
    ]);

    Cache::flush();
    $generated = app(CategoryIdeaGenerationService::class)->generate($category, $office->id, false);

    expect($generated['qualifying'])->toBeTrue();

    return IdeaEvaluation::query()
        ->where('category', $category)
        ->where('office_id', $office->id)
        ->latest('id')
        ->firstOrFail();
}

function requestOfficeConfirmation(User $requester, IdeaEvaluation $evaluation): OfficeConfirmationRequest
{
    test()->actingAs($requester)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertRedirect();

    return OfficeConfirmationRequest::query()
        ->where('requester_user_id', $requester->id)
        ->where('idea_evaluation_id', $evaluation->id)
        ->latest('id')
        ->firstOrFail();
}

test('office opportunity display uses official office identity and never the representative login email', function (): void {
    $representative = User::factory()->create([
        'role' => 'user',
        'name' => 'Display Representative',
        'email' => 'private-login@example.test',
    ]);
    $office = confirmationTestOffice([
        'name' => 'Official Display Office',
        'representative_user_id' => $representative->id,
        'contact_email' => 'official-contact@example.edu',
    ]);
    $evaluation = confirmationTestEvaluation($office, 'Office Confirmation Display');

    $this->get(route('feedback.category', $evaluation->category))
        ->assertOk()
        ->assertSeeText('Official Display Office')
        ->assertSeeText('Display Representative')
        ->assertSeeText('official-contact@example.edu')
        ->assertSeeText('AVAILABLE')
        ->assertSeeText('Consult the office representative before requesting confirmation')
        ->assertDontSeeText('private-login@example.test');
});

test('community opportunity remains visible without office confirmation details', function (): void {
    Feedback::query()->create([
        'title' => 'Community equipment reports are tracked manually',
        'description' => 'Students manually report equipment issues and receive no progress updates.',
        'impact' => 'Students wait for repairs because they cannot follow the reports.',
        'category' => 'Community Confirmation Display',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
        'is_capstone_worthy' => true,
        'capstone_marked_at' => now(),
    ]);

    Cache::flush();

    $this->get(route('feedback.category', 'Community Confirmation Display'))
        ->assertOk()
        ->assertSeeText('Community Confirmation Display')
        ->assertDontSeeText('AVAILABLE')
        ->assertDontSeeText('Request office confirmation')
        ->assertDontSeeText('Official contact:');
});

test('requesting confirmation does not reserve an opportunity and multiple group leaders may request it', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Multiple Pending Confirmation');
    $leaderA = User::factory()->create(['role' => 'user']);
    $leaderB = User::factory()->create(['role' => 'user']);

    $requestA = requestOfficeConfirmation($leaderA, $evaluation);
    $requestB = requestOfficeConfirmation($leaderB, $evaluation);

    expect($requestA->status)->toBe(OfficeConfirmationRequest::STATUS_PENDING)
        ->and($requestB->status)->toBe(OfficeConfirmationRequest::STATUS_PENDING)
        ->and($requestA->active_opportunity_id)->toBeNull()
        ->and($requestB->active_opportunity_id)->toBeNull()
        ->and($requestA->requester_user_id)->toBe($leaderA->id)
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $leaderA->id))->toBe('available');
});

test('non-student accounts cannot request office confirmation', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Student Requester Only');
    $reviewer = User::factory()->create(['role' => 'office_academic']);

    $this->actingAs($reviewer)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertForbidden();

    expect(OfficeConfirmationRequest::query()->count())->toBe(0);
});

test('only the current representative can confirm and other students see the opportunity as taken', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Confirmed Office Opportunity');
    $leader = User::factory()->create(['role' => 'user']);
    $otherStudent = User::factory()->create(['role' => 'user']);
    $confirmationRequest = requestOfficeConfirmation($leader, $evaluation);
    $representative = $office->representative;

    $this->actingAs($representative)
        ->patch(route('office.confirmations.confirm', $confirmationRequest))
        ->assertRedirect();

    expect($confirmationRequest->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and($confirmationRequest->fresh()->active_opportunity_id)->toBe($evaluation->id)
        ->and($confirmationRequest->fresh()->decided_by_user_id)->toBe($representative->id)
        ->and($confirmationRequest->fresh()->decision_at)->not->toBeNull()
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $leader->id))->toBe('office-confirmed')
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $otherStudent->id))->toBe('taken');

    $this->actingAs($leader)
        ->get(route('feedback.category', $evaluation->category))
        ->assertOk()
        ->assertSeeText('OFFICE-CONFIRMED');

    $this->actingAs($otherStudent)
        ->get(route('feedback.category', $evaluation->category))
        ->assertOk()
        ->assertSeeText('TAKEN');
});

test('the first confirmation stales competing pending requests and the second cannot confirm', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Competing Confirmation Requests');
    $leaderA = User::factory()->create(['role' => 'user']);
    $leaderB = User::factory()->create(['role' => 'user']);
    $requestA = requestOfficeConfirmation($leaderA, $evaluation);
    $requestB = requestOfficeConfirmation($leaderB, $evaluation);

    $this->actingAs($office->representative)
        ->patch(route('office.confirmations.confirm', $requestA))
        ->assertRedirect();

    $this->actingAs($office->representative)
        ->patch(route('office.confirmations.confirm', $requestB))
        ->assertRedirect();

    expect($requestA->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and($requestA->fresh()->active_opportunity_id)->toBe($evaluation->id)
        ->and($requestB->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_STALE)
        ->and($requestB->fresh()->active_opportunity_id)->toBeNull()
        ->and(OfficeConfirmationRequest::query()->where('active_opportunity_id', $evaluation->id)->count())->toBe(1);
});

test('representatives cannot decide for another office and reviewer or admin roles do not grant authority', function (): void {
    $officeA = confirmationTestOffice(['name' => 'Authority Office A']);
    $officeB = confirmationTestOffice(['name' => 'Authority Office B']);
    $evaluation = confirmationTestEvaluation($officeB, 'Cross Office Confirmation');
    $leader = User::factory()->create(['role' => 'user']);
    $confirmationRequest = requestOfficeConfirmation($leader, $evaluation);

    $this->actingAs($officeA->representative)
        ->patch(route('office.confirmations.confirm', $confirmationRequest))
        ->assertForbidden();

    $reviewer = User::factory()->create(['role' => 'office_academic', 'is_office_head' => true]);
    $admin = User::factory()->create(['role' => 'admin']);
    CategoryAssignment::query()->create([
        'category' => $evaluation->category,
        'office' => 'office_academic',
    ]);

    $this->actingAs($reviewer)
        ->patch(route('office.confirmations.confirm', $confirmationRequest))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('office.confirmations.confirm', $confirmationRequest))
        ->assertForbidden();

    expect($confirmationRequest->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_PENDING);
});

test('the current representative can decline and the opportunity remains available', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Declined Office Opportunity');
    $leader = User::factory()->create(['role' => 'user']);
    $confirmationRequest = requestOfficeConfirmation($leader, $evaluation);

    $this->actingAs($office->representative)
        ->patch(route('office.confirmations.decline', $confirmationRequest))
        ->assertRedirect();

    expect($confirmationRequest->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_DECLINED)
        ->and($confirmationRequest->fresh()->active_opportunity_id)->toBeNull()
        ->and($confirmationRequest->fresh()->decided_by_user_id)->toBe($office->representative_user_id)
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $leader->id))->toBe('available');
});

test('a confirmed opportunity stales when its source evidence changes and releases its claim without deleting history', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Stale Office Opportunity');
    $leader = User::factory()->create(['role' => 'user']);
    $confirmationRequest = requestOfficeConfirmation($leader, $evaluation);

    $this->actingAs($office->representative)
        ->patch(route('office.confirmations.confirm', $confirmationRequest))
        ->assertRedirect();

    Feedback::query()->whereKey($evaluation->office_source_feedback_ids[0])->update([
        'description' => 'The office has replaced the original paper process with a different workflow.',
    ]);

    expect(app(OfficeConfirmationService::class)->stateFor($evaluation->fresh(), $leader->id))->toBe('unavailable')
        ->and($confirmationRequest->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_STALE)
        ->and($confirmationRequest->fresh()->decision_outcome)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and($confirmationRequest->fresh()->active_opportunity_id)->toBeNull()
        ->and($confirmationRequest->fresh()->decided_by_user_id)->toBe($office->representative_user_id)
        ->and(OfficeConfirmationRequest::query()->whereKey($confirmationRequest->id)->exists())->toBeTrue();
});

test('office deactivation stales a confirmation and reactivation does not restore the active claim', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Inactive Office Opportunity');
    $leader = User::factory()->create(['role' => 'user']);
    $confirmationRequest = requestOfficeConfirmation($leader, $evaluation);

    $this->actingAs($office->representative)
        ->patch(route('office.confirmations.confirm', $confirmationRequest))
        ->assertRedirect();

    $office->update(['is_active' => false]);

    expect(app(OfficeConfirmationService::class)->stateFor($evaluation->fresh(), $leader->id))->toBe('unavailable')
        ->and($confirmationRequest->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_STALE)
        ->and($confirmationRequest->fresh()->active_opportunity_id)->toBeNull();

    $office->update(['is_active' => true]);

    expect(app(OfficeConfirmationService::class)->stateFor($evaluation->fresh(), $leader->id))->toBe('available')
        ->and($confirmationRequest->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_STALE)
        ->and($confirmationRequest->fresh()->active_opportunity_id)->toBeNull();
});

test('a saved idea and its student adopted status remain independent from office confirmation', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Saved Office Opportunity');
    $leader = User::factory()->create(['role' => 'user']);
    $savedIdea = SavedIdea::query()->create([
        'user_id' => $leader->id,
        'title' => $evaluation->idea_title,
        'description' => 'A personal bookmark independent from office agreement.',
        'category' => $evaluation->category,
        'status' => 'Adopted',
        'idea_evaluation_id' => $evaluation->id,
    ]);

    expect(app(OfficeConfirmationService::class)->stateFor($evaluation, $leader->id))->toBe('available')
        ->and(OfficeConfirmationRequest::query()->count())->toBe(0);

    $confirmationRequest = requestOfficeConfirmation($leader, $evaluation);
    $this->actingAs($office->representative)
        ->patch(route('office.confirmations.confirm', $confirmationRequest))
        ->assertRedirect();

    expect($savedIdea->fresh()->status)->toBe('Adopted')
        ->and(SavedIdea::query()->whereKey($savedIdea->id)->exists())->toBeTrue();
});

test('saving an office opportunity does not reserve it or create a confirmation request', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Save Without Reservation');
    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)
        ->post(route('idea.save'), [
            'title' => $evaluation->idea_title,
            'description' => 'A personal bookmark for this office-backed opportunity.',
            'category' => $evaluation->category,
            'office_id' => $office->id,
        ])
        ->assertSessionHasNoErrors();

    expect(SavedIdea::query()->where('user_id', $student->id)->count())->toBe(1)
        ->and(OfficeConfirmationRequest::query()->count())->toBe(0)
        ->and(app(OfficeConfirmationService::class)->stateFor($evaluation, $student->id))->toBe('available');
});

test('a stale pending request cannot be confirmed after its source evidence changes', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Stale Pending Opportunity');
    $leader = User::factory()->create(['role' => 'user']);
    $confirmationRequest = requestOfficeConfirmation($leader, $evaluation);

    Feedback::query()->whereKey($evaluation->office_source_feedback_ids[0])->update([
        'impact' => 'The original institutional impact no longer describes the current problem.',
    ]);

    $this->actingAs($office->representative)
        ->patch(route('office.confirmations.confirm', $confirmationRequest))
        ->assertRedirect();

    expect($confirmationRequest->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_STALE)
        ->and($confirmationRequest->fresh()->active_opportunity_id)->toBeNull()
        ->and(OfficeConfirmationRequest::query()->whereNotNull('active_opportunity_id')->count())->toBe(0);
});

test('the unique active claim column prevents two requests from claiming one evaluation', function (): void {
    $office = confirmationTestOffice();
    $evaluation = confirmationTestEvaluation($office, 'Unique Claim Opportunity');
    $firstRequester = User::factory()->create(['role' => 'user']);
    $secondRequester = User::factory()->create(['role' => 'user']);
    $request = requestOfficeConfirmation($firstRequester, $evaluation);
    $request->update([
        'status' => OfficeConfirmationRequest::STATUS_CONFIRMED,
        'active_opportunity_id' => $evaluation->id,
    ]);

    expect(fn () => OfficeConfirmationRequest::query()->create([
        'requester_user_id' => $secondRequester->id,
        'idea_evaluation_id' => $evaluation->id,
        'office_id' => $office->id,
        'status' => OfficeConfirmationRequest::STATUS_CONFIRMED,
        'decision_outcome' => OfficeConfirmationRequest::STATUS_CONFIRMED,
        'decided_by_user_id' => $office->representative_user_id,
        'decision_at' => now(),
        'office_cluster_key' => $evaluation->office_cluster_key,
        'source_feedback_ids' => $evaluation->office_source_feedback_ids,
        'provenance_fingerprint' => $evaluation->office_provenance_fingerprint,
        'active_opportunity_id' => $evaluation->id,
    ]))->toThrow(QueryException::class);
});

test('canonical office identity is unique per office while community NULL evaluations remain unrestricted', function (): void {
    $officeA = confirmationTestOffice(['name' => 'Identity Office A']);
    $officeB = confirmationTestOffice(['name' => 'Identity Office B']);
    $attributes = [
        'idea_title' => 'Shared Canonical Opportunity',
        'category' => 'Canonical Identity Category',
    ];

    IdeaEvaluation::query()->create($attributes + ['office_id' => $officeA->id]);

    expect(fn () => IdeaEvaluation::query()->create($attributes + ['office_id' => $officeA->id]))
        ->toThrow(QueryException::class);

    IdeaEvaluation::query()->create($attributes + ['office_id' => $officeB->id]);
    IdeaEvaluation::query()->create($attributes + ['office_id' => null]);
    IdeaEvaluation::query()->create($attributes + ['office_id' => null]);

    expect(IdeaEvaluation::query()->where('idea_title', $attributes['idea_title'])->count())->toBe(4);
});

test('representative reassignment changes future authority but preserves the historical confirmer', function (): void {
    $office = confirmationTestOffice();
    $originalRepresentative = $office->representative;
    $evaluationA = confirmationTestEvaluation($office, 'Original Representative Category');
    $leaderA = User::factory()->create(['role' => 'user']);
    $confirmationA = requestOfficeConfirmation($leaderA, $evaluationA);

    $this->actingAs($originalRepresentative)
        ->patch(route('office.confirmations.confirm', $confirmationA))
        ->assertRedirect();

    $newRepresentative = User::factory()->create(['role' => 'adviser']);
    $office->update(['representative_user_id' => $newRepresentative->id]);
    $evaluationB = confirmationTestEvaluation($office->fresh(), 'New Representative Category');
    $leaderB = User::factory()->create(['role' => 'user']);
    $confirmationB = requestOfficeConfirmation($leaderB, $evaluationB);

    $this->actingAs($originalRepresentative)
        ->patch(route('office.confirmations.confirm', $confirmationB))
        ->assertForbidden();

    $this->actingAs($newRepresentative)
        ->patch(route('office.confirmations.confirm', $confirmationB))
        ->assertRedirect();

    expect($confirmationA->fresh()->decided_by_user_id)->toBe($originalRepresentative->id)
        ->and($confirmationA->fresh()->decision_outcome)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and($confirmationB->fresh()->decided_by_user_id)->toBe($newRepresentative->id)
        ->and($confirmationB->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED);
});

test('community evaluations cannot create office confirmation requests', function (): void {
    $leader = User::factory()->create(['role' => 'user']);
    $evaluation = IdeaEvaluation::query()->create([
        'idea_title' => 'Community Only Opportunity',
        'category' => 'Community Only Category',
        'office_id' => null,
    ]);

    $this->actingAs($leader)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertRedirect()
        ->assertSessionHasErrors('confirmation');

    expect(OfficeConfirmationRequest::query()->count())->toBe(0);
});

test('a representative without the reviewer role can access only their own pending requests', function (): void {
    $officeA = confirmationTestOffice(['name' => 'Queue Office A']);
    $officeB = confirmationTestOffice(['name' => 'Queue Office B']);
    $evaluationA = confirmationTestEvaluation($officeA, 'Queue Category A');
    $evaluationB = confirmationTestEvaluation($officeB, 'Queue Category B');
    $leader = User::factory()->create(['role' => 'user']);
    requestOfficeConfirmation($leader, $evaluationA);
    requestOfficeConfirmation($leader, $evaluationB);

    $this->actingAs($officeA->representative)
        ->get(route('office.confirmations.index'))
        ->assertOk()
        ->assertSeeText($evaluationA->idea_title)
        ->assertSeeText($leader->name)
        ->assertDontSeeText($officeB->name);
});
