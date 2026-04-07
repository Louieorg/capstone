<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticatedWithNotice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                return redirect()
                    ->route($user->role === 'admin' ? 'admin.dashboard' : ($user->role === 'adviser' ? 'adviser.dashboard' : 'home'))
                    ->with('info', 'You are already signed in. Log out first to create another account.');
            }
        }

        return $next($request);
    }
}
