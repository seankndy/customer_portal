<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\View;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        View::share('language', language($request));

        return $next($request);
    }
}
