<?php

use App\Models\User;

/**
 * Privacy Rights documents the information handling actually implemented in
 * LIKHA. These tests assert the page exists, is reachable, states the inspected
 * behaviour accurately, and exposes no configuration secrets.
 */
function privacyUser(): User
{
    return User::factory()->create(['role' => 'user']);
}

it('exposes the privacy rights route', function (): void {
    expect(route('help.privacy'))->toBe(url('/help/privacy'));
});

it('lets a signed-in user open the privacy page', function (): void {
    $this->actingAs(privacyUser())
        ->get(route('help.privacy'))
        ->assertOk()
        ->assertSee('Privacy Rights');
});

it('is reachable by a guest because public pages link to it', function (): void {
    $this->get(route('help.privacy'))->assertOk()->assertSee('Privacy Rights');
});

it('renders the information we collect section', function (): void {
    $response = $this->actingAs(privacyUser())->get(route('help.privacy'))->assertOk();

    $response->assertSeeText('Information we collect')
        ->assertSeeText('Account information')
        ->assertSeeText('Your name')
        ->assertSeeText('Your email address')
        ->assertSeeText('Institutional problem submissions')
        ->assertSeeText('Activity and contributions')
        ->assertSeeText('password hashing rather than as readable passwords');
});

it('explains anonymous submissions without overstating them', function (): void {
    $response = $this->actingAs(privacyUser())->get(route('help.privacy'))->assertOk();

    $response->assertSeeText('Anonymous submissions')
        ->assertSeeText('does not associate that feedback record with your user account')
        ->assertSeeText('may still')
        ->assertSeeText('identify a person.');
});

it('explains who can access information', function (): void {
    $response = $this->actingAs(privacyUser())->get(route('help.privacy'))->assertOk();

    $response->assertSeeText('Who can access information')
        ->assertSeeText('Verified users')
        ->assertSeeText('Office representatives')
        ->assertSeeText('Reviewers')
        ->assertSeeText('Advisers')
        ->assertSeeText('Administrators')
        ->assertSeeText('Being a reviewer does not automatically make a user an office representative.');
});

it('documents the external services accurately', function (): void {
    $response = $this->actingAs(privacyUser())->get(route('help.privacy'))->assertOk();

    $response->assertSeeText('External services')
        ->assertSeeText('Google sign-in (OAuth)')
        ->assertSeeText('LIKHA does not receive your Google password.')
        ->assertSeeText('Ollama (AI wording enhancement)')
        ->assertSeeText('disabled by default in the inspected configuration')
        ->assertSeeText('not currently active in the application')
        ->assertSeeText('Email');
});

it('describes the controls users currently have', function (): void {
    $response = $this->actingAs(privacyUser())->get(route('help.privacy'))->assertOk();

    $response->assertSeeText('Your available controls')
        ->assertSeeText('Delete your account')
        ->assertSeeText('Vote or remove your vote on a problem')
        ->assertSeeText('Remove a Saved Idea')
        ->assertSeeText('Changing an email address requires email verification again.')
        ->assertSeeText('Currently unavailable')
        ->assertSeeText('does not currently provide built-in tools for bulk data export');
});

it('describes account deletion accurately', function (): void {
    $response = $this->actingAs(privacyUser())->get(route('help.privacy'))->assertOk();

    $response->assertSeeText('Account deletion')
        ->assertSeeText('You are logged out.')
        ->assertSeeText('Some accounts cannot currently be deleted')
        ->assertSeeText('Uploaded evidence and attachment files stored on disk are not currently removed automatically');
});

it('states that no fixed retention period is currently defined', function (): void {
    $response = $this->actingAs(privacyUser())->get(route('help.privacy'))->assertOk();

    $response->assertSeeText('Retention and records')
        ->assertSeeText('No fixed retention period is currently defined in the LIKHA application.')
        ->assertSeeText('Retention periods and institutional record-management policies are not currently configured')
        ->assertSeeText('Notifications remain after being marked as read.')
        ->assertSeeText('Stale confirmation requests remain recorded.');

    // No invented period anywhere on the page.
    $response->assertDontSeeText('days', false);
    $response->assertDontSeeText('months', false);
    $response->assertDontSeeText('years', false);
});

it('says plainly that no privacy contact is configured', function (): void {
    $response = $this->actingAs(privacyUser())->get(route('help.privacy'))->assertOk();

    $response->assertSeeText('Privacy questions')
        ->assertSeeText('Privacy contact not yet configured')
        ->assertSeeText('does not contain a dedicated privacy officer, privacy email address, or formal')
        ->assertSeeText('should be provided here');
});

it('does not expose configuration secrets or credentials', function (): void {
    $content = (string) $this->actingAs(privacyUser())->get(route('help.privacy'))->assertOk()->getContent();

    foreach ([
        'GOCSPX',                       // google oauth client secret
        'inpgwqtvkjncffna',             // smtp password
        'kentlouiemedinaceli@gmail.com', // smtp account
        'smtp.gmail.com',
        'MAIL_PASSWORD',
        'APP_KEY',
        'remember_token',
        '127.0.0.1:11434',
    ] as $secret) {
        expect($content)->not->toContain($secret);
    }
});

it('points the former placeholder privacy links at the real page', function (): void {
    $login = $this->get(route('login'));
    if ($login->status() === 200) {
        $login->assertSee(route('help.privacy'), false)
            ->assertDontSee('<a href="#">Privacy</a>', false);
    }

    $welcome = $this->get('/');
    $welcome->assertOk()
        ->assertSee(route('help.privacy'), false)
        ->assertDontSee('& <a href="#">Privacy', false);
});

it('is reachable from the student navigation and the profile hub', function (): void {
    $user = privacyUser();

    $this->actingAs($user)->get(route('home'))->assertOk()
        ->assertSeeText('Help &amp; Settings', false)
        ->assertSee(route('help.user-guide'), false)
        ->assertSee(route('help.faq'), false)
        ->assertSee(route('help.privacy'), false);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('User Guide')
        ->assertSeeText('FAQ')
        ->assertSeeText('Privacy Rights');
});

it('keeps the mobile bottom navigation unchanged', function (): void {
    $content = (string) $this->actingAs(privacyUser())->get(route('home'))->assertOk()->getContent();

    foreach (['Home', 'Discover', 'Submit', 'Capstone', 'Profile'] as $slot) {
        expect($content)->toContain('<span>'.$slot.'</span>');
    }

    // Privacy Rights is not a bottom-nav slot.
    $nav = substr($content, (int) strpos($content, 'bottom-nav md:hidden'));
    expect(substr($nav, 0, 4000))->not->toContain('<span>Privacy');
});

it('does not change existing behaviour', function (): void {
    $user = privacyUser();

    $this->actingAs($user)->get(route('help.user-guide'))->assertOk()->assertSeeText('Getting started');
    $this->actingAs($user)->get(route('help.faq'))->assertOk()->assertSeeText('Getting Started');
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSeeText('Account Settings');
    $this->actingAs($user)->get(route('home'))->assertOk();
    $this->actingAs($user)->get(route('discover'))->assertOk();
});
