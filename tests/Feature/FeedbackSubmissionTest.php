<?php

use App\Models\Feedback;
use App\Models\FeedbackVote;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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

test('authenticated users can submit feedback when affected groups include a null entry', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Null entry in affected groups',
        'category' => 'Facilities',
        'description' => 'The form should still save when one affected group entry is empty or null.',
        'impact' => 'This keeps the submission flow working even when optional checkbox values are blank.',
        'frequency' => 'Sometimes',
        'current_process' => 'Report verbally to staff',
        'affected_users' => 'Less than 50',
        'affected_group' => ['Students', null],
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));
    $response->assertSessionHas('success', 'Problem submitted successfully.');

    $feedback = Feedback::query()
        ->where('title', 'Null entry in affected groups')
        ->firstOrFail();

    expect($feedback->affected_group)->toEqual(['Students']);
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

test('authenticated users can submit feedback without uploading any files', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Campus Wi-Fi access issues',
        'category' => 'Facilities',
        'description' => 'Students cannot reliably access campus Wi-Fi in the library and study rooms.',
        'impact' => 'This disrupts learning, assignments, and student work during busy hours.',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));
    $response->assertSessionHas('success', 'Problem submitted successfully.');

    $this->assertDatabaseHas('feedback', [
        'title' => 'Campus Wi-Fi access issues',
        'user_id' => $user->id,
    ]);
});

test('a submission for an active office the user represents is stored against that office', function () {
    $user = User::factory()->create();
    $office = Office::query()->create([
        'name' => 'Academic Success Office',
        'representative_user_id' => $user->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Office association metadata should be stored separately',
        'category' => 'Facilities',
        'description' => 'The report should record the office context while keeping the review pipeline unchanged.',
        'impact' => 'This tests the office association path and its institutional qualification.',
        'frequency' => 'Sometimes',
        'current_process' => 'Report verbally to staff',
        'affected_users' => 'Less than 50',
        'affected_group' => ['Students'],
        'office_id' => $office->id,
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()
        ->where('title', 'Office association metadata should be stored separately')
        ->firstOrFail();

    expect($feedback->office_id)->toBe($office->id);
});

test('a user cannot associate a report with an office they do not represent', function () {
    $representative = User::factory()->create();
    $office = Office::query()->create([
        'name' => 'Registrar Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);
    $otherUser = User::factory()->create();

    $response = $this->actingAs($otherUser)->post(route('feedback.store'), [
        'title' => 'Tampered office assignment attempt',
        'category' => 'Facilities',
        'description' => 'The office metadata should not be accepted when the user does not represent that office.',
        'impact' => 'This ensures the optional office field is enforced server-side and cannot be spoofed.',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'office_id' => $office->id,
        'force_submit' => '1',
    ]);

    $response->assertSessionHasErrors('office_id');
    $this->assertDatabaseMissing('feedback', ['title' => 'Tampered office assignment attempt']);
});

test('authenticated users can submit feedback when choosing other category and process without extra details', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Other category report',
        'category' => 'Other',
        'description' => 'A report that can be submitted even when the custom category detail is left blank.',
        'impact' => 'This affects students and staff in daily operations and should still be saved.',
        'frequency' => 'Often',
        'current_process' => 'Other',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));
    $this->assertDatabaseHas('feedback', [
        'title' => 'Other category report',
        'category' => 'Other',
        'current_process' => 'Other',
        'user_id' => $user->id,
    ]);
});

test('authenticated users can submit an Other category with a blank custom category detail', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Unspecified campus service delay',
        'category' => 'Other',
        'category_other' => '',
        'description' => 'Students and staff wait for a service that is not covered by the listed categories.',
        'impact' => 'This interrupts daily transactions for students and staff visiting the office.',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));
    $response->assertSessionHasNoErrors();

    $feedback = Feedback::query()
        ->where('title', 'Unspecified campus service delay')
        ->firstOrFail();

    expect($feedback->category)->toBe('Other')
        ->and($feedback->category_other)->toBeNull()
        ->and($feedback->is_flagged)->toBeFalse();
});

test('authenticated users can submit feedback with supporting evidence', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Damaged laboratory equipment',
        'category' => 'Facilities',
        'description' => 'The equipment is visibly damaged and students cannot complete the lab activity safely.',
        'impact' => 'This blocks required activities and may create safety issues for students.',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'attachment' => UploadedFile::fake()->createWithContent(
            'unsafe equipment.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        ),
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()
        ->where('title', 'Damaged laboratory equipment')
        ->firstOrFail();

    expect($feedback->attachment_path)->toStartWith('attachments/unsafe-equipment-')
        ->and($feedback->attachment_type)->toBe('image');

    Storage::disk('public')->assertExists($feedback->attachment_path);
});

test('low quality placeholder feedback is rejected before saving', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'test problem',
        'category' => 'Facilities',
        'description' => 'test sample none 123',
        'impact' => 'aaaaaa',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => 'Less than 50',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ]);

    $response->assertSessionHasErrors(['title', 'description', 'impact']);

    $this->assertDatabaseMissing('feedback', [
        'title' => 'test problem',
    ]);
});

test('duplicate feedback is flagged and excluded from normal processing', function () {
    $user = User::factory()->create();

    Feedback::query()->create([
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
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Library printer queue delays',
        'category' => 'Facilities',
        'description' => 'Students wait too long because library printer queue handling is inconsistent during peak hours.',
        'impact' => 'This delays urgent printing requirements and causes missed class submissions.',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ]);

    $response->assertRedirect(route('feedback.submitted', absolute: false));
    $response->assertSessionHas('warning');

    expect(Feedback::query()->where('title', 'Library printer queue delays')->latest('id')->first()->is_flagged)
        ->toBeTrue();
});

test('rapid repeated submissions are flagged for admin review', function () {
    $user = User::factory()->create();

    collect(range(1, 3))->each(function (int $index) use ($user): void {
        Feedback::query()->create([
            'user_id' => $user->id,
            'title' => "Rapid valid campus concern {$index}",
            'description' => "Students experience a unique service delay in office {$index} during enrollment processing hours.",
            'impact' => 'This creates repeated follow ups and disrupts student transaction schedules.',
            'category' => 'Enrollment',
            'frequency' => 'Often',
            'current_process' => 'Manual or paper-based process',
            'affected_users' => '50-200',
            'affected_group' => ['Students'],
            'is_anonymous' => false,
            'status' => 'pending',
            'created_at' => now()->subSeconds(20),
        ]);
    });

    $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Rapid valid campus concern final',
        'category' => 'Enrollment',
        'description' => 'Students experience another documented service delay during enrollment processing hours.',
        'impact' => 'This creates repeated follow ups and disrupts student transaction schedules.',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ])->assertSessionHas('warning');

    expect(Feedback::query()->where('title', 'Rapid valid campus concern final')->first()->is_flagged)
        ->toBeTrue();
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
