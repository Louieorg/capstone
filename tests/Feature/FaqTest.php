<?php

use App\Models\User;

/**
 * The FAQ is documentation inside the authenticated student shell. It must be
 * reachable by a signed-in user, refused to guests, worded in the CURRENT
 * accepted terminology, and must not disturb the shell or navigation.
 */
function faqUser(): User
{
    return User::factory()->create(['role' => 'user']);
}

it('lets an authenticated user open the faq', function (): void {
    $this->actingAs(faqUser())
        ->get(route('help.faq'))
        ->assertOk()
        ->assertSee('Frequently Asked Questions');
});

it('refuses a guest', function (): void {
    $this->get(route('help.faq'))
        ->assertRedirect(route('login'));
});

it('renders all five faq categories', function (): void {
    $response = $this->actingAs(faqUser())->get(route('help.faq'))->assertOk();

    foreach ([
        'Getting Started',
        'Problems & Evidence',
        'Capstone Opportunities',
        'Office Confirmation',
        'AI & The System',
    ] as $category) {
        $response->assertSeeText($category);
    }
});

it('renders every required question', function (): void {
    $response = $this->actingAs(faqUser())->get(route('help.faq'))->assertOk();

    foreach ([
        // Getting started
        'What is LIKHA?',
        'Who can use LIKHA?',
        'What can I do in LIKHA?',
        'What is an institutional problem?',
        // Problems & evidence
        'What should I submit?',
        'Why is supporting evidence important?',
        'What happens after I submit a problem?',
        'What happens if my submission is similar to another problem?',
        'What is the difference between a Community submission and an Office submission?',
        // Opportunities
        'How does LIKHA produce a capstone opportunity?',
        'What is a Community-Generated Idea?',
        'What is an Office-Backed Opportunity?',
        'What do Severity and Confidence mean?',
        'Does saving an idea reserve it?',
        // Confirmation
        'What does Confirmation Requested mean?',
        'Why should I consult the office representative?',
        'Does LIKHA record the consultation?',
        'What happens when an office confirms?',
        'What happens when an office declines?',
        'What does Taken mean?',
        // AI
        'Does AI generate my capstone idea?',
        'What does the AI-enhanced wording do?',
        'Can AI change the DSS decision?',
        'Who determines whether an opportunity qualifies?',
        'Why does LIKHA use a DSS?',
    ] as $question) {
        $response->assertSeeText($question);
    }
});

it('uses the current accepted terminology', function (): void {
    $response = $this->actingAs(faqUser())->get(route('help.faq'))->assertOk();

    foreach ([
        'Community-Generated Idea',
        'Office-Backed Opportunity',
        'Confirmation Requested',
        'Office-Confirmed',
        'Taken',
        'Declined',
        'Saved Idea',
        'office representative',
    ] as $term) {
        $response->assertSeeText($term);
    }

    // The DSS decides / AI explains principle is stated on the page.
    $response->assertSeeText('THE DSS DECIDES.')
        ->assertSeeText('THE AI EXPLAINS.');
});

it('does not reuse the outdated landing page faq wording', function (): void {
    $response = $this->actingAs(faqUser())->get(route('help.faq'))->assertOk();

    // The landing FAQ describes an administrator as the normal reviewer and
    // calls everything a "recommendation". Neither is how LIKHA works now.
    foreach ([
        'evaluated by an administrator',
        'evaluated by an administrator, who may approve or reject the submission',
        'Do I need to install any software',
        'Is Dark Mode available on all pages',
    ] as $outdated) {
        $response->assertDontSeeText($outdated);
    }
});

it('states the representative and reviewer distinction', function (): void {
    $response = $this->actingAs(faqUser())->get(route('help.faq'))->assertOk();

    // The FAQ must not imply that reviewing reports and representing an office
    // are the same thing.
    $response->assertSeeText('Only the office\'s current representative can confirm or decline.', false);
    $response->assertSeeText('office representative');
    $response->assertDontSeeText('an administrator will approve or reject', false);
});

it('keeps the shell and navigation intact', function (): void {
    $response = $this->actingAs(faqUser())->get(route('help.faq'))->assertOk();

    $response->assertSee('app-sidebar', false)
        ->assertSee('sb-account-trigger', false)
        ->assertSee('class="h-icon-btn notif-trigger', false)
        ->assertSeeText('Home Feed')
        ->assertSeeText('Discover')
        ->assertSeeText('Capstone Opportunities')
        ->assertSeeText('Saved Ideas')
        ->assertSeeText('My Contribution');
});

it('links to the user guide and is reachable from the sidebar help group', function (): void {
    $user = faqUser();

    $this->actingAs($user)->get(route('help.faq'))->assertOk()
        ->assertSee(route('help.user-guide'), false);

    $this->actingAs($user)->get(route('home'))->assertOk()
        ->assertSeeText('Help &amp; Settings', false)
        ->assertSee(route('help.faq'), false);

    // The user guide still works and was not modified.
    $this->actingAs($user)->get(route('help.user-guide'))->assertOk()
        ->assertSeeText('Getting started');
});
