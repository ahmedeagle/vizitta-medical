<?php

namespace App\Http\Controllers;

use App\Mail\NewReservationMail;
use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\GeneralNotification;
use App\Models\InsuranceCompanyDoctor;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PromoCode;
use App\Models\People;
use App\Models\Service;
use App\Models\ServiceReservation;
use App\Models\User;
use App\Models\Provider;
use App\Models\Reservation;
use App\Models\ReservedTime;
use App\Traits\DoctorTrait;
use App\Traits\OdooTrait;
use App\Traits\PromoCodeTrait;
use App\Traits\ServiceTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\GlobalTrait;
use Illuminate\Support\Facades\Mail;
use Validator;
use DB;
use DateTime;
use function foo\func;

class ServiceController extends Controller
{
    use GlobalTrait, ServiceTrait, PromoCodeTrait, OdooTrait, SMSTrait;

    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "category_id" => "required|exists:specifications,id",
                "branch_id" => "required|exists:providers,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $services = Service::query();
            $queryStr = $request->queryStr;
            $category_id = $request->category_id;
            $branch_id = $request->branch_id;

            $services = $services->with(['specification' => function ($q1) {
                $q1->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($q2) {
                $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            }, 'provider' => function ($q2) {
                $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'types' => function ($q3) {
                $q3->select('services_type.id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }
            ])->where('branch_id', $branch_id)->where('specification_id', $category_id);

            if (isset($request->queryStr)) {
                $services = $services->where(function ($q4) use ($queryStr) {
                    $q4->where('title_en', 'LIKE', '%' . trim($queryStr) . '%')->orWhere('title_en', 'LIKE', '%' . trim($queryStr) . '%');
                });
            }

            $services = $services
                ->select(
                    'id',
                    DB::raw('title_' . $this->getCurrentLang() . ' as title'),
                    DB::raw('information_' . $this->getCurrentLang() . ' as information')
                    , 'specification_id', 'provider_id', 'branch_id', 'rate', 'price', 'home_price_duration', 'clinic_price_duration', 'status', 'reservation_period as clinic_reservation_period'
                )->paginate(PAGINATION_COUNT);


            if (count($services) > 0) {
                foreach ($services as $key => $service) {
                    $service->time = "";
                    $days = $service->times;
                    $num_of_rates = ServiceReservation::where('service_id', $service->id)
                        ->Where('service_rate', '!=', null)
                        ->Where('service_rate', '!=', 0)
                        ->Where('provider_rate', '!=', null)
                        ->Where('provider_rate', '!=', 0)
                        ->count();

                    $service->num_of_rates = $num_of_rates;
                }
                $total_count = $services->total();
                $per_page = PAGINATION_COUNT;
                $services->getCollection()->each(function ($service) {
                    $service->makeHidden(['available_time', 'provider_id', 'branch_id', 'hide', 'clinic_reservation_period', 'time']);
                    return $service;
                });

                $services = json_decode($services->toJson());
                $servicesJson = new \stdClass();
                $servicesJson->current_page = $services->current_page;
                $servicesJson->total_pages = $services->last_page;
                $servicesJson->total_count = $total_count;
                $servicesJson->per_page = $per_page;
                $servicesJson->data = $services->data;

                return $this->returnData('services', $servicesJson);
            }

            return $this->returnError('E001', trans('messages.No data founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function getServiceRates(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "service_id" => "required|numeric|exists:services,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $service = Service::find($request->service_id);

            if ($service == null)
                return $this->returnError('E001', trans('messages.Service not found'));

            $reservations = $service->
            reservations()
                ->with(['user' => function ($q) {
                    $q->select('id', 'name', 'photo');
                }, 'provider' => function ($qq) {
                    $qq->select('id', 'name_' . app()->getLocale() . ' as name', 'logo');
                }])
                ->select('id', 'user_id', 'service_rate', 'provider_rate', 'rate_date', 'rate_comment', 'provider_id', 'reservation_no')
                ->WhereNotNull('provider_rate')
                ->Where('provider_rate', '!=', 0)
                ->WhereNotNull('service_rate')
                ->Where('service_rate', '!=', 0)
                ->paginate(10);

            if (count($reservations->toArray()) > 0) {

                $reservations->getCollection()->each(function ($reservation) {
                    $reservation->makeHidden(['provider_id', 'for_me', 'branch_name', 'branch_no', 'mainprovider', 'admin_value_from_reservation_price_Tax', 'reservation_total', 'rejected_reason_type']);
                    return $reservation;
                });

                $num_of_rates = ServiceReservation::where('service_id', $request->service_id)
                    ->WhereNotNull('provider_rate')
                    ->Where('provider_rate', '!=', 0)
                    ->WhereNotNull('service_rate')
                    ->Where('service_rate', '!=', 0)
                    ->count('service_rate');

                $num_of_visitors = ServiceReservation::where('service_id', $request->service_id)
                    ->count();


                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $rateJson = new \stdClass();
                $rateJson->current_page = $reservations->current_page;
                $rateJson->total_pages = $reservations->last_page;
                $rateJson->total_count = $total_count;
                $rateJson->per_page = PAGINATION_COUNT;
                $rateJson->general_rate = $service->rate;
                $rateJson->num_of_rates = $num_of_rates;
                $rateJson->num_of_visitors = $num_of_visitors;
                $rateJson->data = $reservations->data;
                return $this->returnData('rates', $rateJson);
            }

            $this->returnError('E001', trans('messages.No rates founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    protected
    function getRandomString($length)
    {
        $characters = '0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        $chkCode = Reservation::where('reservation_no', $string)->first();
        if ($chkCode) {
            $this->getRandomString(8);
        }
        return $string;
    }


}
