<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated extends \Illuminate\Auth\Middleware\RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (\Illuminate\Support\Facades\Auth::guard($guard)->check()) {
                $redirect = redirect($this->redirectTo($request));

                if ($request->isMethod('POST')) {
                    $redirect->with('warning', 'That form session expired, but you are already signed in.');
                }

                return $redirect;
            }
        }

        return $next($request);
    }
}
