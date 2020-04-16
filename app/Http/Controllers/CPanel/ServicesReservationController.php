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
use App\Models\ServiceReservation;
use App\Traits\Dashboard\ReservationTrait;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\GlobalTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\ReservationResource;

class ServicesReservationController extends Controller
{
    use GlobalTrait;

    public function index(Request $request)
    {

        if ($request->reservation_id) {
            $reservation = ServiceReservation::find($request->reservation_id);
            if (!$reservation)
                return $this->returnError('E001', trans('messages.Reservation Not Found'));
        }
        $reservations = ServiceReservation::with(['service' => function ($g) {
            $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'))
                ->with(['specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'user' => function ($q) {
            $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                ->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'provider' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'type' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
        ]);

        if ($request->reservation_id) {
            $reservation = $reservations->first();
            $reservation->makeHidden(['paid', 'branch_id', 'provider_id', 'for_me', 'is_reported', 'reservation_total', 'mainprovider', 'rejected_reason_id', 'rejection_reason', 'user_rejection_reason', 'order', 'is_visit_doctor', 'bill_total', 'latitude', 'longitude', 'admin_value_from_reservation_price_Tax']);
            if (!$reservation)
                return $this->returnError('E001', trans('messages.No Reservations founded'));
            else
                return $this->returnData('reservations', $reservation);
        }

        $reservations = $reservations->paginate(PAGINATION_COUNT);
        $reservations->getCollection()->each(function ($reservation) {
            $reservation->makeHidden(['paid', 'branch_id', 'provider_id', 'for_me', 'is_reported', 'reservation_total', 'mainprovider', 'rejected_reason_id', 'rejection_reason', 'user_rejection_reason', 'order', 'is_visit_doctor', 'bill_total', 'latitude', 'longitude', 'admin_value_from_reservation_price_Tax']);
            return $reservation;
        });

        if (!empty($reservations) && count($reservations->toArray()) > 0) {
            $total_count = $reservations->total();
            $reservations = json_decode($reservations->toJson());
            $reservationsJson = new \stdClass();
            $reservationsJson->current_page = $reservations->current_page;
            $reservationsJson->total_pages = $reservations->last_page;
            $reservationsJson->per_page = PAGINATION_COUNT;
            $reservationsJson->total_count = $total_count;
            $reservationsJson->data = $reservations->data;
            return $this->returnData('reservations', $reservationsJson);
        }
        return $this->returnError('E001', trans('messages.No Reservations founded'));

    }


    public function destroy(Request $request)
    {
        try {

            $reservation = ServiceReservation::find($request->reservation_id);
            if ($reservation == null)
                return $this->returnError('E001', trans('messages.No Reservations founded'));

            if ($reservation->approved) {
                return $this->returnError('E001', trans('messages.Cannot delete approved reservation'));
            } else {
                $reservation->delete();
                return $this->returnSuccessMessage('S000', trans('messages.Reservation deleted successfully'));
            }
        } catch (\Exception $ex) {
            return $this->returnError('E001', $ex->getMessage());
        }
    }


    public function changeStatus(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|max:255",
                "status" => "required|in:1,2"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $reservation_id = $request->reservation_id;
            $status = $request->status;
            $rejection_reason = $request->reason;

            $reservation = ServiceReservation::where('id', $reservation_id)->with('user')->first();

            if ($reservation == null)
                return $this->returnError('E001', trans('messages.No reservation with this number'));
            if ($reservation->approved == 1) {
                return $this->returnError('E001', trans('messages.Reservation already approved'));
            }

            if ($reservation->approved == 2) {
                return $this->returnError('E001', trans('messages.Reservation already rejected'));
            }

            if ($status != 2 && $status != 1) {
                return $this->returnError('E001', trans('messages.status must be 1 or 2'));
            } else {

                if ($status == 2) {
                    if ($rejection_reason == null) {
                        return $this->returnError('E001', trans('messages.please enter rejection reason'));
                    }
                }
//                $this->changerReservationStatus($reservation, $status);

                $reservation->update([
                    'approved' => $status,
                    'rejection_reason' => $rejection_reason
                ]);


                return $this->returnError('E001', trans('messages.reservation status changed successfully'));
            }

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}
