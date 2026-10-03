<?php

use App\Models\CategoryAssignment;
use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\User;
use App\Notifications\IdeaGenerated;
use App\Notifications\OfficeReportAwaitingReview;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * An institutional office report is filed by an office representative and must
 * reach the reviewers whose role is assigned to its category. Reviewer authority
 * comes from role + CategoryAssignment, never from representing the office.
 */
function reviewNotifyOffice(string $name): Office
{
    return Office::query()->create([
        'name' => $name,
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
        'contact_email' => 'review-notify@example.edu',
        'is_active' => true,
    ]);
}

function reviewNotifyPayload(?Office $office, array $overrides = []): array
{
    return array_merge([
        'title' => 'Laboratory equipment availability is tracked manually',
        'description' => 'Laboratory personnel record workstation faults on paper.',
        'impact' => 'Students lose access to working equipment.',
        'category' => 'Review Notify Category',
        'office_id' => $office?->id,
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
    ], $overrides);
}

it('notifies the reviewers assigned to the report category', function (): void {
    Notification::fake();

    $office = reviewNotifyOffice('Review Notify Office');
    CategoryAssignment::query()->create(['category' => 'Review Notify Category', 'office' => 'office_academic']);

    $representative = $office->representative;
    $reviewer = User::factory()->create(['role' => 'office_academic', 'email_verified_at' => now()]);

    $this->actingAs($representative)
        ->post(route('feedback.store'), reviewNotifyPayload($office))
        ->assertRedirect(route('feedback.submitted'));

    Notification::assertSentTo(
        $reviewer,
        OfficeReportAwaitingReview::class,
        function (OfficeReportAwaitingReview $notification) use ($reviewer): bool {
            $data = $notification->toDatabase($reviewer);

            return $notification->via($reviewer) === ['database']
                && $data['type'] === OfficeReportAwaitingReview::TYPE
                && str_contains($data['message'], 'awaiting review')
                && str_contains($data['message'], 'Review Notify Office')
                && $data['category'] === 'Review Notify Category';
        }
    );
});

it('notifies every reviewer holding the assigned role, and no other role', function (): void {
    Notification::fake();

    $office = reviewNotifyOffice('Review Multi Office');

    // A category has exactly one assigned reviewer role.
    CategoryAssignment::query()->create(['category' => 'Review Notify Category', 'office' => 'office_academic']);
    CategoryAssignment::query()->create(['category' => 'Another Review Category', 'office' => 'office_chief']);

    $firstReviewer = User::factory()->create(['role' => 'office_academic', 'email_verified_at' => now()]);
    $secondReviewer = User::factory()->create(['role' => 'office_academic', 'email_verified_at' => now()]);
    $chiefOfAnotherCategory = User::factory()->create(['role' => 'office_chief', 'email_verified_at' => now()]);

    $this->actingAs($office->representative)
        ->post(route('feedback.store'), reviewNotifyPayload($office));

    Notification::assertSentTo($firstReviewer, OfficeReportAwaitingReview::class);
    Notification::assertSentTo($secondReviewer, OfficeReportAwaitingReview::class);
    Notification::assertSentToTimes($chiefOfAnotherCategory, OfficeReportAwaitingReview::class, 0);
});

it('never notifies the representative, an admin, or an office head', function (): void {
    Notification::fake();

    $office = reviewNotifyOffice('Review Non Reviewer Office');
    CategoryAssignment::query()->create(['category' => 'Review Notify Category', 'office' => 'office_academic']);

    $representative = $office->representative;
    $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    $officeHead = User::factory()->create(['role' => 'user', 'is_office_head' => true, 'email_verified_at' => now()]);

    $this->actingAs($representative)
        ->post(route('feedback.store'), reviewNotifyPayload($office));

    Notification::assertNotSentTo($representative, OfficeReportAwaitingReview::class);
    Notification::assertNotSentTo($admin, OfficeReportAwaitingReview::class);
    Notification::assertNotSentTo($officeHead, OfficeReportAwaitingReview::class);
});

it('does not notify reviewers when a plain community report is filed', function (): void {
    Notification::fake();

    CategoryAssignment::query()->create(['category' => 'Review Notify Category', 'office' => 'office_academic']);
    $reviewer = User::factory()->create(['role' => 'office_academic', 'email_verified_at' => now()]);
    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)->post(route('feedback.store'), reviewNotifyPayload(null, ['office_id' => null]));

    Notification::assertNotSentTo($reviewer, OfficeReportAwaitingReview::class);
});

it('sends the reviewer to the office review queue and marks it read', function (): void {
    $office = reviewNotifyOffice('Review Redirect Office');
    CategoryAssignment::query()->create(['category' => 'Review Notify Category', 'office' => 'office_academic']);

    $reviewer = User::factory()->create(['role' => 'office_academic', 'email_verified_at' => now()]);

    $this->actingAs($office->representative)->post(route('feedback.store'), reviewNotifyPayload($office));

    $notification = $reviewer->notifications()->sole();

    $this->actingAs($reviewer)
        ->get(route('notifications.redirect', $notification))
        ->assertRedirect(route('office.review.index'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('shows the report notification in the existing bell dropdown', function (): void {
    $office = reviewNotifyOffice('Review Dropdown Office');
    CategoryAssignment::query()->create(['category' => 'Review Notify Category', 'office' => 'office_academic']);

    $reviewer = User::factory()->create(['role' => 'office_academic', 'email_verified_at' => now()]);

    $this->actingAs($office->representative)->post(route('feedback.store'), reviewNotifyPayload($office));

    $dropdown = $this->actingAs($reviewer)
        ->get(route('notifications.dropdown'))
        ->assertOk()
        ->json();

    expect($dropdown['has_notifications'])->toBeTrue()
        ->and($dropdown['html'])->toContain('awaiting review');
});

it('does not let a non-recipient open the report notification', function (): void {
    $office = reviewNotifyOffice('Review Ownership Office');
    CategoryAssignment::query()->create(['category' => 'Review Notify Category', 'office' => 'office_academic']);

    $reviewer = User::factory()->create(['role' => 'office_academic', 'email_verified_at' => now()]);

    $this->actingAs($office->representative)->post(route('feedback.store'), reviewNotifyPayload($office));

    $notification = $reviewer->notifications()->sole();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('notifications.redirect', $notification))
        ->assertNotFound();
});

it('reaches dss generation on approval and notifies the contributor', function (): void {
    Notification::fake();

    $office = reviewNotifyOffice('Review Approve Office');
    CategoryAssignment::query()->create(['category' => 'Review Notify Category', 'office' => 'office_academic']);

    $representative = $office->representative;
    $reviewer = User::factory()->create(['role' => 'office_academic', 'email_verified_at' => now()]);

    $this->actingAs($representative)->post(route('feedback.store'), reviewNotifyPayload($office));

    $feedback = \App\Models\Feedback::query()->latest('id')->firstOrFail();

    expect($feedback->status)->toBe('pending');

    $this->actingAs($reviewer)->patch(route('office.review.approve', $feedback->id))->assertRedirect();

    expect($feedback->fresh()->status)->toBe('approved')
        ->and($feedback->fresh()->reviewed_by)->toBe($reviewer->id);

    // Approval reaches the DSS pipeline, which notifies the contributing user.
    Notification::assertSentTo($representative, IdeaGenerated::class);
});

it('does not generate dss ideas when the report is rejected', function (): void {
    Notification::fake();

    $office = reviewNotifyOffice('Review Reject Office');
    CategoryAssignment::query()->create(['category' => 'Review Notify Category', 'office' => 'office_academic']);

    $representative = $office->representative;
    $reviewer = User::factory()->create(['role' => 'office_academic', 'email_verified_at' => now()]);

    $this->actingAs($representative)->post(route('feedback.store'), reviewNotifyPayload($office));

    $feedback = \App\Models\Feedback::query()->latest('id')->firstOrFail();

    $this->actingAs($reviewer)->patch(route('office.review.reject', $feedback->id))->assertRedirect();

    Cache::flush();

    expect($feedback->fresh()->status)->toBe('rejected')
        ->and(IdeaEvaluation::query()->where('office_id', $office->id)->exists())->toBeFalse();

    Notification::assertNotSentTo($representative, IdeaGenerated::class);
});
