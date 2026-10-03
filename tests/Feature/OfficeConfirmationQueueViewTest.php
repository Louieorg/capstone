<?php

use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use Illuminate\Support\Facades\Cache;

/**
 * The confirmation queue has to read as an office representative's worklist:
 * every card states the opportunity, who asked, which office it belongs to,
 * when it was asked, and offers the existing Confirm / Decline actions.
 *
 * Requests are created through the real student flow so the office provenance
 * snapshot matches; the queue legitimately hides anything it marks stale.
 */
function queueViewOffice(array $attributes = []): Office
{
    return Office::query()->create(array_merge([
        'name' => 'Queue View Office',
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
        'contact_email' => 'queue-view@example.edu',
        'is_active' => true,
    ], $attributes));
}

function queueViewOpportunity(Office $office, string $category): IdeaEvaluation
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

/**
 * Ask for confirmation the way a student does, so the row is genuinely pending.
 */
function queueViewRequest(IdeaEvaluation $evaluation): void
{
    $leader = User::factory()->create(['role' => 'user']);

    test()
        ->actingAs($leader)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertRedirect();
}

it('shows the opportunity, requester, office, date and both actions on every card', function (): void {
    $office = queueViewOffice(['name' => 'GCC Office']);
    $representative = $office->representative;
    $evaluation = queueViewOpportunity($office, 'Queue View Facilities');

    queueViewRequest($evaluation);

    $response = $this->actingAs($representative)
        ->get(route('office.confirmations.index'))
        ->assertOk()
        ->assertSeeText('Confirmation Requests')
        ->assertSeeText($evaluation->idea_title)
        ->assertSeeText('GCC Office')
        ->assertSeeText('Queue View Facilities')
        ->assertSeeText('group leader')
        ->assertSeeText('Confirm')
        ->assertSeeText('Decline');

    $pending = \App\Models\OfficeConfirmationRequest::query()->sole();

    $response->assertSee(route('office.confirmations.confirm', $pending), false)
        ->assertSee(route('office.confirmations.decline', $pending), false);
});

it('attributes each card to its own office when several are represented', function (): void {
    $representative = User::factory()->create(['role' => 'user']);
    $officeA = queueViewOffice(['name' => 'Queue Office Alpha', 'representative_user_id' => $representative->id]);
    $officeB = queueViewOffice(['name' => 'Queue Office Beta', 'representative_user_id' => $representative->id]);

    $evaluationA = queueViewOpportunity($officeA, 'Queue View Alpha');
    $evaluationB = queueViewOpportunity($officeB, 'Queue View Beta');
    queueViewRequest($evaluationA);
    queueViewRequest($evaluationB);

    $this->actingAs($representative)
        ->get(route('office.confirmations.index'))
        ->assertOk()
        ->assertSeeText('Queue Office Alpha')
        ->assertSeeText('Queue Office Beta')
        ->assertSeeText($evaluationA->idea_title)
        ->assertSeeText($evaluationB->idea_title);
});

it('keeps the consultation concept and never implies LIKHA records it', function (): void {
    $office = queueViewOffice();
    queueViewRequest(queueViewOpportunity($office, 'Queue View Consultation'));

    $this->actingAs($office->representative)
        ->get(route('office.confirmations.index'))
        ->assertOk()
        ->assertSeeText('Consult the office representative before requesting confirmation')
        ->assertSeeText('does not record or verify it');
});

it('shows a clean empty state when nothing is pending', function (): void {
    $office = queueViewOffice();

    $this->actingAs($office->representative)
        ->get(route('office.confirmations.index'))
        ->assertOk()
        ->assertSeeText('No pending confirmation requests for the offices you currently represent.')
        ->assertDontSee('<button type="submit" class="btn-approve">Confirm</button>', false)
        ->assertDontSee('<button type="submit" class="btn-reject">Decline</button>', false);
});

it('offers the queue in the office navigation only to a current representative', function (): void {
    $reviewer = User::factory()->create(['role' => 'office_academic']);
    queueViewOffice();

    $this->actingAs($reviewer)
        ->get(route('office.review.index'))
        ->assertOk()
        ->assertDontSee(route('office.confirmations.index'), false);

    $representative = User::factory()->create(['role' => 'user']);
    queueViewOffice(['name' => 'Representative Nav Office', 'representative_user_id' => $representative->id]);

    $this->actingAs($representative)
        ->get(route('office.confirmations.index'))
        ->assertOk()
        ->assertSee(route('office.confirmations.index'), false);
});

it('does not leak another office request detail', function (): void {
    $office = queueViewOffice(['name' => 'Visible Office']);
    $otherOffice = queueViewOffice(['name' => 'Hidden Office']);

    $visible = queueViewOpportunity($office, 'Queue View Visible');
    $hidden = queueViewOpportunity($otherOffice, 'Queue View Hidden');
    queueViewRequest($visible);
    queueViewRequest($hidden);

    $this->actingAs($office->representative)
        ->get(route('office.confirmations.index'))
        ->assertOk()
        ->assertSeeText($visible->idea_title)
        ->assertDontSeeText($hidden->idea_title)
        ->assertDontSeeText('Hidden Office');
});
