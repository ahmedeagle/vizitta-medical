<?php

namespace App\Http\Middleware;

use Closure;

class SetAPILocale
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
        if($request->lang)
            app()->setLocale($request->lang);
        else
            app()->setLocale($request->segment(4));
        return $next($request);
    }
}
