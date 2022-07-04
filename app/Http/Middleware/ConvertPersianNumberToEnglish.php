<?php

namespace App\Http\Middleware;

use Closure;

class ConvertPersianNumberToEnglish
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
        $data = $request->all();
        foreach ($data as $key => $value)
            if (is_string($value))
                $data[$key] = convert2english($value);
        $request->replace($data);
        return $next($request);
    }

}
