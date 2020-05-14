<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Traits\GlobalTrait;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckDoctorToken
{
    use GlobalTrait;

    public function handle($request, Closure $next)
    {
        $user = null;
        try {
            $user = auth('doctor-api')->userOrFail();
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                //return response()->json(['success' => false, 'msg' => 'INVALID_TOKEN'], 200);
                return $this->returnError('E001', 'INVALID_TOKEN');
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                //return response()->json(['success' => false, 'msg' => 'EXPIRED_TOKEN'], 200);
                return $this->returnError('E001', 'EXPIRED_TOKEN');
            } else {
                //return response()->json(['success' => false, 'msg' => 'TOKEN_NOTFOUND'], 200);
                return $this->returnError('E001', 'TOKEN_NOTFOUND');
            }
        } catch (\Throwable $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                //return response()->json(['success' => false, 'msg' => 'INVALID_TOKEN'], 200);
                return $this->returnError('E001', 'INVALID_TOKEN');
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                //return response()->json(['success' => false, 'msg' => 'EXPIRED_TOKEN'], 200);
                return $this->returnError('E001', 'EXPIRED_TOKEN');
            } else {
                //return response()->json(['success' => false, 'msg' => 'TOKEN_NOTFOUND'], 200);
                return $this->returnError('E001', 'TOKEN_NOTFOUND');
            }
        }

        if (!$user)
            return $this->returnError('E001', 'TOKEN_NOTFOUND');
           // return response()->json(['success' => false, 'msg' => trans('Unauthenticated')], 200);
        // return $this->returnError('E331', trans('Unauthenticated'));
        return $next($request);
    }
}
