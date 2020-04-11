<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\BillResource;
use App\Models\Bill;
use App\Models\Manager;
use App\Models\Mix;
use App\Models\Point;
use App\Models\User;
use App\Traits\Dashboard\AdminTrait;
use App\Traits\Dashboard\BillTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mockery\Exception;
use Illuminate\Support\Facades\Validator;
use JWTAuth;


class ProfileController extends Controller
{
    use AdminTrait, PublicTrait;

    public function edit(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if ($user) {
                $appData = Manager::find($user->id)->makeVisible(['balance', 'unpaid_balance', 'paid_balance']);
                return response()->json(['status' => true, 'data' => $appData]);
            }
            return response()->json(['status' => true, 'data' => []]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "mobile" => "required",
                "email" => "required|email"
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $this->updateInfo($request);
            return response()->json(['status' => true, 'msg' => __('main.data_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}
