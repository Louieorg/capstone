<?php

use App\Models\Feedback;
use App\Models\FeedbackVote;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

test('authenticated users can submit feedback with a department', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Registrar queue delays',
        'category' => 'Enrollment',
        'department' => 'Registrar',
        'description' => 'Students wait too long because the queue handling is inconsistent.',
        'impact' => 'This delays enrollment and affects class attendance for many students.',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));
    $response->assertSessionHas('success', 'Problem submitted successfully.');

    $this->assertDatabaseHas('feedback', [
        'title' => 'Registrar queue delays',
        'department' => 'Registrar',
        'user_id' => $user->id,
    ]);
});

test('authenticated users can submit feedback with a custom department', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Special office processing delays',
        'category' => 'Academic Process',
        'department' => 'Other',
        'department_other' => 'Research Center',
        'description' => 'Requests are delayed because approvals rely on a manual handoff every week.',
        'impact' => 'This slows student and staff transactions and creates repeated follow ups.',
        'frequency' => 'Sometimes',
        'current_process' => 'Send an email or message',
        'affected_users' => 'Less than 50',
        'affected_group' => ['Staff'],
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));

    expect(Feedback::query()->where('title', 'Special office processing delays')->value('department'))
        ->toBe('Research Center');
});

test('authenticated users can submit feedback without selecting a department', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Library printer delays',
        'category' => 'Facilities',
        'description' => 'Printer access is delayed and students have to wait a long time for assistance.',
        'impact' => 'This causes missed submissions and delays urgent printing needs.',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => 'Less than 50',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));
    $response->assertSessionDoesntHaveErrors(['department']);

    expect(Feedback::query()->where('title', 'Library printer delays')->value('department'))
        ->toBeNull();
});

test('community validation updates after a new vote is added', function () {
    Cache::flush();

    $reporter = User::factory()->create();
    $voter = User::factory()->create();

    $feedbacks = collect(range(1, 3))->map(function (int $index) use ($reporter) {
        return Feedback::query()->create([
            'user_id' => $reporter->id,
            'title' => "Class schedule conflict {$index}",
            'description' => 'Students experience recurring schedule conflicts and overlapping class times.',
            'impact' => 'This disrupts attendance, planning, and course completion for many students.',
            'category' => 'Scheduling',
            'frequency' => 'Often',
            'current_process' => 'Manual or paper-based process',
            'affected_users' => '50-200',
            'affected_group' => ['Students'],
            'is_anonymous' => false,
            'status' => 'approved',
        ]);
    });

    $baselineVoters = User::factory()->count(10)->create();

    $feedbacks->each(function (Feedback $feedback) use ($baselineVoters): void {
        $baselineVoters->each(function (User $user) use ($feedback): void {
            FeedbackVote::query()->create([
                'feedback_id' => $feedback->id,
                'user_id' => $user->id,
            ]);
        });
    });

    $this->get(route('feedback.category', ['category' => 'Scheduling']))
        ->assertOk()
        ->assertSeeText('3.75 / 5');

    FeedbackVote::query()->create([
        'feedback_id' => $feedbacks->first()->id,
        'user_id' => $voter->id,
    ]);

    $this->get(route('feedback.category', ['category' => 'Scheduling']))
        ->assertOk()
        ->assertSeeText('3.88 / 5');
});
