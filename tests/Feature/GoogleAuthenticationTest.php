<?php

use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('Google callback authenticates the user and redirects to the app home page', function () {
    $googleUser = new SocialiteUser;
    $googleUser->id = 'google-user-id';
    $googleUser->name = 'Google User';
    $googleUser->email = 'google-user@example.com';

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('stateless')->once()->andReturnSelf();
    $provider->shouldReceive('user')->once()->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($provider);

    $response = $this->get(route('google.callback'));

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});
