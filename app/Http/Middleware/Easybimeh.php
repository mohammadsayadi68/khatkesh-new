<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Easybimeh
{
    private $key = 'Bearer $2y$10$gMNzjuI9N7YiCdgBIoNYN.0uPadkmfyXBEwfFAE0sQ5/vBJ4wzRSu';
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->key==$request->header('Authorization'))
            return $next($request);
        throw new UnauthorizedHttpException('');
    }
}
