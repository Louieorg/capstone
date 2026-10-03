<?php

use App\Models\User;

/**
 * Terms & Conditions documents the rules and behavior actually implemented in
 * LIKHA. These tests assert the page exists, is publicly reachable, describes
 * real system behavior, invents no policy that does not exist, and exposes no
 * configuration secrets.
 */
function termsUser(): User
{
    return User::factory()->create(['role' => 'user']);
}

it('exposes a public terms route', function (): void {
    expect(route('terms'))->toBe(url('/terms'));
});

it('lets a guest read the terms', function (): void {
    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Terms &amp; Conditions', false)
        ->assertSee('Rules and responsibilities for using LIKHA');
});

it('lets an authenticated user read the terms', function (): void {
    $this->actingAs(termsUser())->get(route('terms'))->assertOk();
});

it('states what likha is and what it provides', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('About using LIKHA')
        ->assertSeeText('decision support system that connects institutional problems with capstone opportunities')
        ->assertSeeText('Institutional problem submission')
        ->assertSeeText('Office-backed opportunities')
        ->assertSeeText('Community-generated ideas')
        ->assertSeeText('Office confirmation workflows')
        ->assertSeeText('Adviser-related evaluation features')
        ->assertSeeText('LIKHA is not an AI capstone generator.');
});

it('describes the account rules', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Your account')
        ->assertSeeText('normal registration or through Google sign-in')
        ->assertSeeText('Email verification is')
        ->assertSeeText('Passwords are stored using password hashing rather than as readable passwords.')
        ->assertSeeText('Keep your account credentials confidential.')
        ->assertSeeText('Do not attempt to access features outside your authorization.')
        ->assertSeeText('Account deletion may be')
        ->assertSeeText('unavailable while certain office-representative or office-confirmation records still reference the account.');
});

it('describes submission rules without inventing a truthfulness policy', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Submitting institutional problems')
        ->assertSeeText('Authenticated users can submit institutional problems.')
        ->assertSeeText('Minimum text lengths')
        ->assertSeeText('A meaningful-description check')
        ->assertSeeText('A placeholder-text check')
        ->assertSeeText('A repeated-character check')
        ->assertSeeText('does not currently enforce a formal truthfulness policy')
        ->assertSeeText('Pending')
        ->assertSeeText('does not provide a user-facing edit, withdraw, or delete function');
});

it('explains anonymous submissions', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Anonymous submissions')
        ->assertSeeText('LIKHA removes the user association from that')
        ->assertSeeText('The interface displays the contributor as anonymous.')
        ->assertSeeText('may still identify a person.');
});

it('describes evidence limits', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Uploaded evidence')
        ->assertSeeText('One file')
        ->assertSeeText('JPG, JPEG, PNG or PDF')
        ->assertSeeText('Maximum 5 MB')
        ->assertSeeText('Up to 5 files')
        ->assertSeeText('Maximum 10 MB per file')
        ->assertSeeText('Only upload evidence that is relevant to the institutional problem.');
});

it('describes duplicate and rapid submission handling', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Duplicate and rapid submissions')
        ->assertSeeText('LIKHA can show similar approved problems before you submit.')
        ->assertSeeText('Flagging does not mean the submission is automatically deleted.')
        ->assertSeeText('not a judgment about your intent.');
});

it('describes voting and comments accurately', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Voting, comments and contributions')
        ->assertSeeText('Only approved and non-flagged problems can receive support.')
        ->assertSeeText('Each user can support a problem once.')
        ->assertSeeText('Supporting again toggles the support off.')
        ->assertSeeText('Guests must sign in to support a problem.')
        ->assertSeeText('does not detect')
        ->assertSeeText('coordinated voting')
        ->assertSeeText('Comments are currently append-only from your side.')
        ->assertSeeText('no user-facing comment edit or delete function');
});

it('clarifies saved ideas and ownership', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Saved Ideas and ownership')
        ->assertSeeText('reserve the opportunity')
        ->assertSeeText('create ownership')
        ->assertSeeText('create priority')
        ->assertSeeText('prevent another student or group from exploring the same idea')
        ->assertSeeText('A Saved Idea is a personal bookmark')
        ->assertSeeText('Exploring')
        ->assertSeeText('Adopted')
        ->assertSeeText('In Progress')
        ->assertSeeText('Completed')
        ->assertSeeText('Saved Ideas do not determine who owns a capstone project.');
});

it('describes office confirmation without overstating it', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Office confirmation')
        ->assertSeeText('A confirmation request does not reserve the opportunity.')
        ->assertSeeText('Submitting a request does not create ownership.')
        ->assertSeeText('Multiple pending requests may exist before any of them is confirmed.')
        ->assertSeeText('occurs outside LIKHA and is not recorded as an')
        ->assertSeeText('The opportunity becomes taken for the requesting group.')
        ->assertSeeText('Competing pending requests become stale.')
        ->assertSeeText('The opportunity becomes available again.')
        ->assertSeeText('Only the current office representative may Confirm or Decline.')
        ->assertSeeText('Office reviewer status does not automatically grant office representative authority.')
        ->assertSeeText('Office confirmation is not final institutional approval of a capstone project.');
});

it('separates office representative from reviewer', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Office representatives and reviewers')
        ->assertSeeText('It is not')
        ->assertSeeText('a separate system role.')
        ->assertSeeText('Being a reviewer does not automatically make someone an office representative.')
        ->assertSeeText('Being an office representative does not automatically make someone a reviewer.')
        ->assertSeeText('Self-review is blocked while another eligible reviewer exists.');
});

it('states the dss decides and ai explains boundary', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('THE DSS DECIDES.')
        ->assertSeeText('THE AI EXPLAINS.')
        ->assertSeeText('Clustering')
        ->assertSeeText('Qualification thresholds')
        ->assertSeeText('AI enhancement does not determine whether an opportunity qualifies.')
        ->assertSeeText('AI enhancement does not change severity.')
        ->assertSeeText('AI enhancement does not change confidence.')
        ->assertSeeText('AI enhancement does not change evaluation.')
        ->assertSeeText('AI enhancement does not change thresholds.')
        ->assertSeeText('AI enhancement does not change office scope.')
        ->assertSeeText('If AI enhancement is unavailable or fails, the original wording is retained.')
        ->assertSeeText('AI enhancement is disabled by default in the inspected configuration.');
});

it('lists moderation safeguards and system limitations', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Moderation and system safeguards')
        ->assertSeeText('Placeholder-text rejection')
        ->assertSeeText('Duplicate similarity detection')
        ->assertSeeText('Rapid-submission detection')
        ->assertSeeText('Submission throttling')
        ->assertSeeText('Login rate limiting')
        ->assertSeeText('Reviewer self-review prevention')
        ->assertSeeText('Not every')
        ->assertSeeText('type of misuse is automatically detected.')
        ->assertSeeText('System limitations')
        ->assertSeeText('LIKHA does not provide an uptime or response-time guarantee.')
        ->assertSeeText('Google sign-in depends on Google availability.');
});

it('marks undefined institutional policies as still undefined', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Policies requiring institutional definition')
        ->assertSeeText('The current application does not define formal policies for the following matters:')
        ->assertSeeText('Account suspension and disciplinary procedures')
        ->assertSeeText('Intellectual-property ownership')
        ->assertSeeText('Final institutional capstone approval')
        ->assertSeeText('Governing law and dispute resolution')
        ->assertSeeText('should be established separately by the responsible institution or project authority');

    // No fabricated policy language, penalties or effective date.
    foreach ([
        'Effective date',
        'governing law shall',
        'you agree to indemnify',
        'liability waiver',
        'permanently suspended',
        'shall be fined',
        'subject to disciplinary action',
    ] as $invented) {
        $response->assertDontSeeText($invented);
    }
});

it('does not invent a terms acceptance mechanism', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSeeText('Current system documentation')
        ->assertDontSeeText('acceptance')
        ->assertDontSeeText('consent');

    // No checkbox or acceptance field was introduced on the public pages.
    $login = $this->get(route('login'));
    $login->assertOk()->assertDontSee('name="accept_terms"', false);
});

it('links to privacy rights', function (): void {
    $response = $this->get(route('terms'))->assertOk();

    $response->assertSee(route('help.privacy'), false)
        ->assertSeeText('Privacy and data handling')
        ->assertSeeText('described separately in the Privacy Rights page')
        ->assertSeeText('View Privacy Rights');
});

it('points the former placeholder terms links at the real page', function (): void {
    $this->get(route('login'))->assertOk()
        ->assertSee(route('terms'), false)
        ->assertDontSee('<a href="#">Terms</a>', false);

    $this->get('/register')->assertOk()
        ->assertSee(route('terms'), false)
        ->assertDontSee('<a href="#">Terms</a>', false);

    $this->get('/')->assertOk()
        ->assertSee(route('terms'), false)
        ->assertSee('our <a href="'.route('terms').'">Terms</a> & <a href="'.route('help.privacy').'">Privacy Rights</a>', false);
});

it('does not expose secrets or configuration values', function (): void {
    $content = (string) $this->get(route('terms'))->assertOk()->getContent();

    foreach ([
        'GOCSPX',
        'inpgwqtvkjncffna',
        'kentlouiemedinaceli@gmail.com',
        'smtp.gmail.com',
        'MAIL_PASSWORD',
        'APP_KEY',
        'remember_token',
        '127.0.0.1:11434',
    ] as $secret) {
        expect($content)->not->toContain($secret);
    }
});

it('keeps the bottom navigation at exactly five slots', function (): void {
    $content = (string) $this->actingAs(termsUser())->get(route('home'))->assertOk()->getContent();

    foreach (['Home', 'Discover', 'Submit', 'Capstone', 'Profile'] as $slot) {
        expect($content)->toContain('<span>'.$slot.'</span>');
    }

    $nav = substr($content, (int) strpos($content, 'bottom-nav md:hidden'));
    expect(substr($nav, 0, 4000))->not->toContain('<span>Terms');
});

it('is offered in help and settings without changing existing behaviour', function (): void {
    $user = termsUser();

    $this->actingAs($user)->get(route('home'))->assertOk()
        ->assertSeeText('Help &amp; Settings', false)
        ->assertSee(route('terms'), false);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('Terms &amp; Conditions', false);

    // Existing surfaces still work.
    $this->actingAs($user)->get(route('help.user-guide'))->assertOk()->assertSeeText('Getting started');
    $this->actingAs($user)->get(route('help.faq'))->assertOk()->assertSeeText('Getting Started');
    $this->actingAs($user)->get(route('help.privacy'))->assertOk()->assertSeeText('Privacy Rights');
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSeeText('Account Settings');
    $this->actingAs($user)->get(route('home'))->assertOk();
});
