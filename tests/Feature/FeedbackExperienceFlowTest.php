<?php

use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\User;

it('shows the redesigned home feed with approved problems', function () {
    $user = User::factory()->create();
    $feedback = Feedback::query()->create([
        'user_id' => $user->id,
        'title' => 'Registrar queue stalls during peak enrollment',
        'description' => 'Students wait for hours because only one service window is open during heavy traffic.',
        'impact' => 'Enrollment is delayed and students miss schedule adjustments.',
        'category' => 'Enrollment',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '200-500',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'status' => 'approved',
    ]);

    FeedbackVote::query()->create([
        'feedback_id' => $feedback->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('Home Feed')
        ->assertSeeText('Latest Problems')
        ->assertSeeText('Registrar queue stalls during peak enrollment')
        ->assertSeeText('Support 1')
        ->assertSee('Support this problem');
});

it('allows authenticated users to add supporting experience comments', function () {
    $user = User::factory()->create();
    $feedback = Feedback::query()->create([
        'user_id' => $user->id,
        'title' => 'Library printing backlog',
        'description' => 'Students queue too long because there are not enough working printers.',
        'impact' => 'Urgent coursework submissions are delayed for multiple sections.',
        'category' => 'Library',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'status' => 'approved',
    ]);

    $this->actingAs($user)
        ->post(route('feedback.comments.store', $feedback), [
            'body' => 'The same issue affected our section again this week because two printers were still offline.',
        ])
        ->assertRedirect(route('feedback.show', $feedback));

    expect(FeedbackComment::query()->where('feedback_id', $feedback->id)->count())->toBe(1);

    $this->get(route('feedback.show', $feedback))
        ->assertOk()
        ->assertSeeText('Community experiences')
        ->assertSeeText('two printers were still offline');
});

it('shows related problems and timeline context on the problem detail page', function () {
    $author = User::factory()->create();

    $first = Feedback::query()->create([
        'user_id' => $author->id,
        'title' => 'Enrollment queue remains too slow',
        'description' => 'Students wait for several hours because registrar processing remains manual.',
        'impact' => 'This delays clearance and sectioning for many students.',
        'category' => 'Enrollment',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '200-500',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'status' => 'approved',
        'created_at' => now()->subMonths(2),
    ]);

    $second = Feedback::query()->create([
        'user_id' => $author->id,
        'title' => 'Registrar line still grows every semester',
        'description' => 'Enrollment queue management is manual and students keep waiting for hours.',
        'impact' => 'This disrupts schedule adjustments and advising support.',
        'category' => 'Enrollment',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '200-500',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'status' => 'approved',
        'created_at' => now()->subMonth(),
    ]);

    FeedbackVote::query()->create([
        'feedback_id' => $first->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $this->get(route('feedback.show', $first))
        ->assertOk()
        ->assertSeeText('Related Problems')
        ->assertSeeText('Registrar line still grows every semester')
        ->assertSeeText('Knowledge growth');
});
