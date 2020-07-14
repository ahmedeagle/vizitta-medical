<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\DoctorResource;
use App\Mail\AcceptReservationMail;
use App\Models\ConsultativeDoctorTime;
use App\Models\ReservedTime;
use App\Traits\Dashboard\DoctorTrait;
use App\Traits\Dashboard\PublicTrait;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\InsuranceCompanyDoctor;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use App\Models\DoctorTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;

class DoctorController extends Controller
{
    use DoctorTrait, PublicTrait, GeneralTrait;

    public function index(Request $request)
    {
        $queryStr = '';
        if ($request->queryStr) {
            $queryStr = $request->queryStr;
            $sqlQuery = Doctor::where(function ($q) use ($queryStr) {
                return $q->where('name_en', 'LIKE', '%' . trim($queryStr) . '%')
                    ->orWhere('name_ar', 'LIKE', '%' . trim($queryStr) . '%');
            });
        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $sqlQuery = Doctor::where('name_ar', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('name_en', 'LIKE', '%' . trim($q) . '%')
                ->orWhere(function ($qq) use ($q) {
                    if (trim($q) == 'مفعل') {
                        $qq->where('status', 1);
                    } elseif (trim($q) == 'غير مفعل') {
                        $qq->where('status', 0);
                    }
                })
                ->orWhere('phone', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('application_percentage', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('created_at', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhereHas('specification', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('nationality', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('nickname', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('provider', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                })
                ->orWhereHas('provider', function ($query) use ($q) {
                    $query->whereHas('provider', function ($query) use ($q) {
                        $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                    });
                });

            /*->orderBy('id', 'DESC')
            ->paginate(10);*/
        } else {
            $sqlQuery = Doctor::query();
        }

        $type = $request->type;

        if ($type == 'clinic') {
            $doctors = $sqlQuery->where(function ($q) {
                $q->where('doctor_type', 'clinic')->where('is_consult', '0');
            })->paginate(PAGINATION_COUNT);
        } elseif ($type == 'consultative') {
            $doctors = $sqlQuery->where(function ($q) {
                $q->where('doctor_type', 'consultative');
            })->paginate(PAGINATION_COUNT);
        } elseif ($type == 'both') {
            $doctors = $sqlQuery->where(function ($q) {
                $q->where('doctor_type', 'clinic')->where('is_consult', '1');
            })->paginate(PAGINATION_COUNT);
        } else {
            $doctors = $sqlQuery->orderBy('id', 'DESC')->paginate(PAGINATION_COUNT);
        }

        /*$doctors = Doctor::where(function ($q) use ($queryStr) {
            return $q->where('name_en', 'LIKE', '%' . trim($queryStr) . '%')->orWhere('name_ar', 'LIKE', '%' . trim($queryStr) . '%');
        })->paginate(PAGINATION_COUNT);*/

        $result = new DoctorResource($doctors);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function show(Request $request)
    {
        try {
            $doctor = $this->getDoctorDetailsById($request->id);
            if ($doctor == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $res = ['status' => true, 'data' => []];
            $res['data'] = $doctor;
             return response()->json($res);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function create(Request $request)
    {
        try {
            /*$branchId = Input::get('branch_id');

            if (isset($_COOKIE['working_hours'])) {
                setcookie('working_hours', '', -1);
            }*/

            $branchId = $request->branchId ?? null;

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

            $result['providers'] = $subsetProviders;
            $result['specifications'] = $this->apiGetAllSpecifications();
            $result['nicknames'] = $this->apiGetAllNicknames();
            $result['nationalities'] = $this->apiGetAllNationalities();
            $result['companies'] = $this->apiGetAllInsuranceCompaniesWithSelected(null);
            $result['days'] = ['Saturday' => 'السبت', 'Sunday' => 'الأحد', 'Monday' => 'الإثنين ', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس ', 'Friday' => 'الجمعة '];
            $result['branchId'] = $branchId;

            return response()->json(['status' => true, 'data' => $result]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function store(Request $request)
    {
        try {
            $requestData = $request->all();
            $rules = [
                "doctor_type" => "required|in:clinic,consultative",
                "is_consult" => "in:0,1", ### 0 == clinic && 1 == consultative
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "password" => "sometimes|nullable|max:255",
                "information_ar" => "required|max:255",
                "information_en" => "required|max:255",
                "abbreviation_ar" => "sometimes|max:255",
                "abbreviation_en" => "sometimes|max:255",
                "gender" => "required|in:1,2",
                "nickname_id" => "required|numeric|exists:doctor_nicknames,id",
                "specification_id" => "required|numeric|exists:specifications,id",
                "nationality_id" => "required|numeric|exists:nationalities,id",
                "price" => "required|numeric",
                "status" => "required|in:0,1",
                "waiting_period" => "sometimes|nullable|numeric|min:0",
                "reservation_period" => "required|numeric",
                // "working_days" => "required|array|min:1",
                "application_percentage" => "required|integer"
            ];

            if ($requestData['doctor_type'] == 'clinic') {
                $rules["provider_id"] = "required|numeric|exists:providers,id";
            } elseif ($requestData['doctor_type'] == 'clinic' && $requestData['is_consult'] == 1) {
                if (isset($requestData['consultations_working_days'])) {
                    $rules["consultations_working_days"] = "required|array|min:1";
                }
            }

            if ($requestData['is_consult'] == 1) {
                $rules["password"] = "required|max:100|min:6";
                $rules["price_consulting"] = "required|numeric";
            }

            if($request -> has('phone')  or $requestData['is_consult'] == 1)
                $rules["phone"] = "required|max:100|unique:doctors,phone";

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $fileName = "";
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->saveImage('doctors', $request->photo);
            }
            DB::beginTransaction();
            try {

                $doctorInfo = [
                    "doctor_type" => $request->doctor_type,
                    "is_consult" => $request->is_consult,
                    "name_en" => $request->name_en,
                    "name_ar" => $request->name_ar,
                    "provider_id" => $requestData['doctor_type'] == 'clinic' ? $request->provider_id : null,
                    "nickname_id" => $request->nickname_id,
                    "gender" => $request->gender,
                    "photo" => $fileName,
                    "information_en" => $request->information_en,
                    "information_ar" => $request->information_ar,

                    "abbreviation_ar" => $request->abbreviation_ar,
                    "abbreviation_en" => $request->abbreviation_en,

                    "specification_id" => $request->specification_id,
                    "nationality_id" => $request->nationality_id != 0 ? $request->nationality_id : NULL,
                    "price" => $request->price,
                    "reservation_period" =>$request->reservation_period ,  //only for clinic doctors only otherwise it = 0
                    "waiting_period" => $request->waiting_period,
                    "status" => true,
                    "application_percentage" => $request->application_percentage
                ];


                if ($requestData['is_consult'] == 1) {
                    $doctorInfo['phone'] = trim($request->phone);
                    $doctorInfo['password'] = $request->password;
                    $doctorInfo['price_consulting'] = $request->price_consulting;
                }

                $doctor = Doctor::create($doctorInfo);

                if ($requestData['doctor_type'] == 'clinic') {
                    // Insurance company IDs
                    if ($request->has('insurance_companies') && is_array($request->insurance_companies)) {
                        $insurance_companies_data = [];
                        foreach ($request->insurance_companies as $company) {
                            $insurance_companies_data[] = ['doctor_id' => $doctor->id, 'insurance_company_id' => $company];
                        }
                        $insurancs = InsuranceCompanyDoctor::insert($insurance_companies_data);
                    }
                }

                $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

                // working days  for clinic doctor
                if ($doctor -> doctor_type == 'clinic' && isset($request->working_days) && !is_null($request->working_days)) {
                    $working_days_data = [];
                    foreach ($request->working_days as $working_day) {
                        if (empty($working_day['from']) or empty($working_day['to'])) {
                            return response()->json(['status' => false, 'error' => ['working_day' => __('main.enter_time_from_and_to')]], 200);
                        }
                        $from = Carbon::parse($working_day['from']);
                        $to = Carbon::parse($working_day['to']);
                        if (!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period) {
                            return response()->json(['status' => false, 'error' => ['working_day' => __('main.day_is_incorrect')]], 200);
                        }

                        $workingDays = [
                            'day_name' => strtolower($working_day['day']),
                            'day_code' => substr(strtolower($working_day['day']), 0, 3),
                            'from_time' => $from->format('H:i'),
                            'to_time' => $to->format('H:i'),
                            'order' => array_search(strtolower($working_day['day']), $days),
                            'reservation_period' => $request->reservation_period
                        ];

                        if ($requestData['doctor_type'] == 'clinic') {
                            $workingDays['provider_id'] = $request->provider_id;
                        }
                        $working_days_data[] = $workingDays;
                    }

                    for ($i = 0; $i < count($working_days_data); $i++) {
                        $working_days_data[$i]['doctor_id'] = $doctor->id;
                    }

                    $times = DoctorTime::insert($working_days_data);
                }

                // consultations working
                if (($requestData['doctor_type'] == 'clinic' && $requestData['is_consult'] == 1) or  $requestData['doctor_type'] == 'consultative') {
                    // Optional consultations working days



                    if (isset($requestData['consultations_working_days']) && !is_null($requestData['consultations_working_days'])) {

                        $consultations_working_days_data = [];
                        foreach ($request->consultations_working_days as $working_day) {
                            if (empty($working_day['from']) or empty($working_day['to'])) {
                                return response()->json(['status' => false, 'error' => ['working_day' => __('main.enter_time_from_and_to')]], 200);
                            }
                            $from = Carbon::parse($working_day['from']);
                            $to = Carbon::parse($working_day['to']);
                            if (!in_array($working_day['day'], $days) || $to->diffInMinutes($from) <  15) {  // 15
                                return response()->json(['status' => false, 'error' => ['working_day' => __('main.day_is_incorrect')]], 200);
                            }

                            $consultationsWorkingDays = [
                                'day_name' => strtolower($working_day['day']),
                                'day_code' => substr(strtolower($working_day['day']), 0, 3),
                                'from_time' => $from->format('H:i'),
                                'to_time' => $to->format('H:i'),
                                'order' => array_search(strtolower($working_day['day']), $days),
                                'reservation_period' => 15 // for clinic doctor  but for consulting is 15 min static
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
                return response()->json(['status' => true, 'msg' => __('main.doctor_added_successfully')]);

            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
            }

        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        $doctor = Doctor::with(['consultativeTimes'])->find($request->id);
        $doctor->makeVisible(['specification_id', 'nationality_id', 'provider_id', 'status', 'nickname_id']);
        if ($doctor == null)
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
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

        return response()->json(['status' => true, 'data' => $result]);
    }

    public function update(Request $request)
    {
        try {
            $requestData = $request->all();
            $rules = [
                "id" => "required|exists:doctors,id",
                "doctor_type" => "required|in:clinic,consultative",
                "is_consult" => "in:0,1", ### 0 == clinic && 1 == consultative
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "information_ar" => "required|max:255",
                "information_en" => "required|max:255",
                "abbreviation_ar" => "sometimes|max:255",
                "abbreviation_en" => "sometimes|max:255",
                "gender" => "required|in:1,2",
                "nickname_id" => "required|numeric|exists:doctor_nicknames,id",
                "specification_id" => "required|numeric|exists:specifications,id",
                "nationality_id" => "required|numeric|exists:nationalities,id",
                "price" => "required|numeric",
                "status" => "required|in:0,1",
                "waiting_period" => "sometimes|nullable|numeric|min:0",
                "reservation_period" => "required|numeric",
                // "working_days" => "required|array|min:1",
                "application_percentage" => "required|integer",
            ];

            if ($requestData['doctor_type'] == 'clinic') {
                $rules["provider_id"] = "required|numeric|exists:providers,id";

            } elseif ($requestData['doctor_type'] == 'clinic' && $requestData['is_consult'] == 1) {
                if (isset($requestData['consultations_working_days'])) {
                    $rules["consultations_working_days"] = "required|array|min:1";
                }
            }

            if ($requestData['is_consult'] == 1) {
                // $rules["password"] = "required|max:100|min:6";
                $rules["phone"] = 'required|max:100|unique:doctors,phone,' . $request->id . ',id';
                $rules["price_consulting"] = 'required|numeric';
            }

            if (!empty($request->password)) {
                $rules["password"] = "required|max:100|min:6";
            }

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $doctor = $this->getDoctorById($request->id);
            if ($doctor == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $working_days_data = [];
            $consultations_working_days_data = [];

            // working days
            if ($doctor -> doctor_type == 'clinic' && isset($request->working_days) && !is_null($request->working_days)){

                foreach ($request->working_days as $working_day) {
                    if (!array_key_exists('from', $working_day) or !array_key_exists('to', $working_day)) {
                        return response()->json(['status' => false, 'error' => ['working_day' => __('main.enter_time_from_and_to')]], 200);
                    }
                    $from = Carbon::parse($working_day['from']);
                    $to = Carbon::parse($working_day['to']);
                    if ((!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period)) {
                        return response()->json(['status' => false, 'error' => ['working_day' => __('main.day_is_incorrect')]], 200);
                    }

                    $workingDays = [
                        'day_name' => strtolower($working_day['day']),
                        'day_code' => substr(strtolower($working_day['day']), 0, 3),
                        'from_time' => $from->format('H:i'),
                        'to_time' => $to->format('H:i'),
                        'order' => array_search(strtolower($working_day['day']), $days),
                        'reservation_period' => $request->reservation_period
                    ];

                    if ($requestData['doctor_type'] == 'clinic') {
                        $workingDays['provider_id'] = $request->provider_id;
                    }
                    $working_days_data[] = $workingDays;
                }

                for ($i = 0; $i < count($working_days_data); $i++) {
                    $working_days_data[$i]['doctor_id'] = $doctor->id;
                }
            }

            // consultations working
            if (($requestData['doctor_type'] == 'clinic' && $requestData['is_consult'] == 1) or  $requestData['doctor_type'] == 'consultative') {
                // Optional consultations working days
                if (isset($requestData['consultations_working_days']) && !is_null($requestData['consultations_working_days'])) {
                    foreach ($request->consultations_working_days as $working_day) {
                        if (!array_key_exists('from', $working_day) or !array_key_exists('to', $working_day)) {
                            return response()->json(['status' => false, 'error' => ['working_day' => __('main.enter_time_from_and_to')]], 200);
                        }
                        $from = Carbon::parse($working_day['from']);
                        $to = Carbon::parse($working_day['to']);
                        if ((!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < 15)) {
                            return response()->json(['status' => false, 'error' => ['working_day' => __('main.day_is_incorrect')]], 200);
                        }

                        $consultationsWorkingDays = [
                            'day_name' => strtolower($working_day['day']),
                            'day_code' => substr(strtolower($working_day['day']), 0, 3),
                            'from_time' => $from->format('H:i'),
                            'to_time' => $to->format('H:i'),
                            'order' => array_search(strtolower($working_day['day']), $days),
                            'reservation_period' => 15
                        ];

                        $consultationsWorkingDays['provider_id'] = $request->provider_id;

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

            DB::beginTransaction();

            try {
                $doctorInfo = [
                    "is_consult" => $request->is_consult,
                    "name_en" => $request->name_en,
                    "name_ar" => $request->name_ar,
                    "provider_id" => $requestData['doctor_type'] == 'clinic' ? $request->provider_id : null,
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
                    "application_percentage" => $request->application_percentage
                ];

                if ($requestData['is_consult'] == 1) {
                    if (!empty($request->password)) {
                        $doctorInfo['password'] = $request->password;
                    }
                    $doctorInfo['phone'] = $request->phone;
                    $doctorInfo['price_consulting'] = $request->price_consulting;
                }


                $doctor->update($doctorInfo);

                if ($requestData['doctor_type'] == 'clinic') {
                    // Insurance company IDs
                    if ($request->has('insurance_companies') && is_array($request->insurance_companies) && !empty($request->insurance_companies)) {
                        $doctor->insuranceCompanies()->sync($request->insurance_companies); // manay to many save only the new values and delete others from database
                    } else {
                        // $doctor -> insuranceCompanies() -> delete();
                        InsuranceCompanyDoctor::where('doctor_id', $doctor->id)->delete();
                    }
                }

                $doctor->times()->delete();
                $doctor->times()->insert($working_days_data);

                // consultations working
                if (($requestData['doctor_type'] == 'clinic' && $requestData['is_consult'] == 1) or  $requestData['doctor_type'] == 'consultative') {
                    // Optional consultations working days
                    if (isset($requestData['consultations_working_days']) && !is_null($requestData['consultations_working_days']) && count($consultations_working_days_data) > 0) {
                        $doctor->consultativeTimes()->delete();
                        $doctor->consultativeTimes()->insert($consultations_working_days_data);
                    }
                }

                DB::commit();
                return response()->json(['status' => true, 'msg' => __('main.doctor_updated_successfully')]);

            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
            }

        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $doctor = $this->getDoctorById($request->id);
            if ($doctor == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if (!$doctor->reservation) {
                $doctor->delete();
                return response()->json(['status' => true, 'msg' => __('main.doctor_deleted_successfully')]);
            } else {
                return response()->json(['success' => false, 'error' => __('main.doctor_with_reservations_cannot_be_deleted')], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }

    }

    public function changeStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required",
                "status" => "required",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $doctor = $this->getDoctorById($request->id);
            if ($doctor == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if ($request->status != 0 && $request->status != 1) {
                return response()->json(['status' => false, 'error' => __('main.enter_valid_activation_code')], 200);
            } else {
                $this->changerDoctorStatus($doctor, $request->status);
                return response()->json(['status' => true, 'msg' => __('main.doctor_status_changed_successfully')]);
            }

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function getDoctorDays(Request $request)
    {
        try {
//        $doctor_id = Session::has('doctor_id_for_Edit_reserv') ? Session::get('doctor_id_for_Edit_reserv') : 0;
            $doctor_days = DB::table('doctor_times')->where('doctor_id', $request->doctor_id)->pluck('day_name')->toArray();
            $week_days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $unavailble_days = array_values(array_diff($week_days, $doctor_days));
            $month_days = $this->get_dates(\request()->month, \request()->year);

            $unavailble_day_dates = [];

            if (!empty($unavailble_days) && count($unavailble_days) > 0) {
                $unavailble_day_dates = $this->unavailabledate($month_days, $unavailble_days);
            }
            return response()->json(['status' => true, 'data' => json_decode(json_encode($unavailble_day_dates))]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }

    }

    protected function get_dates($month, $year)
    {
        $start_date = "01-" . $month . "-" . $year;
        $start_time = strtotime($start_date);

        $end_time = strtotime("+1 month", $start_time);

        $index = 0;
        for ($i = $start_time; $i < $end_time; $i += 86400) {
            $name = date("l", $i);
            $list[$index]['day_name'] = strtolower($name);
            $list[$index]['date'] = date('Y-m-d', $i);
            $index++;
        }
        return $list;
    }

    public function unavailabledate($month_days, $unavailble_days)
    {
        $unavaibledates = [];
        $index = 0;
        foreach ($unavailble_days as $dayName) {
            foreach ($month_days as $index => $monthDay) {
                if ($monthDay['day_name'] == $dayName) {
                    $unavaibledates[$index]['day_name'] = $monthDay['day_name'];
                    $unavaibledates[$index]['date'] = $monthDay['date'];
                    $unavaibledates[$index]['classname'] = 'dangerc';
                }
                $index++;
            }
        }

        return array_values($unavaibledates);
    }

// api
    public function getDoctorAvailableTime(Request $request)
    {
        try {
//        $doctor_id = Session::has('doctor_id_for_Edit_reserv') ? Session::get('doctor_id_for_Edit_reserv') : 0;
            $base = url('/') . "/api/";
            $client = new \GuzzleHttp\Client(['base_uri' => $base]);
            $response = $client->request('POST', 'provider/doctor/available/times', [
                'form_params' => [
                    'api_password' => 'Ka@r%*MoAJ!rtPXz',
                    'api_email' => 'api.auth@hs.info',
                    'id' => $request->doctor_id,
                    'date' => $request->date
                ]
            ]);
            $res = json_decode($response->getBody());
            $times = $res->doctor->times;

            return response()->json(['status' => true, 'data' => $times]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }

    }


    /*public function AddShiftTime(Request $request)
    {
        $data['counter'] = $request->counter;
        $data['day_ar'] = $request->day_ar;
        $data['day_en'] = $request->day_en;
        $view = view('doctor.addShiftTimes', $data)->renderSections();
        return response()->json([
            'content' => $view['main'],
        ]);
    }*/

    public function removeShiftTimes(Request $request)
    {
        try {
            $time = DoctorTime::findorfail($request->id);
            $time->delete();
            return response()->json(['status' => true, 'msg' => __('main.doctor_time_deleted_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }

    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "phone" => 'required|exists:doctors,phone',
            "password" => 'required',
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $credentials = $request->only('phone', 'password');

        if ($token = $this->guard()->attempt(['phone' => $request->phone, 'password' => $request->password])) {
            $result['access_token'] = $token;
            $result['user'] = $this->guard()->user();
            // return $this->respondWithToken($token);
            return $this->returnData('data', $result);
        }
        return $this->returnError('E001', __('messages.invalid_username_or_password'));
    }

    public function logout(Request $request)
    {
        /*    try {
                $this->guard()->logout();

                return $this->returnSuccessMessage('E001', __('messages.invalid_username_or_password'));
            } catch (\Exception $ex) {
            }*/
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->returnSuccessMessage(__('main.successfully_logged_out'));
        } catch (JWTException $exception) {
            return $this->returnError('E001', __('main.error_logged_out'));
        }
    }

    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => true,
            'access_token' => $token,
            'user' => $this->guard()->user(),
        ]);
    }

    public function guard()
    {
        return Auth::guard('doctor-api');
    }

}
