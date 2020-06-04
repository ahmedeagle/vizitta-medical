<?php

namespace App\Http\Controllers;

use App\Mail\AcceptReservationMail;
use App\Models\GeneralNotification;
use App\Models\Manager;
use App\Models\Reservation;
use App\Models\ReservedTime;
use App\Traits\DoctorTrait;
use App\Traits\OdooTrait;
use http\Env\Response;
use Illuminate\Http\Request;
use App\Traits\GlobalTrait;
use App\Traits\ProviderTrait;
use App\Models\Provider;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use function MongoDB\BSON\toJSON;
use Validator;
use DB;
use Auth;

class ProviderBranchController extends Controller
{
    use ProviderTrait, GlobalTrait, DoctorTrait, OdooTrait;

    public function __construct(Request $request)
    {

    }

    public function index()
    {
        try {
            $provider = $this->auth('provider-api');
            $branches = $this->getBranches($provider);
            if (count($branches->toArray()) > 0) {
                $total_count = $branches->total();
                $branches->getCollection()->each(function ($branch) {
                    $branch->name = $branch->getTranslatedName();
                    $branch->makeVisible(['provider_id', 'name_en', 'name_ar']);
                    return $branch;
                });
                $branches = json_decode($branches->toJson());
                $branchesJson = new \stdClass();
                $branchesJson->current_page = $branches->current_page;
                $branchesJson->total_pages = $branches->last_page;
                $branchesJson->total_count = $total_count;
                $branchesJson->data = $branches->data;
                return $this->returnData('branches', $branchesJson);
            }
            return $this->returnError('E001', trans('messages.There are no branches found'));
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
            $branchProvider = $this->findProvider($request->id);
            if ($mainProvider->provider_id != null)
                return $this->returnError('D000', trans("messages.You don't have the permission"));

            if ($branchProvider == null)
                return $this->returnError('D000', trans('messages.There is no branch with this id'));

            if ($branchProvider->id == $mainProvider->id)
                return $this->returnError('D000', trans("messages.You can't delete your main account"));

            if ($branchProvider->provider_id != $mainProvider->id)
                return $this->returnError('D000', trans("messages.This branch isn't in your branches"));

            if (count($branchProvider->reservations) > 0)
                return $this->returnError('E001', trans("messages.This provider can't delete because there are reservations in it, you can hide instead"));

            $branchProvider->delete();
            return $this->returnSuccessMessage(trans('messages.Provider deleted successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
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
            if ($mainProvider->provider_id != null)
                return $this->returnError('D000', trans("messages.You don't have the permission"));

            $branchProvider = $this->getProviderByID($request->id);
            if ($branchProvider == null)
                return $this->returnError('D000', trans('messages.There is no branch with this id'));

            if ($branchProvider->id == $mainProvider->id)
                return $this->returnError('D000', trans("messages.You can't disappear your main account"));

            if ($branchProvider->provider_id != $mainProvider->id)
                return $this->returnError('D000', trans("messages.This branch isn't in your branches"));

            $branchProvider->makeVisible(['status']);
            if ($request->hide == '1' or $request->hide == 1) {
                $branchProvider->update([
                    'status' => 0
                ]);
                return $this->returnSuccessMessage(trans('messages.Provider disappeared successfully'));
            } else {
                $branchProvider->update([
                    'status' => 1
                ]);
                return $this->returnSuccessMessage(trans('messages.Provider showed successfully'));
            }

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                "name_en" => "max:255",
                "name_ar" => "max:255",
                "password" => "required|max:255",
                "mobile" => array(
                    "required",
                    "numeric",
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                ),
                "username" => "required|string|max:100|unique:providers,username",
                "email" => "email|max:255|unique:managers,email",
                "city_id" => "required|numeric",
                "district_id" => "required|numeric",
                "street" => "required"
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $exists = $this->checkIfMobileExistsForOtherBranches($request->mobile);
            if ($exists) {
                return $this->returnError('D000', trans("messages.phone number used before"));
            }
            $mainProvider = $this->auth('provider-api', ['city', 'district']);
            DB::beginTransaction();
            if ($mainProvider->provider_id != null)
                return $this->returnError('D000', trans("messages.You don't have the permission"));
            if ($request->filled('branch_no')) {
                if ($mainProvider->id) {
                    $branchs_no = Provider::where('provider_id', $mainProvider->id)->pluck('branch_no')->toArray();
                    if (!empty($branchs_no) && count($branchs_no) > 0) {
                        if (in_array($request->branch_no, $branchs_no)) {
                            return $this->returnError('D000', trans("messages.Branch no already exists in your branches"));
                        }
                    }
                }
            } else {
                return $this->returnError('D000', trans("messages.branch code is required"));
            }
            $validation = $this->validateFields(['mobile' => $request->mobile, 'email' => $request->email, 'city_id' => $request->city_id,
                'district_id' => $request->district_id]);
            /*if($validation->mobile_found !=  0)
                return $this->returnError('E001', trans('messages.This provider already exists'));*/

            if (isset($request->city_id) && $validation->city_found == 0)
                return $this->returnError('D000', trans('messages.Invalid city_id'));

            if (isset($request->district_id) && $validation->district_found == 0)
                return $this->returnError('D000', trans('messages.Invalid district_id'));

            $fileName = "";
            if (isset($request->logo) && !empty($request->logo)) {
                $fileName = $this->saveImage('providers', $request->logo);
            }


            $branch = Provider::create([
                'name_en' => !$request->name_en ? $mainProvider->name_en . ' - ' . $mainProvider->city->name_en . ' - ' . $mainProvider->district->name_en : $request->name_en,
                'name_ar' => !$request->name_ar ? $mainProvider->name_ar . ' - ' . $mainProvider->city->name_ar . ' - ' . $mainProvider->district->name_ar : $request->name_ar,
                'username' => $request->username,
                'password' => $request->password,
                'mobile' => $request->mobile,
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'logo' => $fileName,
                'status' => 1,
                'activation' => 1,
                'email' => $request->email,
                'address' => trim($request->address),
                'address_ar' => trim($request->address_ar),
                'address_en' => trim($request->address_en),
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'provider_id' => $mainProvider->id,
                'device_token' => '',
                'street' => trim($request->street),
                'branch_no' => $request->branch_no
            ]);

            if ($branch->id) {
                $logo = $branch->provider->logo;
                Provider::find($branch->id)->update(['logo' => $logo]);
            }

            // save user  to odoo erp system
            $name = $mainProvider->commercial_ar . ' - ' . $branch->name_ar;
            $odoo_provider_id = $this->saveProviderToOdoo($branch->mobile, $name);
            $branch->update(['odoo_provider_id' => $odoo_provider_id]);
            DB::commit();
            return $this->returnSuccessMessage(trans("messages.Branch added successfully"));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function update(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
                "branch_no" => "required",
                "mobile" => array(
                    "required",
                    "numeric",
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                ),
                "email" => "email|max:255|unique:managers,email",
                "username" => "required|string|max:100",
                "city_id" => "required|numeric",
                "district_id" => "required|numeric",
                "street" => "required",
                "old_password" => "required_with:password",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            DB::beginTransaction();
            $mainProvider = $this->auth('provider-api');

            if ($mainProvider->provider_id != null)
                return $this->returnError('D000', trans("messages.You don't have the permission"));

            $validation = $this->validateFields(['mobile' => $request->mobile, 'city_id' => $request->city_id,
                'district_id' => $request->district_id, 'branch' => ['main_provider_id' => $mainProvider->id, 'provider_id' => $request->id, 'branch_no' => $request->branch_no]]);

            //$provider = $this->getProviderByMobileInUpdate($request->id, $request->mobile);
            //  if($validation->branch_mobile_found != 0)
            //    return $this->returnError('E001', trans('messages.Mobile already used'));
            $exists = $this->checkIfMobileExistsForOtherBranches($request->mobile);
            if ($exists) {
                $proMobile = Provider::whereNotNull('provider_id')->where('mobile', $request->mobile)->first();
                if ($proMobile->id != $request->id)
                    return $this->returnError('D000', trans("messages.phone number used before"));
            }

            if (isset($request->city_id) && $validation->city_found == 0)
                return $this->returnError('D000', trans('messages.Invalid city_id'));

            if (isset($request->district_id) && $validation->district_found == 0)
                return $this->returnError('D000', trans('messages.Invalid district_id'));

            if ($validation->branch_found == 0)
                return $this->returnError('D000', trans("messages.This provider isn't your branch"));

            if ($validation->branch_no_found != 0)
                return $this->returnError('E001', trans('messages.Branch no already exists in your branches'));

            $branch = Provider::find($request->id);

            $exists = $this->checkIfUserNameExistsForOtherBranches($request->username);
            if ($exists) {
                $exists = Provider::whereNotNull('provider_id')->where('username', $request->username)->first();
                if ($exists->id != $request->id)
                    return $this->returnError('D000', trans("messages.user name used before"));
            }

            $data = [
                'branch_no' => $request->branch_no,
                'mobile' => $request->mobile,
                'username' => $request->username,
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'email' => $request->email,
                'address' => $request->address,
                'address_ar' => $request->address_ar,
                'address_en' => $request->address_en,
                'street' => trim($request->street)
            ];

            if (isset($request->password) && !empty($request->password)) {
                //check for old password
                if (Hash::check($request->old_password, $branch->password)) {
                    $data['password'] = $request->password;
                } else {
                    return $this->returnError('E002', trans('messages.invalid old password'));
                }
            }


            if (isset($request->name_ar) && !empty($request->name_ar))
                $data['name_ar'] = $request->name_ar;

            if (isset($request->name_en) && !empty($request->name_en))
                $data['name_en'] = $request->name_en;

            Provider::find($request->id)->update($data);
            DB::commit();
            return $this->returnSuccessMessage(trans("messages.Branch updated successfully"));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function branchDoctors(Request $request)
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
            if ($mainProvider->is_branch == 0) {
                $branch = $this->findProvider($request->id);
                if ($branch == null)
                    return $this->returnError('D000', trans("messages.No branch with this id"));

                if ($branch->provider_id != $mainProvider->id)
                    return $this->returnError('D000', trans("messages.This provider isn't your branch"));

                $doctors = $this->getDoctorsInBranch($branch->id);
            } else
                $doctors = $this->getDoctorsInBranch($mainProvider->id);

            if (count($doctors->toArray()) > 0) {
                $total_count = $doctors->total();
                $doctors = json_decode($doctors->toJson());
                try {

                } catch (\Exception $ex) {
                    return $ex;
                }
                $doctorsJson = new \stdClass();
                $doctorsJson->current_page = $doctors->current_page;
                $doctorsJson->total_pages = $doctors->last_page;
                $doctorsJson->total_count = $total_count;
                $doctorsJson->data = $doctors->data;
                return $this->returnData('doctors', $doctorsJson);
            }
            return $this->returnError('E001', trans('messages.There are no doctors in this branch'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function AllReservations(Request $request)
    {
        $provider = $this->auth('provider-api'); // login provider
        $branches = $provider->providers->pluck('id')->toArray();  // get branches if login is main provider
        if (isset($request->payment_method_id) && $request->payment_method_id != 0) {
            $paymentMethod = $this->getPaymentMethodByID($request->payment_method_id);
            if ($paymentMethod == null)
                return $this->returnError('D000', trans('messages.No payment method with this id'));
        }
        if (isset($request->branch_id) && $request->branch_id != 0) {
            $branch = $this->findProvider($request->branch_id);  // get branch data  if pass branch id
            if ($branch == null)
                return $this->returnError('D000', trans("messages.No branch with this id"));

            if ($branch->provider_id != $provider->id)
                return $this->returnError('D000', trans("messages.This branch isn't in your branches"));
        }

        if (isset($request->doctor_id) && $request->doctor_id != 0) {
            $doctor = $this->checkDoctor($request->doctor_id);  //get doctor by id
            if ($doctor == null)
                return $this->returnError('D000', trans("messages.No doctor with this id"));

            if (!in_array($doctor->provider_id, $branches) /*if login is main provider */ && ($doctor->provider_id != $provider->id) /*if login is branch */)
                return $this->returnError('D000', trans("messages.This doctor isn't in your branches"));
        }

        $status = 0;  //get all reservation filter
        if (isset($request->approved) && in_array($request->approved, [2, 3])) {   //2--->cancelled 3---> complete
            $status = $request->approved;
        }

        if ($provider->provider_id != null) {   // for branches login
            $reservationsResult = $this->getProviderReservations($provider->id, [], null, $request->from_date,
                $request->to_date, $request->payment_method_id, $request->doctor_id, null, $status);
        } else {   // for main provider login
            $branches = $provider->providers()->pluck('id')->toArray();  // all branches for login provider
            $reservationsResult = $this->getMainProviderReservations($provider->id, $branches, $request->branch_id, $request->from_date,
                $request->to_date, $request->payment_method_id, $request->doctor_id, null, $status);
        }

        $reservations = $reservationsResult['reservations'];
        //  $reservationsCount = $reservationsResult['count'];

        /*      $totalIcomePriceForApp = 0;
              $branchsids = $provider->provider_id != null ?  [$provider-> id] :  $provider->providers->pluck('id')->toArray();
              if ($status == 0 || $status == 3) {   // sum total balance only for complete reservations or (fillter with complete only)
                   $completeReservations = Reservation::whereIn('provider_id',$branchsids)->where('approved', 3)->get();
                 $totalReservationPrice =   $completeReservations -> sum('price');
                  if (isset($completeReservations) && $completeReservations->count() > 0) {
                      foreach ($completeReservations as $res) {
                          $totalIcomePriceForApp +=$res->admin_value_from_reservation_price_Tax;
                      }
                  }
              }*/
        $totalReservationPrice = 0;
        $totalReservationPrice = 0;
        $branchsids = $provider->provider_id != null ? [$provider->id] : $provider->providers->pluck('id')->toArray();

        $reservationsCount = Reservation::whereIn('provider_id', $branchsids)->whereIn('approved', [2, 3])->count();   // reservation count of complete and refuse reservationd

        if ($status == 2) {
            $reservationsCount = Reservation::whereIn('provider_id', $branchsids)->whereIn('approved', [2])->count(); // only for refused reservations
        }
        if ($status == 3) {    //  only for  complete  reservations
            $completeReservations = Reservation::whereIn('provider_id', $branchsids)->where('approved', 3)->get();
            $reservationsCount = Reservation::whereIn('provider_id', $branchsids)->whereIn('approved', [3])->count();
            if (isset($completeReservations) && $completeReservations->count() > 0) {   //some only complete price
                foreach ($completeReservations as $res) {
                    $totalReservationPrice += $res->reservation_total;
                }
            }
        }

        if ($status == 0) {   // all reservations
            $completeReservations = Reservation::whereIn('provider_id', $branchsids)->where('approved', 3)->get();
            $reservationsCount = Reservation::whereIn('provider_id', $branchsids)->whereIn('approved', [2, 3])->count();
            if (isset($completeReservations) && $completeReservations->count() > 0) {   //some only complete price
                foreach ($completeReservations as $res) {
                    $totalReservationPrice += $res->reservation_total;
                }
            }
        }

        if (!empty($reservations) && count($reservations->toArray()) > 0) {
            $total_count = $reservations->total();
            $reservations = json_decode($reservations->toJson());
            $reservationsJson = new \stdClass();
            $reservationsJson->reservations_count = $reservationsCount;
            $reservationsJson->prices = (string)($totalReservationPrice);
            $reservationsJson->current_page = $reservations->current_page;
            $reservationsJson->total_pages = $reservations->last_page;
            $reservationsJson->total_count = $total_count;
            $reservationsJson->data = $reservations->data;
            return $this->returnData('reservations', $reservationsJson);
        }
        return $this->returnError('E001', trans('messages.No Reservations founded'));

    }

    public
    function addReservation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "doctor_id" => "required|numeric",
                "day_date" => "required|date",
                "from_time" => "required",
                "to_time" => "required",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            DB::beginTransaction();
            $provider = $this->auth('provider-api'); //branch or provider
            /*    if ($provider->provider_id == null)
                    return $this->returnError('D000', trans("messages.Your account isn't branch to add reservations"));*/

            // $branch = Provider::where('provider_id',$provider->id)->find($request->branch_id);
            /// if($branch == null)
            //  return $this->returnError('D000', trans("messages.Your account isn't branch to add reservations"));

            $doctor = $this->checkDoctor($request->doctor_id);
            if ($doctor == null)
                return $this->returnError('D000', trans('messages.No doctor with this id'));

            if ($provider->provider_id == null) { //provider
                $branches = Provider::where('provider_id', $provider->id)->pluck('id')->toArray();
            } else {
                $branches = [$provider->id];
            }

            if (!in_array($doctor->provider_id, $branches))
                return $this->returnError('D000', trans("messages.This doctor isn't in your branch"));

            if (strtotime($request->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                ($request->day_date == Carbon::now()->format('Y-m-d') && strtotime($request->to_time) < strtotime(Carbon::now()->format('H:i:s'))))
                return $this->returnError('D000', trans("messages.You can't reserve to a time passed"));
            $hasReservation = $this->checkReservationInDate($request->doctor_id, $request->day_date, $request->from_time, $request->to_time);
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
                    if (count($times) == ($key + 1))
                        $last = true;
                    break;
                }
            }
            if (!$rightDay)
                return $this->returnError('E001', trans('messages.This day is not in doctor days'));

            Reservation::create([
                "reservation_no" => $this->getRandomString(8),
                "doctor_id" => $doctor->id,
                "day_date" => date('Y-m-d', strtotime($request->day_date)),
                "from_time" => date('H:i:s', strtotime($request->from_time)),
                "to_time" => date('H:i:s', strtotime($request->to_time)),
                "paid" => true,
                "provider_id" => $doctor->provider_id,  //reservation add by main provider or branch will store with doctor branch always
                'order' => $timeOrder,
                'price' => $doctor->price,
                'approved' => 4,       // reservation status 4 only to occupied this time and this reservation not appear
                'payment_method_id' => 1,
                'use_insurance' => 0,
                'rejection_reason' => ""
            ]);

            if ($last) {
                ReservedTime::create([
                    'doctor_id' => $doctor->id,
                    'day_date' => date('Y-m-d', strtotime($request->day_date))
                ]);
            }
            // Calculate the balance
            // $this->calculateBalance($provider, 1);
            //
            DB::commit();
            return $this->returnSuccessMessage(trans('messages.Reservation approved successfully'));
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

    public
    function UpdateReservationDateTime(Request $request)
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
            $provider = $this->auth('provider-api');
            $reservation = $this->getReservationByNo($request->reservation_no, $provider->id);
            if ($reservation == null)
                return $this->returnError('D000', __('messages.No reservation with this number'));

            if ($reservation->approved != 1)
                return $this->returnError('E001', __('messages.Only approved reservation can be updated'));

            if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                    strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))) {
                return $this->returnError('E001', trans("messages.You can't take action to a reservation passed"));
            }

            /* if ($provider->provider_id == null)
                 return $this->returnError('D000', trans("messages.Your account isn't branch to update reservations"));*/


            $doctor = $reservation->doctor;
            if ($doctor == null)
                return $this->returnError('D000', __('messages.No doctor with this id'));

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


                $notification = GeneralNotification::create([
                    'title_ar' => 'تعديل الحجز رقم  ' . ' ' . $reservation->reservation_no,
                    'title_en' => 'Update Reservation Date for reservation No:' . ' ' . $reservation->reservation_no,
                    'content_ar' => 'قام  مقدم الخدمه  ' . ' ' . $reservation->provider->name_ar . ' ' . 'بتحديث موعد الحجز رقم' . ' ' . $reservation->reservation_no . ' ' . ' الخاص بالمستخدم ' . ' ( ' . $reservation->user->name . ' )',
                    'content_en' => $reservation->provider->name_ar . ' ' . 'change the reservation date for reservation no: ' . ' ' . $reservation->reservation_no . ' ' . 'for user' . ' ( ' . $reservation->user->name . ' )',
                    'notificationable_type' => 'App\Models\Provider',
                    'notificationable_id' => $reservation->provider_id,
                    'data_id' => $reservation->id,
                    'type' => 4 //provider edit  reservation date
                ]);

                $provider = $reservation->provider; //branch
                $mainProvider = Provider::where('id', $provider->provider_id)->first();
                $notify = [
                    'provider_name' =>  $mainProvider->name_ar,
                    'reservation_no' =>  $reservation->reservation_no,
                    'reservation_id' => $reservation->id,
                    'content' => ' تعديل الحجز رقم ' . ' ' . $reservation->reservation_no,
                    'photo' => $mainProvider->logo,
                    'notification_id' => $notification->id
                ];
                //fire pusher  notification for admin  stop pusher for now
                try {
                    event(new \App\Events\ProviderEditReservationTime($notify));   // fire pusher new reservation  event notification*/
                } catch (\Exception $ex) {
                }

            } catch (\Exception $ex) {

            }
            return $this->returnSuccessMessage(trans('messages.Reservation updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function branchesFixedReservations(Request $request)
    {

        $provider = $this->auth('provider-api');
        if ($provider->provider_id == null)
            return $this->returnError('D000', trans("messages.Your account isn't branch"));

        $status = 0;  //get all reservation
        if (isset($request->approved) && in_array($request->approved, [2, 3])) {   //2--->cancelled 3---> complete
            $status = $request->approved;
        }

        $reservationsResult = $this->getFixedReservations($provider->id, $request->doctor_id, $request->from_date, $request->to_date, null, $status);
        $reservations = $reservationsResult['reservations'];
        // $reservationsCount = $reservationsResult['count'];
        //$prices = $reservationsResult['prices'];
        $branchsids = $provider->provider_id != null ? [$provider->id] : $provider->providers->pluck('id')->toArray();
        $completeReservations = Reservation::whereIn('provider_id', $branchsids)->where('approved', 3)->get();
        $totalReservationPrice = 0;
        $branchsids = $provider->provider_id != null ? [$provider->id] : $provider->providers->pluck('id')->toArray();
        $reservationsCount = Reservation::whereIn('provider_id', $branchsids)->whereIn('approved', [2, 3])->count();
        if ($status == 0 || $status == 3) {   // sum total balance only for complete reservations or (fillter with complete only)
            $completeReservations = Reservation::whereIn('provider_id', $branchsids)->where('approved', 3)->get();
            if (isset($completeReservations) && $completeReservations->count() > 0) {
                foreach ($completeReservations as $res) {
                    $totalReservationPrice += $res->reservation_total;
                }
            }
        }

        if (count($reservations->toArray()) > 0) {
            $total_count = $reservations->total();
            $reservations = json_decode($reservations->toJson());
            $reservationsJson = new \stdClass();
            $reservationsJson->reservations_count = $reservationsCount;
            $reservationsJson->prices = (string)($totalReservationPrice);
            $reservationsJson->current_page = $reservations->current_page;
            $reservationsJson->total_pages = $reservations->last_page;
            $reservationsJson->total_count = $total_count;
            $reservationsJson->data = $reservations->data;
            return $this->returnData('reservations', $reservationsJson);
        }

        return $this->returnError('E001', trans('messages.No Reservations founded'));

    }

    public
    function deleteReservation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            DB::beginTransaction();
            $provider = $this->auth('provider-api');
            if ($provider->provider_id == null)
                return $this->returnError('D000', trans("messages.Your account isn't branch"));

            $reservation = $this->getReservationByID($request->id);
            if ($reservation == null)
                return $this->returnError('D000', trans("messages.No reservation with this id"));

            if ($reservation->user_id != null)
                return $this->returnError('D000', trans("messages.You can't delete online reservation"));

            if ($reservation->provider_id != $provider->id)
                return $this->returnError('D000', trans("messages.This reservation isn't yours"));

            $reservedDay = $this->getReservedDay($reservation->doctor_id, $reservation->day_date);
            if ($reservedDay != null)
                $reservedDay->delete();

            $reservation->delete();
            DB::commit();
            return $this->returnSuccessMessage(trans('messages.Reservation deleted successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}
