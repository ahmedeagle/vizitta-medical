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
                "id" => "required|exists:specifications,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $services = $this->getServices($request -> id);

            if (count($services) > 0) {
                foreach ($services as $key => $service) {
                    $service->time = "";
                    $days = $service->times;
                }
                $total_count = $services->total();
                $per_page = PAGINATION_COUNT;
                $services->getCollection()->each(function ($service) {
                    $service->makeHidden(['available_time', 'provider_id', 'branch_id','hide','clinic_reservation_period','time']);
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
