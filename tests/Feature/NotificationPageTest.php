<?php

use App\Models\User;
use App\Notifications\IdeaGenerated;

test('notifications are marked as read when the notifications page is opened', function () {
    $user = User::factory()->create();

    $user->notify(new IdeaGenerated('Queue Management System', 'Enrollment'));

    expect($user->fresh()->unreadNotifications)->toHaveCount(1);

    $response = $this->actingAs($user)->get(route('notifications'));

    $response
        ->assertOk()
        ->assertSeeText('A new capstone idea has been generated from problems in Enrollment!');

    expect($user->fresh()->unreadNotifications)->toHaveCount(0);
    expect($user->fresh()->notifications()->first()->read_at)->not->toBeNull();
});

test('generated idea notifications redirect to the related category idea and are marked as read', function () {
    $user = User::factory()->create();

    $user->notify(new IdeaGenerated('Queue Management System', 'Enrollment'));

    $notification = $user->fresh()->notifications()->first();

    $response = $this->actingAs($user)->get(route('notifications.redirect', $notification));

    $response->assertRedirect(
        route('feedback.category', ['category' => 'Enrollment', 'idea' => 'Queue Management System']).'#idea-queue-management-system'
    );

    expect($notification->fresh()->read_at)->not->toBeNull();
});
