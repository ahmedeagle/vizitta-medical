<?php

namespace App\Traits;

use App\Models\Doctor;
use App\Models\Message;
use App\Models\ServiceReservation;
use App\Models\Ticket;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Reservation;
use Carbon\Carbon;
use DateTime;
use DB;
use Illuminate\Support\Facades\Auth;

trait ProviderTrait
{
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
//                "working_days" => "required|array|min:1",
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
                $rules["phone"] = "required|max:100|unique:doctors,phone";
                $rules["price_consulting"] = "required|numeric";
            }


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
}
