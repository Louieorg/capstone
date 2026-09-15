<?php

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    Event::fake();

    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    Event::assertDispatched(Registered::class);
    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('authenticated users are redirected away from registration with a notice', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/register');

    $response->assertRedirect(route('home', absolute: false));
    $response->assertSessionHas('info', 'You are already signed in. Log out first to create another account.');
});
