<?php

namespace App\Http\Controllers;

use App\Http\Resources\CPanel\BalanceResource;
use App\Http\Resources\CPanel\ConsultingBalanceResource;
use App\Http\Resources\CPanel\SingleDoctorBalanceResource;
use App\Http\Resources\CPanel\SingleProviderResource;
use App\Http\Resources\CustomReservationsResource;
use App\Models\Doctor;
use App\Models\DoctorConsultingReservation;
use App\Models\Provider;
use App\Http\Controllers\Controller;
use App\Models\ProviderType;
use App\Models\Reservation;
use App\Models\ServiceReservation;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Validator;

class BalanceController extends Controller
{
    use GlobalTrait;

    //get all reservation doctor - services - offers which cancelled [2 by branch ,5 by user] or complete [3]
    public function getBalanceHistory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "type" => "required|in:home_services,clinic_services,doctor,offer,consulting",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $type = $request->type;

            $provider = $this->auth('provider-api');
            if ($provider->provider_id != null)
                return $this->returnError('D000', trans("messages.Your account isn't  provider"));

            $branches = $provider->providers()->pluck('id')->toArray();  // branches ids

            $reservations = $this->recordReservationsByType($branches, $type);

            if (count($reservations->toArray()) > 0) {

                $reservations->getCollection()->each(function ($reservation) use ($request) {
                    $reservation->makeHidden(['order', 'reservation_total', 'admin_value_from_reservation_price_Tax', 'mainprovider', 'is_reported', 'branch_no', 'for_me', 'rejected_reason_id', 'is_visit_doctor', 'rejection_reason', 'user_rejection_reason']);
                    if ($request->type == 'home_services') {
                        $reservation->reservation_type = 'home_services';
                    } elseif ($request->type == 'clinic_services') {
                        $reservation->reservation_type = 'clinic_services';
                    } elseif ($request->type == 'doctor') {
                        $reservation->reservation_type = 'doctor';
                    } elseif ($request->type == 'offer') {
                        $reservation->reservation_type = 'offer';
                    }
                    elseif ($request->type == 'consulting') {
                        $reservation->reservation_type = 'consulting';
                    }
                    elseif ($request->type == 'all') {

                        $this->addReservationTypeToResult($reservation);
                        $reservation->makeVisible(["bill_total"]);
                        $reservation->makeHidden(["offer_id",
                            "doctor_id",
                            "service_id",
                            "doctor_rate",
                            "service_rate",
                            "provider_rate",
                            "offer_rate",
                            "paid",
                            "use_insurance",
                            "promocode_id",
                            "provider_id",
                            "branch_id",
                            "rate_comment",
                            "rate_date",
                            "address",
                            "latitude",
                            "branch_name",
                            "comment_report",
                            "rejected_reason_type",
                            "rejection_resoan",
                            "longitude"]);


                    } else {
                        $reservation->reservation_type = 'undefined';
                    }
                    return $reservation;
                });

                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $reservationsJson = new \stdClass();
                $reservationsJson->current_page = $reservations->current_page;
                $reservationsJson->total_pages = $reservations->last_page;
                $reservationsJson->total_count = $total_count;
                //$reservationsJson->complete_reservation__count = $complete_reservation__count;
                //$reservationsJson->complete_reservation__amount = $complete_reservation__amount;
                $reservationsJson->per_page = PAGINATION_COUNT;
                $reservationsJson->data = $reservations->data;

                return $this->returnData('reservations', $reservationsJson);
            }
            return $this->returnData('reservations', $reservations);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function recordReservationsByType($providers, $type)
    {
        if ($type == 'home_services') {
            return $this->getHomeServicesRecordReservations($providers);
        } elseif ($type == 'clinic_services') {
            return $this->getClinicServicesRecordReservations($providers);
        } elseif ($type == 'doctor') {
            return $this->getDoctorRecordReservations($providers);
        } elseif ($type == 'offer') {
            return $this->getOfferRecordReservations($providers);
        } elseif ($type == 'consulting') {
            return $this->getConsultingRecordReservations($providers);
        }
    }

    protected function getConsultingRecordReservations($providers)
    {
        return $reservations = DoctorConsultingReservation::with(['paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }])
            ->whereNotNull('provider_id')
            ->whereIn('provider_id', $providers)
            ->where('approved', '3')
            ->whereNotNull('chat_duration')
            ->where('chat_duration', '!=', 0)
            ->select('id', 'discount_type', 'hours_duration', 'reservation_no', 'application_balance_value', 'custom_paid_price', 'remaining_price', 'payment_type', 'price', 'bill_total', 'payment_method_id')
            ->orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);
    }

    protected function getHomeServicesRecordReservations($providers)
    {
        return $reservations = ServiceReservation::whereHas('type', function ($e) {
            $e->where('id', 1);
        })->with(['paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
        ])
            ->whereIn('branch_id', $providers)
            ->where('approved', 3)
            ->where('is_visit_doctor', 1)
            ->select('id', 'reservation_no', 'application_balance_value', 'custom_paid_price', 'remaining_price', 'payment_type', 'price', 'bill_total', 'payment_method_id')
            ->orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);
    }

    protected function getClinicServicesRecordReservations($providers)
    {
        return $reservations = ServiceReservation::whereHas('type', function ($e) {
            $e->where('id', 2);
        })->with(['paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
        ])
            ->whereIn('branch_id', $providers)
            ->where('approved', 3)
            ->where('is_visit_doctor', 1)
            ->select('id', 'reservation_no', 'application_balance_value', 'custom_paid_price', 'remaining_price', 'payment_type', 'price', 'bill_total', 'payment_method_id')
            ->orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);
    }

    protected function getDoctorRecordReservations($providers)
    {

        return $reservations = Reservation::with(['paymentMethod' => function ($q) {
            $q->select('id', 'name_' . app()->getLocale() . ' as name');
        }])
            ->whereIn('provider_id', $providers)
            ->where('approved', 3)   //reservations which cancelled by user or branch or complete
            ->whereNotNull('doctor_id')
            ->where('doctor_id', '!=', 0)
            ->orderBy('id', 'DESC')
            ->select('id', 'reservation_no', 'application_balance_value', 'custom_paid_price', 'remaining_price', 'payment_type', 'price', 'bill_total', 'payment_method_id')
            ->paginate(PAGINATION_COUNT);
    }

    protected function getOfferRecordReservations($providers)
    {
        return $reservations = Reservation::with(['paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }])
            ->whereIn('provider_id', $providers)
            ->where('approved', 3)
            ->whereNotNull('offer_id')
            ->where('offer_id', '!=', 0)
            ->select('id', 'reservation_no', 'application_balance_value', 'custom_paid_price', 'remaining_price', 'payment_type', 'price', 'bill_total', 'payment_method_id')
            ->orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);
    }

}
