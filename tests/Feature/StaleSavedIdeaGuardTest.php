<?php

use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\SavedIdea;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Hardening #9 — a NEW saved idea may only be created from an IdeaEvaluation
 * whose category is still a current DSS-qualified category.
 *
 * IdeaEvaluation rows stay stored forever, and an existing saved idea stays a
 * historical personal record. Only the creation path is guarded.
 */
function guardIdea(array $attributes = []): IdeaEvaluation
{
    return IdeaEvaluation::query()->create(array_merge([
        'idea_title' => 'Campus Records Retrieval System',
        'category' => 'Guard Category',
        'feasibility' => 4,
        'impact' => 5,
        'complexity' => 3,
        'innovation' => 4,
        'overall_score' => 4.2,
        'recommendation' => 'Highly Recommended',
    ], $attributes));
}

function guardReport(array $attributes = []): Feedback
{
    return Feedback::query()->create(array_merge([
        'title' => 'Records retrieval is handled on paper',
        'description' => 'Students and staff cannot find records because retrieval depends on manual or paper-based processes.',
        'impact' => 'Repeated follow ups delay every student transaction that needs a record.',
        'category' => 'Guard Category',
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

/**
 * Make a category current the established institutional way: one
 * office-approved, capstone-worthy report qualifies its cluster without
 * meeting the report and vote thresholds.
 */
function guardQualifyCategory(string $category = 'Guard Category'): void
{
    guardReport([
        'category' => $category,
        'title' => 'Office identified records retrieval gap in '.$category,
        'is_capstone_worthy' => true,
        'capstone_marked_at' => now(),
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function guardSavePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Campus Records Retrieval System',
        'description' => 'A single place for students to track records requests across campus offices.',
        'category' => 'Guard Category',
    ], $overrides);
}

test('a current evaluation can still be saved', function (): void {
    $student = User::factory()->create();
    guardQualifyCategory();

    $evaluation = guardIdea();

    $this->actingAs($student)
        ->post(route('idea.save'), guardSavePayload())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success')
        ->assertRedirect(route('feedback.category', [
            'category' => $evaluation->category,
            'idea' => $evaluation->idea_title,
        ]).'#idea-'.Str::slug($evaluation->idea_title));

    $idea = SavedIdea::query()->where('user_id', $student->id)->firstOrFail();

    expect($idea->idea_evaluation_id)->toBe($evaluation->id)
        ->and($idea->title)->toBe($evaluation->idea_title)
        ->and($idea->category)->toBe($evaluation->category);
});

test('a stale evaluation cannot be newly saved', function (): void {
    $student = User::factory()->create();

    // The evaluation row exists and is retained, but its category has no
    // qualifying feedback at all.
    guardIdea(['category' => 'Dormant Category', 'idea_title' => 'Dormant Records System']);

    $this->actingAs($student)
        ->from(route('discover'))
        ->post(route('idea.save'), guardSavePayload([
            'title' => 'Dormant Records System',
            'category' => 'Dormant Category',
        ]))
        ->assertSessionHasErrors('title');

    expect(SavedIdea::query()->count())->toBe(0)
        ->and(IdeaEvaluation::query()->where('idea_title', 'Dormant Records System')->exists())->toBeTrue();
});

test('an existing stale saved idea remains visible', function (): void {
    $student = User::factory()->create();

    // Created directly so the historical record does not depend on the new
    // save guard. Its category is never made to qualify.
    $evaluation = guardIdea(['category' => 'Dormant Category', 'idea_title' => 'Historic Records System']);

    SavedIdea::query()->create([
        'user_id' => $student->id,
        'title' => $evaluation->idea_title,
        'description' => 'A historical saved idea whose category no longer qualifies.',
        'category' => $evaluation->category,
        'idea_evaluation_id' => $evaluation->id,
    ]);

    $this->actingAs($student)
        ->get(route('user.ideas'))
        ->assertOk()
        ->assertSeeText('Historic Records System')
        ->assertSeeText('View original opportunity');
});

test('a tampered idea evaluation id is ignored', function (): void {
    $student = User::factory()->create();
    guardQualifyCategory();

    $evaluation = guardIdea();

    $otherEvaluation = guardIdea([
        'idea_title' => 'Unrelated System',
        'category' => 'Guard Category',
    ]);

    $this->actingAs($student)
        ->post(route('idea.save'), guardSavePayload([
            'idea_evaluation_id' => $otherEvaluation->id,
        ]))
        ->assertSessionHasNoErrors();

    $idea = SavedIdea::query()->where('user_id', $student->id)->firstOrFail();

    expect($idea->idea_evaluation_id)->toBe($evaluation->id)
        ->and(SavedIdea::query()->count())->toBe(1);
});

test('a tampered user id is ignored', function (): void {
    $student = User::factory()->create();
    $intruder = User::factory()->create();
    guardQualifyCategory();

    guardIdea();

    $this->actingAs($student)
        ->post(route('idea.save'), guardSavePayload(['user_id' => $intruder->id]))
        ->assertSessionHasNoErrors();

    expect(SavedIdea::query()->where('user_id', $student->id)->count())->toBe(1)
        ->and(SavedIdea::query()->where('user_id', $intruder->id)->count())->toBe(0);
});

test('a mismatched title and category cannot attach to another evaluation', function (): void {
    $student = User::factory()->create();
    guardQualifyCategory();

    guardIdea(['idea_title' => 'Campus Records Retrieval System', 'category' => 'Guard Category']);

    $this->actingAs($student)
        ->from(route('discover'))
        ->post(route('idea.save'), guardSavePayload([
            'title' => 'Campus Records Retrieval System',
            'category' => 'Some Other Category',
        ]))
        ->assertSessionHasErrors('title');

    expect(SavedIdea::query()->count())->toBe(0);
});

test('saving the same current idea twice still creates only one row', function (): void {
    $student = User::factory()->create();
    guardQualifyCategory();

    guardIdea();

    $this->actingAs($student)->post(route('idea.save'), guardSavePayload())->assertSessionHas('success');
    $this->actingAs($student)->post(route('idea.save'), guardSavePayload())->assertSessionHas('info');

    expect(SavedIdea::query()->where('user_id', $student->id)->count())->toBe(1);
});

test('the saved idea status lifecycle is unaffected by the guard', function (): void {
    $student = User::factory()->create();
    guardQualifyCategory();

    $evaluation = guardIdea();

    $idea = SavedIdea::query()->create([
        'user_id' => $student->id,
        'title' => $evaluation->idea_title,
        'description' => 'A saved idea tracked with an adopted status.',
        'category' => $evaluation->category,
        'idea_evaluation_id' => $evaluation->id,
    ]);

    $this->assertDatabaseHas('saved_ideas', [
        'id' => $idea->id,
        'status' => 'Exploring',
    ]);

    $this->actingAs($student)
        ->from(route('user.ideas'))
        ->patch(route('idea.updateStatus', $idea->id), ['status' => 'Completed'])
        ->assertRedirect(route('user.ideas'))
        ->assertSessionHas('success');

    expect($idea->fresh()->status)->toBe('Completed');
});
