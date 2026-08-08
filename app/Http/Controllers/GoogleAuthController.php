<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        // 1. Best match: an account already linked to this exact Google identity.
        //    Google's ID never changes, even if the person's email does.
        $user = User::where('google_id', $googleUser->id)->first();

        if (! $user) {
            // 2. No linked account yet — fall back to matching by email,
            //    covering someone who registered by password first and is
            //    now linking Google for the first time.
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Link this Google identity to their existing account.
                $user->forceFill(['google_id' => $googleUser->id])->save();
            }
        }

        if (! $user) {
            // 3. No match at all — brand new account.
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                // Random, unguessable — this account is only ever meant to be
                // accessed via Google, never via the password login form.
                // The User model's 'password' => 'hashed' cast hashes this on save.
                'password' => \Illuminate\Support\Str::random(40),
                'email_verified_at' => now(),
            ]);
        } elseif (is_null($user->email_verified_at)) {
            // A successful Google login is at least as strong proof of email
            // ownership as clicking a verification link.
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user);

        return redirect()->route('home');
    }
}
