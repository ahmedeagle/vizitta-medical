<?php

namespace App\Http\Controllers\CPanel\DoctorArea;

use App\Http\Controllers\ChattingController;
use App\Models\DoctorConsultingReservation;
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
         //where validation!!
        try {
            $doctor = $this->getAuthDoctor();
            $type = $request->type;

            $reservations = DoctorConsultingReservation::where(function ($query) use ($doctor, $type) {
                $query->where('doctor_id', $doctor->id)
                    ->where('approved', $type);
            })->paginate(PAGINATION_COUNT);
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
                "status" => "required|in:1,2", // 1 == confirmed && 2 == canceled
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $reservation_id = $request->reservation_id;
            $status = $request->status;
            $rejection_reason_id = $request->reason_id;
            $rejection_reason_text = $request->reason_text;

            $reservation = DoctorConsultingReservation::where('id', $reservation_id)->with('user')->first();

            if ($reservation == null)
                return $this->returnError('E001', __('messages.No reservation with this number'));
            if ($reservation->approved == 1) {
                return $this->returnError('E001', __('messages.Reservation already approved'));
            }

            if ($reservation->approved == 2) {
                return $this->returnError('E001', __('messages.Reservation already rejected'));
            }

            if ($status != 2 && $status != 1) {
                return $this->returnError('E001', __('messages.status must be 1 or 2'));
            } else {

                if ($status == 2) {
                    if ($rejection_reason_id == null) {
                        return $this->returnError('E001', __('messages.please enter rejection reason'));
                    }
                }
                $data = [
                    'approved' => $status,
                ];

                if (!empty($rejection_reason_id))
                    $data['rejection_reason'] = $rejection_reason_id;

                if (!empty($rejection_reason_text))
                    $data['doctor_rejection_reason'] = $rejection_reason_text;

                $reservation->update($data);
                ///////initial empty chat id ////////

                if ($status == 1) { //doctor accept reservation
                    // initialize chat
                    $this->startChatting($reservation->id, $reservation->user_id, '1');  // 1 ---> user
                }
                return $this->returnData('', '', __('messages.reservation status changed successfully'));
            }

        } catch (\Exception $ex) {
            return $this->returnError('E001', __('main.oops_error'));
        }
    }

    ##################### End change doctor consulting reservation status ########################

}
