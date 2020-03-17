<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\BillResource;
use App\Models\Bill;
use App\Models\Mix;
use App\Models\Point;
use App\Models\User;
use App\Traits\Dashboard\BillTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mockery\Exception;
use Illuminate\Support\Facades\Validator;

class BillController extends Controller
{
    use PublicTrait, BillTrait;

    public function index()
    {
        $bills = Bill::has('reservation')->orderBy('created_at', 'DESC')->paginate(PAGINATION_COUNT);
        $result['bills'] = new BillResource($bills);
        $result['price'] = Mix::value('point_price');
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function show(Request $request)
    {
        try {
            $bill = Bill::find($request->bill_id);
            if (!$bill)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            $billData = Bill::with('reservation')->find($bill->id);
            return response()->json(['status' => true, 'data' => $billData]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $bills = $this->getBillById($request->id);
            if ($bills == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $bills->delete();
            return response()->json(['status' => true, 'msg' => __('main.order_deleted_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }


    public function addPointToUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|exists:reservations,id",
                "user_id" => "required|exists:users,id",
                "points" => "required|min:1"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $user = User::find($request->user_id);
            if (!$user) {
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            }

            $exists = $this->checkUserPoints($request->user_id, $request->reservation_id);

            if ($exists) {
                Point::where('id', $exists->id)->update([
                    'points' => $request->points
                ]);

                return response()->json(['status' => true, 'msg' => __('main.points_updated_successfully')]);
            }

            Point::create([
                'user_id' => $request->user_id,
                'reservation_id' => $request->reservation_id,
                'points' => $request->points
            ]);

            try {
                $name = 'name_' . app()->getLocale();
                $bodyUser = "  تم اضافه {$request->points } نقطه لحسابكم وذالك لرفعكم فاتوره الكشف   ";

                //send push notification
                (new \App\Http\Controllers\NotificationController(['title' => 'ربح نقاط ', 'body' => $bodyUser]))->sendUser($user);

                //send mobile sms
                $message = $bodyUser;
                $this->sendSMS($user->mobile, $message);

            } catch (\Exception $ex) {
                return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
            }
            return response()->json(['status' => true, 'msg' => __('main.points_added_successfully')]);

        } catch (Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }


}
