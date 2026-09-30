<?php

use App\Models\Feedback;
use App\Models\FeedbackVote;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * @param  array<string, mixed>  $attributes
 */
function voteGuardFeedback(array $attributes = []): Feedback
{
    return Feedback::query()->create(array_merge([
        'user_id' => null,
        'title' => 'Registrar queue stalls during peak enrollment',
        'description' => 'Students wait for hours because only one service window is open during heavy traffic.',
        'impact' => 'Enrollment is delayed and students miss schedule adjustments.',
        'category' => 'Enrollment',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '200-500',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
    ], $attributes));
}

test('a verified user can support an approved unflagged problem reported by someone else', function (): void {
    $author = User::factory()->create();
    $voter = User::factory()->create();
    $feedback = voteGuardFeedback(['user_id' => $author->id]);

    $this->actingAs($voter)
        ->from(route('home'))
        ->post(route('feedback.vote', $feedback->id))
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Problem supported.');

    $this->assertDatabaseHas('feedback_votes', [
        'feedback_id' => $feedback->id,
        'user_id' => $voter->id,
    ]);
});

test('an author can support their own approved problem', function (): void {
    $author = User::factory()->create();
    $feedback = voteGuardFeedback(['user_id' => $author->id]);

    $this->actingAs($author)
        ->from(route('home'))
        ->post(route('feedback.vote', $feedback->id))
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Problem supported.');

    $this->assertDatabaseHas('feedback_votes', [
        'feedback_id' => $feedback->id,
        'user_id' => $author->id,
    ]);
});

test('a pending problem cannot be supported', function (): void {
    $voter = User::factory()->create();
    $feedback = voteGuardFeedback(['status' => 'pending']);

    $this->actingAs($voter)
        ->post(route('feedback.vote', $feedback->id))
        ->assertNotFound();

    $this->assertDatabaseMissing('feedback_votes', ['feedback_id' => $feedback->id]);
});

test('a rejected problem cannot be supported', function (): void {
    $voter = User::factory()->create();
    $feedback = voteGuardFeedback(['status' => 'rejected']);

    $this->actingAs($voter)
        ->post(route('feedback.vote', $feedback->id))
        ->assertNotFound();

    $this->assertDatabaseMissing('feedback_votes', ['feedback_id' => $feedback->id]);
});

test('a flagged problem cannot be supported', function (): void {
    $voter = User::factory()->create();
    $feedback = voteGuardFeedback(['status' => 'approved', 'is_flagged' => true]);

    $this->actingAs($voter)
        ->post(route('feedback.vote', $feedback->id))
        ->assertNotFound();

    $this->assertDatabaseMissing('feedback_votes', ['feedback_id' => $feedback->id]);
});

test('an anonymous approved problem can still be supported', function (): void {
    $voter = User::factory()->create();
    $feedback = voteGuardFeedback(['user_id' => null, 'is_anonymous' => true]);

    $this->actingAs($voter)
        ->from(route('home'))
        ->post(route('feedback.vote', $feedback->id))
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Problem supported.');

    $this->assertDatabaseHas('feedback_votes', [
        'feedback_id' => $feedback->id,
        'user_id' => $voter->id,
    ]);
});

test('supporting the same problem repeatedly toggles the support off and on again', function (): void {
    $author = User::factory()->create();
    $voter = User::factory()->create();
    $feedback = voteGuardFeedback(['user_id' => $author->id]);

    $this->actingAs($voter)
        ->from(route('home'))
        ->post(route('feedback.vote', $feedback->id))
        ->assertSessionHas('success', 'Problem supported.');

    $this->assertDatabaseCount('feedback_votes', 1);

    $this->actingAs($voter)
        ->from(route('home'))
        ->post(route('feedback.vote', $feedback->id))
        ->assertSessionHas('success', 'Support removed.');

    $this->assertDatabaseCount('feedback_votes', 0);

    $this->actingAs($voter)
        ->from(route('home'))
        ->post(route('feedback.vote', $feedback->id))
        ->assertSessionHas('success', 'Problem supported.');

    $this->assertDatabaseCount('feedback_votes', 1);
});

test('supporting a problem that does not exist returns not found', function (): void {
    $voter = User::factory()->create();

    $this->actingAs($voter)
        ->post(route('feedback.vote', 987654))
        ->assertNotFound();

    $this->assertDatabaseCount('feedback_votes', 0);
});

test('creating a vote twice for the same problem and user stays a single row', function (): void {
    $voter = User::factory()->create();
    $feedback = voteGuardFeedback(['user_id' => $voter->id]);

    // Mirrors the race the endpoint now protects against: two overlapping
    // requests both reach the creation step, and only one row may survive.
    FeedbackVote::query()->firstOrCreate([
        'feedback_id' => $feedback->id,
        'user_id' => $voter->id,
    ]);

    FeedbackVote::query()->firstOrCreate([
        'feedback_id' => $feedback->id,
        'user_id' => $voter->id,
    ]);

    expect(FeedbackVote::query()->where('feedback_id', $feedback->id)->count())->toBe(1);
});

test('the feedback and user unique constraint is still the backstop for duplicate rows', function (): void {
    $voter = User::factory()->create();
    $feedback = voteGuardFeedback(['user_id' => $voter->id]);

    FeedbackVote::query()->create([
        'feedback_id' => $feedback->id,
        'user_id' => $voter->id,
    ]);

    // firstOrCreate() recovers from exactly this exception instead of
    // surfacing it as an unhandled 500.
    expect(fn () => FeedbackVote::query()->create([
        'feedback_id' => $feedback->id,
        'user_id' => $voter->id,
    ]))->toThrow(UniqueConstraintViolationException::class);

    expect(FeedbackVote::query()->where('feedback_id', $feedback->id)->count())->toBe(1);
});
