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

}
