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

class ServiceController extends Controller
{
    use GlobalTrait, ServiceTrait, PromoCodeTrait, OdooTrait, SMSTrait;


    public function reserveTime(Request $request)
    {
        $rules = [
            "service_id" => "required|numeric|exists:services,id",
            "payment_method_id" => "required|numeric|exists:payment_methods,id",
            "day_date" => "required|date",
            "agreement" => "required|boolean",
            "from_time" => "required",
            "to_time" => "required",
            "address" => "required"
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $user = $this->auth('user-api');
        if ($user == null)
            return $this->returnError('E001', trans('messages.There is no user with this id'));
        $validation = $this->validateFields(['service' => ['service_id' => $request->service_id, 'day_date' => $request->day_date, 'from_time' => $request->from_time, 'to_time' => $request->to_time]]);

        if (!$request->agreement)
            return $this->returnError('E006', trans('messages.Agreement is required'));


        if (strtotime($request->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
            ($request->day_date == Carbon::now()->format('Y-m-d') && strtotime($request->to_time) < strtotime(Carbon::now()->format('H:i:s'))))
            return $this->returnError('D000', trans("messages.You can't reserve to a time passed"));

        if ($validation->service_found)
            return $this->returnError('E001', trans('messages.This time is not available'));

        $service = Service::with('times')->find($request->service_id);
        $specification = $service->specification_id;
        $reservationDayName = date('l', strtotime($request->day_date));

        $rightDay = false;
        $timeOrder = 1;
        $last = false;
        $times = [];
        $day_code = substr(strtolower($reservationDayName), 0, 3);

        foreach ($service->times as $time) {
            if ($time['day_code'] == $day_code) {
                $times = $this->getServiceTimePeriodsInDay($time, substr(strtolower($reservationDayName), 0, 3), false);
                foreach ($times as $key => $time) {
                    if ($time['from_time'] == Carbon::parse($request->from_time)->format('H:i')
                        && $time['to_time'] == Carbon::parse($request->to_time)->format('H:i')) {
                        $rightDay = true;
                        $timeOrder = $key + 1;
                        if (count($times) == ($key + 1))
                            $last = true;
                        break;
                    }
                }
            }
        }

        if (!$rightDay)
            return $this->returnError('E001', trans('messages.This day is not in service days'));


        $reservationCode = $this->getRandomString(8);

        $reservation = Reservation::create([
            "reservation_no" => $reservationCode,
            "user_id" => $user->id,
            "service_id" => $service->id,
            "day_date" => date('Y-m-d', strtotime($request->day_date)),
            "from_time" => date('H:i:s', strtotime($request->from_time)),
            "to_time" => date('H:i:s', strtotime($request->to_time)),
            "payment_method_id" => $request->payment_method_id,
            "paid" => 0,
            "provider_id" => $service->branch_id,
            'order' => $timeOrder,
            'price' => $request->price
        ]);

        $provider = Provider::find($service->provider_id);
        $branch = Provider::find($service->branch_id);

        if (!$provider)
            return $this->returnError('E001', 'لا يوجد مقدم خدمه  للحجز');

        $provider->makeVisible(['application_percentage_bill', 'application_percentage']);

        if ($last) {
            ReservedTime::create([
                'service_id' => $service->id,
                'day_date' => date('Y-m-d', strtotime($request->day_date))
            ]);
        }

        $reserve = new \stdClass();
        $reserve->reservation_no = $reservation->reservation_no;
        $reserve->day_date = date('l', strtotime($request->day_date));
        $reserve->code = date('l', strtotime($request->day_date));
        $reserve->day_date = date('l', strtotime($request->day_date));
        $reserve->reservation_date = date('Y-m-d', strtotime($request->day_date));
        $reserve->price = $reservation->price;
        $reserve->payment_method = $reservation->paymentMethod()->select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->first();
        $reserve->from_time = $reservation->from_time;
        $reserve->to_time = $reservation->to_time;
        $reserve->provider = Provider::providerSelection()->find($service->provider_id);
        $reserve->branch = Provider::providerSelection()->find($service->branch_id);

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $reserve->branch->distance = (string)$this->getDistance($reserve->branch->latitude, $reserve->branch->longitude, $request->latitude, $request->longitude, 'K');
        }
        $reserve->service = Reservation::find($reservation->id)->doctorInfo;
        DB::commit();

        try {
            //push notification
            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New Service Reservation'), 'body' => __('messages.You have new service reservation')]))->sendProvider($branch); // branch
            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New Service Reservation'), 'body' => __('messages.You have new service reservation')]))->sendProvider($provider); // main  provider

            $providerName = $provider->{'name_' . app()->getLocale()};
            $smsMessage = __('messages.dear_service_provider') . ' ( ' . $providerName . ' ) ' . __('messages.provider_have_new_reservation_from_MedicalCall');
            $this->sendSMS($provider->mobile, $smsMessage);  //sms for main provider

            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New Service Reservation'), 'body' => __('messages.You have new service reservation')]))->sendProviderWeb($branch, null, 'new_reservation'); //branch
            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New Service Reservation'), 'body' => __('messages.You have new service reservation')]))->sendProviderWeb($provider, null, 'new_reservation');  //main provider
            $notification = GeneralNotification::create([
                'title_ar' => 'حجز خدمة جديد لدي مقدم الخدمة ' . ' ' . $providerName,
                'title_en' => 'New service reservation for ' . ' ' . $providerName,
                'content_ar' => 'هناك حجز خدمة جديد برقم ' . ' ' . $reservation->reservation_no . ' ' . ' ( ' . $providerName . ' )',
                'content_en' => __('messages.You have new reservation no:') . ' ' . $reservation->reservation_no . ' ' . ' ( ' . $providerName . ' )',
                'notificationable_type' => 'App\Models\Provider',
                'notificationable_id' => $reservation->provider_id,
                'data_id' => $reservation->id,
                'type' => 1 //new reservation
            ]);
            $notify = [
                'provider_name' => $providerName,
                'reservation_no' => $reservation->reservation_no,
                'reservation_id' => $reservation->id,
                'content' => __('messages.You have new reservation no:') . ' ' . $reservation->reservation_no . ' ' . ' ( ' . $providerName . ' )',
                'photo' => $reserve->provider->logo,
                'notification_id' => $notification->id
            ];
            //fire pusher  notification for admin  stop pusher for now
            try {
                event(new \App\Events\NewReservation($notify));   // fire pusher new reservation  event notification*/
            } catch (\Exception $ex) {
            }
        } catch (\Exception $ex) {
        }
        return $this->returnData('reservation', $reserve);
    }

    protected function getRandomString($length)
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
