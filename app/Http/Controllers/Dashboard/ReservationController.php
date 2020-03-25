<?php

namespace App\Http\Controllers\Dashboard;

use App\Mail\AcceptReservationMail;
use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\GeneralNotification;
use App\Models\PaymentMethod;
use App\Models\Provider;
use App\Models\Reason;
use App\Models\Reservation;
use App\Models\ReservedTime;
use App\Traits\Dashboard\ReservationTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Flashy;
use Illuminate\Support\Facades\Mail;
use Validator;
use Session;
use DB;
use Vinkla\Hashids\Facades\Hashids;
use function foo\func;

class ReservationController extends Controller
{
    use ReservationTrait;

    public function getDataTable($status)
    {
        return $this->getAllReservations($status);
    }

    public function index()
    {
        $data = [];
        $data['reasons'] = Reason::get();
        $status = 'all';
        $list = ['delay', 'all', 'today_tomorrow', 'pending', 'approved', 'reject', 'rejected_by_user', 'completed', 'complete_visited', 'complete_not_visited'];

        if (request('status')) {
            if (!in_array(request('status'), $list)) {
                $data['reservations'] = $this->getReservationByStatus();
            } else {
                $status = request('status') ? request('status') : $status;
                $data['reservations'] = $this->getReservationByStatus($status);
            }
            return view('reservation.index', $data);
        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $data['reservations'] = Reservation::where('reservation_no', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('day_date', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('from_time', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('to_time', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('price', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('bill_total', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('discount_type', 'LIKE', '%' . trim($q) . '%')
                ->orWhereHas('user', function ($query) use ($q) {
                    $query->where('name', 'LIKE', '%' . trim($q) . '%');
                })
                ->orWhereHas('doctor', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('paymentMethod', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                })
                ->orWhereHas('branch', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                    $query->orWhereHas('provider', function ($query) use ($q) {
                        $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                    });
                })->orderBy('day_date', 'DESC')
                ->paginate(10);
        } else {
            $data['reservations'] = Reservation::orderBy('day_date', 'DESC')
                ->paginate(10);
        }
        return view('reservation.index', $data);
    }

    protected function getReservationByStatus($status = 'all')
    {
        if ($status == 'delay') {
            $allowTime = 15;  // 15 min
            return $reservaitons = Reservation::selection()
                ->where('approved', 0)->
                whereRaw('ABS(TIMESTAMPDIFF(MINUTE,created_at,CURRENT_TIMESTAMP)) >= ?', $allowTime)
                ->orderBy('day_date', 'DESC')
                ->paginate(10);
        } elseif ($status == 'today_tomorrow') {
            return $reservaitons = Reservation::selection()->where('approved', '!=', 2)->where(function ($q) {
                $q->whereDate('day_date', Carbon::today())->orWhereDate('day_date', Carbon::tomorrow());
            })->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'pending') {
            return $reservaitons = Reservation::selection()->where('approved', 0)->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'approved') {
            return $reservaitons = Reservation::selection()->where('approved', 1)->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'reject') {
            return $reservaitons = Reservation::selection()->where('approved', 2)->whereNotNull('rejection_reason')->where('rejection_reason', '!=', '')->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'rejected_by_user') {
            return $reservaitons = Reservation::selection()->where('approved', 5)->paginate(10);
        } elseif ($status == 'completed') {
            return $reservaitons = Reservation::selection()->where('approved', 3)->orderBy('day_date', 'DESC')->orderBy('from_time', 'ASC')->paginate(10);
        } elseif ($status == 'complete_visited') {
            return $reservaitons = Reservation::selection()->where('approved', 3)->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'complete_not_visited') {
            return $reservaitons = Reservation::selection()->where('approved', 2)->where(function ($q) {
                $q->whereNull('rejection_reason')->orwhere('rejection_reason', '=', '')->orwhere('rejection_reason', 0);
            })->orderBy('day_date', 'DESC')->paginate(10);
        } else {
            return $reservaitons = Reservation::selection()->orderBy('day_date', 'DESC')->paginate(10);
        }
    }

    public function providerReservations($provider_id, Request $request)
    {
        $data = [];
        $data['provider'] = Provider::findOrFail($provider_id);
        $data['branchsIds'] = $data['provider']->providers()->pluck('id', 'name_ar')->toArray();
        $data['count'] = Reservation::whereIn('provider_id', $data['branchsIds'])->whereIn('approved', [3])->count();   // only completed reservations

        $totalReservationPrice = 0;

        $completeReservations = Reservation::whereIn('provider_id', $data['branchsIds'])->whereIn('approved', [3])->get();

        if (isset($completeReservations) && $completeReservations->count() > 0) {   //some only complete price
            foreach ($completeReservations as $res) {
                $totalReservationPrice += $res->reservation_total;
            }
        }
        $data['total'] = $totalReservationPrice;
        if (isset($completeReservations) && $completeReservations->count() > 0) {   //some only complete price
            foreach ($completeReservations as $res) {
                $totalReservationPrice += $res->reservation_total;
            }
        }


        $data['doctorsIds'] = Doctor::whereIn('provider_id', $data['branchsIds'])->pluck('id', 'name_ar')->toArray();
        $data['paymentMethodIds'] = PaymentMethod::pluck('id', 'name_ar')->toArray();
        //falter keys

        $data['from_date'] = $request->filled('from_date') ? $request->from_date : '';
        $data['to_date'] = $request->filled('to_date') ? $request->to_date : '';
        $data['doctor_id'] = $request->filled('doctor_id') ? $request->doctor_id : '';
        $data['branch_id'] = $request->filled('branch_id') ? $request->branch_id : '';
        $data['payment_method_id'] = $request->filled('payment_method_id') ? $request->payment_method_id : '';

        return view('provider.reservations.index', $data);
    }

    public function getProviderReservationsDataTable($provider_id, Request $request)
    {
        return $this->getReservationByProviderId($provider_id, $request);
    }

    public function getMainProviderReservations($provider_id, $branches = [], $branchId = 0, $fromDate = null, $toDate = null,
                                                $paymentMethodId = null, $doctorId = null, $countfalg = false)
    {
        $reservations = Reservation::query();
        $reservations = $reservations->with(['commentReport' => function ($q) use ($provider_id) {
            $q->where('provider_id', $provider_id);
        }, 'doctor' => function ($g) {
            $g->select('id', 'nickname_id', 'specification_id', 'nationality_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                ->with(['nickname' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'coupon' => function ($qu) {
            $qu->select('id', 'coupons_type_id', 'title_' . app()->getLocale() . ' as title', 'code', 'photo', 'price');
        }, 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'user' => function ($q) {
            $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                ->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'provider' => function ($qq) use ($provider_id, $branches, $branchId) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'people' => function ($p) {
            $p->select('id', 'name', 'insurance_company_id', 'insurance_image')->with(['insuranceCompany' => function ($qu) {
                $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
            }]);
        }])->whereIn('provider_id', $branches);

        if (!empty($branchId) && $branchId != 0)
            $reservations = $reservations->where('provider_id', $branchId);

        /*   if ($doctorId != null && $doctorId != 0)
               $reservations = $reservations->where('doctor_id', $doctorId);

           if ($paymentMethodId != null && $paymentMethodId != 0)
               $reservations = $reservations->where('payment_method_id', $paymentMethodId);

           if ($fromDate != null && !empty($fromDate))
               $reservations = $reservations->where('day_date', '>=', date('Y-m-d', strtotime($fromDate)));

           if ($toDate != null && !empty($toDate))
               $reservations = $reservations->where('day_date', '<=', date('Y-m-d', strtotime($toDate)));*/

        $result = $reservations->orderBy('day_date')->orderBy('from_time')->paginate(15);   // refuse and completed
        $count = $reservations->count();    // only completed
        $sum = $reservations->sum('price'); // only completed
        return ['count' => $count,
            'prices' => $sum,
            'reservations' => $result];
    }


    public function view($id)
    {
        try {


            if (request('notification')) {  // mark as read
                GeneralNotification::where('seen', '0')
                    ->where('id', Hashids::decode(request('notification')))
                    ->update(['seen' => '1']);
            }

            //mark seen if ther is notification
            $reservation = $this->getReservationById($id);
            if ($reservation == null){
                Flashy::error('الحجز غير موجود او ربما يكون قد حجز ');
                return  redirect()->back();
            }


            $reservation->makeVisible(['rejection_reason']);

            return view('reservation.view', compact('reservation'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id)
    {
        try {
            $reservation = $this->getReservationById($id);
            if ($reservation == null)
                return view('errors.404');

            if ($reservation->approved) {
                Flashy::error('لا يمكن مسح حجز موافق عليه');
            } else {
                $reservation->delete();
                Flashy::success('تم مسح الحجز بنجاح');
            }
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }


    public function rejectReservation(Request $request)
    {

        $id = $request->id;
        $status = $request->status;
        $rejection_reason = $request->rejection_reason;

        $reservation = Reservation::where('id', $id)->with('user')->first();
        if ($reservation == null)
            return view('errors.404');


        if ($reservation->approved == 2) {
            Flashy::error(trans('messages.Reservation already rejected'));
            return redirect()->back();
        }
        /*
                if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                    (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                        strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))
                ) {
                    Flashy::error(trans("messages.You can't take action to a reservation passed"));
                    return redirect()->back();
                }*/

        if ($status != 2) {
            Flashy::error('إدخل الكود صحيح');
        } else {

            if ($status == 2) {
                if ($rejection_reason == null or !is_numeric($rejection_reason)) {
                    Flashy::error('رجاء ادخال سبب رفض الحجز ');
                    return redirect()->back();
                }
            }
            $this->changerReservationStatus($reservation, $status, $rejection_reason);
            Flashy::success('تم تغيير حالة الحجز بنجاح');
        }
        return redirect()->back();
    }

    public function changeStatus($id, $status, $rejection_reason = null)
    {
        $reservation = Reservation::where('id', $id)->with('user')->first();

        if ($reservation == null)
            return view('errors.404');
        if ($reservation->approved == 1) {
            Flashy::error(trans('messages.Reservation already approved'));
            return redirect()->back();
        }

        if ($reservation->approved == 2) {
            Flashy::error(trans('messages.Reservation already rejected'));
            return redirect()->back();
        }

        /*    if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                    strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))
            ) {
                Flashy::error(trans("messages.You can't take action to a reservation passed"));
                return redirect()->back();
            }*/

        if ($status != 2 && $status != 1) {
            Flashy::error('إدخل الكود صحيح');
        } else {

            if ($status == 2) {
                if ($rejection_reason == null) {
                    Flashy::error('رجاء ادخال سبب رفض الحجز ');
                    return redirect()->back();
                }
            }
            $this->changerReservationStatus($reservation, $status);
            Flashy::success('تم تغيير حالة الحجز بنجاح');
        }
        return redirect()->back();

    }


    /*
     *  if (!empty($_REQUEST['year']) && !empty($_REQUEST['month'])) {
        $year = intval($_REQUEST['year']);
        $month = intval($_REQUEST['month']);
        $lastday = intval(strftime('%d', mktime(0, 0, 0, ($month == 12 ? 1 : $month + 1), 0, ($month == 12 ? $year + 1 : $year))));
        $dates = array();
        for ($i = 0; $i <= (rand(4, 10)); $i++) {
            $date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, $lastday), 2, '0', STR_PAD_LEFT);
            $dates[$i] = array(
                'date' => $date,
                'badge' => ($i & 1) ? true : false,
                'title' => 'Example for ' . $date,
                'body' => '<p class="lead">Information for this date</p><p>You can add <strong>html</strong> in this block</p>',
                'footer' => 'Extra information',
            );
            if (!empty($_REQUEST['grade'])) {
                $dates[$i]['badge'] = false;
                $dates[$i]['classname'] = 'grade-' . rand(1, 4);
            }
            if (!empty($_REQUEST['action'])) {
                $dates[$i]['title'] = 'Action for ' . $date;
                $dates[$i]['body'] = '<p>The footer of this modal window consists of two buttons. One button to close the modal window without further action.</p>';
                $dates[$i]['body'] .= '<p>The other button [Go ahead!] fires myFunction(). The content for the footer was obtained with the AJAX request.</p>';
                $dates[$i]['body'] .= '<p>The ID needed for the function can be retrieved with jQuery: <code>dateId = $(this).closest(\'.modal\').attr(\'dateId\');</code></p>';
                $dates[$i]['body'] .= '<p>The second argument is true in this case, so the function can handle closing the modal window: <code>myFunction(dateId, true);</code></p>';
                $dates[$i]['footer'] = '
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="dateId = $(this).closest(\'.modal\').attr(\'dateId\'); myDateFunction(dateId, true);">Go ahead!</button>
            ';
            }
        }
        echo json_encode($dates);
    } else {
        echo json_encode(array());
    }
     */
    public function editReservationDateTime($reservationId)
    {
        try {
            $reservation = Reservation::find($reservationId);
            if (!$reservation) {
                return view('errors.404');
            }
            if ($reservation->approved == 2 or $reservation->approved == 3) {   // 2-> cancelled  3 -> complete
                Flashy::error('لايمكن تحديث الموعد لهذا الحجز ');
                return redirect()->back();
            }
            $doctor_id = $reservation->doctor->id;
            if (!$doctor_id || $doctor_id == 0) {
                return view('errors.404');
            }
            Session::put('doctor_id_for_Edit_reserv', $doctor_id);
            return view('reservation.updateTime', compact('reservation'));
        } catch (\Exception $ex) {
            Flashy::error('هناك خطا ما من فضلك حاول مجددا ');
            return view('errors.404');
        }
    }

    public function UpdateReservationDateTime(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_no" => "required|max:255",
                "day_date" => "required|date",
                "from_time" => "required",
                "to_time" => "required",
            ]);

            if ($validator->fails()) {
                Flashy::error(' هناك خطا يرجي المحاوله فيما بعد  ');
                return redirect()->back();
            }

            DB::beginTransaction();
            $reservation = Reservation::where('reservation_no', $request->reservation_no)->with('user')->first();
            if ($reservation == null) {
                return response()->json(['errNum' => 1, 'message' => 'لا يوجد حجز بهذا الرقم ']);
            }
            /*  if ($reservation->approved != 1) {
                  return response()->json(['errNum' => 1, 'message' => 'الحجوزات الموافق عليها فقط التي يمكن  تعديل موعدها ']);
              }*/
            /*
                        if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                            (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                                strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))) {
                            return response()->json(['errNum' => 1, 'message' => 'عفوا لايمكن حجز موعد مر ']);
                        }*/
            $provider = Provider::find($reservation->provider_id);


            $doctor = $reservation->doctor;
            if ($doctor == null) {
                return response()->json(['errNum' => 1, 'message' => trans('messages.No doctor with this id')]);
            }
            /*
                        if (strtotime($request->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                            ($request->day_date == Carbon::now()->format('Y-m-d') && strtotime($request->to_time) < strtotime(Carbon::now()->format('H:i:s')))) {
                            return response()->json(['errNum' => 1, 'message' => "لا يمكن حجز موعد مر "]);
                        }*/

            $hasReservation = $this->checkReservationInDate($doctor->id, $request->day_date, $request->from_time, $request->to_time);
            if ($hasReservation) {
                return response()->json(['errNum' => 1, 'message' => trans('messages.This time is not available')]);
            }

            $reservationDayName = date('l', strtotime($request->day_date));
            $rightDay = false;
            $timeOrder = 1;
            $last = false;
            $times = $this->getDoctorTimesInDay($doctor->id, $reservationDayName);
            foreach ($times as $key => $time) {
                if ($time['from_time'] == Carbon::parse($request->from_time)->format('H:i')
                    && $time['to_time'] == Carbon::parse($request->to_time)->format('H:i')) {
                    $rightDay = true;
                    $timeOrder = $key + 1;
                    //if(count($times) == ($key+1))
                    //  $last = true;
                    break;
                }
            }
            if (!$rightDay) {
                return response()->json(['errNum' => 1, 'message' => trans('messages.This day is not in doctor days')]);
            }

            $reservation->update([
                "day_date" => date('Y-m-d', strtotime($request->day_date)),
                "from_time" => date('H:i:s', strtotime($request->from_time)),
                "to_time" => date('H:i:s', strtotime($request->to_time)),
                'order' => $timeOrder,
                //"approved" => 1,
            ]);

            if ($last) {
                ReservedTime::create([
                    'doctor_id' => $doctor->id,
                    'day_date' => date('Y-m-d', strtotime($request->day_date))
                ]);
            }

            if ($reservation->user->email != null)
                Mail::to($reservation->user->email)->send(new AcceptReservationMail($reservation->reservation_no));

            DB::commit();
            try {
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated user reservation')]))->sendProvider($reservation->provider);
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated your reservation')]))->sendUser($reservation->user);
            } catch (\Exception $ex) {

            }
            return response()->json(['errNum' => 0, 'message' => trans('messages.Reservation updated successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['errNum' => 1, 'message' => $ex]);
        }
    }


    protected function checkReservationInDate($doctorId, $dayDate, $fromTime, $toTime)
    {
        // effect by date
        $reservation = Reservation::where([
            ['doctor_id', '=', $doctorId],
            ['day_date', '=', Carbon::parse($dayDate)->format('Y-m-d')],
            ['from_time', '=', $fromTime],
            ['to_time', '=', $toTime],
        ])->where('approved', '!=', 2)->first();
        if ($reservation != null)
            return true;

        else
            return false;
    }

    protected function getDoctorTimesInDay($doctorId, $dayName, $count = false)
    {
        // effect by date
        $doctorTimes = DoctorTime::query();
        $doctorTimes = $doctorTimes->where('doctor_id', $doctorId)->whereRaw('LOWER(day_name) = ?', strtolower($dayName))
            ->orderBy('created_at')->orderBy('order');

        $times = $this->getDoctorTimePeriods($doctorTimes->get());
        if ($count)
            if (!empty($times) && is_array($times))
                return count($times);
            else
                return 0;

        return $times;
    }


    protected function getDoctorTimePeriods($working_days)
    {
        $times = [];
        $j = 0;
        foreach ($working_days as $working_day) {
            $from = strtotime($working_day['from_time']);
            $to = strtotime($working_day['to_time']);
            $diffInterval = ($to - $from) / 60;
            $periodCount = $diffInterval / $working_day['time_duration'];
            for ($i = 0; $i < round($periodCount); $i++) {
                $times[$j]['day_code'] = $working_day['day_code'];
                $times[$j]['day_name'] = $working_day['day_name'];
                $times[$j]['from_time'] = Carbon::parse($working_day['from_time'])->addMinutes($working_day['time_duration'] * $i)->format('H:i');
                $times[$j]['to_time'] = Carbon::parse($working_day['from_time'])->addMinutes($working_day['time_duration'] * ($i + 1))->format('H:i');
                $times[$j++]['time_duration'] = $working_day['time_duration'];
            }
        }
        return $times;
    }


}
