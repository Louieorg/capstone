<?php

use App\Models\AdviserReview;
use App\Models\IdeaEvaluation;
use App\Models\User;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function adviserReviewValidationPayload(array $overrides = []): array
{
    return array_merge([
        'idea_title' => 'Campus Request Tracker',
        'category' => 'Academic Process',
        'comment' => 'The proposal addresses a recurring campus workflow issue.',
        'recommendation' => 'Recommended',
        'feasibility' => 4,
        'impact' => 5,
        'complexity' => 3,
        'innovation' => 2,
    ], $overrides);
}

test('adviser review requires its identifying and review fields', function (string $field): void {
    $adviser = User::factory()->create(['role' => 'adviser']);
    $payload = adviserReviewValidationPayload();
    unset($payload[$field]);

    $this->actingAs($adviser)
        ->from(route('adviser.dashboard'))
        ->post(route('adviser.review'), $payload)
        ->assertSessionHasErrors($field);

    expect(AdviserReview::query()->count())->toBe(0);
})->with([
    'idea title' => 'idea_title',
    'category' => 'category',
    'comment' => 'comment',
    'recommendation' => 'recommendation',
    'feasibility score' => 'feasibility',
    'impact score' => 'impact',
    'complexity score' => 'complexity',
    'innovation score' => 'innovation',
]);

test('adviser review scores must be integers from one through five', function (string $field, mixed $value): void {
    $adviser = User::factory()->create(['role' => 'adviser']);

    $this->actingAs($adviser)
        ->from(route('adviser.dashboard'))
        ->post(route('adviser.review'), adviserReviewValidationPayload([$field => $value]))
        ->assertSessionHasErrors($field);

    expect(AdviserReview::query()->count())->toBe(0);
})->with([
    'feasibility below one' => ['feasibility', 0],
    'impact above five' => ['impact', 6],
    'complexity is nonnumeric' => ['complexity', 'high'],
    'innovation is fractional' => ['innovation', 3.5],
]);

test('adviser review rejects recommendations outside the database choices', function (): void {
    $adviser = User::factory()->create(['role' => 'adviser']);

    $this->actingAs($adviser)
        ->from(route('adviser.dashboard'))
        ->post(route('adviser.review'), adviserReviewValidationPayload([
            'recommendation' => 'Maybe',
        ]))
        ->assertSessionHasErrors('recommendation');

    expect(AdviserReview::query()->count())->toBe(0);
});

test('valid adviser review stores the authenticated identity and updates a matching evaluation', function (): void {
    $adviser = User::factory()->create(['role' => 'adviser']);
    $otherUser = User::factory()->create();
    $evaluation = IdeaEvaluation::query()->create([
        'idea_title' => 'Campus Request Tracker',
        'category' => 'Academic Process',
        'overall_score' => 4.0,
    ]);

    $this->actingAs($adviser)
        ->from(route('adviser.dashboard'))
        ->post(route('adviser.review'), adviserReviewValidationPayload([
            'user_id' => $otherUser->id,
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $review = AdviserReview::query()->firstOrFail();
    $evaluation->refresh();

    expect($review->user_id)->toBe($adviser->id)
        ->and($review->idea_title)->toBe('Campus Request Tracker')
        ->and($review->category)->toBe('Academic Process')
        ->and($review->recommendation)->toBe('Recommended')
        ->and((int) $evaluation->adviser_feasibility)->toBe(4)
        ->and((int) $evaluation->adviser_impact)->toBe(5)
        ->and((int) $evaluation->adviser_complexity)->toBe(3)
        ->and((int) $evaluation->adviser_innovation)->toBe(2)
        ->and((float) $evaluation->final_score)->toBe(3.93);
});

test('valid adviser review remains accepted when no evaluation matches', function (): void {
    $adviser = User::factory()->create(['role' => 'adviser']);

    $this->actingAs($adviser)
        ->from(route('adviser.dashboard'))
        ->post(route('adviser.review'), adviserReviewValidationPayload([
            'idea_title' => 'No matching idea',
            'category' => 'Unmatched Category',
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(AdviserReview::query()->count())->toBe(1)
        ->and(IdeaEvaluation::query()->count())->toBe(0);
});
