<?php

use App\Models\Office;
use App\Models\User;

/**
 * The confirmation queue is an office's own worklist. Only a user who currently
 * represents at least one ACTIVE office may open it — resolved from
 * Office.representative_user_id, never from a role.
 */
function queueOffice(array $attributes = []): Office
{
    return Office::query()->create(array_merge([
        'name' => 'Queue Access Office',
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
        'contact_email' => 'queue-access@example.edu',
        'is_active' => true,
    ], $attributes));
}

it('allows a current representative of an active office to open the queue', function (): void {
    $office = queueOffice();
    $representative = $office->representative;

    $this->actingAs($representative)
        ->get(route('office.confirmations.index'))
        ->assertOk();
});

it('refuses a normal student', function (): void {
    queueOffice();

    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('office.confirmations.index'))
        ->assertForbidden();
});

it('refuses an admin', function (): void {
    queueOffice();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('office.confirmations.index'))
        ->assertForbidden();
});

it('refuses a reviewer who is not a representative', function (): void {
    $office = queueOffice();

    $this->actingAs(User::factory()->create(['role' => 'office_academic']))
        ->get(route('office.confirmations.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['role' => 'office_chief']))
        ->get(route('office.confirmations.index'))
        ->assertForbidden();
});

it('allows a reviewer who is also the current representative of an active office', function (): void {
    $reviewer = User::factory()->create(['role' => 'office_academic']);
    queueOffice(['representative_user_id' => $reviewer->id]);

    $this->actingAs($reviewer)
        ->get(route('office.confirmations.index'))
        ->assertOk();
});

it('refuses a former representative whose office now names someone else', function (): void {
    $office = queueOffice();
    $formerRepresentative = $office->representative;

    $office->forceFill([
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
    ])->save();

    $this->actingAs($formerRepresentative)
        ->get(route('office.confirmations.index'))
        ->assertForbidden();
});

it('refuses the representative of an inactive office', function (): void {
    $office = queueOffice(['is_active' => false]);
    $representative = $office->representative;

    $this->actingAs($representative)
        ->get(route('office.confirmations.index'))
        ->assertForbidden();
});

it('allows a representative of several active offices', function (): void {
    $representative = User::factory()->create(['role' => 'user']);
    queueOffice(['name' => 'Queue Office One', 'representative_user_id' => $representative->id]);
    queueOffice(['name' => 'Queue Office Two', 'representative_user_id' => $representative->id]);

    $this->actingAs($representative)
        ->get(route('office.confirmations.index'))
        ->assertOk();
});

it('allows access again once an inactive office is activated', function (): void {
    $office = queueOffice(['is_active' => false]);
    $representative = $office->representative;

    $this->actingAs($representative)->get(route('office.confirmations.index'))->assertForbidden();

    $office->forceFill(['is_active' => true])->save();

    $this->actingAs($representative)->get(route('office.confirmations.index'))->assertOk();
});

it('still requires authentication', function (): void {
    $this->get(route('office.confirmations.index'))
        ->assertRedirect(route('login'));
});
