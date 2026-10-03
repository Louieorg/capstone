<?php

use App\Models\Office;
use App\Models\User;

/**
 * An Office Representative is still a normal user, so they navigate through the
 * student sidebar. The Confirmation Requests item belongs there, but only while
 * the account actually represents an active office.
 */
function representativeNavOffice(array $attributes = []): Office
{
    return Office::query()->create(array_merge([
        'name' => 'Nav Representative Office',
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
        'contact_email' => 'nav-representative@example.edu',
        'is_active' => true,
    ], $attributes));
}

it('shows Confirmation Requests in the student sidebar to a current representative', function (): void {
    $representative = User::factory()->create(['role' => 'user']);
    representativeNavOffice(['representative_user_id' => $representative->id]);

    $this->actingAs($representative)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeText('Confirmation Requests')
        ->assertSee(route('office.confirmations.index'), false);
});

it('does not show Confirmation Requests to a normal student', function (): void {
    representativeNavOffice();

    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('home'))
        ->assertOk()
        ->assertDontSeeText('Confirmation Requests')
        ->assertDontSee(route('office.confirmations.index'), false);
});

it('does not show Confirmation Requests to a reviewer who is not a representative', function (): void {
    representativeNavOffice();

    $reviewer = User::factory()->create(['role' => 'office_academic']);

    $this->actingAs($reviewer)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSeeText('Confirmation Requests')
        ->assertDontSee(route('office.confirmations.index'), false);
});

it('does not show Confirmation Requests for a former representative', function (): void {
    $office = representativeNavOffice();
    $formerRepresentative = $office->representative;

    $office->forceFill([
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
    ])->save();

    $this->actingAs($formerRepresentative)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSeeText('Confirmation Requests');
});

it('does not show Confirmation Requests for a representative of an inactive office', function (): void {
    $office = representativeNavOffice(['is_active' => false]);

    $this->actingAs($office->representative)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSeeText('Confirmation Requests');
});

it('keeps the representative queue link in the mobile profile hub', function (): void {
    $representative = User::factory()->create(['role' => 'user']);
    representativeNavOffice(['representative_user_id' => $representative->id]);

    $this->actingAs($representative)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSeeText('Confirmation Requests')
        ->assertSee(route('office.confirmations.index'), false);
});

it('leaves a representative role untouched in the database', function (): void {
    $representative = User::factory()->create(['role' => 'user']);
    representativeNavOffice(['representative_user_id' => $representative->id]);

    expect($representative->fresh()->role)->toBe('user');
});
