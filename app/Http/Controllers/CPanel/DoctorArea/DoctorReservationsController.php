<?php

namespace App\Http\Controllers\CPanel\DoctorArea;

use App\Http\Controllers\ChattingController;
use App\Models\DoctorConsultingReservation;
use App\Models\Provider;
use App\Models\Reason;
use App\Traits\ChattingTrait;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\GlobalTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\DoctorArea\DoctorConsultingReservationResource;

class DoctorReservationsController extends Controller
{
    use GlobalTrait, ChattingTrait;

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
                    'date' => 'required|format:Y-m-d',
                ]);
                $today        = date('Y-m-d');
                array_push($conditions, [DB::raw('DATE(day_date)'), $today]);
            }

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $doctor = $this->getAuthDoctor();
            $type = $request->type;

            $conditions[] = ['doctor_id',$doctor->id];
            $conditions[] = ['approved', $type];

            $reservations = DoctorConsultingReservation::with(['user'=>function($q){
                $q -> select('id','name','photo');
            }])
                ->where($conditions)
                ->paginate(PAGINATION_COUNT);


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
                    'rejected_reason_notes' => 'sometimes|nullable|string',
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

            if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                    strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))
            ) {

                return $this->returnError('E001', trans("messages.You can't take action to a reservation passed"));
            }

            if ($status == 1) { //doctor accept reservation
                // initialize chat
                $this->startChatting($reservation->id, $reservation->user_id, '1');  // 1 ---> user
            }


            $reservation->update([
                'approved' => $request->status, //approve reservation
                'chat_duration' => isset($request->chat_duration) ? $request->chat_duration : 0
            ]);

            DB::commit();

            $payment_method = $reservation->paymentMethod->id;   // 1- cash otherwise electronic
            $application_percentage_of_consulting = $reservation->doctor->application_percentage ? $reservation->doctor->application_percentage : 0;
            if ($payment_method == 1 && $request->status == 3) {//1- cash reservation 3-complete reservation
                $totalBill = 0;
                $comment = " نسبة ميدكال كول من كشف (استشاري) حجز نقدي ";
                $invoice_type = 0;
                try {
                    // $this->calculateConsultingReservationBalance($application_percentage_of_consulting, $reservation);
                } catch (\Exception $ex) {
                }
            }

            if ($payment_method != 1 && $request->status == 3) {//  visa reservation 3-complete reservation
                $totalBill = 0;
                $comment = " نسبة ميدكال كول من كشف (استشاري) حجز الكتروني ";
                $invoice_type = 0;
                try {
                    // $this->calculateConsultingReservationBalance($application_percentage_of_consulting, $reservation);
                } catch (\Exception $ex) {
                }
            }
            $name = 'name_' . app()->getLocale();
            //send push notifications goes here

            return $this->returnSuccessMessage('', __('messages.reservation status changed successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    ##################### End change doctor consulting reservation status ########################
}
