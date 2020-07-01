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
                "type" => "required|in:home_services,clinic_services,doctor,offer,all",
                "provider_id" => "required|exists:providers,id"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $type = $request->type;

            $provider = Provider::where('id', $request->provider_id)->first();
            if ($provider->provider_id != null) { // main provider
                return $this->returnError('E001', 'لابد ان يكون الحساب مقدم خدمة');
            }

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
                    } elseif ($request->type == 'all') {

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
        } else {
            // return all reservations

            return $this->getAllRecordReservations($providers);
        }
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

    public function getAllRecordReservations($providers)
    {
        $doctor_reservations = Reservation::doctorSelection()
            ->whereIn('provider_id', $providers)
            ->whereIn('approved', [2, 3, 5])   //reservations which cancelled by user or branch or complete
            ->whereNotNull('doctor_id')
            ->where('doctor_id', '!=', 0)
            /*  ->whereDate('day_date', '>=', Carbon::now()->format('Y-m-d'))*/
            ->orderBy('id', 'DESC');


        $home_services_reservations = ServiceReservation::serviceSelection()
            ->serviceSelection()
            ->whereHas('type', function ($e) {
                $e->where('id', 1);
            })
            ->whereIn('branch_id', $providers)
            ->whereIn('approved', [2, 3, 5])   //reservations which cancelled by user or branch or complete
            ->orderBy('id', 'DESC');

        $clinic_services_reservations = ServiceReservation::serviceSelection()->serviceSelection()->whereHas('type', function ($e) {
            $e->where('id', 2);
        })
            ->whereIn('branch_id', $providers)
            ->whereIn('approved', [2, 3, 5])   //reservations which cancelled by user or branch or complete
            ->orderBy('id', 'DESC');

        return Reservation::OfferReservationSelection()->with(['offer' => function ($q) {
            $q->select('id',
                DB::raw('title_' . app()->getLocale() . ' as title'),
                'expired_at',
                'price'
            );
        }, 'doctor' => function ($g) {
            $g->select('id', 'nickname_id', 'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                ->with(['nickname' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'rejectionResoan' => function ($rs) {
            $rs->select('id', DB::raw('name_' . app()->getLocale() . ' as rejection_reason'));
        }, 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'user' => function ($q) {
            $q->select('id', 'name', 'mobile', 'email', 'address', 'insurance_image', 'insurance_company_id', 'mobile')
                ->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'people' => function ($p) {
            $p->select('id', 'name', 'insurance_company_id', 'insurance_image')->with(['insuranceCompany' => function ($qu) {
                $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
            }]);
        }, 'provider' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'service' => function ($g) {
            $g->select('id', 'specification_id', \Illuminate\Support\Facades\DB::raw('title_' . app()->getLocale() . ' as title'), 'price')
                ->with(['specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'type' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }])
            ->whereIn('provider_id', $providers)
            ->whereIn('approved', [2, 3, 5])   //reservations which cancelled by user or branch or complete
            ->whereNotNull('offer_id')
            ->where('offer_id', '!=', 0)
            /*  ->whereDate('day_date', '>=', Carbon::now()->format('Y-m-d'))*/
            ->orderBy('id', 'DESC')
            ->union($doctor_reservations)
            ->union($home_services_reservations)
            ->union($clinic_services_reservations)
            ->paginate(PAGINATION_COUNT);
    }

}
