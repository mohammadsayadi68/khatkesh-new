<?php

namespace App\Http\Middleware;

use App\Constansts\Constants;
use App\Constants\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->role == User::ROLE_ADMIN)
            return $next($request);
        throw new NotFoundHttpException();
    }
}
