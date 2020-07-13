<?php

namespace App\Http\Controllers;

use App\Mail\NewReservationMail;
use App\Models\ConsultativeDoctorTime;
use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\GeneralNotification;
use App\Models\HyperPayTransaction;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCompanyDoctor;
use App\Models\Nationality;
use App\Models\Nickname;
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

            $rules = [
                "branch_id" => "required|numeric",
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "is_consult" => "required|in:0,1",
                "nickname_id" => "required|max:255|numeric",
                "gender" => "required|min:1|max:2",
                "specification_id" => "required|numeric",
                "price" => "required|numeric",
                "information_en" => "sometimes|nullable",
                "information_ar" => "sometimes|nullable",
                //  "insurance_companies" => "required|array",
                // "working_days" => "required|array",
                "waiting_period" => "sometimes|nullable|numeric|min:0",
                "reservation_period" => "required|numeric",
                "nationality_id" => "required|numeric|exists:nationalities,id",
            ];

            if ($request->is_consult == 1) {

                if (isset($request->consultations_working_days)) {
                    $rules["consultations_working_days"] = "required|array|min:1";
                }
                $rules["password"] = "required|max:100|min:6";
                $rules["phone"] = 'required|max:100|unique:doctors,phone';
                $rules["price_consulting"] = "required|numeric";
            }

            $validator = Validator::make($request->all(), $rules);

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

            $_data = [
                "name_en" => $request->name_en,
                "name_ar" => $request->name_ar,
                "is_consult" => $request->is_consult,
                "provider_id" => $request->branch_id,
                "nickname_id" => $request->nickname_id,
                "gender" => $request->gender,
                "photo" => $fileName,
                "information_en" => $request->information_en,
                "information_ar" => $request->information_ar,
                "abbreviation_ar" => $request->abbreviation_ar,
                "abbreviation_en" => $request->abbreviation_en,
                "reservation_period" => $request->reservation_period,
                "waiting_period" => $request->waiting_period,
                "specification_id" => $request->specification_id,
                "nationality_id" => $request->nationality_id != 0 ? $request->nationality_id : NULL,
                "price" => $request->price,
                "status" => true];

            if ($request->is_consult == 1) {

                $_data['phone'] = trim($request->phone);
                $_data['password'] = $request->password;
                $_data['price_consulting'] = $request->price_consulting;
            }

            $doctor = Doctor::create($_data);


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


            // consultations working
            if ($request->is_consult == 1) {
                // Optional consultations working days
                if (isset($request->consultations_working_days) && !is_null($request->consultations_working_days)) {

                    $consultations_working_days_data = [];
                    foreach ($request->consultations_working_days as $working_day) {
                        if (empty($working_day['from']) or empty($working_day['to'])) {
                            return $this->returnError('D000', __('main.enter_time_from_and_to'));
                        }
                        $from = Carbon::parse($working_day['from']);
                        $to = Carbon::parse($working_day['to']);
                        if (!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period) {
                            return $this->returnError('D000', __('main.day_is_incorrect'));
                        }

                        $consultationsWorkingDays = [
                            'day_name' => strtolower($working_day['day']),
                            'day_code' => substr(strtolower($working_day['day']), 0, 3),
                            'from_time' => $from->format('H:i'),
                            'to_time' => $to->format('H:i'),
                            'order' => array_search(strtolower($working_day['day']), $days),
                            'reservation_period' => $request->reservation_period
                        ];

                        $consultationsWorkingDays['provider_id'] = $request->provider_id;
                        $consultations_working_days_data[] = $consultationsWorkingDays;
                    }

                    for ($i = 0; $i < count($consultations_working_days_data); $i++) {
                        $consultations_working_days_data[$i]['doctor_id'] = $doctor->id;
                    }

                    $times = ConsultativeDoctorTime::insert($consultations_working_days_data);
                }
            }

            DB::commit();
            return $this->returnSuccessMessage(trans('messages.Doctor added successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getAllActiveBranches()
    {
        return Provider::where('status', true)->whereNotNull('provider_id')->select('name_ar', 'id', 'provider_id')->get();
    }

    public function apiGetAllSpecifications()
    {
        return Specification::active()->select(\Illuminate\Support\Facades\DB::raw('id, name_' . app()->getLocale() . ' as name'))->get();
    }

    public function apiGetAllNicknames()
    {
        return Nickname::active()->select(DB::raw('id, name_' . app()->getLocale() . ' as name'))->get();
    }

    public function apiGetAllNationalities()
    {
        return Nationality::select(DB::raw('id, name_' . app()->getLocale() . ' as name'))->get();
    }

    public function apiGetAllInsuranceCompaniesWithSelected($doctor = null)
    {
        if ($doctor != null) {
            return InsuranceCompany::select('id', 'name_' . app()->getLocale() . ' as name', DB::raw('IF ((SELECT count(id) FROM insurance_company_doctor WHERE insurance_company_doctor.doctor_id = ' . $doctor->id . ' AND insurance_company_doctor.insurance_company_id = insurance_companies.id) > 0, 1, 0) as selected'))->get();
        } else {
            return InsuranceCompany::select('id', 'name_' . app()->getLocale() . ' as name', DB::raw('0 as selected'))->get();
        }
    }

    public function edit(Request $request)
    {
        try {
            $rules = [
                "id" => "required|exists:doctors,id",
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $doctor = Doctor::with('consultativeTimes')->find($request->id);
            $doctor->makeVisible(['specification_id', 'nationality_id', 'provider_id', 'status', 'nickname_id']);
            if ($doctor == null)
                return $this->returnError('D000', __('main.not_found'));
            $providers = $this->getAllActiveBranches();
            if (isset($providers) && $providers->count() > 0) {
                foreach ($providers as $index => $provider) {
                    $main_Provider = $provider->provider->name_ar;
                    $provider->provider_name = $main_Provider;
                    $provider->name = $provider->provider_name . ' - ' . $provider->name_ar;   // merge provider name behind branch  name
                }
            }

            $subsetProviders = $providers->map(function ($provider) {
                return collect($provider->toArray())
                    ->only(['id', 'name'])
                    ->all();
            });

            $result['doctor'] = $doctor;
            $result['providers'] = $subsetProviders;
            $result['specifications'] = $this->apiGetAllSpecifications();
            $result['nicknames'] = $this->apiGetAllNicknames();
            $result['nationalities'] = $this->apiGetAllNationalities();
            $result['companies'] = $this->apiGetAllInsuranceCompaniesWithSelected($doctor);
            $result['days'] = ['Saturday' => 'السبت', 'Sunday' => 'الأحد', 'Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة'];
            $result['times'] = $doctor->times()->get();
            $result['consultativeTimes'] = $doctor->consultativeTimes()->get();
            $days_code = ['sat' => 'Saturday', 'sun' => 'Sunday', 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday'];

//        $days_ar = ['السبت' => 'Saturday', 'الأحد' => 'Sunday', 'الإثنين ' => 'Monday', 'الثلاثاء' => 'Tuesday', 'الأربعاء' => 'Wednesday', 'الخميس ' => 'Thursday', 'الجمعة ' => 'Friday'];

            if (!empty($result['times']) && count($result['times']) > 0) {
                foreach ($result['times'] as $time) {
                    $time['day_code'] = $days_code[$time['day_code']];
                }
            }

            if (!empty($result['consultativeTimes']) && count($result['consultativeTimes']) > 0) {
                foreach ($result['consultativeTimes'] as $time) {
                    $time['day_code'] = $days_code[$time['day_code']];
                }
            }
            //            return response()->json(['status' => true, 'data' => $result]);
            return $this->returnData('doctor', $result);
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

                $countRate = $countRate = Doctor::find($request->id)->reservations()
                    ->Where('doctor_rate', '!=', null)
                    ->Where('doctor_rate', '!=', 0)
                    ->Where('provider_rate', '!=', null)
                    ->Where('provider_rate', '!=', 0)
                    ->count();
                $doctor->rate_count = $countRate;
                $doctor->working_days = $days;
                $user = $this->auth('user-api');

                if (isset($user) && $user != null) {
                    $favouriteDoctor = $this->getDoctorFavourite($request->id, $user->id);
                    if ($favouriteDoctor != null)
                        $doctor->favourite = 1;
                    else
                        $doctor->favourite = 0;
                }

                if ($doctor != null && $doctor->is_consult == 1) {
                    $consultativeTimes = $doctor->consultativeTimes()->get();
                    $days_code = ['sat' => 'Saturday', 'sun' => 'Sunday', 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday'];
                    if (!empty($consultativeTimes) && count($consultativeTimes) > 0) {
                        foreach ($consultativeTimes as $time) {
                            $time['day_code'] = $days_code[$time['day_code']];
                        }
                    }
                    $doctor->consultations_working_days = $consultativeTimes;
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
            $requestData = $request->all();
            $rules = [
                "id" => "required|exists:doctors,id",
                "branch_id" => "required|numeric",
                "is_consult" => "in:0,1", ### 0 == clinic && 1 == consultative
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "information_ar" => "required|max:255",
                "information_en" => "required|max:255",
                "abbreviation_ar" => "required|max:255",
                "abbreviation_en" => "required|max:255",
                "gender" => "required|in:1,2",
                "nickname_id" => "required|numeric|exists:doctor_nicknames,id",
                "specification_id" => "required|numeric|exists:specifications,id",
                "nationality_id" => "required|numeric|exists:nationalities,id",
                "price" => "required|numeric",
                "status" => "required|in:0,1",
                "waiting_period" => "sometimes|nullable|numeric|min:0",
                "reservation_period" => "required|numeric",
                "working_days" => "required|array|min:1",
            ];

            if ($requestData['is_consult'] == 1) {
                if (isset($request->consultations_working_days)) {
                    $rules["consultations_working_days"] = "required|array|min:1";
                }

                $rules["price_consulting"] = 'required|numeric';
                $rules["phone"] = 'required|max:100|unique:doctors,phone,' . $request->id . ',id';
                if (!empty($request->password)) {
                    $rules["password"] = "required|max:100|min:6";
                }
            }

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $doctor = Doctor::find($request->id);
            if ($doctor == null)
                return $this->returnError('E001', __('main.not_found'));

            $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $working_days_data = [];
            $consultations_working_days_data = [];

            // working days
            if (isset($request->working_days) && !is_null($request->working_days)) {

                foreach ($request->working_days as $working_day) {
                    if (!array_key_exists('from', $working_day) or !array_key_exists('to', $working_day)) {
                        return $this->returnError('E001', __('main.enter_time_from_and_to'));

                    }
                    $from = Carbon::parse($working_day['from']);
                    $to = Carbon::parse($working_day['to']);
                    if ((!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period)) {
                        return $this->returnError('E001', __('main.day_is_incorrect'));
                    }

                    $workingDays = [
                        'day_name' => strtolower($working_day['day']),
                        'day_code' => substr(strtolower($working_day['day']), 0, 3),
                        'from_time' => $from->format('H:i'),
                        'to_time' => $to->format('H:i'),
                        'order' => array_search(strtolower($working_day['day']), $days),
                        'reservation_period' => $request->reservation_period
                    ];

                    $workingDays['provider_id'] = $request->branch_id;

                    $working_days_data[] = $workingDays;
                }

                for ($i = 0; $i < count($working_days_data); $i++) {
                    $working_days_data[$i]['doctor_id'] = $doctor->id;
                }
            }

            // consultations working
            if ($requestData['is_consult'] == 1) {
                // Optional consultations working days
                if (isset($requestData['consultations_working_days']) && !is_null($requestData['consultations_working_days'])) {

                    foreach ($request->consultations_working_days as $working_day) {
                        if (!array_key_exists('from', $working_day) or !array_key_exists('to', $working_day)) {
                            return $this->returnError('E001', __('main.enter_time_from_and_to'));
                        }
                        $from = Carbon::parse($working_day['from']);
                        $to = Carbon::parse($working_day['to']);
                        if ((!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period)) {
                            return $this->returnError('E001', __('main.day_is_incorrect'));
                        }

                        $consultationsWorkingDays = [
                            'day_name' => strtolower($working_day['day']),
                            'day_code' => substr(strtolower($working_day['day']), 0, 3),
                            'from_time' => $from->format('H:i'),
                            'to_time' => $to->format('H:i'),
                            'order' => array_search(strtolower($working_day['day']), $days),
                            'reservation_period' => $request->reservation_period
                        ];

                        $consultationsWorkingDays['provider_id'] = $request->branch_id;

                        $consultations_working_days_data[] = $consultationsWorkingDays;
                    }

                    for ($i = 0; $i < count($consultations_working_days_data); $i++) {
                        $consultations_working_days_data[$i]['doctor_id'] = $doctor->id;
                    }
                }
            }

            $path = $doctor->photo;
            if (isset($request->photo)) {
                $path = $this->saveImage('doctors', $request->photo);
            }

            \Illuminate\Support\Facades\DB::beginTransaction();

            try {
                $_doctorInfo = [
                    "is_consult" => $request->is_consult,
                    "name_en" => $request->name_en,
                    "name_ar" => $request->name_ar,
                    "provider_id" => $request->branch_id,
                    "nickname_id" => $request->nickname_id,
                    "gender" => $request->gender,
                    "photo" => $path,
                    "information_en" => $request->information_en,
                    "information_ar" => $request->information_ar,
                    "abbreviation_ar" => $request->abbreviation_ar,
                    "abbreviation_en" => $request->abbreviation_en,
                    "specification_id" => $request->specification_id,
                    "nationality_id" => $request->nationality_id != 0 ? $request->nationality_id : null,
                    "price" => $request->price,
                    "reservation_period" => $request->reservation_period,
                    "waiting_period" => $request->waiting_period,
                    "status" => $request->status,
                ];


                if ($requestData['is_consult'] == 1) {
                    $_doctorInfo['phone'] = $request->phone;
                    $_doctorInfo['price_consulting'] = $request->price_consulting;
                    if (!empty($request->password)) {
                        $_doctorInfo['password'] = $request->password;
                    }
                }

                $doctor->update($_doctorInfo);

                // Insurance company IDs
                if ($request->has('insurance_companies') && is_array($request->insurance_companies) && !empty($request->insurance_companies)) {
                    $doctor->insuranceCompanies()->sync($request->insurance_companies); // manay to many save only the new values and delete others from database
                } else {
                    // $doctor -> insuranceCompanies() -> delete();
                    InsuranceCompanyDoctor::where('doctor_id', $doctor->id)->delete();
                }

                $doctor->times()->delete();
                $doctor->times()->insert($working_days_data);

                // consultations working
                if ($requestData['is_consult'] == 1) {
                    // Optional consultations working days
                    if (isset($requestData['consultations_working_days']) && !is_null($requestData['consultations_working_days']) && count($consultations_working_days_data) > 0) {
                        $doctor->consultativeTimes()->delete();
                        $doctor->consultativeTimes()->insert($consultations_working_days_data);
                    }
                }

                DB::commit();
                return $this->returnSuccessMessage(__('main.doctor_updated_successfully'));

            } catch (\Exception $ex) {
                DB::rollback();
                return $ex;
                return $this->returnError($ex->getCode(), $ex);
            }

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
                        return strtotime($value['from_time']) > strtotime(date('H:i:s'));
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
        $reserve->reservation_id = $reservation->id;        //  $reserve->payment_method  = ($request->payment_method_id == 1) ? trans('messages.cash') : trans('messages.card');
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
            $this->sendSMS(Provider::find($doctor->provider_id)->provider->mobile, __('messages.You have new reservation'));  //sms for main provider
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
                'type' => 1 //new doctor reservation
            ]);

            $notify = [
                'provider_name' => $providerName,
                'reservation_no' => $reservation->reservation_no,
                'reservation_id' => $reservation->id,
                'content' => __('messages.You have new reservation no:') . ' ' . $reservation->reservation_no . ' ' . ' ( ' . $providerName . ' )',
                'photo' => $reserve->provider->logo,
                'notification_id' => $notification->id
            ];

            event(new \App\Events\NewReservation($notify));   // fire pusher new reservation  event notification*/
            (new \App\Http\Controllers\NotificationController(['title' => $notification->title_ar, 'body' => $notification->content_ar]))->sendAdminWeb(1);
        } catch (\Exception $ex) {
            return $ex;
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
        $reserve->reservation_id = $reservation->id;
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


        $reserve->specification = Specification::where('id', $reserve->doctor->specification_id)->select('id', 'name_' . app()->getLocale() . ' as name')->first();
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

            $providerName = Provider::find($doctor->provider_id)->provider->{'name_' . app()->getLocale()};
            $smsMessage = __('messages.dear_service_provider') . ' ( ' . $providerName . ' ) ' . __('messages.provider_have_new_reservation_from_MedicalCall');
            $this->sendSMS(Provider::find($doctor->provider_id)->provider->mobile,
                $smsMessage);  //sms for main provider
            /*  $this->sendSMS(Provider::find($doctor->provider_id)->mobile,
                  $smsMessage);  //sms for branch*/

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
                //   event(new \App\Events\NewReservation($notify));   // fire pusher new reservation  event notification*/
                (new \App\Http\Controllers\NotificationController(['title' => $notification->title_ar, 'body' => $notification->content_ar]))->sendAdminWeb(1);
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

       /*$merchantId =  $this -> getHyperPayMerchantTransactionUniqueId(10);
        $hyperPayTransaction= HyperPayTransaction::create([
            'random_id' => $merchantId
        ]);*/

        $url = env('PAYTABS_CHECKOUTS_URL', 'https://oppwa.com/v1/checkouts');
        $data =
            "entityId=" . env('PAYTABS_ENTITYID', '8ac7a4ca6d0680f7016d14c5bbb716d8') .
            "&amount=" . $request->price .
            "&currency=SAR" .
            "&paymentType=DB" .
            "&notificationUrl=https://mcallapp.com";
//           "&merchantTransactionId=".$hyperPayTransaction -> merchantId ;
        //"&testMode=EXTERNAL" .
        //"&customer.email=" . $userEmail;

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization:Bearer ' . env('PAYTABS_AUTHORIZATION', 'OGFjN2E0Y2E2ZDA2ODBmNzAxNmQxNGM1NzMwYzE2ZDR8QVpZRXI1ZzZjZQ')));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, env('PAYTABS_SSL', false));// this should be set to true in production
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

//        HyperPayTransaction::where('random_id',$hyperPayTransaction -> merchantId ) -> update(['checkout_id' => $id ]);

        return $this->returnData('checkoutId', $id, trans('messages.Checkout id successefully retrieved'), 'S001');

    }

    public function checkPaymentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "checkoutId" => 'required',
            "resource" => 'required',
        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $url = env('PAYTABS_BASE_URL', 'https://test.oppwa.com/');
        $url .= $request->resource;
        $url .= "?entityId=" . env('PAYTABS_ENTITYID', '8ac7a4ca6d0680f7016d14c5bbb716d8');

        // $url = env('PAYTABS_CHECKOUTS_URL', 'https://test.oppwa.com/v1/checkouts') . '/' . $request->checkoutId . "/payment";
        //$url .= "?entityId=" . env('PAYTABS_ENTITYID', '8ac7a4ca6d0680f7016d14c5bbb716d8');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . env('PAYTABS_AUTHORIZATION', 'OGFjN2E0Y2E2ZDA2ODBmNzAxNmQxNGM1NzMwYzE2ZDR8QVpZRXI1ZzZjZQ')));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, env('PAYTABS_SSL', false));// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
//            return curl_error($ch);

            return $this->returnData('status', 'حدث خطا ما يرجي المحاولة مجددا', trans('messages.Payment status'), 'D001');
        }
        curl_close($ch);
        $r = json_decode($responseData);
        $obj = new \stdClass();
        $obj->id = isset($r->id) ? $r->id : '0';
        $obj->res = $r->result;

       // HyperPayTransaction::where('checkout_id',$request -> checkoutId) ->update(['transaction_id',$obj->id]);
        return $this->returnData('status', $obj, trans('messages.Payment status'), 'S001');
    }


    ///////////apple pay credential ///////////////////
    public function get_checkout_id_apple_pay(Request $request)
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

        $url = env('PAYTABS_CHECKOUTS_URL', 'https://oppwa.com/v1/checkouts');
        $data =
            "entityId=" . env('PAYTABS_APPLE_PAY_ENTITYID', '8ac7a4c8729db6f90172a323404c16f6') .
            "&amount=" . $request->price .
            "&currency=SAR" .
            "&paymentType=DB" .
            "&shopperResultUrl=com.wisyst.Medical.Call.payments";

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization:Bearer ' . env('PAYTABS_APPLE_PAY_AUTHORIZATION', 'OGFjN2E0Y2E2ZDA2ODBmNzAxNmQxNGM1NzMwYzE2ZDR8QVpZRXI1ZzZjZQ==')));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, env('PAYTABS_SSL', false));// this should be set to true in production
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

    public function checkPaymentStatus_apple_pay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "checkoutId" => 'required',
            "resource" => 'required',
        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $url = env('PAYTABS_BASE_URL', 'https://test.oppwa.com/');
        $url .= $request->resource;
        $url .= "?entityId=" . env('PAYTABS_APPLE_PAY_ENTITYID', '8ac7a4c8729db6f90172a323404c16f6');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . env('PAYTABS_APPLE_PAY_AUTHORIZATION', 'OGFjN2E0Y2E2ZDA2ODBmNzAxNmQxNGM1NzMwYzE2ZDR8QVpZRXI1ZzZjZQ==')));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, env('PAYTABS_SSL', false));// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
//            return curl_error($ch);

            return $this->returnData('status', 'حدث خطا ما يرجي المحاولة مجددا', trans('messages.Payment status'), 'D001');
        }
        curl_close($ch);
        $r = json_decode($responseData);
        $obj = new \stdClass();
        $obj->id = isset($r->id) ? $r->id : '0';
        $obj->res = $r->result;
        return $this->returnData('status', $obj, trans('messages.Payment status'), 'S001');
    }


    ///////////////stc pay///////////////////////
    public function get_checkout_id_stc_pay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "price" => array('required', 'regex:/^\d+(\.\d{1,2})?$/', 'min:1'),
            "mobile" =>
            array(
                "required",
                "digits_between:8,10",
                "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
            )
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $user = $this->auth('user-api');
        $userEmail = $user->email ? $user->email : 'info@wisyst.info';

        $url = env('PAYTABS_CHECKOUTS_URL', 'https://oppwa.com/v1/checkouts');
        $data =
            "entityId=" . env('PAYTABS_ENTITYID', '8ac7a4c8729db6f90172a323404c16f6') .
            "&amount=" . $request->price .
            "&currency=SAR" .
            "&paymentType=DB" .
            "&customParameters[branch_id]=1" .
            "&customParameters[teller_id]=1" .
            "&customParameters[device_id]=1" .
            "&customParameters[bill_number]=" .
            "&customParameters[SHOPPER_payment_mode]=mobile" .
            "&customer.mobile=" . $request->mobile; // STCPAY mobile number 05xxxxxxxx

        //  "&shopperResultUrl=com.wisyst.Medical.Call.payments";
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization:Bearer ' . env('PAYTABS_AUTHORIZATION', 'OGFjN2E0Y2E2ZDA2ODBmNzAxNmQxNGM1NzMwYzE2ZDR8QVpZRXI1ZzZjZQ==')));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, env('PAYTABS_SSL', false));// this should be set to true in production
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
