<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Bill;
use App\Models\Mix;
use App\Models\Point;
use App\Models\User;
use App\Traits\Dashboard\BillTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mockery\Exception;
use Validator;
use Flashy;

class BillController extends Controller
{
    use PublicTrait, BillTrait;

    public function getDataTable()
    {
        return $this->getAll();
    }

    public function index()
    {
        $price = Mix::value('point_price');
        return view('bills.index')->with('price', $price);
    }

    public function show($bill_id)
    {
        $bill = Bill::find($bill_id);
        if (!$bill)
            return abort('404');
          $billData = Bill::with('reservation')->find($bill->id);
        return view('bills.view', compact('billData'));

    }

    public function delete($id)
    {
        try {
            $bills = $this->getBillById($id);
            if ($bills == null)
                return view('errors.404');

            $bills->delete();
            Flashy::success('تم  حذف الفاتوره  بنجاح');

            return redirect()->route('admin.bills.index');
        } catch (\Exception $ex) {
            return view('errors.404');
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
                Flashy::error($validator->errors()->first());
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }


            $user = User::find($request->user_id);
            if (!$user) {
                Flashy::error('المستخدم غير موجود ');
                return redirect()->back()->withInput($request->all());
            }


            $exists = $this->checkUserPoints($request->user_id, $request->reservation_id);

            if ($exists) {
                Point::where('id',$exists -> id) -> update([
                    'points' => $request->points
                ]);

                Flashy::success(' تم تحديث عدد النقاط  بنجاح  ');
                return redirect()->back();
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

            }
            Flashy::success(' تم اضافة عدد النقاط  بنجاح  ');
            return redirect()->back();

        } catch (Exception $ex) {
            return abort('404');
        }

        return $request;
    }


}
