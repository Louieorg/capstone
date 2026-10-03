<?php

use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\SavedIdea;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use Illuminate\Support\Facades\Cache;

/**
 * A stored IdeaEvaluation is a retained category-level artifact. These tests
 * pin the visibility contract: a stored row is only presented to students as a
 * current opportunity while its category still qualifies for DSS generation.
 * Nothing is ever deleted, and Saved Idea references stay intact.
 */
function staleVisibilityFeedback(array $attributes = []): Feedback
{
    return Feedback::query()->create(array_merge([
        'title' => 'Campus records are stored on paper and in many places',
        'description' => 'Students and staff cannot find records because retrieval depends on manual or paper-based processes.',
        'impact' => 'Repeated follow ups delay every student transaction that needs a record.',
        'category' => 'Qualifying Category',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
        'is_capstone_worthy' => false,
    ], $attributes));
}

function staleVisibilityIdea(string $title, string $category): IdeaEvaluation
{
    return IdeaEvaluation::query()->create([
        'idea_title' => $title,
        'category' => $category,
        'feasibility' => 4,
        'impact' => 5,
        'complexity' => 3,
        'innovation' => 4,
        'overall_score' => 4.2,
        'recommendation' => 'Highly Recommended',
    ]);
}

/**
 * A category qualifies once it holds one institutionally validated report,
 * which is the established path that bypasses the report and vote thresholds.
 */
function staleVisibilityQualifyCategory(string $category = 'Qualifying Category'): void
{
    staleVisibilityFeedback([
        'category' => $category,
        'title' => 'Office identified records retrieval gap in '.$category,
        'is_capstone_worthy' => true,
        'capstone_marked_at' => now(),
    ]);
}

test('a stored idea in a category that no longer qualifies is hidden from discover', function (): void {
    staleVisibilityIdea('Retired Records Retrieval System', 'Retired Category');

    $this->get(route('discover'))
        ->assertOk()
        ->assertDontSeeText('Retired Records Retrieval System');
});

test('a stored idea in a category that no longer qualifies is hidden from the home page', function (): void {
    Cache::flush();

    staleVisibilityIdea('Retired Records Retrieval System', 'Retired Category');

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSeeText('Retired Records Retrieval System');
});

test('a stored idea in a category that no longer qualifies is not surfaced on capstone opportunities', function (): void {
    staleVisibilityQualifyCategory();
    staleVisibilityIdea('Current Records Retrieval System', 'Qualifying Category');
    staleVisibilityIdea('Retired Records Retrieval System', 'Retired Category');

    $this->get(route('capstone.opportunities'))
        ->assertOk()
        ->assertDontSeeText('DSS Ideas by Category')
        // A qualifying community idea is surfaced here as a Community-Generated Idea.
        ->assertSeeText('Community-Generated Ideas')
        ->assertSeeText('Current Records Retrieval System')
        // A category that no longer qualifying is still hidden.
        ->assertDontSeeText('Retired Records Retrieval System')
        ->assertDontSeeText('Retired Category');
});

test('a stored idea is still exposed while its category keeps qualifying', function (): void {
    staleVisibilityQualifyCategory();
    staleVisibilityIdea('Current Records Retrieval System', 'Qualifying Category');

    $this->get(route('discover'))
        ->assertOk()
        ->assertSeeText('Current Records Retrieval System')
        ->assertSee(route('feedback.category', [
            'category' => 'Qualifying Category',
            'idea' => 'Current Records Retrieval System',
        ]));
});

test('visibility hides a stale idea without deleting it or breaking saved idea references', function (): void {
    $student = User::factory()->create();

    $stale = staleVisibilityIdea('Retired Records Retrieval System', 'Retired Category');

    $savedIdea = SavedIdea::query()->create([
        'user_id' => $student->id,
        'title' => $stale->idea_title,
        'description' => 'A saved idea whose category no longer qualifies for generation.',
        'category' => $stale->category,
        'idea_evaluation_id' => $stale->id,
    ]);

    $this->get(route('discover'))
        ->assertOk()
        ->assertDontSeeText('Retired Records Retrieval System');

    // The retained row and the student's saved reference are both untouched.
    expect(IdeaEvaluation::query()->whereKey($stale->id)->exists())->toBeTrue()
        ->and($savedIdea->fresh()->idea_evaluation_id)->toBe($stale->id);

    $this->actingAs($student)
        ->get(route('user.ideas'))
        ->assertOk()
        ->assertSeeText('Retired Records Retrieval System')
        ->assertSeeText('View original opportunity');
});

test('the visibility helper reports qualifying categories without writing or generating', function (): void {
    Cache::flush();

    staleVisibilityQualifyCategory('Qualifying Category');
    staleVisibilityIdea('Current Records Retrieval System', 'Qualifying Category');
    staleVisibilityIdea('Retired Records Retrieval System', 'Retired Category');

    $qualifying = app(CategoryIdeaGenerationService::class)->qualifyingCategories();

    expect($qualifying->all())->toContain('Qualifying Category')
        ->and($qualifying->all())->not->toContain('Retired Category')
        // Read-only: visibility filtering must never create or persist rows.
        ->and(IdeaEvaluation::query()->count())->toBe(2);
});
