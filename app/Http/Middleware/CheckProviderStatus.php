<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Traits\GlobalTrait;
use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckProviderStatus
{
    use GlobalTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
 
        $user = $this->auth('provider-api');

        if(!$user || !$user->activation)
            return $this->returnError('E332', trans('Unactivated'));

        return $next($request);
    }
}
