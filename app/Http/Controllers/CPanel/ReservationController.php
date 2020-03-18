<?php

namespace App\Http\Controllers\CPanel;

use App\Mail\AcceptReservationMail;
use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\PaymentMethod;
use App\Models\Provider;
use App\Models\Reason;
use App\Models\Reservation;
use App\Models\ReservedTime;
use App\Traits\Dashboard\ReservationTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\ReservationResource;

class ReservationController extends Controller
{
    use ReservationTrait;

    public function index()
    {
        $data = [];
        $data['reasons'] = Reason::get();
        $status = 'all';
        $list = ['delay', 'all', 'today_tomorrow', 'pending', 'approved', 'reject', 'completed', 'complete_visited', 'complete_not_visited'];

        if (request('status')) {
            if (!in_array(request('status'), $list)) {
                $data['reservations'] = $this->getReservationByStatus();
            } else {
                $status = request('status') ? request('status') : $status;
                $data['reservations'] = $this->getReservationByStatus($status);
            }
            return response()->json(['status' => true, 'data' => $data]);
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
        return response()->json(['status' => true, 'data' => $data]);
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

    public function show(Request $request)
    {
        try {
            $reservation = $this->getReservationById($request->id);
            if ($reservation == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $reservation->makeVisible(['rejection_reason']);

            return response()->json(['status' => true, 'data' => $reservation]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function changeStatus(Request $request)
    {
        $reservation = Reservation::where('id', $request->id)->with('user')->first();

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

        if ($request->status != 2 && $request->status != 1) {
            Flashy::error('إدخل الكود صحيح');
        } else {

            if ($request->status == 2) {
                if ($request->rejection_reason == null) {
                    Flashy::error('رجاء ادخال سبب رفض الحجز ');
                    return redirect()->back();
                }
            }
            $this->changerReservationStatus($reservation, $request->status);
            Flashy::success('تم تغيير حالة الحجز بنجاح');
        }
        return redirect()->back();

    }

}
