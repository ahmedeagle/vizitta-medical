<?php

namespace App\Http\Controllers\CPanel\DoctorArea;

use App\Http\Controllers\ChattingController;
use App\Models\DoctorConsultingReservation;
use App\Models\Provider;
use App\Models\Reason;
use App\Traits\ChattingTrait;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\GlobalTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\DoctorArea\DoctorConsultingReservationResource;

class DoctorReservationsController extends Controller
{
    use GlobalTrait, ChattingTrait, SMSTrait;

    public function index(Request $request)
    {
        try {

            $rules = [
                "type" => "required|in:0,1,2,3",
            ];

            $conditions = [];
            $validator = Validator::make($request->all(), $rules);

            if ($request->has('date')) {
                $validator->addRules([
                    'date' => 'required|date_format:Y-m-d',
                ]);
                $date = $request->date;
                array_push($conditions, [DB::raw('DATE(day_date)'), $date]);
            }

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $doctor = $this->getAuthDoctor();
            $type = $request->type;

            $conditions[] = ['doctor_id', $doctor->id];
            $conditions[] = ['approved', $type];

            $reservations = DoctorConsultingReservation::with(['user' => function ($q) {
                $q->select('id', 'name', 'photo');
            }])
                ->where($conditions)
                ->paginate(PAGINATION_COUNT);

            if (isset($reservations) && $reservations->count() > 0 && ($type == 0 or $type == 1)) { // only for current and new reservation we add key to know if the reservation allow chat or not
                foreach ($reservations as $key => $reservation) {
                    $consulting_start_date = date('Y-m-d H:i:s', strtotime($reservation->day_date . ' ' . $reservation->from_time));
                    $consulting_end_date = date('Y-m-d H:i:s', strtotime($reservation->day_date . ' ' . $reservation->to_time));
                    $reservation->consulting_start_date = $consulting_start_date;
                    $reservation->consulting_end_date = $consulting_end_date;
                    //return $consulting_start_date .' > = '.date('Y-m-d H:i:s');
                    if (date('Y-m-d H:i:s') >= $consulting_start_date && ($this->getDiffBetweenTwoDate(date('Y-m-d H:i:s'), $consulting_start_date) <= $reservation->hours_duration)) {
                        $reservation->allow_chat = 1;
                    } else {
                        $reservation->allow_chat = 0;
                    }
                    // $reservation->makeHidden(['day_date', 'from_time', 'to_time', 'rejected_reason_type', 'reservation_total', 'for_me', 'is_reported', 'branch_name', 'branch_no', 'mainprovider', 'admin_value_from_reservation_price_Tax']);
                    // $reservation->doctor->makeHidden(['times']);
                }
            }

            $result = new DoctorConsultingReservationResource($reservations);
//            return response()->json(['status' => true, 'data' => $result]);
            return $this->returnData('data', $result);
        } catch (\Exception $ex) {
            return $this->returnError('E001', __('main.oops_error'));
        }
    }

    public function getRejectedReasons(Request $request)
    {
        try {
            $result = Reason::get(['id', DB::raw('name_' . $this->getCurrentLang() . ' as name')]);
            return $this->returnData('data', $result);


        } catch (\Exception $ex) {
            return $this->returnError('E001', __('main.oops_error'));
        }
    }

    function getDiffBetweenTwoDate($ConsultingDate)
    {
        $end = Carbon::parse($ConsultingDate, 'Asia/Riyadh');
        $now = Carbon::now('Asia/Riyadh');
        return $length = $now->diffInMinutes($end);
    }

    ##################### Start change doctor consulting reservation status ########################
    public function changeStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|max:255",
                "status" => "required|in:1,2,3", // 1 == confirmed && 2 == canceled && 3 == complete
            ]);

            if ($request->status == 2) {
                $validator->addRules([
                    'rejected_reason_id' => 'required|string',
                    'reason_text' => 'sometimes|nullable|string',
                ]);
            }

            if ($request->status == 3) {
                $validator->addRules([
                    'chat_duration' => 'required',
                ]);
            }

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $reservation_id = $request->reservation_id;
            $status = $request->status;

            \Illuminate\Support\Facades\DB::beginTransaction();
            $reservation = DoctorConsultingReservation::where('id', $reservation_id)->with('user')->first();

            if ($reservation == null)
                return $this->returnError('E001', __('messages.No reservation with this number'));

            if ($reservation->approved == 1 && $request->status == 1)
                return $this->returnError('E001', trans('messages.Reservation already approved'));

            if ($reservation->approved == 2 && $request->status == 2)
                return $this->returnError('E001', trans('messages.Reservation already rejected'));

            if ($reservation->approved == 3 && $request->status == 3)
                return $this->returnError('E001', trans('messages.Reservation already Completed'));

            if ($reservation->approved == 2 && $request->status == 3)
                return $this->returnError('E001', trans('messages.Reservation already rejected'));

            if ($reservation->approved == 0 && $request->status == 3)
                return $this->returnError('E001', trans('messages.Reservation must be approved first'));

            if ($request->status == 1) {
                if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                    (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                        strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))
                ) {
                    return $this->returnError('E001', trans("messages.You can't take action to a reservation passed"));
                }

            }

            if ($status == 1) { //doctor accept reservation
                // initialize chat
                $this->startChatting($reservation->id, $reservation->user_id, '1');  // 1 ---> user
                $this->sendSMS($reservation->user->mobile, $reservation->reservation_no . 'تم قبول حجزك برقم -  ');
            }
            if ($status == 2) {
                $this->sendSMS($reservation->user->mobile, $reservation->reservation_no . 'تم رفض حجزك برقم -  ');

            $reservation->update([
                'rejection_reason' => $request->rejected_reason_id,
                'doctor_rejection_reason' =>$request -> reason_text
            ]);

            }


            $reservation->update([
                'approved' => $request->status, //approve reservation
                'chat_duration' => isset($request->chat_duration) ? $request->chat_duration : 0
            ]);

            DB::commit();

            $payment_method = $reservation->paymentMethod->id;   // 1- cash otherwise electronic
            $application_percentage_of_consulting = $reservation->doctor->application_percentage ? $reservation->doctor->application_percentage : 0;

            if ($payment_method != 1 && $request->status == 3) {//  visa reservation 3-complete reservation
                $totalBill = 0;
                $doctor_type = $reservation->doctor->doctor_type;

                $invoice_type = 0;

            }
            $name = 'name_' . app()->getLocale();
            //send push notifications goes here

            return $this->returnSuccessMessage('', __('messages.reservation status changed successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    ##################### End change doctor consulting reservation status ########################
    private function calculateConsultingReservationBalance($application_percentage_of_consulting, $reservation, $doctor_type = 'clinic')
    {
        $total_amount = floatval($reservation->total_price);
        $MC_percentage = $application_percentage_of_consulting;
        $reservationBalanceBeforeAdditionalTax = ($total_amount * $MC_percentage) / 100;
        $additional_tax_value = ($reservationBalanceBeforeAdditionalTax * env('ADDITIONAL_TAX', '5')) / 100;
        $reservationBalance = $total_amount - ($reservationBalanceBeforeAdditionalTax + $additional_tax_value);
        $doctor = $reservation->doctor;  // always get branch
        $provider = $reservation->provider;  // always get branch

        if ($doctor_type == 'clinic') {
            $discountType = " نسبة ميدكال كول من كشف (استشاري تابع لفرع ) حجز الكتروني ";
            $doctor->update([
                'balance' => $doctor->balance + $reservationBalance,
            ]);
            $provider->update([
                'balance' => $provider->balance + $reservationBalance,
            ]);
        } else {
            $discountType = " نسبة ميدكال كول من كشف (استشاري غير تابع لفرع) حجز الكتروني ";
            $doctor->update([
                'balance' => $doctor->balance + $reservationBalance,
            ]);
        }

        $reservation->update([
            'discount_type' => $discountType,
            'application_balance_value' => $reservationBalance
        ]);


        return true;
    }
}
