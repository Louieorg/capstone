<?php

use App\Models\Office;
use App\Models\OfficeConfirmationRequest;
use App\Models\User;

/**
 * Office representation is a relationship on active Office rows, never a role.
 * These tests prove the profile/account UI says so, and that saying so changes
 * nothing about authorization.
 */
function representativeOffice(array $attributes = []): Office
{
    $attributes['representative_user_id'] ??= User::factory()->create(['role' => 'user'])->id;

    return Office::query()->create(array_merge([
        'name' => 'Represented Office',
        'contact_email' => 'represented@example.edu',
        'is_active' => true,
    ], $attributes));
}

it('shows a user representing one active office as Office Representative with the office name', function (): void {
    $user = User::factory()->create(['role' => 'user', 'name' => 'Darell Dela Cruz']);
    representativeOffice(['name' => 'GCC Office', 'representative_user_id' => $user->id]);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('Office Representative')
        ->assertSeeText('GCC Office')
        ->assertDontSeeText('User');

    expect($user->fresh()->isOfficeRepresentative())->toBeTrue()
        ->and($user->fresh()->activeRepresentedOffices()->pluck('name')->all())->toBe(['GCC Office'])
        // The role itself is untouched: representation is not a role.
        ->and($user->fresh()->role)->toBe('user');
});

it('shows every active represented office for a user representing several', function (): void {
    $user = User::factory()->create(['role' => 'user']);
    representativeOffice(['name' => 'GCC Office', 'representative_user_id' => $user->id]);
    representativeOffice(['name' => 'Registrar Office', 'representative_user_id' => $user->id]);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('Office Representative')
        ->assertSeeText('GCC Office')
        ->assertSeeText('Registrar Office');
});

it('keeps the normal role display for a user who represents no office', function (): void {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('User')
        ->assertDontSeeText('Office Representative');

    expect($user->isOfficeRepresentative())->toBeFalse();
});

it('does not present a deactivated office as a current representative position', function (): void {
    $user = User::factory()->create(['role' => 'user']);
    representativeOffice([
        'name' => 'Retired Office',
        'representative_user_id' => $user->id,
        'is_active' => false,
    ]);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('User')
        ->assertDontSeeText('Office Representative')
        ->assertDontSeeText('Retired Office');

    expect($user->isOfficeRepresentative())->toBeFalse()
        ->and($user->activeRepresentedOffices())->toBeEmpty();
});

it('ignores submitting an office report, a reviewer role, and a category assignment', function (): void {
    $reviewer = User::factory()->create(['role' => 'office_academic']);
    representativeOffice();

    $this->actingAs($reviewer)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('Academic Affairs')
        ->assertDontSeeText('Office Representative');

    expect($reviewer->isOfficeRepresentative())->toBeFalse();
});

it('keeps office reviewer labels unchanged even when the user also represents an office', function (): void {
    $chief = User::factory()->create(['role' => 'office_chief']);
    representativeOffice(['representative_user_id' => $chief->id]);

    $this->actingAs($chief)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('Chief Administrative Office')
        ->assertDontSeeText('Office Representative');
});

it('surfaces the representative position in the sidebar account control', function (): void {
    $user = User::factory()->create(['role' => 'user', 'name' => 'Darell Dela Cruz']);
    representativeOffice(['name' => 'GCC Office', 'representative_user_id' => $user->id]);

    $this->actingAs($user)->get(route('home'))->assertOk()
        ->assertSeeText('Office Representative')
        ->assertSeeText('GCC Office');
});

it('still requires the current office representative to confirm a request', function (): void {
    $office = representativeOffice();
    $representative = User::factory()->create(['role' => 'user']);
    $office->forceFill(['representative_user_id' => $representative->id])->save();

    $request = OfficeConfirmationRequest::query()->create([
        'requester_user_id' => User::factory()->create(['role' => 'user'])->id,
        'idea_evaluation_id' => \App\Models\IdeaEvaluation::query()->create([
            'idea_title' => 'Representative authority check',
            'category' => 'Authority Check',
            'office_id' => $office->id,
            'overall_score' => 3.0,
        ])->id,
        'office_id' => $office->id,
        'status' => OfficeConfirmationRequest::STATUS_PENDING,
        'office_cluster_key' => 'authority-check',
        'source_feedback_ids' => [],
        'provenance_fingerprint' => 'authority-check-fingerprint',
    ]);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->patch(route('office.confirmations.confirm', $request))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['role' => 'office_academic']))
        ->patch(route('office.confirmations.confirm', $request))
        ->assertForbidden();

    $this->actingAs($representative)
        ->patch(route('office.confirmations.confirm', $request))
        ->assertRedirect();
});

it('keeps the account menu usable for a representative', function (): void {
    $user = User::factory()->create(['role' => 'user']);
    representativeOffice(['representative_user_id' => $user->id]);

    $this->actingAs($user)->get(route('home'))->assertOk()
        ->assertSee('sb-account-trigger', false)
        ->assertSeeText('Profile')
        ->assertSeeText('Toggle theme')
        ->assertSeeText('Logout')
        ->assertSee('id="logoutForm"', false);
});
