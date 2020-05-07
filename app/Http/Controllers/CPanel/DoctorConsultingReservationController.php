<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\DoctorConsultingReservationDetailsResource;
use App\Models\DoctorConsultingReservation;
use App\Models\Specification;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\GlobalTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\DoctorConsultingReservationResource;

class DoctorConsultingReservationController extends Controller
{
    use GlobalTrait;

    public function index(Request $request)
    {
        try {
            $reservations = DoctorConsultingReservation::paginate(PAGINATION_COUNT);
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
                    'approved' => $status,
                ];

                if (!empty($rejection_reason))
                    $data['rejection_reason'] = $rejection_reason;

                $check = $reservation->update($data);

                return response()->json(['status' => true, 'msg' => __('messages.reservation status changed successfully'), 'check'=>$data]);
            }

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    ##################### End change doctor consulting reservation status ########################

    public function getReservationDetails(Request $request)
    {
        try {
            $reservation = DoctorConsultingReservation::find($request->id);
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

}
