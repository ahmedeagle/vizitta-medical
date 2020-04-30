<?php

namespace App\Http\Controllers\CPanel\DoctorArea;

use App\Models\DoctorConsultingReservation;
use App\Models\Reason;
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
    use GlobalTrait;

    public function index(Request $request)
    {
        try {
            $doctor = $this->getAuthDoctor();
            $type = $request->type;

            $reservations = DoctorConsultingReservation::where(function ($query) use ($doctor, $type) {
                $query->where('doctor_id', $doctor->id)
                    ->where('approved', $type);
            })->paginate(PAGINATION_COUNT);
            $result = new DoctorConsultingReservationResource($reservations);
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function getRejectedReasons(Request $request)
    {
        try {
            $result = Reason::get(['id', DB::raw('name_' . $this->getCurrentLang() . ' as name')]);
            return response()->json(['status' => true, 'data' => $result, 'msg' => '']);


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
                "status" => "required|in:1,2", // 1 == confirmed && 2 == canceled
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $reservation_id = $request->reservation_id;
            $status = $request->status;
            $rejection_reason_id = $request->reason_id;
            $rejection_reason_text = $request->reason_text;

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
                    if ($rejection_reason_id == null) {
                        return response()->json(['success' => false, 'error' => __('messages.please enter rejection reason')], 200);
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

                return response()->json(['status' => true, 'msg' => __('messages.reservation status changed successfully')]);
            }

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

##################### End change doctor consulting reservation status ########################

}
