<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->from(route('landing'))->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response
        ->assertRedirect(route('landing'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('landing page shows a warning after an invalid login attempt', function () {
    $user = User::factory()->create();

    $response = $this->from(route('landing'))
        ->followingRedirects()
        ->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

    $response->assertSee('Incorrect email or password. Please try again.');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
