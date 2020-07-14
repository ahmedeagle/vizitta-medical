<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\DoctorConsultingReservationDetailsResource;
use App\Models\Chat;
use App\Models\DoctorConsultingReservation;
use App\Models\Specification;
use App\Traits\ChattingTrait;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\GlobalTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\DoctorConsultingReservationResource;
use function foo\func;

class DoctorConsultingReservationController extends Controller
{
    use GlobalTrait, ChattingTrait,SMSTrait;

    public function index(Request $request)
    {

        $status = 'all';
        $list = ['all', 'pending', 'approved', 'reject', 'completed', 'complete_visited', 'complete_not_visited'];
        try {

            if (request('status')) {
                if (!in_array(request('status'), $list)) {
                    $reservations = $this->getReservationByStatus();
                } else {
                    $status = request('status') ? request('status') : $status;
                     $reservations = $this->getReservationByStatus($status);
                }
            } elseif (request('generalQueryStr')) {  //search all column
                $q = request('generalQueryStr');
                $reservations = DoctorConsultingReservation::where('reservation_no', 'LIKE', '%' . trim($q) . '%')
                    ->orWhere('day_date', 'LIKE binary', '%' . trim($q) . '%')
                    ->orWhere('from_time', 'LIKE binary', '%' . trim($q) . '%')
                    ->orWhere('to_time', 'LIKE binary', '%' . trim($q) . '%')
                    ->orWhere('price', 'LIKE', '%' . trim($q) . '%')
                    ->orWhere('total_price', 'LIKE', '%' . trim($q) . '%')
                    ->orWhere('bill_total', 'LIKE', '%' . trim($q) . '%')
                    ->orWhere('discount_type', 'LIKE', '%' . trim($q) . '%')
                    ->orWhereHas('user', function ($query) use ($q) {
                        $query->where('name', 'LIKE', '%' . trim($q) . '%');
                    })
                    ->orWhereHas('doctor', function ($query) use ($q) {
                        $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                    })->orWhereHas('paymentMethod', function ($query) use ($q) {
                        $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                    })
                    ->orWhereHas('provider', function ($query) use ($q) {
                        $query->where(function ($query) use ($q) {
                            $query->where('name_en', 'LIKE', '%' . trim($q) . '%')
                                ->orwhere('name_ar', 'LIKE', '%' . trim($q) . '%');
                        })
                            ->orWhereHas('provider', function ($query) use ($q) {
                                $query->where('name_en', 'LIKE', '%' . trim($q) . '%')
                                    ->orwhere('name_ar', 'LIKE', '%' . trim($q) . '%');
                            });

                    })
                    ->orWhere(function ($qq) use ($q) {
                        if (trim($q) == 'معلق') {
                            $qq->where('approved', '0');
                        } elseif (trim($q) == 'مقبول') {
                            $qq->where('approved', '1');
                        } elseif (trim($q) == 'مرفوض') {
                            $qq->whereIn('approved', ['2', '5']);
                        } elseif (trim($q) == 'مكتمل') {
                            $qq->where('approved', '3');
                        }
                    })
                    ->orderBy('day_date', 'DESC')
                    ->paginate(PAGINATION_COUNT);

                //  $data['reservations'] = new DoctorConsultingReservationResource($res);

            } else {
                $reservations = DoctorConsultingReservation::paginate(PAGINATION_COUNT);
            }


            $result = new DoctorConsultingReservationResource($reservations);
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $reservation = DoctorConsultingReservation::find($request->reservation_id);
            if ($reservation == null)
                return response()->json(['success' => false, 'error' => __('messages.No Reservations founded')], 200);

            if ($reservation->approved) {
                return response()->json(['success' => false, 'error' => __('messages.Cannot delete approved reservation')], 200);
            } else {
                $reservation->delete();
                return response()->json(['status' => true, 'msg' => __('messages.Reservation deleted successfully')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    ##################### Start change doctor consulting reservation status ########################
    public function changeStatus(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|max:255",
                "status" => "required|in:1,2" // 1 == confirmed && 2 == canceled
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $reservation_id = $request->reservation_id;
            $status = $request->status;
            $rejection_reason = $request->reason;

            $reservation = DoctorConsultingReservation::where('id', $reservation_id)->with('user')->first();

            if ($reservation == null)
                return response()->json(['success' => false, 'error' => __('messages.No reservation with this number')], 200);
            if ($reservation->approved == 1) {
                return response()->json(['success' => false, 'error' => __('messages.Reservation already approved')], 200);
            }

            if ($reservation->approved == 2) {
                return response()->json(['success' => false, 'error' => __('messages.Reservation already rejected')], 200);
            }

            if ($status != 2 && $status != 1) {
                return response()->json(['success' => false, 'error' => __('messages.status must be 1 or 2')], 200);
            } else {

                if ($status == 2) {
                    if ($rejection_reason == null) {
                        return response()->json(['success' => false, 'error' => __('messages.please enter rejection reason')], 200);
                    }
                }

                $data = [
                    'approved' => (string)$status,
                ];

                if (!empty($rejection_reason))
                    $data['rejection_reason'] = $rejection_reason;

                $reservation->update($data);

                if ($status == 1) { //admin accept reservation
                    // initialize chat id for firebase
                    $this->startChatting($reservation->id, $reservation->user_id, '1');  // 1 ---> user
                }



                if($status == 1){
                    //send mobile sms

                    $this->sendSMS($reservation->user->mobile,   $reservation -> reservation_no. 'تم قبول حجزك برقم -  ');
                }else{
                    //send mobile sms
                     $this->sendSMS($reservation->user->mobile,  $reservation -> reservation_no. 'تم رفض حجزك برقم -  ');
                }

                return response()->json(['status' => true, 'msg' => __('messages.reservation status changed successfully')]);
            }

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    ##################### End change doctor consulting reservation status ########################

    public function getReservationDetails(Request $request)
    {
        try {
            $reservation = DoctorConsultingReservation::with(['rejectionResoan' => function($q){
                $q ->select('id', 'name_' . app()->getLocale() . ' as name');
            }]) -> find($request->id);
            if (!$reservation)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $result = new DoctorConsultingReservationDetailsResource($reservation);
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function getConsultingCategories(Request $request)
    {
        try {
            $result = Specification::whereHas('doctors', function ($q) {
                $q->where('doctor_type', 'consultative')->orWhere(function ($query) {
                    $query->where('doctor_type', 'clinic')->where('is_consult', 1);
                });
            })->get(['id', DB::raw('name_' . $this->getCurrentLang() . ' as name')]);
            return $this->returnData('specifications', $result);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    protected function getReservationByStatus($status = 'all')
    {
        if ($status == 'delay') {
            $allowTime = 15;  // 15 min
            return $reservaitons = DoctorConsultingReservation::where('approved', 0)
                ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE,created_at,CURRENT_TIMESTAMP)) >= ?', $allowTime)
                ->orderBy('day_date', 'DESC')
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'today_tomorrow') {
            return $reservaitons = DoctorConsultingReservation::where('approved', '!=', 2)->where(function ($q) {
                $q->whereDate('day_date', Carbon::today())->orWhereDate('day_date', Carbon::tomorrow());
            })->orderBy('day_date', 'DESC')->paginate(PAGINATION_COUNT);
        } elseif ($status == 'pending') {
            return $reservaitons = DoctorConsultingReservation::where('approved', '0')
                ->orderBy('day_date', 'DESC')
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'approved') {
            return $reservaitons = DoctorConsultingReservation::where('approved', '1')
                ->orderBy('day_date', 'DESC')
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'reject') {
            return $reservaitons = DoctorConsultingReservation::where(function ($q) {
                $q->where('approved', '2');
            })->orderBy('day_date', 'DESC')->paginate(PAGINATION_COUNT);
        } elseif ($status == 'completed') {
            return $reservaitons = DoctorConsultingReservation::where('approved', '3')
                ->orderBy('day_date', 'DESC')
                ->orderBy('from_time', 'ASC')
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'complete_visited') {
            return $reservaitons = DoctorConsultingReservation::whereNotNull('chat_duration')
                ->where('chat_duration','!=',0)
                ->where('approved', '3')
                ->orderBy('day_date', 'DESC')
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'complete_not_visited') {
            return $reservaitons = DoctorConsultingReservation::where('approved', '3')
                ->where(function ($q) {
                   $q->whereNull('chat_duration')
                    ->orwhere('chat_duration',0);
            })->orderBy('day_date', 'DESC')
                ->paginate(PAGINATION_COUNT);
        } else {
            return $reservaitons = DoctorConsultingReservation::orderBy('day_date', 'DESC')->paginate(PAGINATION_COUNT);
        }
    }

}
