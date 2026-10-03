<?php

use App\Models\CategoryAssignment;
use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\OfficeConfirmationRequest;
use App\Models\User;
use App\Notifications\OfficeConfirmationRequested;
use App\Services\CategoryIdeaGenerationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * A student asks an office to confirm a capstone opportunity. The office's
 * CURRENT representative has to hear about it through the existing notification
 * bell, and nobody else may.
 */
function officeNotificationOffice(array $attributes = []): Office
{
    $representative = User::factory()->create(array_merge([
        'role' => 'user',
        'name' => 'Current Office Representative',
    ], $attributes['representative'] ?? []));

    return Office::query()->create(array_merge([
        'name' => 'Notification Test Office',
        'representative_user_id' => $representative->id,
        'contact_email' => 'office-notify@example.edu',
        'is_active' => true,
    ], collect($attributes)->except('representative')->all()));
}

function officeNotificationEvaluation(Office $office, string $category = 'Notification Category'): IdeaEvaluation
{
    Feedback::query()->create([
        'title' => 'Laboratory equipment availability is tracked manually',
        'description' => 'Laboratory personnel record workstation faults on paper and cannot see availability in real time.',
        'impact' => 'Students lose access to working equipment while faults stay unresolved.',
        'category' => $category,
        'office_id' => $office->id,
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students', 'Staff'],
        'is_anonymous' => false,
        'is_flagged' => false,
        'status' => 'approved',
        'is_capstone_worthy' => true,
        'capstone_marked_at' => now(),
    ]);

    Cache::flush();
    app(CategoryIdeaGenerationService::class)->generate($category, $office->id, false);

    return IdeaEvaluation::query()
        ->where('category', $category)
        ->where('office_id', $office->id)
        ->latest('id')
        ->firstOrFail();
}

it('notifies the current office representative exactly once when a request is created', function (): void {
    Notification::fake();

    $office = officeNotificationOffice();
    $evaluation = officeNotificationEvaluation($office);
    $student = User::factory()->create(['role' => 'user', 'name' => 'Rina Santos']);

    $this->actingAs($student)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertRedirect();

    $request = OfficeConfirmationRequest::query()
        ->where('requester_user_id', $student->id)
        ->where('idea_evaluation_id', $evaluation->id)
        ->sole();

    $representative = $office->representative;

    Notification::assertSentTo(
        $representative,
        OfficeConfirmationRequested::class,
        function (OfficeConfirmationRequested $notification, array $channels) use ($representative, $evaluation, $request): bool {
            return $notification->via($representative) === ['database']
                && $notification->toDatabase($representative)['message']
                    === "Rina Santos requested office confirmation for {$evaluation->idea_title}."
                && $notification->toDatabase($representative)['idea_evaluation_id'] === (int) $evaluation->id
                && $notification->toDatabase($representative)['confirmation_request_id'] === (int) $request->id
                && $notification->toDatabase($representative)['type'] === OfficeConfirmationRequested::TYPE;
        }
    );

    Notification::assertSentToTimes($representative, OfficeConfirmationRequested::class, 1);
});

it('does not notify the student, an admin, or a reviewer', function (): void {
    Notification::fake();

    $office = officeNotificationOffice();
    $evaluation = officeNotificationEvaluation($office);
    $student = User::factory()->create(['role' => 'user']);
    $admin = User::factory()->create(['role' => 'admin']);
    $reviewer = User::factory()->create(['role' => 'office_academic']);
    $adviser = User::factory()->create(['role' => 'adviser']);

    CategoryAssignment::query()->create(['category' => $evaluation->category, 'office' => 'office_academic']);

    $this->actingAs($student)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertRedirect();

    Notification::assertNotSentTo($student, OfficeConfirmationRequested::class);
    Notification::assertNotSentTo($admin, OfficeConfirmationRequested::class);
    Notification::assertNotSentTo($reviewer, OfficeConfirmationRequested::class);
    Notification::assertNotSentTo($adviser, OfficeConfirmationRequested::class);
});

it('does not notify a different office representative', function (): void {
    Notification::fake();

    $office = officeNotificationOffice();
    $otherOffice = officeNotificationOffice(['name' => 'Unrelated Office']);
    $evaluation = officeNotificationEvaluation($office);
    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertRedirect();

    Notification::assertNotSentTo($otherOffice->representative, OfficeConfirmationRequested::class);
    Notification::assertSentTo($office->representative, OfficeConfirmationRequested::class);
});

it('does not notify again when the existing pending request is reused', function (): void {
    Notification::fake();

    $office = officeNotificationOffice();
    $evaluation = officeNotificationEvaluation($office);
    $student = User::factory()->create(['role' => 'user']);
    $representative = $office->representative;

    $this->actingAs($student)->post(route('office.confirmations.store', $evaluation));
    $this->actingAs($student)->post(route('office.confirmations.store', $evaluation));
    $this->actingAs($student)->post(route('office.confirmations.store', $evaluation));

    // Three clicks, one created request: the representative is told exactly once.
    Notification::assertSentToTimes($representative, OfficeConfirmationRequested::class, 1);
    Notification::assertSentTimes(OfficeConfirmationRequested::class, 1);

    expect(OfficeConfirmationRequest::query()
        ->where('idea_evaluation_id', $evaluation->id)
        ->where('status', OfficeConfirmationRequest::STATUS_PENDING)
        ->count())->toBe(1);
});

it('notifies the new current representative when the representative changes', function (): void {
    Notification::fake();

    $office = officeNotificationOffice();
    $previousRepresentative = $office->representative;
    $evaluation = officeNotificationEvaluation($office);

    $newRepresentative = User::factory()->create(['role' => 'user', 'name' => 'Incoming Representative']);
    $office->forceFill(['representative_user_id' => $newRepresentative->id])->save();

    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)
        ->post(route('office.confirmations.store', $evaluation))
        ->assertRedirect();

    Notification::assertSentTo($newRepresentative, OfficeConfirmationRequested::class);
    Notification::assertNotSentTo($previousRepresentative, OfficeConfirmationRequested::class);
});

it('sends the representative to their existing confirmation queue', function (): void {
    $office = officeNotificationOffice();
    $evaluation = officeNotificationEvaluation($office);
    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)->post(route('office.confirmations.store', $evaluation));

    $representative = $office->representative;
    $notification = $representative->notifications()->sole();

    $this->actingAs($representative)
        ->get(route('notifications.redirect', $notification))
        ->assertRedirect(route('office.confirmations.index'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('does not let another user open the notification', function (): void {
    $office = officeNotificationOffice();
    $evaluation = officeNotificationEvaluation($office);
    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)->post(route('office.confirmations.store', $evaluation));

    $notification = $office->representative->notifications()->sole();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('notifications.redirect', $notification))
        ->assertNotFound();
});

it('leaves confirmation authority with the current representative only', function (): void {
    $office = officeNotificationOffice();
    $evaluation = officeNotificationEvaluation($office);
    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)->post(route('office.confirmations.store', $evaluation));

    $request = OfficeConfirmationRequest::query()->sole();
    $representative = $office->representative;

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->patch(route('office.confirmations.confirm', $request))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['role' => 'office_academic']))
        ->patch(route('office.confirmations.confirm', $request))
        ->assertForbidden();

    $this->actingAs($representative)
        ->patch(route('office.confirmations.confirm', $request))
        ->assertRedirect();

    expect($request->fresh()->status)->toBe(OfficeConfirmationRequest::STATUS_CONFIRMED);
});
