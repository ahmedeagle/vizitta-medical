<?php

namespace App\Http\Controllers\Dashboard;

use App\Mail\AcceptReservationMail;
use App\Models\ReservedTime;
use App\Traits\Dashboard\DoctorTrait;
use App\Traits\Dashboard\PublicTrait;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\InsuranceCompanyDoctor;
use App\Http\Controllers\Controller;
use Flashy;
use Illuminate\Support\Facades\Input;
use Twilio\Rest\Api;
use Validator;
use App\Models\Provider;
use App\Models\DoctorTime;
use Carbon\Carbon;
use DB;
use Session;

class DoctorController extends Controller
{
    use DoctorTrait, PublicTrait;

    public function getDataTable()
    {
        try {
            $queryStr = request('queryStr');
            return $this->getAll($queryStr);
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index()
    {
        $queryStr = '';
        if (request('queryStr')) {
            $queryStr = request('queryStr');
        }
        return view('doctor.index')->with('queryStr', $queryStr);
    }


    public function create()
    {
        $branchId = Input::get('branch_id');

        if (isset($_COOKIE['working_hours'])) {
            setcookie('working_hours', '', -1);
        }

        $providers = $this->getAllActiveBranches();
        if (isset($providers) && $providers->count() > 0) {
            foreach ($providers as $index => $provider) {
                $main_Provider = $provider->provider->name_ar;
                $provider->provider_name = $main_Provider;
                $provider->name = $provider->provider_name . ' - ' . $provider->name_ar;   // merge provider name behind branch  name
            }
        }

        $providers = $providers->pluck('name', 'id');
        $specifications = $this->getAllSpecifications();
        $nicknames = $this->getAllNicknames();
        $nationalities = $this->getAllNationalities();
        $companies = $this->getAllInsuranceCompaniesWithSelected(null);
        $days = ['Saturday' => 'السبت', 'Sunday' => 'الأحد', 'Monday' => 'الإثنين ', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس ', 'Friday' => 'الجمعة '];
        return view('doctor.create', compact('providers', 'nicknames', 'specifications', 'nationalities', 'companies', 'days'))->with('branchId', $branchId);
    }


    public function store(Request $request)
    {

        $times = [];
        $times = $_COOKIE['working_hours'];
        if (isset($_COOKIE['working_hours'])) {
            $times = $_COOKIE['working_hours'];
            $request->request->add(['working_days' => json_decode($times, true)]);
        }

        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "information_ar" => "required|max:255",
                "information_en" => "required|max:255",
                "abbreviation_ar" => "required|max:255",
                "abbreviation_en" => "required|max:255",
                "gender" => "required|in:1,2",
                "provider_id" => "required|numeric|exists:providers,id",
                "nickname_id" => "required|numeric|exists:doctor_nicknames,id",
                "specification_id" => "required|numeric|exists:specifications,id",
                "nationality_id" => "required|numeric|exists:nationalities,id",
                "price" => "required|numeric",
                "status" => "required|in:0,1",
                //  "insurance_companies"   => "required|array|min:1",
                //"insurance_companies.*"   => "required",
                "reservation_period" => "required|numeric",
                "waiting_period" => "sometimes|nullable|numeric|min:0",
                "working_days" => "required|array|min:1",
            ]);


            if (isset($_COOKIE['working_hours'])) {
                unset($_COOKIE['working_hours']);
                setcookie('working_hours', null, -1, '/');
            }

            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

            $fileName = "";
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->uploadImage('doctors', $request->photo);
            }
            DB::beginTransaction();
            try {
                $doctor = Doctor::create([
                    "name_en" => $request->name_en,
                    "name_ar" => $request->name_ar,
                    "provider_id" => $request->provider_id,
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
                    "reservation_period" => $request->reservation_period,
                    "waiting_period" => $request->waiting_period,
                    "status" => true]);


                // Insurance company IDs
                if ($request->has('insurance_companies') && is_array($request->insurance_companies)) {
                    $insurance_companies_data = [];
                    foreach ($request->insurance_companies as $company) {
                        $insurance_companies_data[] = ['doctor_id' => $doctor->id, 'insurance_company_id' => $company];
                    }
                    $insurancs = InsuranceCompanyDoctor::insert($insurance_companies_data);

                }

                // working days
                $working_days_data = [];
                $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                foreach ($request->working_days as $working_day) {
                    if (empty($working_day['from']) or empty($working_day['to'])) {
                        Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                        return redirect()->back()->with('working_day', 'لابد من ادخال من والي ')->withInput($request->all());
                    }
                    $from = Carbon::parse($working_day['from']);
                    $to = Carbon::parse($working_day['to']);
                    if (!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period) {
                        Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                        return redirect()->back()->with('working_day', 'هذا اليوم غير صحيح او ربما يكون الفرق  بين من والي اقل من وقت الحجز المدخل اعلاه ')->withInput($request->all());
                    }

                    $working_days_data[] = [
                        'provider_id' => $request->provider_id,
                        'day_name' => strtolower($working_day['day']),
                        'day_code' => substr(strtolower($working_day['day']), 0, 3),
                        'from_time' => $from->format('H:i'),
                        'to_time' => $to->format('H:i'),
                        'order' => array_search(strtolower($working_day['day']), $days),
                        'reservation_period' => $request->reservation_period];
                }

                for ($i = 0; $i < count($working_days_data); $i++) {
                    $working_days_data[$i]['doctor_id'] = $doctor->id;
                }
                $times = DoctorTime::insert($working_days_data);
                DB::commit();
                Flashy::success('تمت اضافة الطبيب بنجاح ');
                return redirect()->route('admin.doctor');

            } catch (\Exception $e) {
                DB::rollback();
            }
        } catch (Exception $e) {
            return abort('404');
        }
    }


    public function view($id)
    {
        try {
            $doctor = $this->getDoctorById($id);
            if ($doctor == null)
                return view('errors.404');

            return view('doctor.view', compact('doctor'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function edit($id)
    {

        if (isset($_COOKIE['working_hoursedit'])) {
            setcookie('working_hoursedit', '', -1);
        }

        $doctor = $this->getDoctorById($id);
        if ($doctor == null)
            return view('errors.404');
        $providers = $this->getAllActiveBranches();
        if (isset($providers) && $providers->count() > 0) {
            foreach ($providers as $index => $provider) {
                $main_Provider = $provider->provider->name_ar;
                $provider->provider_name = $main_Provider;
                $provider->name = $provider->provider_name . ' - ' . $provider->name_ar;   // merge provider name behind branch  name
            }
        }

        $providers = $providers->pluck('name', 'id');
        $specifications = $this->getAllSpecifications();
        $nicknames = $this->getAllNicknames();
        $nationalities = $this->getAllNationalities();
        $companies = $this->getAllInsuranceCompaniesWithSelected($doctor);
        $times = $doctor->times()->get();

        //trans namd of all days
        $days = ['Saturday' => 'السبت', 'Sunday' => 'الأحد', 'Monday' => 'الإثنين ', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس ', 'Friday' => 'الجمعة '];

        $days_code = ['sat' => 'Saturday', 'sun' => 'Sunday', 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday'];

        $days_ar = ['السبت' => 'Saturday', 'الأحد' => 'Sunday', 'الإثنين ' => 'Monday', 'الثلاثاء' => 'Tuesday', 'الأربعاء' => 'Wednesday', 'الخميس ' => 'Thursday', 'الجمعة ' => 'Friday
'];
        if (!empty($times) && count($times) > 0) {
            foreach ($times as $time) {
                $time['day_code'] = $days_code[$time['day_code']];
            }
        }
        return view('doctor.edit', compact('doctor', 'providers', 'nicknames', 'specifications', 'nationalities', 'companies', 'days', 'times'));
    }


    public function update($id, Request $request)
    {
        $times = [];
        if (isset($_COOKIE['working_hoursedit'])) {
            $times = $_COOKIE['working_hoursedit'];
            $request->request->add(['working_days' => json_decode($times, true)]);
        }

        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "information_ar" => "required|max:255",
                "information_en" => "required|max:255",
                "abbreviation_ar" => "required|max:255",
                "abbreviation_en" => "required|max:255",
                "gender" => "required|in:1,2",
                "provider_id" => "required|numeric|exists:providers,id",
                "nickname_id" => "required|numeric|exists:doctor_nicknames,id",
                "specification_id" => "required|numeric|exists:specifications,id",
                "nationality_id" => "required|numeric|exists:nationalities,id",
                "price" => "required|numeric",
                "status" => "required|in:0,1",
                //   "insurance_companies"   => "required|array|min:1",
                //   "insurance_companies.*"   => "required",
                "reservation_period" => "required|numeric",
                "waiting_period" => "sometimes|nullable|numeric|min:0",
                "working_days" => "required|array|min:1",
            ]);

            if (isset($_COOKIE['working_hoursedit'])) {
                unset($_COOKIE['working_hoursedit']);
                setcookie('working_hoursedit', null, -1, '/');
            }

            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $doctor = $this->getDoctorById($id);
            if ($doctor == null)
                return view('errors.404');

            // working days
            $working_days_data = [];
            $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            foreach ($request->working_days as $working_day) {
                if (!array_key_exists('from', $working_day) or !array_key_exists('to', $working_day)) {
                    Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                    return redirect()->back()->with('working_day', 'لابد من ادخال من والي ')->withInput($request->all());
                }
                $from = Carbon::parse($working_day['from']);
                $to = Carbon::parse($working_day['to']);
                if ((!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period)) {

                    Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                    return redirect()->back()->with('working_day', 'هذا اليوم غير صحيح او ربما يكون الفرق  بين من والي اقل من وقت الحجز المدخل اعلاه ')->withInput($request->all());
                }

                $working_days_data[] = [
                    'provider_id' => $request->provider_id,
                    'day_name' => strtolower($working_day['day']),
                    'day_code' => substr(strtolower($working_day['day']), 0, 3),
                    'from_time' => $from->format('H:i'),
                    'to_time' => $to->format('H:i'),
                    'order' => array_search(strtolower($working_day['day']), $days),
                    'reservation_period' => $request->reservation_period];
            }

            for ($i = 0; $i < count($working_days_data); $i++) {
                $working_days_data[$i]['doctor_id'] = $doctor->id;
            }

            $path = $doctor->photo;
            if (isset($request->photo)) {
                $path = $this->uploadImage('doctors', $request->photo);
            }

            DB::beginTransaction();

            try {
                $this->updateDoctor($doctor, $request);
                $doctor->update([
                    'photo' => $path
                ]);


                // Insurance company IDs
                if ($request->has('insurance_companies') && is_array($request->insurance_companies) && !empty($request->insurance_companies)) {
                    $doctor->insuranceCompanies()->sync($request->insurance_companies); // manay to many save only the new values and delete others from database

                 } else {
                    // $doctor -> insuranceCompanies() -> delete();
                    InsuranceCompanyDoctor::where('doctor_id', $doctor->id)->delete();
                 }

                $doctor->times()->delete();
                $doctor->times()->insert($working_days_data);
                DB::commit();
                Flashy::success('تم تعديل الدكتور بنجاح');
                return redirect()->route('admin.doctor');

            } catch (\Exception $e) {
                DB::rollback();
            }

        } catch (\Exception $e) {
            return abort('404');
        }
    }

    public function destroy($id)
    {
        try {
            $doctor = $this->getDoctorById($id);
            if ($doctor == null)
                return view('errors.404');

            if (!$doctor->reservation) {
                $doctor->delete();
                Flashy::success('تم مسح الدكتور بنجاح');
                return redirect()->route('admin.doctor');

            } else {
                Flashy::error('لا يمكن مسح دكتور مرتبط بحجوزات');
                return redirect()->route('admin.doctor');

            }
        } catch (\Exception $ex) {
            return view('errors.404');
        }

    }

    public function changeStatus($id, $status)
    {
        try {
            $doctor = $this->getDoctorById($id);
            if ($doctor == null)
                return view('errors.404');

            if ($status != 0 && $status != 1) {
                Flashy::error('إدخل كود التفعيل صحيح');
            } else {
                $this->changerDoctorStatus($doctor, $status);
                Flashy::success('تم تغيير حالة الدكتور بنجاح');
            }
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }


    public function getDoctorDays()
    {
//           هنا  انا بجيب ايام عمل الطبيب في الاسبوع وبعدين من الكالندر في الادمن هو بيبعتلي الشهر والسنه فبجيب كل الايام الي هو مش شغال فيها ع شبيل المثال الاحد والاتنثين و بنادي داله بتجبلي كل الاحد والاتنين الي ف الشهر ده بتواريخهم عشان اقدر اعرض هذه الايام طول الشهر انها غير متاحه علي الكاليندر في الادمن في صفحه تعديل موعد حجز

        $doctor_id = Session::has('doctor_id_for_Edit_reserv') ? Session::get('doctor_id_for_Edit_reserv') : 0;
        $doctor_days = DB::table('doctor_times')->where('doctor_id', $doctor_id)->pluck('day_name')->toArray();
        $week_days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $unavailble_days = array_values(array_diff($week_days, $doctor_days));
        $month_days = $this->get_dates(\request()->month, \request()->year);

        $unavailble_day_dates = [];

        if (!empty($unavailble_days) && count($unavailble_days) > 0) {

            $unavailble_day_dates = $this->unavailabledate($month_days, $unavailble_days);
        }
        return response()->json(json_decode(json_encode($unavailble_day_dates)));

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
    public function getDoctorAvailableTime($date)
    {
        $doctor_id = Session::has('doctor_id_for_Edit_reserv') ? Session::get('doctor_id_for_Edit_reserv') : 0;
        $base = url('/') . "/api/";
        $client = new \GuzzleHttp\Client(['base_uri' => $base]);
        $response = $client->request('POST', 'provider/doctor/available/times', [
            'form_params' => [
                'api_password' => 'Ka@r%*MoAJ!rtPXz',
                'api_email' => 'api.auth@hs.info',
                'id' => $doctor_id,
                'date' => $date
            ]
        ]);
        $res = json_decode($response->getBody());
        $times = $res->doctor->times;

        $view = view('reservation.avbTimes', compact('times'))->renderSections();
        return response()->json([
            'content' => $view['main'],
        ]);

    }


    public function AddShiftTime(Request $request)
    {
        $data['counter'] = $request->counter;
        $data['day_ar'] = $request->day_ar;
        $data['day_en'] = $request->day_en;
        $view = view('doctor.addShiftTimes', $data)->renderSections();
        return response()->json([
            'content' => $view['main'],
        ]);
    }


    public function removeShiftTimes(Request $request)
    {
        $id = $request->id;
        $time = DoctorTime::findorfail($id);
        $time->delete();
        return response()->json([]);
    }

    /* public function UpdateReservationDateTime(Request $request)
     {
         try {
             $validator = Validator::make($request->all(), [
                 "reservation_no" => "required|max:255",
                 "day_date" => "required|date",
                 "from_time" => "required",
                 "to_time" => "required",
             ]);

             if ($validator->fails()) {
                 $code = $this->returnCodeAccordingToInput($validator);
                 return $this->returnValidationError($code, $validator);
             }

             DB::beginTransaction();
             $reservation = $this->getReservationByNo($request->reservation_no);
             if ($reservation == null)
                 return $this->returnError('D000', trans('messages.No reservation with this number'));

             if ($reservation->approved != 1)
                 return $this->returnError('E001', trans('messages.Only approved reservation can be  updated '));

             if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                 (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                     strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))) {
                 return $this->returnError('E001', trans("messages.You can't take action to a reservation passed"));
             }

             if ($provider->provider_id == null)
                 return $this->returnError('D000', trans("messages.Your account isn't branch to update reservations"));


             $doctor = $reservation->doctor;
             if ($doctor == null)
                 return $this->returnError('D000', trans('messages.No doctor with this id'));

             if (strtotime($request->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                 ($request->day_date == Carbon::now()->format('Y-m-d') && strtotime($request->to_time) < strtotime(Carbon::now()->format('H:i:s'))))
                 return $this->returnError('D000', trans("messages.You can't reserve to a time passed"));
             $hasReservation = $this->checkReservationInDate($doctor->id, $request->day_date, $request->from_time, $request->to_time);
             if ($hasReservation)
                 return $this->returnError('E001', trans('messages.This time is not available'));

             $reservationDayName = date('l', strtotime($request->day_date));
             $rightDay = false;
             $timeOrder = 1;
             $last = false;
             $times = $this->getDoctorTimesInDay($doctor->id, $reservationDayName);
             foreach ($times as $key => $time) {
                 if ($time['from_time'] == Carbon::parse($request->from_time)->format('H:i')
                     && $time['to_time'] == Carbon::parse($request->to_time)->format('H:i')) {
                     $rightDay = true;
                     $timeOrder = $key + 1;
                     //if(count($times) == ($key+1))
                     //  $last = true;
                     break;
                 }
             }
             if (!$rightDay)
                 return $this->returnError('E001', trans('messages.This day is not in doctor days'));

             $reservation->update([
                 "day_date" => date('Y-m-d', strtotime($request->day_date)),
                 "from_time" => date('H:i:s', strtotime($request->from_time)),
                 "to_time" => date('H:i:s', strtotime($request->to_time)),
                 'order' => $timeOrder,
                 "approved" => 1,
                 "approved" => 1,
             ]);

             if ($last) {
                 ReservedTime::create([
                     'doctor_id' => $doctor->id,
                     'day_date' => date('Y-m-d', strtotime($request->day_date))
                 ]);
             }

             if ($reservation->user->email != null)
                 Mail::to($reservation->user->email)->send(new AcceptReservationMail($reservation->reservation_no));

             DB::commit();
             try {
                 (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated user reservation')]))->sendProvider($reservation->provider);
                 (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated your reservation')]))->sendUser($reservation->user);
             } catch (\Exception $ex) {

             }
             return $this->returnSuccessMessage(trans('messages.Reservation updated successfully'));
         } catch (\Exception $ex) {
             return $this->returnError($ex->getCode(), $ex->getMessage());
         }
     }*/


}
