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
use App\Models\Specification;
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
use function foo\func;

class DoctorController extends Controller
{
    use GlobalTrait, DoctorTrait, PromoCodeTrait, OdooTrait, SMSTrait;

    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "branch_id" => "required|numeric",
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "nickname_id" => "required|max:255|numeric",
                "gender" => "required|min:1|max:2",
                "specification_id" => "required|numeric",
                "price" => "required|numeric",
                "information_en" => "sometimes|nullable",
                "information_ar" => "sometimes|nullable",
                //  "insurance_companies" => "required|array",
                "working_days" => "required|array",
                "reservation_period" => "required|numeric",
                "nationality_id" => "required|numeric|exists:nationalities,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $mainProvider = $this->auth('provider-api');

            $validation = $this->validateFields(['provider_id' => $request->branch_id, 'specification_id' => $request->specification_id,
                'nickname_id' => $request->nickname_id, 'nationality_id' => $request->nationality_id, 'insurance_companies' => $request->insurance_companies,
                'branch' => ['main_provider_id' => $mainProvider->id, 'provider_id' => $request->branch_id, 'branch_no' => 0]]);

            DB::beginTransaction();

            if (!$validation->provider_found)
                return $this->returnError('D000', trans("messages.There is no branch with this id"));

            if (!$validation->branch_found)
                return $this->returnError('D000', trans("messages.This branch isn't in your branches"));

            if (!$validation->specification_found)
                return $this->returnError('D000', trans("messages.There is no specification with this id"));

            if (!$validation->nickname_found)
                return $this->returnError('D000', trans("messages.There is no nickname with this id"));

            if (isset($request->nationality_id) && $request->nationality_id != 0) {
                if (!$validation->nationality_found)
                    return $this->returnError('D000', trans("messages.There is no nationality with this id"));
            }

            /* if($validation->insurance_companies_found != count($request->insurance_companies))
                 return $this->returnError('D000', trans("messages.There is one incorrect insurance company id"));*/

            // working days
            $working_days_data = [];
            $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            foreach ($request->working_days as $working_day) {
                $from = Carbon::parse($working_day['from']);
                $to = Carbon::parse($working_day['to']);
                if (!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period)
                    return $this->returnError('D000', trans("messages.There is one day with incorrect name"));
                $working_days_data[] = [
                    'provider_id' => $request->branch_id,
                    'day_name' => strtolower($working_day['day']),
                    'day_code' => substr(strtolower($working_day['day']), 0, 3),
                    'from_time' => $from->format('H:i'),
                    'to_time' => $to->format('H:i'),
                    'order' => array_search(strtolower($working_day['day']), $days),
                    'reservation_period' => $request->reservation_period];
            }

            $fileName = "";
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->saveImage('doctors', $request->photo);
            }


            $doctor = Doctor::create([
                "name_en" => $request->name_en,
                "name_ar" => $request->name_ar,
                "provider_id" => $request->branch_id,
                "nickname_id" => $request->nickname_id,
                "gender" => $request->gender,
                "photo" => $fileName,
                "information_en" => $request->information_en,
                "information_ar" => $request->information_ar,
                "specification_id" => $request->specification_id,
                "nationality_id" => $request->nationality_id != 0 ? $request->nationality_id : NULL,
                "price" => $request->price,
                "status" => true]);


            if ($request->has('insurance_companies')) {
                if (is_array($request->insurance_companies) && count($request->insurance_companies) > 0) {
                    // Insurance company IDs
                    $insurance_companies_data = [];
                    foreach ($request->insurance_companies as $company) {
                        $insurance_companies_data[] = ['doctor_id' => $doctor->id, 'insurance_company_id' => $company];
                    }
                    InsuranceCompanyDoctor::insert($insurance_companies_data);
                }
            }

            for ($i = 0; $i < count($working_days_data); $i++) {
                $working_days_data[$i]['doctor_id'] = $doctor->id;
            }
            DoctorTime::insert($working_days_data);
            DB::commit();
            return $this->returnSuccessMessage(trans('messages.Doctor added successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            //$doctor = Doctor::with('times')->find($request->id);
            $doctor = $this->getDoctorByID($request->id);
            if ($doctor != null) {
                $doctor->time = "";
                $days = $this->geDaysDoctorExist($doctor->id);
                $match = $this->getMatchedDateToDays($days);
                if (!$match || $match['date'] == null)
                    return $this->returnError('E001', trans('messages.doctor is not available'));
                $doctorTimesCount = $this->getDoctorTimePeriodsInDay($match['day'], $match['day']['day_code'], true);
                $availableTime = $this->getFirstAvailableTime($doctor->id, $doctorTimesCount, $days, $match['date'], $match['index']);

                $doctor->time = $availableTime;
                $countRate = count($doctor->reservations);
                $doctor->rate_count = $countRate;
                $doctor->working_days = $days;
                if (isset($request->user_id) && $request->user_id != 0) {
                    $favouriteDoctor = $this->getDoctorFavourite($doctor->id, $request->user_id);
                    if ($favouriteDoctor != null)
                        $doctor->favourite = 1;
                    else
                        $doctor->favourite = 0;
                }
                return $this->returnData('doctor', json_decode($doctor, FALSE));
            }
            return $this->returnError('E001', trans('messages.No doctor with this id'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
                "branch_id" => "required|numeric",
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "nickname_id" => "required|max:255|numeric",
                "gender" => "required|min:1|max:2",
                "specification_id" => "required|numeric",
                "price" => "required|numeric",
                "information_en" => "sometimes|nullable",
                "information_ar" => "sometimes|nullable",
                //  "insurance_companies" => "required|array",
                "working_days" => "required|array",
                "reservation_period" => "required|numeric",
                "nationality_id" => "required|numeric|exists:nationalities,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $mainProvider = $this->auth('provider-api');

            $validation = $this->validateFields(['provider_id' => $request->branch_id, 'specification_id' => $request->specification_id,
                'nickname_id' => $request->nickname_id, 'nationality_id' => $request->nationality_id, 'insurance_companies' => $request->insurance_companies,
                'branch' => ['main_provider_id' => $mainProvider->id, 'provider_id' => $request->branch_id, 'branch_no' => 0]]);

            DB::beginTransaction();

            $doctor = Doctor::find($request->id);
            if ($doctor == null)
                return $this->returnError('D000', trans("messages.There is no doctor with this id"));

            if (!$validation->provider_found)
                return $this->returnError('D000', trans("messages.There is no branch with this id"));

            if (!$validation->branch_found)
                return $this->returnError('D000', trans("messages.This branch isn't in your branches"));

            if (!$validation->specification_found)
                return $this->returnError('D000', trans("messages.There is no specification with this id"));

            if (!$validation->nickname_found)
                return $this->returnError('D000', trans("messages.There is no nickname with this id"));

            if (isset($request->nationality_id) && $request->nationality_id != 0) {
                if (!$validation->nationality_found)
                    return $this->returnError('D000', trans("messages.There is no nationality with this id"));
            }

            /*  if ($validation->insurance_companies_found != count($request->insurance_companies))
                  return $this->returnError('D000', trans("messages.There is one incorrect insurance company id"));*/

            // working days
            $working_days_data = [];
            $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

            foreach ($request->working_days as $working_day) {
                $from = Carbon::parse($working_day['from']);
                $to = Carbon::parse($working_day['to']);
                if (!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period)
                    return $this->returnError('D000', trans("messages.There is one day with incorrect name"));
                $working_days_data[] = ['doctor_id' => $doctor->id,
                    'provider_id' => $request->branch_id,
                    'day_name' => strtolower($working_day['day']),
                    'day_code' => substr(strtolower($working_day['day']), 0, 3),
                    'from_time' => $from->format('H:i'),
                    'to_time' => $to->format('H:i'),
                    'reservation_period' => $request->reservation_period];
            }

            $fileName = $doctor->photo;
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->saveImage('doctors', $request->photo);
            }

            $doctor->update([
                "name_en" => $request->name_en,
                "name_ar" => $request->name_ar,
                "provider_id" => $request->branch_id,
                "nickname_id" => $request->nickname_id != 0 ? $request->nickname_id : $doctor->nickname_id,
                "gender" => $request->gender,
                "photo" => $fileName,
                "information_en" => $request->information_en,
                "information_ar" => $request->information_ar,
                "reservation_period" => $request->reservation_period,
                "specification_id" => $request->specification_id != 0 ? $request->specification_id : $doctor->specification_id,
                "nationality_id" => $request->nationality_id != 0 ? $request->nationality_id : $doctor->nationality_id,
                "price" => $request->price
            ]);


            // Insurance company IDs
            //$insurance_companies_data = [];
            //foreach ($request->insurance_companies as $company){
            //  $insurance_companies_data[] = ['doctor_id' => $doctor->id, 'insurance_company_id' => $company];
            //}

            if ($request->has('insurance_companies')) {
                if (is_array($request->insurance_companies) && count($request->insurance_companies) > 0) {
                    $doctor->insuranceCompanies()->sync($request->insurance_companies);
                } else {
                    InsuranceCompanyDoctor::where('doctor_id', $doctor->id)->delete();
                }
            } else {
                InsuranceCompanyDoctor::where('doctor_id', $doctor->id)->delete();
            }

            $doctor->times()->delete();
            $doctor->times()->insert($working_days_data);

            DB::commit();
            return $this->returnSuccessMessage(trans('messages.Doctor updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $mainProvider = $this->auth('provider-api');
            $branches = $mainProvider->providers()->pluck('id')->toArray();
            DB::beginTransaction();
            $doctor = $this->checkDoctor($request->id);
            if ($doctor == null)
                return $this->returnError('D000', trans("messages.No doctor with this id"));

            if (!in_array($doctor->provider_id, $branches) && $doctor->provider_id != $mainProvider->id)
                return $this->returnError('D000', trans("messages.This doctor isn't in this provider"));

            if (count($doctor->reservations) > 0)
                return $this->returnError('D000', trans("messages.The doctor can not be deleted"));

            $doctor->delete();
            DB::commit();
            return $this->returnSuccessMessage(trans('messages.Doctor deleted successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getTimes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $doctor = Doctor::with('times')->find($request->id);
            if ($doctor == null)
                return $this->returnError('E001', trans('messages.No doctor with this id'));

            $times = $this->getOfferTimePeriods($doctor->times);
            if (count($times) > 0) {
                return $this->returnData('times', $times);
            }
            return $this->returnError('E001', trans('messages.No doctor times found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getTimesAsArrayOfDayCodes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",

            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $times = DoctorTime::where('doctor_id', $request->id)->pluck('day_code');

            if (count($times) > 0)
                return $this->returnData('timesCodes', $times);
            else
                return $this->returnData('timesCodes', $times, trans('messages.no times for doctor'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    //get availbles  slot times by day
    public function getAvailableTimes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
                "date" => "required|date_format:Y-m-d",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $date = $request->date;
            $doctor = $this->findDoctor($request->id);
            $d = new DateTime($date);
            $day_name = strtolower($d->format('l'));
            $days_name = ['saturday' => 'sat', 'sunday' => 'sun', 'monday' => 'mon', 'tuesday' => 'tue', 'wednesday' => 'wed', 'thursday' => 'thu', 'friday' => 'fri'];
            $dayCode = $days_name[$day_name];


            if ($doctor != null) {
                $day = $doctor->times()->where('day_code', $dayCode)->first();
                $doctorTimesCount = $this->getDoctorTimePeriodsInDay($day, $dayCode, true);
                $times = [];
                $date = $request->date;
                $doctorTimesCount = $this->getDoctorTimePeriodsInDay($day, $dayCode, true);
                $availableTime = $this->getAllAvailableTime($doctor->id, $doctorTimesCount, [$day], $date);
                if (count((array)$availableTime))
                    array_push($times, $availableTime);

                $res = [];
                if (count($times)) {
                    foreach ($times as $key => $time) {
                        $res = array_merge_recursive($time, $res);
                    }
                }
                $doctor->times = $res;

                ########### Start To Get Doctor Times After The Current Time ############
                $collection = collect($doctor->times);
                $filtered = $collection->filter(function ($value, $key) {

                    if (date('Y-m-d') == $value['date'])
                        return $value['from_time'] > date('H:i:s');
                    else
                        return $value;
                });
                $doctor->times = array_values($filtered->all());
                ########### End To Get Doctor Times After The Current Time ############

                return $this->returnData('doctor', json_decode($doctor, true));
            }

            return $this->returnError('E001', trans('messages.No doctor with this id'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getAvailableTimesold(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $doctor = $this->findDoctor($request->id);
            if ($doctor != null) {
                $days = $doctor->times()->get();
                $times = [];
                if (count($days) > 0) {
                    $days_name = ['sat' => 'Saturday', 'sun' => 'Sunday', 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday'];
                    foreach ($days as $day) {
                        $dayCode = $day['day_code'];


                        $date = $this->getMatchedDateToDayName($days_name[$dayCode]);
                        if ($date == null)
                            return $this->returnError('E001', trans('messages.doctor is not available'));
                        $doctorTimesCount = $this->getDoctorTimePeriodsInDay($day, $dayCode, true);
                        $availableTime = $this->getAllAvailableTime($doctor->id, $doctorTimesCount, [$day], $date);

                        if (count((array)$availableTime))
                            array_push($times, $availableTime);
                    }
                }

                $res = [];
                if (count($times)) {
                    foreach ($times as $key => $time) {
                        $res = array_merge_recursive($time, $res);
                    }
                }
                $doctor->times = $res;

                return $this->returnData('doctor', json_decode($doctor, true));
            }
            return $this->returnError('E001', trans('messages.No doctor with this id'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function reserveTime(Request $request)
    {
        $rules = [
            "promocode" => "max:255",
            "doctor_id" => "required|numeric",
            "payment_method_id" => "required|numeric",
            "day_date" => "required|date",
            "agreement" => "required|boolean",
            "from_time" => "required",
            "to_time" => "required",
            "doctor_rate" => "numeric|min:0|max:5",
            "provider_rate" => "numeric|min:0|max:5",
            "use_insurance" => "boolean",
            "name" => "max:255",
            "birth_date" => "sometimes|nullable|date",
            "for_me" => "required|boolean",
        ];

        if ($request->use_insurance == 1 or $request->use_insurance == '1') {
            $rules['insurance_company_id'] = 'required|exists:insurance_companies,id';
            $rules['insurance_image'] = 'required';
        }

        if ($request->for_me == 0 or $request->for_me == '0') {
            $rules['phone'] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $user = $this->auth('user-api');
        if ($user == null)
            return $this->returnError('E001', trans('messages.There is no user with this id'));

        $validation = $this->validateFields(['doctor_id' => $request->doctor_id, 'payment_method_id' => $request->payment_method_id,
            'reservation' => ['doctor_id' => $request->doctor_id, 'day_date' => $request->day_date, 'from_time' => $request->from_time, 'to_time' => $request->to_time]]);

        DB::beginTransaction();
        if (!$request->agreement)
            return $this->returnError('E006', trans('messages.Agreement is required'));

        //$doctor = $this->findDoctor($request->doctor_id);
        if (!$validation->doctor_found)
            return $this->returnError('D000', trans('messages.No doctor with this id'));

        //$paymentMethod = $this->getPaymentMethodByID($request->payment_method_id);
        if (!$validation->payment_method_found)
            return $this->returnError('D000', trans('messages.No payment method with this id'));

        if (strtotime($request->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
            ($request->day_date == Carbon::now()->format('Y-m-d') && strtotime($request->to_time) < strtotime(Carbon::now()->format('H:i:s'))))
            return $this->returnError('D000', trans("messages.You can't reserve to a time passed"));

        //$reservedDay = $this->getReservedDay($doctor->id, $request->day_date);
        //  if($validation->reserved_times_found)
        //    return $this->returnError('E001', trans('messages.All day times already reserved'));

        //$hasReservation = $this->checkReservationInDate($request->doctor_id, $request->day_date, $request->from_time, $request->to_time);
        if ($validation->reservation_found)
            return $this->returnError('E001', trans('messages.This time is not available'));

        $doctor = Doctor::with('times')->find($request->doctor_id);
        $specification = $doctor->specification_id;
        $promoCode = null;
        $promo_id = null;

        $reserveWithPrepaidCoupon = false;
        if (isset($request->promocode)) {
            $promoCode = $this->getPromoByCode($request->promocode, $doctor->id, $doctor->provider_id); // discount coupon
            if ($promoCode) {

                if (strtotime($request->day_date) > strtotime($promoCode->expired_at)) {
                    return $this->returnError('E002', trans('messages.reservation_date_greater_than_coupon_expired_at') . '( ' . $promoCode->expired_at . ' )');
                }

                $promo_id = $promoCode->id;
                if ($promoCode->available_count > 0) {
                    $promoCode->update([
                        'available_count' => ($promoCode->available_count - 1)
                    ]);
                } else {
                    return $this->returnError('E002', trans('messages.exceeded the coupon limit'));
                }
            } else {  // prepaid coupon
                $promoCode = $this->getPaidPromoByCode($request->promocode);
                if (!$promoCode) {
                    return $this->returnError('E002', trans('messages.PromoCode is invalid'));
                }
                $promo_id = $promoCode->offer_id;
                // check if  coupon paid by login user or invite
                $owner = $this->checkIfCoupounPaid($promoCode->offer_id, $user->id);
                if (!$owner) {
                    return $this->returnError('E002', trans('messages.you not Owner Of this coupon'));
                }
                $promo = PromoCode::find($promoCode->offer_id);
                if ($promo) {
                    if ($promo->available_count > 0) {
                        $promo->update([
                            'available_count' => ($promo->available_count - 1)
                        ]);
                    } else {
                        return $this->returnError('E002', trans('messages.exceeded the coupon limit'));
                    }
                } else {
                    return $this->returnError('E002', trans('messages.coupon not found'));
                }
                $reserveWithPrepaidCoupon = true;
            }
        }

        $reservationDayName = date('l', strtotime($request->day_date));
        $rightDay = false;
        $timeOrder = 1;
        $last = false;
        $times = [];
        $day_code = substr(strtolower($reservationDayName), 0, 3);
        foreach ($doctor->times as $time) {
            if ($time['day_code'] == $day_code) {
                $times = $this->getDoctorTimePeriodsInDay($time, substr(strtolower($reservationDayName), 0, 3), false);
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

        $reserve->provider = Provider::providerSelection()->find($reservation->provider_id);

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $reserve->provider->distance = (string)$this->getDistance($reserve->provider->latitude, $reserve->provider->longitude, $request->latitude, $request->longitude, 'K');
        }
        $reserve->branch = $branch;
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
            //$this->sendSMS(Provider::find($doctor->provider_id)->provider->mobile, __('messages.You have new reservation'));  //sms for main provider
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
            event(new \App\Events\NewReservation($notify));   // fire pusher new reservation  event notification*/
        } catch (\Exception $ex) {
            return $this->returnData('reservation', $reserve);
        }
        return $this->returnData('reservation', $reserve);
    }

    public function reserveTimeV2(Request $request)
    {
        $rules = [
            "promocode" => "max:255",
            "doctor_id" => "required|numeric",
            "payment_method_id" => "required|numeric",
            "day_date" => "required|date",
            "agreement" => "required|boolean",
            "from_time" => "required",
            "to_time" => "required",
            "doctor_rate" => "numeric|min:0|max:5",
            "provider_rate" => "numeric|min:0|max:5",
            "use_insurance" => "boolean",
            "name" => "max:255",
            "birth_date" => "sometimes|nullable|date",
            "for_me" => "required|boolean",
        ];

        if ($request->use_insurance == 1 or $request->use_insurance == '1') {
            $rules['insurance_company_id'] = 'required|exists:insurance_companies,id';
            $rules['insurance_image'] = 'required';
        }

        if ($request->for_me == 0 or $request->for_me == '0') {
            $rules['phone'] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $user = $this->auth('user-api');
        if ($user == null)
            return $this->returnError('E001', trans('messages.There is no user with this id'));

        $validation = $this->validateFields(['doctor_id' => $request->doctor_id, 'payment_method_id' => $request->payment_method_id,
            'reservation' => ['doctor_id' => $request->doctor_id, 'day_date' => $request->day_date, 'from_time' => $request->from_time, 'to_time' => $request->to_time]]);

        DB::beginTransaction();
        if (!$request->agreement)
            return $this->returnError('E006', trans('messages.Agreement is required'));

        //$doctor = $this->findDoctor($request->doctor_id);
        if (!$validation->doctor_found)
            return $this->returnError('D000', trans('messages.No doctor with this id'));

        //$paymentMethod = $this->getPaymentMethodByID($request->payment_method_id);
        if (!$validation->payment_method_found)
            return $this->returnError('D000', trans('messages.No payment method with this id'));

        if (strtotime($request->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
            ($request->day_date == Carbon::now()->format('Y-m-d') && strtotime($request->to_time) < strtotime(Carbon::now()->format('H:i:s'))))
            return $this->returnError('D000', trans("messages.You can't reserve to a time passed"));

        //$reservedDay = $this->getReservedDay($doctor->id, $request->day_date);
        //  if($validation->reserved_times_found)
        //    return $this->returnError('E001', trans('messages.All day times already reserved'));

        //$hasReservation = $this->checkReservationInDate($request->doctor_id, $request->day_date, $request->from_time, $request->to_time);
        if ($validation->reservation_found)
            return $this->returnError('E001', trans('messages.This time is not available'));

        $doctor = Doctor::with('times')->find($request->doctor_id);
        $specification = $doctor->specification_id;
        $promoCode = null;
        $promo_id = null;

        $reserveWithPrepaidCoupon = false;
        if (isset($request->promocode)) {
            $promoCode = $this->getPromoByCode($request->promocode, $doctor->id, $doctor->provider_id); // discount coupon
            if ($promoCode) {

                if (strtotime($request->day_date) > strtotime($promoCode->expired_at)) {
                    return $this->returnError('E002', trans('messages.reservation_date_greater_than_coupon_expired_at') . '( ' . $promoCode->expired_at . ' )');
                }

                $promo_id = $promoCode->id;
                if ($promoCode->available_count > 0) {
                    $promoCode->update([
                        'available_count' => ($promoCode->available_count - 1)
                    ]);
                } else {
                    return $this->returnError('E002', trans('messages.exceeded the coupon limit'));
                }
            } else {  // prepaid coupon
                $promoCode = $this->getPaidPromoByCode($request->promocode);
                if (!$promoCode) {
                    return $this->returnError('E002', trans('messages.PromoCode is invalid'));
                }
                $promo_id = $promoCode->offer_id;
                // check if  coupon paid by login user or invite
                $owner = $this->checkIfCoupounPaid($promoCode->offer_id, $user->id);
                if (!$owner) {
                    return $this->returnError('E002', trans('messages.you not Owner Of this coupon'));
                }
                $promo = PromoCode::find($promoCode->offer_id);
                if ($promo) {
                    if ($promo->available_count > 0) {
                        $promo->update([
                            'available_count' => ($promo->available_count - 1)
                        ]);
                    } else {
                        return $this->returnError('E002', trans('messages.exceeded the coupon limit'));
                    }
                } else {
                    return $this->returnError('E002', trans('messages.coupon not found'));
                }
                $reserveWithPrepaidCoupon = true;
            }
        }

        $reservationDayName = date('l', strtotime($request->day_date));
        $rightDay = false;
        $timeOrder = 1;
        $last = false;
        $times = [];
        $day_code = substr(strtolower($reservationDayName), 0, 3);
        foreach ($doctor->times as $time) {
            if ($time['day_code'] == $day_code) {
                $times = $this->getDoctorTimePeriodsInDay($time, substr(strtolower($reservationDayName), 0, 3), false);
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


        $reserve->specification = Specification::where('id',$reserve->doctor -> specification_id)->select('id','name_'.app()->getLocale().' as name') ->  first();
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


    public function get_checkout_id(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "price" => array('required', 'regex:/^\d+(\.\d{1,2})?$/', 'min:1'),
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }


        $user = $this->auth('user-api');
        $userEmail = $user->email ? $user->email : 'info@wisyst.info';


        $url = "https://test.oppwa.com/v1/checkouts";
        $data =
            "entityId=8ac7a4ca6d0680f7016d14c5bbb716d8" .
            "&amount=" . $request->price .
            "&currency=SAR" .
            "&paymentType=DB" .
            "&notificationUrl=" .
            "&merchantTransactionId=400" .
            "&testMode=EXTERNAL" .
            "&customer.email=" . $userEmail;

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization:Bearer OGFjN2E0Y2E2ZDA2ODBmNzAxNmQxNGM1NzMwYzE2ZDR8QVpZRXI1ZzZjZQ'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if (curl_errno($ch)) {

                // return response()->json(['status' => false, 'errNum' => 3, 'msg' => $msg[3]]);
            }
            curl_close($ch);

        } catch (\Exception $ex) {

            return $this->returnError($ex->getCode(), $ex->getMessage());
        }

        $id = json_decode($responseData)->id;
        return $this->returnData('checkoutId', $id, trans('messages.Checkout id successefully retrieved'), 'S001');

    }


    public function checkPaymentStatus(Request $request)
    {


        $validator = Validator::make($request->all(), [
            "checkoutId" => 'required',
        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }


        $url = "https://test.oppwa.com/v1/checkouts/{$request -> checkoutId}/payment";
        $url .= "?entityId=8ac7a4ca6d0680f7016d14c5bbb716d8";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer OGFjN2E0Y2E2ZDA2ODBmNzAxNmQxNGM1NzMwYzE2ZDR8QVpZRXI1ZzZjZQ'));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);

        }
        curl_close($ch);

        $r = json_decode($responseData);

        return $this->returnData('status', $r->result, trans('messages.Payment status'), 'S001');

    }

    public function hide(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
                "hide" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $mainProvider = $this->auth('provider-api');
            $branches = $mainProvider->providers()->pluck('id')->toArray();
            DB::beginTransaction();
            try {
                $doctor = $this->checkDoctor($request->id);
                if ($doctor == null)
                    return $this->returnError('D000', trans("messages.No doctor with this id"));

                if (!in_array($doctor->provider_id, $branches) && $doctor->provider_id != $mainProvider->id)
                    return $this->returnError('D000', trans("messages.This doctor isn't in this provider"));

                if ($request->hide == '1') {
                    $doctor->update([
                        'status' => 0
                    ]);
                    DB::commit();
                    return $this->returnSuccessMessage(trans('messages.Doctor disappeared successfully'));
                } else {
                    $doctor->update([
                        'status' => 1
                    ]);
                    DB::commit();
                    return $this->returnSuccessMessage(trans('messages.Doctor showed successfully'));
                }

            } catch (\Exception $ex) {
                return $this->returnError($ex->getCode(), $ex->getMessage());
            }

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function updateReservation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_no" => "required",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $user = $this->auth('user-api');
            $reservation = $this->getReservationByNumber($request->reservation_no);
            if ($reservation == null)
                return $this->returnError('D000', trans('messages.There is no reservation with this number'));

            if ($reservation->user_id != $user->id)
                return $this->returnError('D000', trans('messages.This reservation not belongs to this user'));

            if ($reservation->payment_method_id == 1)
                return $this->returnError('D000', trans('messages.This reservation is not by online payment'));

            if ($reservation->paid)
                return $this->returnError('D000', trans('messages.This reservation already paid'));

            $reservation->update([
                'paid' => 1
            ]);
            return $this->returnSuccessMessage(trans('messages.Payment & Reservation proceeded successfully, waiting for provider approval'), 'S002');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
}
