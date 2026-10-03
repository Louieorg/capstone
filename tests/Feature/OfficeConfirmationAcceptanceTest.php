<?php

use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\OfficeConfirmationRequest;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use App\Services\OfficeConfirmationService;
use Illuminate\Support\Facades\Cache;

/**
 * End-to-end acceptance for the office confirmation workflow.
 *
 * Everything is built through the real pipeline (approved report -> DSS
 * generation -> student request -> representative decision) so provenance,
 * availability and the active-claim constraint all behave as they do in
 * production. Nothing here reinterprets the lifecycle.
 */
function acceptanceOffice(array $attributes = []): Office
{
    return Office::query()->create(array_merge([
        'name' => 'Acceptance Office',
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
        'contact_email' => 'acceptance@example.edu',
        'is_active' => true,
    ], $attributes));
}

function acceptanceOpportunity(Office $office, string $category): IdeaEvaluation
{
    Feedback::query()->create([
        'title' => 'Laboratory equipment availability is tracked manually',
        'description' => 'Laboratory personnel record workstation faults on paper and cannot see availability in real time.',
        'impact' => 'Students lose access to working equipment while faults stay unresolved.',
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
    app(CategoryIdeaGenerationService::class)->generate($category, $office->id, false);

    return IdeaEvaluation::query()
        ->where('category', $category)
        ->where('office_id', $office->id)
        ->latest('id')
        ->firstOrFail();
}

function acceptanceState(IdeaEvaluation $evaluation, ?int $viewerId = null): string
{
    return app(OfficeConfirmationService::class)->stateFor($evaluation, $viewerId);
}

function acceptanceLeader(string $name): User
{
    return User::factory()->create(['role' => 'user', 'name' => $name]);
}

/*
|--------------------------------------------------------------------------
| TEST 1 — complete confirmation flow
|--------------------------------------------------------------------------
*/

test('ACCEPTANCE 1: available -> request -> notify -> queue -> confirm -> taken', function (): void {
    $office = acceptanceOffice(['name' => 'Acceptance Lab Office']);
    $representative = $office->representative;
    $evaluation = acceptanceOpportunity($office, 'Acceptance Lab');
    $leader = acceptanceLeader('Rina Santos');

    // The student sees an available, office-backed opportunity and the
    // consultation instruction before requesting.
    $categoryUrl = route('feedback.category', ['category' => 'Acceptance Lab', 'scope' => 'office']);

    $this->actingAs($leader)->get($categoryUrl)->assertOk()
        ->assertSeeText('AVAILABLE')
        ->assertSeeText('Consult the office representative before requesting confirmation');

    expect(acceptanceState($evaluation))->toBe('available');

    // Student requests confirmation.
    $this->actingAs($leader)
        ->from($categoryUrl)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertRedirect($categoryUrl)
        ->assertSessionHas('success');

    // The current representative is notified, once.
    $notification = $representative->notifications()->sole();
    expect($notification->data['message'])
        ->toContain('Rina Santos requested office confirmation for')
        ->and(OfficeConfirmationRequest::query()->count())->toBe(1);

    // Click-through reaches the representative queue, which lists the request.
    $this->actingAs($representative)
        ->get(route('notifications.redirect', $notification))
        ->assertRedirect(route('office.confirmations.index'));

    $this->actingAs($representative)->get(route('office.confirmations.index'))->assertOk()
        ->assertSeeText($evaluation->idea_title)
        ->assertSeeText('Acceptance Lab Office')
        ->assertSeeText('Rina Santos')
        ->assertSeeText('group leader')
        ->assertSeeText('Confirm')
        ->assertSeeText('Decline');

    // Representative confirms.
    $request = OfficeConfirmationRequest::query()->sole();

    $this->actingAs($representative)
        ->patch(route('office.confirmations.confirm', $request))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($request->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and(acceptanceState($evaluation, $leader->id))->toBe('office-confirmed')
        ->and(acceptanceState($evaluation, User::factory()->create(['role' => 'user'])->id))->toBe('taken');

    // A later group can no longer claim it.
    $latecomer = acceptanceLeader('Late Arrival');

    $this->actingAs($latecomer)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertSessionHasErrors('confirmation');

    expect(OfficeConfirmationRequest::query()->where('requester_user_id', $latecomer->id)->count())->toBe(0);
});

test('ACCEPTANCE 1b: confirming one request makes the competing pending request stale', function (): void {
    $office = acceptanceOffice(['name' => 'Acceptance Contested Office']);
    $representative = $office->representative;
    $evaluation = acceptanceOpportunity($office, 'Acceptance Contested');

    $firstLeader = acceptanceLeader('First Leader');
    $secondLeader = acceptanceLeader('Second Leader');

    // Two groups may hold a pending request before either is confirmed.
    $this->actingAs($firstLeader)->post(route('office.confirmations.store', $evaluation));
    $this->actingAs($secondLeader)->post(route('office.confirmations.store', $evaluation));

    expect(OfficeConfirmationRequest::query()->where('status', OfficeConfirmationRequest::STATUS_PENDING)->count())
        ->toBe(2);

    $winning = OfficeConfirmationRequest::query()
        ->where('requester_user_id', $firstLeader->id)
        ->sole();

    $losing = OfficeConfirmationRequest::query()
        ->where('requester_user_id', $secondLeader->id)
        ->sole();

    $this->actingAs($representative)->patch(route('office.confirmations.confirm', $winning));

    expect($winning->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and($losing->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_STALE);
});

/*
|--------------------------------------------------------------------------
| TEST 2 — decline flow
|--------------------------------------------------------------------------
*/

test('ACCEPTANCE 2: declining returns the opportunity to available for another group', function (): void {
    $office = acceptanceOffice(['name' => 'Acceptance Decline Office']);
    $representative = $office->representative;
    $evaluation = acceptanceOpportunity($office, 'Acceptance Decline');

    $firstLeader = acceptanceLeader('Declined First');
    $this->actingAs($firstLeader)->post(route('office.confirmations.store', $evaluation));

    $request = OfficeConfirmationRequest::query()->sole();

    $this->actingAs($representative)
        ->patch(route('office.confirmations.decline', $request))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($request->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_DECLINED)
        ->and($request->fresh()->active_opportunity_id)->toBeNull()
        ->and(acceptanceState($evaluation))->toBe('available');

    // Another group can now take it, and confirm.
    $secondLeader = acceptanceLeader('Second After Decline');
    $this->actingAs($secondLeader)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertSessionHas('success');

    $secondRequest = OfficeConfirmationRequest::query()
        ->where('requester_user_id', $secondLeader->id)
        ->sole();

    expect(acceptanceState($evaluation, $secondLeader->id))->toBe('available');

    $this->actingAs($representative)->patch(route('office.confirmations.confirm', $secondRequest));

    expect($secondRequest->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and(acceptanceState($evaluation, $secondLeader->id))->toBe('office-confirmed');
});

/*
|--------------------------------------------------------------------------
| TEST 3 — authority
|--------------------------------------------------------------------------
*/

test('ACCEPTANCE 3: only the current office representative may decide', function (): void {
    $office = acceptanceOffice(['name' => 'Acceptance Authority Office']);
    $representative = $office->representative;
    $evaluation = acceptanceOpportunity($office, 'Acceptance Authority');
    $leader = acceptanceLeader('Authority Leader');

    $this->actingAs($leader)->post(route('office.confirmations.store', $evaluation));
    $request = OfficeConfirmationRequest::query()->sole();

    $formerRepresentative = $representative;

    // Every non-representative actor is refused.
    foreach ([
        'normal student' => User::factory()->create(['role' => 'user']),
        'admin' => User::factory()->create(['role' => 'admin']),
        'office_academic reviewer' => User::factory()->create(['role' => 'office_academic']),
        'office_chief reviewer' => User::factory()->create(['role' => 'office_chief']),
        'representative of another office' => acceptanceOffice(['name' => 'Acceptance Other Office'])->representative,
    ] as $label => $actor) {
        $this->actingAs($actor)
            ->patch(route('office.confirmations.confirm', $request))
            ->assertForbidden();

        $this->actingAs($actor)
            ->patch(route('office.confirmations.decline', $request))
            ->assertForbidden();
    }

    // A former representative has lost authority too.
    $office->forceFill([
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
    ])->save();

    $this->actingAs($formerRepresentative)
        ->patch(route('office.confirmations.confirm', $request))
        ->assertForbidden();

    expect($request->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_PENDING);
});

test('ACCEPTANCE 3b: a reviewer who is also the current representative may decide', function (): void {
    $reviewerRepresentative = User::factory()->create(['role' => 'office_academic']);
    $office = acceptanceOffice([
        'name' => 'Acceptance Reviewer Rep Office',
        'representative_user_id' => $reviewerRepresentative->id,
    ]);

    $evaluation = acceptanceOpportunity($office, 'Acceptance Reviewer Rep');
    $leader = acceptanceLeader('Reviewer Rep Leader');

    $this->actingAs($leader)->post(route('office.confirmations.store', $evaluation));
    $request = OfficeConfirmationRequest::query()->sole();

    $this->actingAs($reviewerRepresentative)
        ->get(route('office.confirmations.index'))
        ->assertOk();

    $this->actingAs($reviewerRepresentative)
        ->patch(route('office.confirmations.confirm', $request))
        ->assertRedirect();

    expect($request->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and($request->fresh()->decided_by_user_id)->toBe($reviewerRepresentative->id);
});

/*
|--------------------------------------------------------------------------
| TEST 4 — representative change
|--------------------------------------------------------------------------
*/

test('ACCEPTANCE 4: changing the representative transfers authority immediately', function (): void {
    $userA = acceptanceLeader('Representative A');
    $office = acceptanceOffice(['name' => 'Acceptance Handover Office', 'representative_user_id' => $userA->id]);
    $evaluation = acceptanceOpportunity($office, 'Acceptance Handover');

    $leader = acceptanceLeader('Handover Leader');
    $this->actingAs($leader)->post(route('office.confirmations.store', $evaluation));
    $request = OfficeConfirmationRequest::query()->sole();

    // A acts while A is the representative.
    $this->actingAs($userA)->get(route('office.confirmations.index'))->assertOk();

    $userB = acceptanceLeader('Representative B');
    $office->forceFill(['representative_user_id' => $userB->id])->save();

    // A can no longer queue or decide.
    $this->actingAs($userA)->get(route('office.confirmations.index'))->assertForbidden();
    $this->actingAs($userA)->patch(route('office.confirmations.confirm', $request))->assertForbidden();
    $this->actingAs($userA)->patch(route('office.confirmations.decline', $request))->assertForbidden();

    // B is now the authorised representative.
    $this->actingAs($userB)->get(route('office.confirmations.index'))->assertOk();
    $this->actingAs($userB)->patch(route('office.confirmations.confirm', $request))->assertRedirect();

    expect($request->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and($request->fresh()->decided_by_user_id)->toBe($userB->id);
});

/*
|--------------------------------------------------------------------------
| TEST 5 — inactive office
|--------------------------------------------------------------------------
*/

test('ACCEPTANCE 5: deactivating an office withdraws queue access and blocks deciding', function (): void {
    $office = acceptanceOffice(['name' => 'Acceptance Dormant Office']);
    $representative = $office->representative;
    $evaluation = acceptanceOpportunity($office, 'Acceptance Dormant');

    $leader = acceptanceLeader('Dormant Leader');
    $this->actingAs($leader)->post(route('office.confirmations.store', $evaluation));
    $request = OfficeConfirmationRequest::query()->sole();

    $office->forceFill(['is_active' => false])->save();

    // Queue access is gone.
    $this->actingAs($representative)->get(route('office.confirmations.index'))->assertForbidden();

    // And no decision can be granted while the office is inactive.
    $this->actingAs($representative)
        ->patch(route('office.confirmations.confirm', $request))
        ->assertRedirect();

    expect($request->fresh()->status)->not->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED)
        ->and(acceptanceState($evaluation))->toBe('unavailable');

    // Reactivating restores the current representative's access.
    $office->forceFill(['is_active' => true])->save();

    $this->actingAs($representative)->get(route('office.confirmations.index'))->assertOk();
});

/*
|--------------------------------------------------------------------------
| TEST 6 — multiple offices
|--------------------------------------------------------------------------
*/

test('ACCEPTANCE 6: a representative of two offices sees only their own offices requests', function (): void {
    $representative = acceptanceLeader('Two Office Representative');
    $officeA = acceptanceOffice(['name' => 'Acceptance Office A', 'representative_user_id' => $representative->id]);
    $officeB = acceptanceOffice(['name' => 'Acceptance Office B', 'representative_user_id' => $representative->id]);
    $officeC = acceptanceOffice(['name' => 'Acceptance Office C']);

    $evaluationA = acceptanceOpportunity($officeA, 'Acceptance Multi A');
    $evaluationB = acceptanceOpportunity($officeB, 'Acceptance Multi B');
    $evaluationC = acceptanceOpportunity($officeC, 'Acceptance Multi C');

    $this->actingAs(acceptanceLeader('Multi Leader A'))->post(route('office.confirmations.store', $evaluationA));
    $this->actingAs(acceptanceLeader('Multi Leader B'))->post(route('office.confirmations.store', $evaluationB));
    $this->actingAs(acceptanceLeader('Multi Leader C'))->post(route('office.confirmations.store', $evaluationC));

    $this->actingAs($representative)->get(route('office.confirmations.index'))->assertOk()
        ->assertSeeText('Acceptance Office A')
        ->assertSeeText('Acceptance Office B')
        ->assertSeeText($evaluationA->idea_title)
        ->assertSeeText($evaluationB->idea_title)
        ->assertDontSeeText('Acceptance Office C')
        ->assertDontSeeText($evaluationC->idea_title);
});

/*
|--------------------------------------------------------------------------
| TEST 7 — notification duplication
|--------------------------------------------------------------------------
*/

test('ACCEPTANCE 7: repeated requests reuse the pending request and notify once', function (): void {
    $office = acceptanceOffice(['name' => 'Acceptance Repeat Office']);
    $representative = $office->representative;
    $evaluation = acceptanceOpportunity($office, 'Acceptance Repeat');
    $leader = acceptanceLeader('Repeat Leader');

    $this->actingAs($leader)->post(route('office.confirmations.store', $evaluation));
    $this->actingAs($leader)->post(route('office.confirmations.store', $evaluation));
    $this->actingAs($leader)->post(route('office.confirmations.store', $evaluation));

    expect(OfficeConfirmationRequest::query()->count())->toBe(1)
        ->and($representative->notifications()->count())->toBe(1);
});
