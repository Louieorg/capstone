<?php

use App\Models\Feedback;
use App\Models\User;

function duplicateWarningPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Library printer queue delays',
        'category' => 'Facilities',
        'description' => 'Students wait too long because library printer queue handling is inconsistent during peak hours.',
        'impact' => 'This delays urgent printing requirements and causes missed class submissions.',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'current_step' => 4,
    ], $overrides);
}

function duplicateWarningExistingFeedback(User $user): Feedback
{
    return Feedback::query()->create([
        'user_id' => $user->id,
        'title' => 'Library printer queue delays',
        'description' => 'Students wait too long because library printer queue handling is inconsistent during peak hours.',
        'impact' => 'This delays urgent printing requirements and causes missed class submissions.',
        'category' => 'Facilities',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
    ]);
}

test('a similar submission without force submit redirects back without creating feedback', function (): void {
    $user = User::factory()->create();
    $existingFeedback = duplicateWarningExistingFeedback($user);

    $response = $this->actingAs($user)
        ->from(route('feedback.create'))
        ->post(route('feedback.store'), duplicateWarningPayload());

    $response->assertRedirect(route('feedback.create', absolute: false));
    $response->assertSessionHas('similarProblems', fn ($similarProblems): bool => $similarProblems->contains('id', $existingFeedback->id));
    expect(Feedback::query()->count())->toBe(1);
});

test('false force submit values still show the duplicate warning', function (string $forceSubmit): void {
    $user = User::factory()->create();
    duplicateWarningExistingFeedback($user);

    $response = $this->actingAs($user)
        ->from(route('feedback.create'))
        ->post(route('feedback.store'), duplicateWarningPayload(['force_submit' => $forceSubmit]));

    $response->assertRedirect(route('feedback.create', absolute: false));
    $response->assertSessionHas('similarProblems');
    expect(Feedback::query()->count())->toBe(1);
})->with(['zero' => '0', 'false' => 'false']);

test('a similar submission with force submit is saved', function (): void {
    $user = User::factory()->create();
    duplicateWarningExistingFeedback($user);

    $this->actingAs($user)
        ->post(route('feedback.store'), duplicateWarningPayload(['force_submit' => '1']))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    expect(Feedback::query()->count())->toBe(2);
});

test('a submission without similar problems is saved without force submit', function (): void {
    $user = User::factory()->create();
    duplicateWarningExistingFeedback($user);

    $this->actingAs($user)
        ->post(route('feedback.store'), duplicateWarningPayload([
            'title' => 'Enrollment appointment availability',
            'category' => 'Enrollment',
            'description' => 'Students cannot book enrollment appointments when campus offices are fully occupied.',
        ]))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    expect(Feedback::query()->count())->toBe(2);
});

test('the duplicate warning renders explicit choices and restores submitted text', function (): void {
    $user = User::factory()->create();
    duplicateWarningExistingFeedback($user);

    $this->actingAs($user)
        ->from(route('feedback.create'))
        ->post(route('feedback.store'), duplicateWarningPayload());

    $this->get(route('feedback.create'))
        ->assertOk()
        ->assertSee('step: 4', false)
        ->assertSee('name="force_submit" value="1"', false)
        ->assertSeeText('Submit anyway')
        ->assertSee(route('feedback.index'))
        ->assertSeeText('Review existing problems')
        ->assertSeeText('If you attached files, please attach them again.')
        ->assertSee('value="Library printer queue delays"', false)
        ->assertSee('Students wait too long because library printer queue handling is inconsistent during peak hours.', false)
        ->assertSee('This delays urgent printing requirements and causes missed class submissions.', false);
});

test('the normal form has no force submit field', function (): void {
    $this->get(route('feedback.create'))
        ->assertOk()
        ->assertDontSee('force_submit', false);
});

test('an exact duplicate with force submit is saved and flagged', function (): void {
    $user = User::factory()->create();
    duplicateWarningExistingFeedback($user);

    $this->actingAs($user)
        ->post(route('feedback.store'), duplicateWarningPayload(['force_submit' => '1']))
        ->assertSessionHas('warning');

    expect(Feedback::query()->latest('id')->firstOrFail()->is_flagged)->toBeTrue();
});
