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
    use GlobalTrait, DoctorTrait, PromoCodeTrait, OdooTrait, SMSTrait;


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
        try {

            DB::beginTransaction();

        } catch (\Exception $ex) {

            DB::rollback();
            return $this->returnError('D000', __('messages.sorry please try again later'));
        }


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
        //return $this->returnError('E001', ['rightDay'=>$rightDay, 'times'=> $times, 'days'=>[$reservationDayName, substr(strtolower($reservationDayName),0,3), $time]]);

        /*$times = $this->getDoctorTimesInDay($doctor->id, $reservationDayName);
        foreach ($times as $key => $time){
            if($time->from_time == Carbon::parse($request->from_time)->format('H:i:s')
                && $time->to_time == Carbon::parse($request->to_time)->format('H:i:s')){
                $rightDay = true;
                $timeOrder = $time->order;
                if(count($times) == ($key+1))
                    $last = true;
                break;
            }
        }*/

        if (!$rightDay)
            return $this->returnError('E001', trans('messages.This day is not in doctor days'));

        $people = null;
        $path = "";
        if (isset($request->insurance_image)) {
            $path = $this->saveImage('users', $request->insurance_image);
        }

        if (!$request->for_me) {
            $people = People::create([
                'name' => $request->name ? $request->name : $user->name,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'user_id' => $user->id,
                'insurance_company_id' => $request->use_insurance ? $request->insurance_company_id : NULL,
                'insurance_image' => $request->use_insurance ? $path : "",
                'insurance_expire_date' => $request->insurance_expire_date ? $request->insurance_expire_date : Null
            ]);
        } else {
            $user->update([
                'name' => $request->name ? $request->name : $user->name,
                'birth_date' => $request->birth_date ? $request->birth_date : $user->birth_date,
                'insurance_image' => $request->insurance_image ? $path : $user->insurance_image,
                'insurance_company_id' => $request->insurance_company_id ? $request->insurance_company_id : $user->insurance_company_id,
                'insurance_expire_date' => $request->insurance_expire_date ? $request->insurance_expire_date : Null
            ]);
        }

        $reservationCode = $this->getRandomString(8);
        $reservation = Reservation::create([
            "reservation_no" => $reservationCode,
            "user_id" => $user->id,
            "doctor_id" => $doctor->id,
            "day_date" => date('Y-m-d', strtotime($request->day_date)),
            "from_time" => date('H:i:s', strtotime($request->from_time)),
            "to_time" => date('H:i:s', strtotime($request->to_time)),
            "payment_method_id" => $request->payment_method_id,
            "paid" => 0,
            "use_insurance" => isset($request->use_insurance) ? $request->use_insurance : false,
            "promocode_id" => $promo_id,
            "provider_id" => $doctor->provider_id,
            'order' => $timeOrder,
            'price' => (!empty($request->price) ? $request->price : $doctor->price),
            'people_id' => $people ? $people->id : $people,
            'doctor_rate' => $request->doctor_rate,
            'provider_rate' => $request->provider_rate,
        ]);

        $provider = Provider::find($reservation->provider->provider_id);
        if (!$provider)
            return $this->returnError('E001', 'لا يوجد مقدم خدمه  للحجز');

        $provider->makeVisible(['application_percentage_bill', 'application_percentage']);

        /* if (!$reserveWithPrepaidCoupon) {
             //if there is bill  take app percentage from bill + reservation price
             if ($provider->application_percentage_bill > 0 && $provider->application_percentage > 0) {
                 $discountType = ' فاتوره + كشف ';
                 $reservation->update(['discount_type' => $discountType]);
             } elseif ($provider->application_percentage_bill > 0) {
                 $discountType = 'خصم  علي  الفاتوره';
                 $reservation->update(['discount_type' => $discountType]);
             } elseif ($provider->application_percentage > 0) {
                 $discountType = 'خصم  علي   الكشف';
                 $reservation->update(['discount_type' => $discountType]);
             }
         }*/

        if ($last) {
            ReservedTime::create([
                'doctor_id' => $doctor->id,
                'day_date' => date('Y-m-d', strtotime($request->day_date))
            ]);
        }
        // Sending provider mail
        if ($doctor->provider->email != null) {
            $lang = app()->getLocale();
            $dayName = trans('messages.' . date('l', strtotime($request->day_date)));
            $providerName = $doctor->provider->name_ar;
            app()->setLocale('ar');
            try {
                Mail::to($doctor->provider->email)
                    ->send(new NewReservationMail($providerName, $dayName, $request->day_date, $request->from_time, $request->to_time));
            } catch (\Exception $ex) {
            }
            app()->setLocale($lang);
        }

        $insuranceData = User::where('id', $user->id)
            ->select('insurance_company_id as id',
                'insurance_image as image',
                'insurance_expire_date',
                DB::raw('IFNULL((SELECT name_' . app()->getLocale() . ' FROM insurance_companies WHERE insurance_companies.id = users.insurance_company_id), "") AS name')
            )->first();
        $insuranceData->makeVisible(['insurance_company_id']);
        //use this coupon offer to save it after make reservation to odoo by  odoo_offer_id from payment table

        $reserve = new \stdClass();
        $reserve->reservation_no = $reservation->reservation_no;
        //  $reserve->payment_method  = ($request->payment_method_id == 1) ? trans('messages.cash') : trans('messages.card');
        $reserve->day_date = date('l', strtotime($request->day_date));
        $reserve->code = date('l', strtotime($request->day_date));
        $reserve->day_date = date('l', strtotime($request->day_date));
        $reserve->reservation_date = date('Y-m-d', strtotime($request->day_date));
        $reserve->price = $reservation->price;
        $reserve->payment_method = $reservation->paymentMethod()->select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->first();
        $reserve->from_time = $reservation->from_time;
        $reserve->to_time = $reservation->to_time;
        $branch = Reservation::find($reservation->id)->branchId;

        $reserve->provider = Provider::providerSelection()->find($reservation->provider->provider_id);
        $reserve->branch = $branch;

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $reserve->branch->distance = (string)$this->getDistance($reserve->branch->latitude, $reserve->branch->longitude, $request->latitude, $request->longitude, 'K');
        }
        $reserve->doctor = Reservation::find($reservation->id)->doctorInfo;
        $reserve->coupon = PromoCode::selection2()->find($reservation->promocode_id);
        if ($reserve->payment_method->id == 5)   // prepaid coupon
            $reserve->coupon->code = $promoCode->code;

        $reserve->insurance_company = $insuranceData;
        // $reserve->doctor         =  Reservation::find($reservation -> id) -> doctor() -> first();

        DB::commit();

        if (isset($promo_id) && $promo_id != null) {
            event(new \App\Events\OfferWasUsed(PromoCode::select('id', 'uses')->find($promo_id)));   // fire increase uses number event if reservation with coupon
        }

        try {
            //push notification
            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New Reservation'), 'body' => __('messages.You have new reservation')]))->sendProvider(Provider::find($doctor->provider_id)); // branch
            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New Reservation'), 'body' => __('messages.You have new reservation')]))->sendProvider(Provider::find($doctor->provider_id)->provider); // main  provider

//            $this->sendSMS(Provider::find($doctor->provider_id)->provider->mobile, __('messages.You have new reservation'));  //sms for main provider

            $providerName = Provider::find($doctor->provider_id)->provider->{'name_' . app()->getLocale()};
            $smsMessage = __('messages.dear_service_provider') . ' ( ' . $providerName . ' ) ' . __('messages.provider_have_new_reservation_from_MedicalCall');
            $this->sendSMS(Provider::find($doctor->provider_id)->provider->mobile, $smsMessage);  //sms for main provider

            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New Reservation'), 'body' => __('messages.You have new reservation')]))->sendProviderWeb(Provider::find($doctor->provider_id), null, 'new_reservation'); //branch
            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New Reservation'), 'body' => __('messages.You have new reservation')]))->sendProviderWeb(Provider::find($doctor->provider_id)->provider, null, 'new_reservation');  //main provider
            $notification = GeneralNotification::create([
                'title_ar' => 'حجز جديد لدي مقدم الخدمة ' . ' ' . $providerName,
                'title_en' => 'New reservation for ' . ' ' . $providerName,
                'content_ar' => 'هناك حجز جديد برقم ' . ' ' . $reservation->reservation_no . ' ' . ' ( ' . $providerName . ' )',
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
