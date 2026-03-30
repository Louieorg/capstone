<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'email' => ['required','string','email'],
            'password' => ['required','string'],
            'g-recaptcha-response' => ['required']
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages()
    {
        return [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot.'
        ];
    }

    /**
     * Verify CAPTCHA manually
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $response = Http::withOptions([
                'verify' => false
            ])->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => env('NOCAPTCHA_SECRET'),
                'response' => request('g-recaptcha-response'),
            ]);

            $result = $response->json();

            if (!isset($result['success']) || !$result['success']) {
                $validator->errors()->add(
                    'captcha',
                    'Captcha verification failed. Please try again.'
                );
            }
        });
    }

    /**
     * Attempt to authenticate the request's credentials.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure login request is not rate limited
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get rate limiter key
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
