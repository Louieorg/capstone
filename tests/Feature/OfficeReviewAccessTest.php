<?php

use App\Models\CategoryAssignment;
use App\Models\Feedback;
use App\Models\User;

/**
 * Create a problem that the office review routes can act on.
 */
function officeReviewAccessFeedback(): Feedback
{
    return Feedback::query()->create([
        'title' => 'Office review access probe',
        'description' => 'Laboratory staff write equipment faults on paper during every shift.',
        'impact' => 'Students wait for working equipment while faults stay unreported.',
        'category' => 'Office Review Access Facilities',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'status' => 'pending',
    ]);
}

/**
 * Create a verified office reviewer for the academic affairs office.
 */
function officeReviewAccessReviewer(): User
{
    return User::factory()->create([
        'role' => 'office_academic',
        'is_office_head' => true,
        'office_department' => 'CICS',
    ]);
}

test('a guest is redirected to the login page from the office review queue', function (): void {
    officeReviewAccessFeedback();

    $this->get(route('office.review.index'))
        ->assertRedirect(route('login'));
});

test('a guest is redirected to the login page when approving an office report', function (): void {
    $feedback = officeReviewAccessFeedback();

    $this->patch(route('office.review.approve', $feedback->id))
        ->assertRedirect(route('login'));
});

test('a guest is redirected to the login page when rejecting an office report', function (): void {
    $feedback = officeReviewAccessFeedback();

    $this->patch(route('office.review.reject', $feedback->id))
        ->assertRedirect(route('login'));
});

test('a guest is redirected to the login page when marking a report capstone-worthy', function (): void {
    $feedback = officeReviewAccessFeedback();

    $this->patch(route('office.review.mark-capstone', $feedback->id))
        ->assertRedirect(route('login'));
});

test('a verified user who is not an office reviewer cannot open the office review queue', function (): void {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('office.review.index'))
        ->assertForbidden();
});

test('a verified user who is not an office reviewer cannot act on an office report', function (): void {
    $user = User::factory()->create(['role' => 'user']);
    $feedback = officeReviewAccessFeedback();

    $this->actingAs($user)
        ->patch(route('office.review.approve', $feedback->id))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('office.review.reject', $feedback->id))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('office.review.mark-capstone', $feedback->id))
        ->assertForbidden();

    expect($feedback->fresh()->status)->toBe('pending');
});

test('an unverified office reviewer is sent to email verification instead of the review queue', function (): void {
    $reviewer = User::factory()->unverified()->create([
        'role' => 'office_academic',
        'is_office_head' => true,
        'office_department' => 'CICS',
    ]);

    $this->actingAs($reviewer)
        ->get(route('office.review.index'))
        ->assertRedirect(route('verification.notice'));
});

test('an office reviewer reaches the review queue for their office categories', function (): void {
    $reviewer = officeReviewAccessReviewer();

    CategoryAssignment::query()->create([
        'category' => 'Office Review Access Facilities',
        'office' => 'office_academic',
    ]);

    officeReviewAccessFeedback();

    $this->actingAs($reviewer)
        ->get(route('office.review.index'))
        ->assertOk()
        ->assertSeeText('Office review access probe');
});
