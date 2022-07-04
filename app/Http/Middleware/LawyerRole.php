<?php

namespace App\Http\Middleware;

use App\Constants\User;
use Closure;
use Illuminate\Auth\AuthenticationException;

class LawyerRole
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        if (\Auth::user()->role!==User::ROLE_LAWYER)
            throw new AuthenticationException();
        else
            return $next($request);
    }
}
