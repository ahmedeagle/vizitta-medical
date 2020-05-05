<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\ManagerPermissionsResource;
use App\Models\AdminWebToken;
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
        $adminWebToken = $request->web_token;

        if ($token = $this->guard()->attempt($credentials)) {

            if ($adminWebToken) {
                $admin = $this->guard()->user();
                $webToken = AdminWebToken::where('token', $adminWebToken)->where('admin_id', $admin->id)->first();
                if (!$webToken) {
                    AdminWebToken::create([
                        'admin_id' => $admin->id,
                        'token' => $adminWebToken,
                    ]);
                }
            }

            return $this->respondWithToken($token, $adminWebToken);
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
            $adminWebToken = $request->only('web_token');

            $admin = $this->guard()->user();
            $webToken = AdminWebToken::where('token', $adminWebToken)->where('admin_id', $admin->id)->first();
            if ($webToken) {
                $webToken->delete();
            }

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

    protected function respondWithToken($token, $adminWebToken = '')
    {
        $result = [
            'status' => true,
            'access_token' => $token,
            'admin_web_token' => $adminWebToken,
            'user' => $this->guard()->user(),
        ];
        $res = Manager::with('permissions')->find($this->guard()->user()->id);
        $result['user']['permissions'] = new ManagerPermissionsResource($res->permissions);
        return response()->json($result);
    }

    public function guard()
    {
        return Auth::guard('manager-api');
    }
}
