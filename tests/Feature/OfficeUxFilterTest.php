<?php

use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\User;

function officeUxFeedback(array $attributes = []): Feedback
{
    return Feedback::query()->create(array_merge([
        'title' => 'Office UX problem',
        'description' => 'Students experience recurring delays that need a coordinated solution.',
        'impact' => 'This affects student transactions and creates repeated follow ups.',
        'category' => 'Office UX Category',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
        'is_capstone_worthy' => true,
        'capstone_marked_at' => now(),
    ], $attributes));
}

function officeUxOffice(array $attributes = []): Office
{
    return Office::query()->create(array_merge([
        'name' => 'Office UX Office',
        'representative_user_id' => User::factory()->create([
            'name' => 'Office UX Representative',
        ])->id,
        'contact_email' => 'office-ux@example.edu',
        'is_active' => true,
    ], $attributes));
}

test('problem scope filters separate office and community feedback', function (): void {
    $office = officeUxOffice();
    officeUxFeedback([
        'title' => 'Office-scoped problem',
        'office_id' => $office->id,
    ]);
    officeUxFeedback([
        'title' => 'Community-scoped problem',
        'office_id' => null,
    ]);

    $this->get(route('discover', ['scope' => 'office']))
        ->assertOk()
        ->assertSeeText('Office-scoped problem')
        ->assertSeeText('Office Problem')
        ->assertSeeText('Associated with Office UX Office')
        ->assertSeeText('Representative: Office UX Representative')
        ->assertDontSeeText('Community-scoped problem');

    $this->get(route('discover', ['scope' => 'community']))
        ->assertOk()
        ->assertSeeText('Community-scoped problem')
        ->assertDontSeeText('Office-scoped problem')
        ->assertDontSeeText('Office UX Office');
});

test('capstone scope filters feedback and existing idea evaluations without generating new records', function (): void {
    $office = officeUxOffice();
    officeUxFeedback([
        'title' => 'Office-backed capstone problem',
        'category' => 'Office UX Category',
        'office_id' => $office->id,
    ]);
    officeUxFeedback([
        'title' => 'Community capstone problem',
        'category' => 'Community UX Category',
        'office_id' => null,
    ]);
    IdeaEvaluation::query()->create([
        'idea_title' => 'Office UX Opportunity',
        'category' => 'Office UX Category',
        'office_id' => $office->id,
        'overall_score' => 4.2,
    ]);
    IdeaEvaluation::query()->create([
        'idea_title' => 'Community UX Opportunity',
        'category' => 'Community UX Category',
        'office_id' => null,
        'overall_score' => 3.8,
    ]);

    $this->get(route('capstone.opportunities', ['scope' => 'office']))
        ->assertOk()
        ->assertSeeText('Office-backed capstone problem')
        ->assertSeeText('Office-Backed')
        ->assertSeeText('Office UX Category')
        ->assertDontSeeText('Community capstone problem')
        ->assertDontSeeText('Community UX Category');

    $this->get(route('capstone.opportunities', ['scope' => 'community']))
        ->assertOk()
        ->assertSeeText('Community capstone problem')
        ->assertSeeText('Community UX Category')
        ->assertDontSeeText('Office-backed capstone problem')
        ->assertDontSeeText('Office UX Office');
});

test('office-backed opportunity presentation exposes official office identity while community presentation does not', function (): void {
    $office = officeUxOffice();
    officeUxFeedback([
        'title' => 'Office presentation problem',
        'category' => 'Office Presentation Category',
        'office_id' => $office->id,
    ]);
    officeUxFeedback([
        'title' => 'Community presentation problem',
        'category' => 'Community Presentation Category',
        'office_id' => null,
    ]);

    $this->get(route('capstone.opportunities', ['scope' => 'office']))
        ->assertSeeText('Office UX Office')
        ->assertSeeText('Office UX Representative')
        ->assertSeeText('office-ux@example.edu')
        ->assertSeeText('Office-Backed Opportunity');

    $this->get(route('capstone.opportunities', ['scope' => 'community']))
        ->assertSeeText('Community Opportunity')
        ->assertDontSeeText('Office UX Representative')
        ->assertDontSeeText('office-ux@example.edu');
});

test('category opportunity scope shows office identity and existing availability status', function (): void {
    $office = officeUxOffice();
    officeUxFeedback([
        'title' => 'Office category opportunity problem',
        'category' => 'Office Category Opportunity',
        'office_id' => $office->id,
    ]);
    officeUxFeedback([
        'title' => 'Community category opportunity problem',
        'category' => 'Office Category Opportunity',
        'office_id' => null,
    ]);

    $this->get(route('feedback.category', [
        'category' => 'Office Category Opportunity',
        'scope' => 'office',
    ]))
        ->assertOk()
        ->assertSeeText('Office-Backed')
        ->assertSeeText('Office UX Office')
        ->assertSeeText('Office UX Representative')
        ->assertSeeText('office-ux@example.edu')
        ->assertSeeText('AVAILABLE')
        ->assertDontSeeText('Community category opportunity problem');
});

test('discover recent opportunities follow the selected office and community scope while default shows both', function (): void {
    $office = officeUxOffice();
    officeUxFeedback([
        'category' => 'Office Recent Category',
        'office_id' => $office->id,
    ]);
    officeUxFeedback([
        'category' => 'Community Recent Category',
        'office_id' => null,
    ]);
    IdeaEvaluation::query()->create([
        'idea_title' => 'Office Recent Opportunity',
        'category' => 'Office Recent Category',
        'office_id' => $office->id,
    ]);
    IdeaEvaluation::query()->create([
        'idea_title' => 'Community Recent Opportunity',
        'category' => 'Community Recent Category',
        'office_id' => null,
    ]);

    $this->get(route('discover', ['scope' => 'office']))
        ->assertSeeText('Office Recent Opportunity')
        ->assertDontSeeText('Community Recent Opportunity');

    $this->get(route('discover', ['scope' => 'community']))
        ->assertSeeText('Community Recent Opportunity')
        ->assertDontSeeText('Office Recent Opportunity');

    $this->get(route('discover'))
        ->assertSeeText('Office Recent Opportunity')
        ->assertSeeText('Community Recent Opportunity');
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
