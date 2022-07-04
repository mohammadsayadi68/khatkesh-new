<?php

namespace App\Http\Middleware;

use Closure;

class UserVip
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (\Auth::user()->vip)
            return $next($request);
        $createdAtTimestamp = \Auth::user()->created_at->addDays(30)->timestamp;
        $now = now()->timestamp;
        if ($now > $createdAtTimestamp) {
            return response()->json([
                'code' => -1,
                'createdAtTimestamp' => \Auth::user()->created_at->timestamp,
            ], 403);
        }
        return $next($request);
    }
}
