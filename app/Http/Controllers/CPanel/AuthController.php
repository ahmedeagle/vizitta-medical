<?php

namespace App\Http\Controllers\CPanel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Manager;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('CheckManagerToken:manager-api', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }
        return response()->json(['status' => false, 'error' => __('main.invalid_email_or_password')], 200);
    }

    public function me(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        return response()->json(['status' => true, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['status' => true, 'message' => __('main.successfully_logged_out')], 200);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'error' => __('main.error_logged_out'),
            ], 200);
        }
    }

    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => true,
            'access_token' => $token,
            'user' => $this->guard()->user(),
        ]);
    }

    public function guard()
    {
        return Auth::guard('manager-api');
    }
}
