<?php

use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use Illuminate\Support\Facades\Cache;

/**
 * The Capstone Opportunities page has to carry two clearly different things:
 *
 *   Office-Backed Opportunity = approved problem marked capstone-worthy, with an office
 *   Community-Generated Idea  = DSS concept produced from community reports, no office
 *
 * Neither may be mistaken for the other.
 */
function capstoneScopeOffice(string $name = 'Capstone Scope Office'): Office
{
    return Office::query()->create([
        'name' => $name,
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
        'contact_email' => 'capstone-scope@example.edu',
        'is_active' => true,
    ]);
}

function capstoneScopeFeedback(array $attributes = []): Feedback
{
    return Feedback::query()->create(array_merge([
        'title' => 'Laboratory equipment availability is tracked manually',
        'description' => 'Laboratory personnel record workstation faults on paper and cannot see availability in real time.',
        'impact' => 'Students lose access to working equipment while faults stay unresolved.',
        'category' => 'Capstone Scope Category',
        'office_id' => null,
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students', 'Staff'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
        'is_capstone_worthy' => true,
        'capstone_marked_at' => now(),
    ], $attributes));
}

/**
 * Generate a real DSS concept for a category, then return the stored rows.
 */
function capstoneScopeIdeas(string $category, ?Office $office): void
{
    Cache::flush();
    app(CategoryIdeaGenerationService::class)->generate($category, $office?->id, false);
}

it('shows an office-backed opportunity in the Office-Backed scope', function (): void {
    $office = capstoneScopeOffice();
    capstoneScopeFeedback(['office_id' => $office->id, 'category' => 'Capstone Office Category']);
    capstoneScopeIdeas('Capstone Office Category', $office);

    $this->get(route('capstone.opportunities', ['scope' => 'office']))
        ->assertOk()
        ->assertSeeText('Office-Backed Opportunity')
        ->assertSeeText($office->name)
        // An office-scoped view never shows community opportunities or community ideas.
        ->assertDontSeeText('Community Opportunity')
        ->assertDontSeeText('Community-Generated Ideas');
});

it('shows a community-generated idea in the Community scope', function (): void {
    capstoneScopeFeedback(['office_id' => null, 'category' => 'Capstone Community Category']);
    capstoneScopeIdeas('Capstone Community Category', null);

    $idea = IdeaEvaluation::query()
        ->where('category', 'Capstone Community Category')
        ->whereNull('office_id')
        ->firstOrFail();

    $this->get(route('capstone.opportunities', ['scope' => 'community']))
        ->assertOk()
        ->assertSeeText('Community-Generated Ideas')
        ->assertSeeText($idea->idea_title)
        ->assertSee(route('feedback.category', ['category' => $idea->category, 'scope' => 'community']), false);
});

it('shows both kinds together in the All scope', function (): void {
    $office = capstoneScopeOffice('Capstone All Office');
    capstoneScopeFeedback([
        'title' => 'Office reported laboratory access gap',
        'office_id' => $office->id,
        'category' => 'Capstone All Office Category',
    ]);
    capstoneScopeIdeas('Capstone All Office Category', $office);

    capstoneScopeFeedback([
        'title' => 'Community reported study space crowding',
        'office_id' => null,
        'category' => 'Capstone All Community Category',
    ]);
    capstoneScopeIdeas('Capstone All Community Category', null);

    $communityIdea = IdeaEvaluation::query()
        ->where('category', 'Capstone All Community Category')
        ->whereNull('office_id')
        ->firstOrFail();

    $this->get(route('capstone.opportunities'))
        ->assertOk()
        ->assertSeeText('Office reported laboratory access gap')
        ->assertSeeText('Office-Backed Opportunity')
        ->assertSeeText('Community-Generated Ideas')
        ->assertSeeText($communityIdea->idea_title);
});

it('never labels a community-generated idea with office information', function (): void {
    $office = capstoneScopeOffice('Capstone No Label Office');
    capstoneScopeFeedback(['office_id' => null, 'category' => 'Capstone No Label Category']);
    capstoneScopeIdeas('Capstone No Label Category', null);

    $this->get(route('capstone.opportunities', ['scope' => 'community']))
        ->assertOk()
        ->assertSeeText('Community-Generated Ideas')
        ->assertDontSeeText('Office-Backed Opportunity')
        ->assertDontSeeText('Representative:')
        ->assertDontSeeText('Official contact:')
        ->assertDontSeeText($office->name);
});

it('does not present an office-backed idea as a community-generated idea', function (): void {
    $office = capstoneScopeOffice('Capstone Office Idea Office');
    capstoneScopeFeedback(['office_id' => $office->id, 'category' => 'Capstone Office Idea Category']);
    capstoneScopeIdeas('Capstone Office Idea Category', $office);

    $officeIdea = IdeaEvaluation::query()
        ->where('category', 'Capstone Office Idea Category')
        ->whereNotNull('office_id')
        ->firstOrFail();

    $this->get(route('capstone.opportunities', ['scope' => 'community']))
        ->assertOk()
        ->assertDontSeeText('Community-Generated Ideas')
        ->assertDontSeeText($officeIdea->idea_title);
});

it('keeps the existing card actions on opportunities', function (): void {
    $office = capstoneScopeOffice('Capstone Actions Office');
    $feedback = capstoneScopeFeedback(['office_id' => $office->id, 'category' => 'Capstone Actions Category']);
    capstoneScopeIdeas('Capstone Actions Category', $office);

    $this->get(route('capstone.opportunities'))
        ->assertOk()
        ->assertSeeText('View Problem')
        ->assertSeeText('Explore DSS Ideas')
        ->assertSee(route('feedback.show', $feedback), false)
        ->assertSee(route('feedback.category', ['category' => 'Capstone Actions Category']), false);
});

it('does not restore the per-category dss count block', function (): void {
    capstoneScopeFeedback(['office_id' => null, 'category' => 'Capstone Counts Category']);
    capstoneScopeIdeas('Capstone Counts Category', null);

    $this->get(route('capstone.opportunities'))
        ->assertOk()
        ->assertDontSeeText('DSS Ideas by Category')
        ->assertDontSee('1 idea')
        ->assertSeeText('Community-Generated Ideas');
});

it('hides community-generated ideas under the Office-Backed scope', function (): void {
    capstoneScopeFeedback(['office_id' => null, 'category' => 'Capstone Scope Split']);
    capstoneScopeIdeas('Capstone Scope Split', null);

    $idea = IdeaEvaluation::query()->whereNull('office_id')->firstOrFail();

    $this->get(route('capstone.opportunities', ['scope' => 'office']))
        ->assertOk()
        ->assertDontSeeText('Community-Generated Ideas')
        ->assertDontSeeText($idea->idea_title);
});
