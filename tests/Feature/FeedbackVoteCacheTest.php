<?php

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

test('voting clears the current users cached home feed', function () {
    $author = User::factory()->create();
    $voter = User::factory()->create();
    $feedback = Feedback::query()->create([
        'user_id' => $author->id,
        'title' => 'Long registrar queues',
        'description' => 'Students wait in long lines because enrollment requests are handled manually.',
        'impact' => 'Enrollment delays prevent students from finalizing their schedules on time.',
        'category' => 'Enrollment',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'status' => 'approved',
    ]);

    $cacheKey = "home-page-data:{$voter->id}:votes-10:reports-3";

    $this->actingAs($voter)->get(route('home'))->assertOk();
    expect(Cache::has($cacheKey))->toBeTrue();

    $this->post(route('feedback.vote', $feedback), [], ['referer' => route('home')])
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Problem supported.');

    expect(Cache::has($cacheKey))->toBeFalse();

    $this->get(route('home'))->assertSeeText('Support 1');

    $this->assertDatabaseHas('feedback_votes', [
        'feedback_id' => $feedback->id,
        'user_id' => $voter->id,
    ]);
});
