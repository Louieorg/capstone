<?php

use App\Models\User;

/**
 * The User Guide is documentation inside the authenticated student shell. It must
 * be reachable by a signed-in user and refused to guests, and it must not disturb
 * the existing navigation.
 */
function userGuideUser(): User
{
    return User::factory()->create(['role' => 'user']);
}

it('lets an authenticated user open the user guide', function (): void {
    $this->actingAs(userGuideUser())
        ->get(route('help.user-guide'))
        ->assertOk()
        ->assertSee('User Guide');
});

it('refuses a guest', function (): void {
    $this->get(route('help.user-guide'))
        ->assertRedirect(route('login'));
});

it('renders every major user guide section', function (): void {
    $response = $this->actingAs(userGuideUser())->get(route('help.user-guide'))->assertOk();

    $sections = [
        'Getting started',
        'Submitting an institutional problem',
        'Discovering problems',
        'Understanding capstone opportunities',
        'Understanding DSS analysis',
        'Saved ideas',
        'Office confirmation',
        'Office representatives',
        'Reviewers',
        'Notifications',
        'Profile and account',
    ];

    foreach ($sections as $section) {
        $response->assertSeeText($section);
    }
});

it('explains the opportunity and confirmation distinctions plainly', function (): void {
    $response = $this->actingAs(userGuideUser())->get(route('help.user-guide'))->assertOk();

    $response->assertSeeText('Office-Backed Opportunity')
        ->assertSeeText('Community-Generated Idea')
        // Saved ideas never imply a claim.
        ->assertSeeText('Saving an idea does not reserve it.')
        ->assertSeeText('Saving an idea is not office confirmation')
        ->assertSeeText('Saving an idea does not establish ownership.')
        // Confirmation states and the out-of-LIKHA consultation.
        ->assertSeeText('Available')
        ->assertSeeText('Confirmation requested')
        ->assertSeeText('Office-confirmed')
        ->assertSeeText('Taken')
        ->assertSeeText('Declined')
        ->assertSeeText('Consultation happens outside LIKHA.')
        // Reviewing is separate from representing.
        ->assertSeeText('A reviewer is not automatically an office representative');
});

it('states the dss decides and ai explains principle', function (): void {
    $response = $this->actingAs(userGuideUser())->get(route('help.user-guide'))->assertOk();

    $response->assertSeeText('THE DSS DECIDES.')
        ->assertSeeText('THE AI EXPLAINS.')
        ->assertSeeText('It does not decide qualification, severity, confidence, thresholds, office scope, or whether an opportunity exists.');
});

it('renders inside the student shell with the existing navigation intact', function (): void {
    $response = $this->actingAs(userGuideUser())->get(route('help.user-guide'))->assertOk();

    // The shell and its navigation still render.
    $response->assertSee('app-sidebar', false)
        ->assertSee('sb-account-trigger', false)
        ->assertSee('class="h-icon-btn notif-trigger', false)
        ->assertSeeText('Home Feed')
        ->assertSeeText('Discover')
        ->assertSeeText('Capstone Opportunities')
        ->assertSeeText('Saved Ideas')
        ->assertSeeText('My Contribution');
});

it('is reachable from the sidebar help and settings group', function (): void {
    $response = $this->actingAs(userGuideUser())->get(route('home'))->assertOk();

    $response->assertSeeText('Help &amp; Settings', false)
        ->assertSee(route('help.user-guide'), false);
});

it('leaves the existing profile and navigation routes working', function (): void {
    $user = userGuideUser();

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSeeText('Account Settings');
    $this->actingAs($user)->get(route('home'))->assertOk();
    $this->actingAs($user)->get(route('discover'))->assertOk();
    $this->actingAs($user)->get(route('capstone.opportunities'))->assertOk();
});
