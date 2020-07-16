<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationsResource;
use App\Mail\AcceptReservationMail;
use App\Mail\NewReplyMessageMail;
use App\Mail\NewUserMessageMail;
use App\Mail\RejectReservationMail;
use App\Models\Bill;
use App\Models\CommentReport;
use App\Models\Doctor;
use App\Models\Favourite;
use App\Models\GeneralNotification;
use App\Models\Message;
use App\Models\Mix;
use App\Models\Point;
use App\Models\Provider;
use App\Models\Reciever;
use App\Models\ReportingType;
use App\Models\ReservedTime;
use App\Models\Service;
use App\Models\User;
use App\Models\Reservation;
use App\Models\UserToken;
use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use App\Traits\OdooTrait;
use App\Traits\ReservationTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use App\Traits\UserTrait;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Validator;
use DB;
use Auth;
use Mail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use UserTrait, GlobalTrait, DoctorTrait, ReservationTrait, SMSTrait, OdooTrait;

    public function __construct()
    {
    }

    public function index()
    {
        return $permisions = \App\Models\Manager::with('permissions')->find(1);
        //  \App\Models\Manager::first() ->givePermissionTo($permisions);
    }

    public function store(Request $request)
    {
                   try {

            $validator = Validator::make($request->all(), [
                "name" => "required|max:255",
                "device_token" => "required|min:63|max:255",
                "mobile" => array(
                    "required",
                    "unique:users,mobile",
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                ),
                //"id_number" => "sometimes|numeric|unique:users,id_number|digits_between:8,20",
                // "birth_date" => "sometimes|date",
                "agreement" => "required|boolean",
                "email" => "email|max:255|unique:users,email|unique:managers,email",
                "address" => "max:255",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = $this->getUserByMobileOrEmailOrID($request->mobile, $request->email);
            if ($user != null)
                return $this->returnError('E001', trans('messages.This user already exists'));

            if (!$request->agreement)
                return $this->returnError('E006', trans('messages.Agreement is required'));

            if (isset($request->city_id) && !$this->checkCityByID($request->city_id))
                return $this->returnError('D000', trans('messages.Invalid city_id'));

            if (isset($request->insurance_company_id) && !$this->checkInsuranceCompanyByID($request->insurance_company_id))
                return $this->returnError('D000', trans('messages.Your Activation Code'));

            $activationCode = (string)rand(1000, 9999);

            $fileName = "";
            if (isset($request->insurance_image)) {
                $fileName = $this->saveImage('users', $request->insurance_image);
            }


            $android_device_hasCode = '';
            if ($request->has('android_device_hasCode') && $request->android_device_hasCode != null && $request->android_device_hasCode != '') {
                $android_device_hasCode = $request->android_device_hasCode;
            }



            if (!preg_match("~^0\d+$~", $request->mobile)) {
                  $phone = '0' . $request->mobile;
            }else{
                  $phone = $request->mobile;
            }


            $user = User::create([
                'name' => trim($request->name),
                'mobile' => $phone,
                'id_number' => $request->id_number,
                'email' => $request->email,
                'address' => trim($request->address),
                'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                'status' => 0,
                'city_id' => $request->city_id,
                'insurance_company_id' => $request->insurance_company_id,
                'no_of_sms' => 1,
                'activation_code' => $activationCode,
                'device_token' => $request->device_token,
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'insurance_image' => $fileName,
                'api_token' => '',
                'password' => 'none',
                'token_created_at' => Carbon::now(),
                'android_device_hasCode' => $android_device_hasCode,
            ]);

            // save user  to odoo erp system
            $odoo_user_id = $this->saveUserToOdoo($user->mobile, $user->name);
            $user->update(['odoo_user_id' => $odoo_user_id]);

            $deviceHash = $user->android_device_hasCode === null ? '' : $user->android_device_hasCode;
            $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
            $this->sendSMS($phone, $message);

            return $this->returnData('user', json_decode(json_encode($this->authUserByMobile($phone), JSON_FORCE_OBJECT)));
        } catch (\Exception $ex) {
                       return $ex;
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function storeV2(Request $request)
    {
        try {


            $validator = Validator::make($request->all(), [
                "name" => "required|max:255",
                "device_token" => "required|min:63|max:255",
                "mobile" => array(
                    "required",
                    "unique:users,mobile",
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                ),
                //"id_number" => "sometimes|numeric|unique:users,id_number|digits_between:8,20",
                // "birth_date" => "sometimes|date",
                "agreement" => "required|boolean",
                "email" => "email|max:255|unique:users,email|unique:managers,email",
                "address" => "max:255",
                "device" => "required|in:android,ios",
                // "photo" => "required",
            ]);


            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = $this->getUserByMobileOrEmailOrID($request->mobile, $request->email);
            if ($user != null)
                return $this->returnError('E001', trans('messages.This user already exists'));


            $invited_by_code = "";
            $settings = Mix::select('owner_points', 'invited_points')->first();
            $owner_points = 0;
            $invited_points = 0;
            if ($settings) {
                $owner_points = $settings->owner_points;
                $invited_points = $settings->invited_points;
            }

            if ($request->has('invitation_by_code') && !empty($request->invitation_by_code)) {
                $invited_by_code = strtolower($request->invitation_by_code);
                $codeOwner = User::where('invitation_code', $invited_by_code)->first();
                if (!$codeOwner) {
                    return $this->returnError('E001', trans('messages.invitation code not exist'));
                }
                //the owner Of code
                $Points = $codeOwner->invitation_points + $owner_points;
                $codeOwner->update(['invitation_points' => $Points]);
            }

            if (!$request->agreement)
                return $this->returnError('E006', trans('messages.Agreement is required'));

            if (isset($request->city_id) && !$this->checkCityByID($request->city_id))
                return $this->returnError('D000', trans('messages.Invalid city_id'));

            if (isset($request->insurance_company_id) && !$this->checkInsuranceCompanyByID($request->insurance_company_id))
                return $this->returnError('D000', trans('messages.Your Activation Code'));

            $activationCode = (string)rand(1000, 9999);

            $fileName = "";
            if (isset($request->insurance_image)) {
                $fileName = $this->saveImage('users', $request->insurance_image);
            }


            $android_device_hasCode = '';
            if ($request->has('android_device_hasCode') && $request->android_device_hasCode != null && $request->android_device_hasCode != '') {
                $android_device_hasCode = $request->android_device_hasCode;
            }

//            $userPhoto = "";
//            if (isset($request->photo) && !empty($request->photo)) {
//                $userPhoto = $this->saveImage('users', $request->photo);
//            }



            if (!preg_match("~^0\d+$~", $request->mobile)) {
                $phone = '0' . $request->mobile;
            }else{
                $phone = $request->mobile;
            }


            $user = User::create([
                'name' => trim($request->name),
                'mobile' => $phone,
                'id_number' => $request->id_number,
                'email' => $request->email,
                'address' => trim($request->address),
                'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                'status' => 0,
                'city_id' => $request->city_id,
                'insurance_company_id' => $request->insurance_company_id,
                'no_of_sms' => 1,
                'activation_code' => $activationCode,
                'invited_by_code' => $invited_by_code ? $invited_by_code : "",
                'invitation_points' => $invited_points,
                'device_token' => $request->device_token,
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'insurance_image' => $fileName,
                'api_token' => '',
                'password' => 'none',
                'token_created_at' => Carbon::now(),
                'android_device_hasCode' => $android_device_hasCode,
                'operating_system' => $request->device,
                // 'photo' => $userPhoto,
            ]);

            // save user  to odoo erp system
            $odoo_user_id = $this->saveUserToOdoo($user->mobile, $user->name);
            $user->update(['odoo_user_id' => $odoo_user_id]);

            $deviceHash = $user->android_device_hasCode === null ? '' : $user->android_device_hasCode;
            $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
            $this->sendSMS($user->mobile, $message);

            return $this->returnData('user', json_decode(json_encode($this->authUserByMobile($phone), JSON_FORCE_OBJECT)));
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    public function update(Request $request)
    {
        try {
            $user = $this->auth('user-api');

            if (!$user) {
                return $this->returnError('D000', trans('messages.User not found'));
            }

            $validator = Validator::make($request->all(), [
                "name" => 'required|max:255',
                /* "id_number" => [
                     "required",
                     "digits_between:8,20",
                     Rule::unique('users', 'id_number')->ignore($user->id),
                 ],*/
                "mobile" => array(
                    "numeric",
                    "digits_between:8,10",
                    Rule::unique('users', 'mobile')->ignore($user->id),
                    //   "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                ),
//                "birth_date" => "required|date",
                "insurance_expire_date" => "sometimes|nullable|date",
                "city_id" => "numeric",
            ]);


            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }


            $fileName = "";
            if (isset($request->insurance_company_id) && $request->insurance_company_id != 0) {
                $insuranceCompany = $this->getInsuranceCompanyByID($request->insurance_company_id);
                if ($insuranceCompany == null)
                    return $this->returnError('D000', trans('messages.There is no insurance company with this id'));
            }
            if (isset($request->city_id) && $request->city_id != 0) {
                $city = $this->getCityByID($request->city_id);
                if ($city == null)
                    return $this->returnError('D000', trans('messages.There is no city with this id'));
            }
            if (isset($request->insurance_image) && !empty($request->insurance_image)) {
                $fileName = $this->saveImage('users', $request->insurance_image);
            }


            $activation = 0;
            if (isset($request->mobile)) {
                if ($user->mobile == '0123456789') {  //apple account test

                    $user->update([
                        'mobile' => $request->mobile ? $request->mobile : $user->mobile,
                        'status' => 1,
                        //'activation_code' => $activationCode,
                        'name' => trim($request->name),
                        'city_id' => $request->city_id ? $request->city_id : $user->city_id,
                        'id_number' => $request->id_number,
                        'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                        'insurance_expire_date' => $request->has('insurance_expire_date') ? date('Y-m-d', strtotime($request->insurance_expire_date)) : "",
                        'insurance_company_id' => $request->insurance_company_id ? $request->insurance_company_id : $user->insurance_company_id,
                        'insurance_image' => $fileName != null ? $fileName : $user->insurance_image,
                    ]);

                } else {
                    if ($request->mobile != $user->mobile) {
                        $activation = 1;
                        $activationCode = (string)rand(1000, 9999);
                        $deviceHash = $user->android_device_hasCode === null ? '' : $user->android_device_hasCode;
                        $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
                        $this->sendSMS($user->mobile, $message);

                        $user->update([
                            'mobile' => $request->mobile ? $request->mobile : $user->mobile,
                            'status' => 0,
                            'activation_code' => $activationCode,
                            'name' => trim($request->name),
                            'city_id' => $request->city_id ? $request->city_id : $user->city_id,
                            'id_number' => $request->id_number,
                            'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                            'insurance_expire_date' => $request->has('insurance_expire_date') ? date('Y-m-d', strtotime($request->insurance_expire_date)) : "",
                            'insurance_company_id' => $request->insurance_company_id ? $request->insurance_company_id : $user->insurance_company_id,
                            'insurance_image' => $fileName != null ? $fileName : $user->insurance_image,
                        ]);
                    } else {
                        $user->update([
                            'name' => trim($request->name),
                            'city_id' => $request->city_id ? $request->city_id : $user->city_id,
                            'id_number' => $request->id_number,
                            'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                            'insurance_expire_date' => $request->has('insurance_expire_date') ? date('Y-m-d', strtotime($request->insurance_expire_date)) : "",
                            'insurance_company_id' => $request->insurance_company_id ? $request->insurance_company_id : $user->insurance_company_id,
                            'insurance_image' => $fileName != null ? $fileName : $user->insurance_image,
                        ]);
                    }

                }
            } else {   // not change phone
                $user->update([
                    'name' => trim($request->name),
                    'city_id' => $request->city_id ? $request->city_id : $user->city_id,
                    'id_number' => $request->id_number,
                    'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                    'insurance_expire_date' => $request->has('insurance_expire_date') ? date('Y-m-d', strtotime($request->insurance_expire_date)) : "",
                    'insurance_company_id' => $request->insurance_company_id ? $request->insurance_company_id : $user->insurance_company_id,
                    'insurance_image' => $fileName != null ? $fileName : $user->insurance_image,
                ]);
            }

            $user = $this->getAllData($user->id, $activation);
            return $this->returnData('user', json_decode(json_encode($user, JSON_FORCE_OBJECT)),
                trans('messages.User data updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function updateV2(Request $request)
    {
        try {
            $user = $this->auth('user-api');

            if (!$user) {
                return $this->returnError('D000', trans('messages.User not found'));
            }

            $validator = Validator::make($request->all(), [
                "name" => 'required|max:255',
                /* "id_number" => [
                     "required",
                     "digits_between:8,20",
                     Rule::unique('users', 'id_number')->ignore($user->id),
                 ],*/
                "mobile" => array(
                    "numeric",
                    "digits_between:8,10",
                    Rule::unique('users', 'mobile')->ignore($user->id),
                    //   "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                ),
//                "birth_date" => "required|date",
                "insurance_expire_date" => "sometimes|nullable|date",
                "city_id" => "sometimes|nullable|numeric",
                "gender" => "required|in:1,2,3" // 1->male 2->female 3->none
            ]);


            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }


            $fileName = "";
            if (isset($request->insurance_company_id) && $request->insurance_company_id != 0) {
                $insuranceCompany = $this->getInsuranceCompanyByID($request->insurance_company_id);
                if ($insuranceCompany == null)
                    return $this->returnError('D000', trans('messages.There is no insurance company with this id'));
            }
            if (isset($request->city_id) && $request->city_id != 0) {
                $city = $this->getCityByID($request->city_id);
                if ($city == null)
                    return $this->returnError('D000', trans('messages.There is no city with this id'));
            }
            if (isset($request->insurance_image) && !empty($request->insurance_image)) {
                $fileName = $this->saveImage('users', $request->insurance_image);
            }

            $userPhoto = $user->photo;
            if (isset($request->photo) && !empty($request->photo)) {
                $userPhoto = $this->saveImage('users', $request->photo);
            }

            $activation = 0;
            if (isset($request->mobile)) {
                if ($user->mobile == '0123456789') {  //apple account test

                    $user->update([
                        'mobile' => $request->mobile ? $request->mobile : $user->mobile,
                        'status' => 1,
                        'gender' => $request->gender,
                        //'activation_code' => $activationCode,
                        'name' => trim($request->name),
                        'city_id' => $request->city_id ? $request->city_id : $user->city_id,
                        'id_number' => $request->id_number,
                        'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                        'insurance_expire_date' => $request->has('insurance_expire_date') ? date('Y-m-d', strtotime($request->insurance_expire_date)) : "",
                        'insurance_company_id' => $request->insurance_company_id ? $request->insurance_company_id : $user->insurance_company_id,
                        'insurance_image' => $fileName != null ? $fileName : $user->insurance_image,
                        'photo' => $userPhoto
                    ]);

                } else {
                    if ($request->mobile != $user->mobile) {
                        $activation = 1;
                        $activationCode = (string)rand(1000, 9999);
                        $deviceHash = $user->android_device_hasCode === null ? '' : $user->android_device_hasCode;
                        $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
                        $this->sendSMS($user->mobile, $message);

                        $user->update([
                            'mobile' => $request->mobile ? $request->mobile : $user->mobile,
                            'status' => 0,
                            'genderg' => $request->gender,
                            'activation_code' => $activationCode,
                            'name' => trim($request->name),
                            'city_id' => $request->city_id ? $request->city_id : $user->city_id,
                            'id_number' => $request->id_number,
                            'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                            'insurance_expire_date' => $request->has('insurance_expire_date') ? date('Y-m-d', strtotime($request->insurance_expire_date)) : "",
                            'insurance_company_id' => $request->insurance_company_id ? $request->insurance_company_id : $user->insurance_company_id,
                            'insurance_image' => $fileName != null ? $fileName : $user->insurance_image,
                            'photo' => $userPhoto
                        ]);
                    } else {
                        $user->update([
                            'name' => trim($request->name),
                            'city_id' => $request->city_id ? $request->city_id : $user->city_id,
                            'id_number' => $request->id_number,
                            'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                            'insurance_expire_date' => $request->has('insurance_expire_date') ? date('Y-m-d', strtotime($request->insurance_expire_date)) : "",
                            'insurance_company_id' => $request->insurance_company_id ? $request->insurance_company_id : $user->insurance_company_id,
                            'insurance_image' => $fileName != null ? $fileName : $user->insurance_image,
                            'photo' => $userPhoto,
                            'gender' => $request->gender,
                        ]);
                    }

                }
            } else {   // not change phone
                $user->update([
                    'name' => trim($request->name),
                    'city_id' => $request->city_id ? $request->city_id : $user->city_id,
                    'id_number' => $request->id_number,
                    'birth_date' => $request->has('birth_date') ? date('Y-m-d', strtotime($request->birth_date)) : null,
                    'insurance_expire_date' => $request->has('insurance_expire_date') ? date('Y-m-d', strtotime($request->insurance_expire_date)) : "",
                    'insurance_company_id' => $request->insurance_company_id ? $request->insurance_company_id : $user->insurance_company_id,
                    'insurance_image' => $fileName != null ? $fileName : $user->insurance_image,
                    'photo' => $userPhoto,
                    'gender' => $request->gender,
                ]);
            }
            $user = $this->getAllData($user->id, $activation);
            return $this->returnData('user', json_decode(json_encode($user, JSON_FORCE_OBJECT)),
                trans('messages.User data updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function activateAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "activation_code" => "required|max:255",
                "api_token" => "required"
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = User::where('api_token', $request->api_token)->first();

            if ($user == null)
                return $this->returnError('E001', trans('messages.User not found'));

            //if ($user->status)
            //  return $this->returnError(' ', trans('messages.This user already activated'));

            if ($user->activation_code == null)
                return $this->returnError('E0100', trans('messages.There is no activation code entered before'));

            if ($request->activation_code == $user->activation_code) {
                $user->status = 1;
                $user->update();
                return $this->returnData('user', json_decode(json_encode($user, JSON_FORCE_OBJECT)));
            }
            return $this->returnError('E001', trans('messages.This code is not valid please enter it again'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function checkID(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "mobile_id" => "required|numeric",
                "api_token" => "required"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = $this->getUserByMobileOrEmailOrID($request->mobile_id, '', $request->mobile_id);
            if ($user != null)
                return $this->returnError('E001', trans('messages.User founded already'));

            return $this->returnSuccessMessage(trans('messages.User not found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function checkMobil(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "mobile" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = $this->getUserByMobileOrEmailOrID($request->mobile);
            if ($user != null)
                return $this->returnSuccessMessage(trans('messages.User founded already'));
            else
                return $this->returnError('E001', trans('messages.user not found'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }

    }

    public function login(Request $request)
    {
        try {
            if ($request->mobile_id == '0123456789' or $request->mobile_id == '0123456789') { // only for apple test

                $user = $this->authUserByIDOrMobile($request->mobile_id, $request->mobile_id);
                $user->device_token = $request->device_token;
                $user->update();

                $insuranceData = User::where('id', $user->id)
                    ->select('insurance_company_id as id',
                        DB::raw('IFNULL(insurance_image,"") as image'),
                        DB::raw('IFNULL((SELECT name_' . app()->getLocale() . ' FROM insurance_companies WHERE insurance_companies.id = users.insurance_company_id), "") AS name')
                    )->first();

                $cityData = User::where('id', $user->id)
                    ->select('city_id as id',
                        DB::raw('IFNULL((SELECT name_' . app()->getLocale() . ' FROM cities WHERE cities.id = users.city_id), "") AS name')
                    )->first();

                $user->insurance_company = $insuranceData;
                $user->city = $cityData;
                unset($user->insurance_company_id);
                return $this->returnData('user', json_decode(json_encode($user, JSON_FORCE_OBJECT)));
            }

            $validator = Validator::make($request->all(), [
                "device_token" => "required|min:64|max:255",
                "mobile_id" => "required|numeric"
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = $this->authUserByIDOrMobile($request->mobile_id, $request->mobile_id);
            if ($user != null) {
                unset($user->id_correct);
                unset($user->email_correct);
                unset($user->mobile2_correct);
                unset($user->mobile_correct);
                DB::beginTransaction();
                $deviceHash = $user->android_device_hasCode === null ? '' : $user->android_device_hasCode;
                $activationCode = (string)rand(1000, 9999);
                $message = trans('messages.Your Activation Code') . "{$activationCode} {$deviceHash}";
                $this->sendSMS($user->mobile, $message);

                $user->activation_code = $activationCode;
                $user->device_token = $request->device_token;
                $user->token_created_at = Carbon::now();
                $android_device_hasCode = '';
                if ($request->has('android_device_hasCode') && $request->android_device_hasCode != null && $request->android_device_hasCode != '') {
                    $android_device_hasCode = $request->android_device_hasCode;
                }
                $user->android_device_hasCode = $android_device_hasCode;
                $user->update();

                $insuranceData = User::where('id', $user->id)
                    ->select('insurance_company_id as id',
                        DB::raw('IFNULL(insurance_image,"") as image'),
                        DB::raw('IFNULL((SELECT name_' . app()->getLocale() . ' FROM insurance_companies WHERE insurance_companies.id = users.insurance_company_id), "") AS name')
                    )->first();

                $cityData = User::where('id', $user->id)
                    ->select('city_id as id',
                        DB::raw('IFNULL((SELECT name_' . app()->getLocale() . ' FROM cities WHERE cities.id = users.city_id), "") AS name')
                    )->first();

                $user->insurance_company = $insuranceData;
                $user->city = $cityData;
                unset($user->insurance_company_id);
                DB::commit();
                return $this->returnData('user', json_decode(json_encode($user, JSON_FORCE_OBJECT)));
            }
            return $this->returnError('E001', trans('messages.No result, please check your registration before'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function resendActivation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "api_token" => "required"
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = User::where('api_token', $request->api_token)->first();

            if ($user == null)
                return $this->returnError('E001', trans('messages.User not found'));

            //  if ($user->status == '1')
            //  return $this->returnError('E0103', trans("messages.This user already activated"));

            if ($user->no_of_sms == '3')
                return $this->returnError('E001', trans('messages.You exceed the limit of resending activation messages'));

            // ignor apple test account
            if ($user->mobile != '0123456789') {
                // resend code again
                $activationCode = (string)rand(1000, 9999);
                $user->activation_code = $activationCode;
                $user->no_of_sms = ($user->no_of_sms + 1);
                $user->update();
                $deviceHash = $user->android_device_hasCode === null ? '' : $user->android_device_hasCode;
                $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
                $this->sendSMS($user->mobile, $message);
            }
            return $this->returnData('user', json_decode(json_encode($user, JSON_FORCE_OBJECT)));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function updateUserLocation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "longitude" => "required|max:255",
                "latitude" => "required|max:255"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $user = $this->auth('user-api');
            $user->longitude = $request->longitude;
            $user->latitude = $request->latitude;
            $user->update();
            return $this->returnSuccessMessage(trans('messages.Location saved successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getCurrentReserves(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            $reservations = $this->getCurrentReservations($user->id);

            if (isset($reservations) && $reservations->count() > 0) {

                foreach ($reservations as $key => $reservation) {
                    $main_provider = Provider::where('id', $reservation->provider['provider_id'])->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'))->first();
                    $reservation->main_provider = $main_provider;
                }
            }

            if (count($reservations->toArray()) > 0) {

                $reservations->getCollection()->each(function ($reservation) {
                    $reservation->editable = $this->checkReservationTime($reservation);
                    return $reservation;
                });

                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $reservationsJson = new \stdClass();
                $reservationsJson->current_page = $reservations->current_page;
                $reservationsJson->total_pages = $reservations->last_page;
                $reservationsJson->total_count = $total_count;
                $reservationsJson->data = $reservations->data;
                return $this->returnData('reservations', $reservationsJson);
            }
            return $this->returnError('E001', trans('messages.No reservations founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getFinishedReserves(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            $reservations = $this->getFinishedReservations($user->id);

            if (isset($reservations) && $reservations->count() > 0) {

                foreach ($reservations as $key => $reservation) {
                    $main_provider = Provider::where('id', $reservation->provider['provider_id'])->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'))->first();
                    $reservation->main_provider = $main_provider;
                }
            }


            if (count($reservations->toArray()) > 0) {
                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $reservationsJson = new \stdClass();
                $reservationsJson->current_page = $reservations->current_page;
                $reservationsJson->total_pages = $reservations->last_page;
                $reservationsJson->total_count = $total_count;
                $reservationsJson->data = $reservations->data;
                return $this->returnData('reservations', $reservationsJson);
            }
            return $this->returnError('E001', trans('messages.No reservations founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function ReservationDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|exists:reservations,id"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $reservation = $this->getReservationByNoWihRelation($request->reservation_id);
            if ($reservation == null)
                return $this->returnError('E001', trans('messages.No reservation with this number'));


            $reservation->makeHidden(['offer_id', 'last_day_date',
                'last_from_time',
                'last_day_date',
                'last_to_time',
                'paid',
                'promocode_id',
                'order',
                'rejection_reason',
                'is_visit_doctor',
                'branch_no',
                'admin_value_from_reservation_price_Tax',
                'admin_value_from_reservation_price_Tax',
                'reservation_total',
                'comment_report'
            ]);
            $reservation->doctor->makeHidden(['times']);

            return $this->returnData('reservation', $reservation);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    protected function getReservationByNoWihRelation($reservation_id)
    {
        return Reservation::with(['doctor' => function ($g) {
            $g->select('id', 'nickname_id', 'specification_id', 'nationality_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                ->with(['nickname' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'rejectionResoan' => function ($rs) {
            $rs->select('id', DB::raw('name_' . app()->getLocale() . ' as rejection_reason'));
        }, 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        },
            'offer' => function ($qu) {
                $qu->select('id', DB::raw('title_' . app()->getLocale() . ' as title'), 'photo', 'price', 'price_after_discount');
            }
            , 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_company_id', 'insurance_image', 'mobile')->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
            }, 'provider' => function ($qq) {
                $qq->whereNotNull('provider_id')->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'latitude', 'longitude')
                    ->with(['provider' => function ($g) {
                        $g->select('id', 'type_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                            ->with(['type' => function ($gu) {
                                $gu->select('id', 'type_id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                    }]);
            }, 'people' => function ($p) {
                $p->select('id', 'name', 'insurance_company_id', 'insurance_image')->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
            }])->where('id', $reservation_id)
            ->first();
    }


    public function getUserData(Request $request)
    {
        try {
            $user = $this->auth('user-api', ['insuranceCompany' => function ($q) {
                $q->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'city' => function ($q) {
                $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }]);


            if (!$user)
                return $this->returnError('E022', 'User not found');

            $user->year = $user->birth_date ? date('Y', strtotime($user->birth_date)) : "";
            $user->month = $user->birth_date ? date('m', strtotime($user->birth_date)) : "";
            $user->day = $user->birth_date ? date('d', strtotime($user->birth_date)) : "";
            return $this->returnData('user', json_decode(json_encode($user, JSON_FORCE_OBJECT)));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function userRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "doctor_rate" => "required|numeric|min:0|max:5",
            "provider_rate" => "required|numeric|min:0|max:5",
            "rate_comment" => "required|string",
            "reservation_id" => "required|numeric",
            "bill_photo" => "sometimes|nullable"
        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        $user = $this->auth('user-api');

        if (!$user)
            return $this->returnError('E001', trans("messages.User not found"));

        $reservation = $this->getReservationWithData($request->reservation_id, $user->id);

        if (!$reservation)
            return $this->returnError('E001', trans("messages.reservation not found"));

        if ($reservation == null)
            return $this->returnError('E001', trans('messages.No reservation with this id'));

        /* if ($reservation->doctor_rate != null && $reservation->doctor_rate != 0)
             return $this->returnError('E001', trans('messages.This doctor already has rated before'));

         if ($reservation->provider_rate != null && $reservation->provider_rate != 0)
             return $this->returnError('E001', trans('messages.This provider already has rated before'));*/

        // if not complete or reject
        if ($reservation->approved != 3 && $reservation->approved != 2)
            return $this->returnError('E001', trans('messages.reservation status not completed or rejected'));

        $provider = $reservation->provider;
        if ($provider == null)
            return $this->returnError('E001', trans('messages.No provider with this id'));

        $MainProvider = $provider->Provider;
        if ($MainProvider == null)
            return $this->returnError('E001', trans('messages.No provider with this id'));

        // if this reservation has bill then ,user allow to upload total bill image to admin
        if (($MainProvider->application_percentage > 0 or $MainProvider->application_percentage_bill > 0) && $reservation->promocode_id == null && $reservation->approved == 3) {//bill_photo
            if ($request->filled('bill_photo')) {

                $path = $this->saveImage('bills', $request->bill_photo);
                $reservation->update([
                    'bill_photo' => $path,
                ]);

                Bill::create([
                    'reservation_id' => $reservation->id,
                    'reservation_no' => $reservation->reservation_no,
                    'photo' => $path
                ]);
            } else {
                return $this->returnError('E001', trans('messages.please upload bill photo'));
            }
        }
        // rate
        $reservation->update([
            'doctor_rate' => $request->doctor_rate,
            'provider_rate' => $request->provider_rate,
            'rate_comment' => $request->rate_comment,
            'rate_date' => Carbon::now(),
        ]);

        // doctor rate
        $doctor = Doctor::where('id', $reservation->doctor->id)->first();
        if ($doctor) {
            $sumAll = $doctor->reservations()->sum('doctor_rate');
            $countRate = count($doctor->reservations);
            if ($countRate > 0) {
                $rate = $sumAll / $countRate;
                $doctor->update([
                    'rate' => $sumAll ? number_format($rate, 1) : 0
                ]);
            }
        } else {
            return $this->returnError('E001', trans('messages.No doctor with this id'));
        }

        $sumAll = $provider->reservations()->sum('provider_rate');
        $countRate = count($provider->reservations);

        if ($countRate > 0) {
            $rate = $sumAll / $countRate;
            $provider->update([
                'rate' => $sumAll ? number_format($rate, 1) : 0
            ]);
        }

        $notification = GeneralNotification::create([
            'title_ar' => 'تقييم جديد لمقدم الخدمه  ' . ' ' . '(' . $MainProvider->name_ar . ')',
            'title_en' => 'New rating for ' . ' ' . '(' . $MainProvider->name_ar . ')',
            'content_ar' => ' تقييم  جديد علي الحجز رقم ' . ' ' . $reservation->reservation_no,
            'content_en' => 'New rating for reservation No: ' . ' ' . $reservation->reservation_no . ' ' . ' ( ' . $MainProvider->name_ar . ' )',
            'notificationable_type' => 'App\Models\Provider',
            'notificationable_id' => $reservation->provider_id,
            'data_id' => $reservation->id,
            'type' => 4  //user rate provider and doctor
        ]);

        $notify = [
            'provider_name' => $MainProvider->name_ar,
            'reservation_no' => $reservation->reservation_no,
            'reservation_id' => $reservation->id,
            'content' => ' تقييم  جديد علي الحجز رقم ' . ' ' . $reservation->reservation_no,
            'photo' => $MainProvider->logo,
            'notification_id' => $notification->id
        ];


        try {
            ########### admin firebase push notifications ##############################
            (new \App\Http\Controllers\NotificationController(['title' => $notification->title_ar, 'body' => $notification->content_ar]))->sendAdminWeb(4);
            event(new \App\Events\NewProviderRate($notify));   // fire pusher new reservation  event notification*/
        } catch (\Exception $ex) {
        }

        return $this->returnSuccessMessage(trans('messages.Rate saved successfully'));
    }


    public function userRatingService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "reservation_id" => "required|numeric|exists:service_reservations,id",
            "service_rate" => "required|numeric|min:0|max:5",
            "provider_rate" => "required|numeric|min:0|max:5",
            "rate_comment" => "required|string",
            //"bill_photo" => "sometimes|nullable"
        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        $user = $this->auth('user-api');

        if (!$user)
            return $this->returnError('E001', trans("messages.User not found"));

        $reservation = $this->getServiceReservationWithData($request->reservation_id, $user->id);

        if (!$reservation)
            return $this->returnError('E001', trans("messages.reservation not found"));

        if ($reservation == null)
            return $this->returnError('E001', trans('messages.No reservation with this id'));


        // if not complete or reject
        if ($reservation->approved != 3 && $reservation->approved != 2)
            return $this->returnError('E001', trans('messages.reservation status not completed or rejected'));

        $provider = $reservation->provider;
        if ($provider == null)
            return $this->returnError('E001', trans('messages.No provider with this id'));

        $MainProvider = $provider->Provider;
        if ($MainProvider == null)
            return $this->returnError('E001', trans('messages.No provider with this id'));

        /* // if this reservation has bill then ,user allow to upload total bill image to admin
         if (($MainProvider->application_percentage > 0 or $MainProvider->application_percentage_bill > 0) && $reservation->promocode_id == null && $reservation->approved == 3) {//bill_photo
             if ($request->filled('bill_photo')) {

                 $path = $this->saveImage('bills', $request->bill_photo);
                 $reservation->update([
                     'bill_photo' => $path,
                 ]);

                 Bill::create([
                     'reservation_id' => $reservation->id,
                     'reservation_no' => $reservation->reservation_no,
                     'photo' => $path
                 ]);
             } else {
                 return $this->returnError('E001', trans('messages.please upload bill photo'));
             }
         }*/

        // rate
        $reservation->update([
            'service_rate' => $request->service_rate,
            'provider_rate' => $request->provider_rate,
            'rate_comment' => $request->rate_comment,
            'rate_date' => Carbon::now(),
        ]);

        // service rate
        $service = Service::where('id', $reservation->service->id)->first();
        if ($service) {
            $sumAll = $service->reservations()->sum('service_rate');
            $countRate = count($service->reservations);
            if ($countRate > 0) {
                $rate = $sumAll / $countRate;
                $service->update([
                    'rate' => $sumAll ? number_format($rate, 1) : 0
                ]);
            }
        } else {
            return $this->returnError('E001', trans('messages.No service with this id'));
        }

        $sumAll = $provider->reservations()->sum('provider_rate');
        $countRate = count($provider->reservations);

        if ($countRate > 0) {
            $rate = $sumAll / $countRate;
            $provider->update([
                'rate' => $sumAll ? number_format($rate, 1) : 0
            ]);
        }

        $notification = GeneralNotification::create([
            'title_ar' => 'تقييم جديد لمقدم الخدمه  ' . ' ' . '(' . $MainProvider->name_ar . ')',
            'title_en' => 'New rating for ' . ' ' . '(' . $MainProvider->name_ar . ')',
            'content_ar' => ' تقييم  جديد علي حجز خدمه رقم ' . ' ' . $reservation->reservation_no,
            'content_en' => 'New rating for reservation No: ' . ' ' . $reservation->reservation_no . ' ' . ' ( ' . $MainProvider->name_ar . ' )',
            'notificationable_type' => 'App\Models\Provider',
            'notificationable_id' => $reservation->provider_id,
            'data_id' => $reservation->id,
            'type' => 6 //user rate provider and service
        ]);

        $notify = [
            'provider_name' => $MainProvider->name_ar,
            'reservation_no' => $reservation->reservation_no,
            'reservation_id' => $reservation->id,
            'content' => ' تقييم  جديد علي الحجز رقم ' . ' ' . $reservation->reservation_no,
            'photo' => $MainProvider->logo,
            'notification_id' => $notification->id
        ];
        //fire pusher  notification for admin  stop pusher for now
        /*try {
            event(new \App\Events\NewProviderRate($notify));   // fire pusher new reservation  event notification
        } catch (\Exception $ex) {
        }*/

        return $this->returnSuccessMessage(trans('messages.Rate saved successfully'));
    }


    public function UpdateReservationDateTime(Request $request)
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
            $user = $this->auth('user-api');
            $reservation = Reservation::where('reservation_no', $request->reservation_no)->where('user_id', $user->id)->first();
            if ($reservation == null)
                return $this->returnError('D000', trans('messages.No reservation with this number'));

            if ($reservation->approved == 2)
                return $this->returnError('E001', trans('messages.Only approved Or pending reservation can  be updated'));


            if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                    strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))) {
                return $this->returnError('E001', trans("messages.You can't take action to a reservation passed"));
            }

            $doctor = $reservation->doctor;
            if ($doctor == null)
                return $this->returnError('D000', trans('messages.No doctor with this id'));

            if (strtotime($request->day_date) <= strtotime(Carbon::now()->format('Y-m-d')) && strtotime($request->to_time) < strtotime(Carbon::now()->format('H:i:s')))
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
                "last_day_date" => $reservation->day_date,
                "last_from_time" => $reservation->from_time,
                "last_to_time" => $reservation->to_time,
                "day_date" => date('Y-m-d', strtotime($request->day_date)),
                "from_time" => date('H:i:s', strtotime($request->from_time)),
                "to_time" => date('H:i:s', strtotime($request->to_time)),
                'order' => $timeOrder,
                "approved" => 0,
            ]);

            if ($last) {
                ReservedTime::create([
                    'doctor_id' => $doctor->id,
                    'day_date' => date('Y-m-d', strtotime($request->day_date))
                ]);
            }


            $provider = $reservation->provider; //branch
            $mainProvider = Provider::where('id', $provider->provider_id)->first();

            if ($reservation->user->email != null)
                Mail::to($reservation->user->email)->send(new AcceptReservationMail($reservation->reservation_no));

            DB::commit();
            try {
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The user') . ' ' . $reservation->user->name . ' ' . __('messages.updated reservation')]))->sendProvider($provider);
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The user') . ' ' . $reservation->user->name . ' ' . __('messages.updated reservation')]))->sendProvider($mainProvider);

                $msg = __('messages.The user') . ' ' . $reservation->user->name . ' ' . __('messages.updated reservation');

                $this->sendSMS($mainProvider->mobile, $msg);  //sms for main provider

                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The user') . ' ' . $reservation->user->name . ' ' . __('messages.updated reservation')]))
                    ->sendProviderWeb($provider, $reservation->reservation_no, 'update_reservation');
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The user') . ' ' . $reservation->user->name . ' ' . __('messages.updated reservation')]))
                    ->sendProviderWeb($mainProvider, $reservation->reservation_no, 'update_reservation');

                // (new \App\Http\Controllers\NotificationController(['title'=>__('messages.Reservation Status'), 'body'=>__('messages.The branch').$provider->getTranslatedName().__('messages.updated your reservation')]))->sendUser($reservation->user);
                $notification = GeneralNotification::create([
                    'title_ar' => 'تعديل الحجز رقم  ' . ' ' . $reservation->reservation_no,
                    'title_en' => 'Update Reservation Date for reservation No:' . ' ' . $reservation->reservation_no,
                    'content_ar' => 'قام المستخدم  ' . ' ' . $reservation->user->name . ' ' . 'بتحديث موعد الحجز رقم' . ' ' . $reservation->reservation_no . ' ' . 'لمقدم الخدمة ' . ' ( ' . $mainProvider->name_ar . ' )',
                    'content_en' => $reservation->user->name . ' ' . 'change the reservation date for reservation no: ' . ' ' . $reservation->reservation_no . ' ' . 'for provider' . ' ( ' . $mainProvider->name_ar . ' )',
                    'notificationable_type' => 'App\Models\Provider',
                    'notificationable_id' => $reservation->provider_id,
                    'data_id' => $reservation->id,
                    'type' => 3 //user edit doctor reservation date
                ]);


                $notify = [
                    'provider_name' => $mainProvider->name_ar,
                    'reservation_no' => $reservation->reservation_no,
                    'reservation_id' => $reservation->id,
                    'content' => ' تعديل الحجز رقم ' . ' ' . $reservation->reservation_no,
                    'photo' => $mainProvider->logo,
                    'notification_id' => $notification->id
                ];
                //fire pusher  notification for admin  stop pusher for now
                ########### admin firebase push notifications ##############################
                (new \App\Http\Controllers\NotificationController(['title' => $notification->title_ar, 'body' => $notification->content_ar]))->sendAdminWeb(3);

                event(new \App\Events\UserEditReservationTime($notify));   // fire pusher new reservation  event notification*/
            } catch (\Exception $ex) {
            }
            return $this->returnSuccessMessage(trans('messages.Reservation updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function reportingComment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "reservation_no" => "required|max:191",
            "reporting_type_id" => "required",
        ]);

        $user = $this->auth('user-api');
        if ($user == null)
            return $this->returnError('E001', trans("messages.User not found"));

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $reservation = Reservation::where('reservation_no', $request->reservation_no)->first();
        if ($reservation == null)
            return $this->returnError('D000', trans('messages.No reservation with this number'));

        $reporting_type = ReportingType::find($request->reporting_type_id);
        if ($reporting_type == null)
            return $this->returnError('D000', trans('messages.No reporting type with this id'));

        CommentReport::create(['user_id' => $user->id, 'reservation_no' => $request->reservation_no, 'reporting_type_id' => $request->reporting_type_id]);
        return $this->returnSuccessMessage(trans('messages.Comment Reported  successfully'));
    }

    public function getProviderRate(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }


            $provider = Provider::find($request->provider_id);

            if ($provider == null)
                return $this->returnError('E001', trans('messages.Provider not found'));

            if ($provider->provider_id == null)
                return $this->returnError('D000', trans("messages.Your account isn't branch"));

            $reservations = $provider->reservations()->with(['user' => function ($q) {
                $q->select('id', 'name');
            }])->select('id', 'doctor_rate', 'provider_rate', 'rate_date', 'rate_comment', 'provider_id', 'reservation_no')
                ->Where('doctor_rate', '!=', null)
                ->Where('doctor_rate', '!=', 0)
                ->Where('provider_rate', '!=', null)
                ->Where('provider_rate', '!=', 0)
                ->paginate(10);


            if ($provider->reservations == null || count($reservations->toArray()) == 0)
                return $this->returnError('E001', trans('messages.No rates for this provider'));


            return $this->returnData('rates', $reservations);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getProviderRateV2(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }


            $provider = Provider::find($request->provider_id);

            if ($provider == null)
                return $this->returnError('E001', trans('messages.Provider not found'));

            if ($provider->provider_id == null)
                return $this->returnError('D000', trans("messages.Your account isn't branch"));

            $reservations = $provider->reservations()
                ->with(['user' => function ($q) {
                    $q->select('id', 'name', 'photo');
                }])->select('id', 'user_id', 'doctor_rate', 'provider_rate', 'rate_date', 'rate_comment', 'provider_id', 'reservation_no')
                // ->Where('doctor_rate', '!=', null)
                //->Where('doctor_rate', '!=', 0)
                ->Where('provider_rate', '!=', null)
                ->Where('provider_rate', '!=', 0)
                ->paginate(10);

            if (count($reservations->toArray()) > 0) {
                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $rateJson = new \stdClass();
                $rateJson->current_page = $reservations->current_page;
                $rateJson->total_pages = $reservations->last_page;
                $rateJson->total_count = $total_count;
                $rateJson->data = $reservations->data;
                return $this->returnData('rates', $rateJson);
            }

            $this->returnError('E001', trans('messages.No rates founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function getPoints(Request $request)
    {
        try {

            $user = $this->auth('user-api');
            if ($user == null)
                return $this->returnError('E001', trans("messages.User not found"));
            $points = Point::where('user_id', $user->id)->first();

            if ($points) {
                $points = [
                    "points" => $points->points
                ];
                return $this->returnData('points', json_decode(json_encode($points, JSON_FORCE_OBJECT)));

            } else {
                $points = [
                    "points" => 0,
                ];
                return $this->returnData('points', json_decode(json_encode($points, JSON_FORCE_OBJECT)));
            }

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function getFavouriteDoctors(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            $doctors = $this->getFavourDoctors($user->id);

            /*$doctors->getCollection()->each(function ($doctor) {
                       $doctor->makeVisible(['provider_id']);
                       return $doctor;
                   });*/

            if (isset($doctors) && $doctors->count() > 0) {
                foreach ($doctors as $key => $doctor) {
                    $main_provider = Provider::where('id', $doctor->provider['provider_id'])->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'))->first();
                    $doctor->main_provider = $main_provider;
                }
            }

            if (count($doctors->toArray()) > 0) {
                $total_count = $doctors->total();
                $doctors = json_decode($doctors->toJson());
                $doctorsJson = new \stdClass();
                $doctorsJson->current_page = $doctors->current_page;
                $doctorsJson->total_pages = $doctors->last_page;
                $doctorsJson->total_count = $total_count;
                $doctorsJson->data = $doctors->data;
                return $this->returnData('doctors', $doctorsJson);
            }
            return $this->returnError('E001', trans('messages.No favourites founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getFavouriteProviders(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            $providers = $this->getFavourProviders($user->id, ($user != null && strlen($user->longitude) > 6 ? $user->longitude : $request->longitude), ($user != null && strlen($user->latitude) > 6 ? $user->latitude : $request->latitude));
            if (count($providers->toArray()) > 0) {

                $providers->getCollection()->each(function ($provider) {
                    $provider->favourite = count($provider->favourites) > 0 ? 1 : 0;
                    $provider->distance = (string)number_format($provider->distance * 1.609344, 2);
                    $provider->has_doctors = $provider->doctors()->count() > 0 ? 1 : 0;
                    $provider->has_home_services = $provider->homeServices()->count() > 0 ? 1 : 0;
                    $provider->has_clinic_services = $provider->clinicServices()->count() > 0 ? 1 : 0;
                    return $provider;
                });

                $total_count = $providers->total();
                $providers = json_decode($providers->toJson());
                $providersJson = new \stdClass();
                $providersJson->current_page = $providers->current_page;
                $providersJson->total_pages = $providers->last_page;
                $providersJson->total_count = $total_count;
                $providersJson->data = $providers->data;
                return $this->returnData('providers', $providersJson);
            }
            return $this->returnError('E001', trans('messages.No favourites founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function removeFromFavourite(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "doctor_id" => "numeric",
                "provider_id" => "numeric"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            DB::beginTransaction();
            $user = $this->auth('user-api');
            if (!isset($request->doctor_id) && !isset($request->provider_id))
                return $this->returnError('E001', trans('messages.Please enter doctor id or provider id'));

            $favourite = null;
            if (isset($request->doctor_id) && $request->doctor_id != 0) {
                //$doctor = $this->checkDoctor($request->doctor_id);
                // if($doctor == null)
                //   return $this->returnError('D000', trans("messages.There is no doctor with this id"));

                $favourite = $this->checkDoctorInFavourites($user->id, $request->doctor_id);
                if ($favourite == null)
                    return $this->returnError('E001', trans("messages.This doctor is not in favourite"));
            }
            if (isset($request->provider_id) && $request->provider_id != 0) {
                //$provider = $this->checkProvider($request->provider_id);
                //if($provider == null)
                //  return $this->returnError('D000', trans("messages.There is no provider with this id"));

                $favourite = $this->checkProviderInFavourites($user->id, $request->provider_id);
                if ($favourite == null)
                    return $this->returnError('E001', trans("messages.This provider is not in favourite"));
            }
            $favourite->delete();
            DB::commit();
            return $this->returnSuccessMessage(trans('messages.Deleted from favourite successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function addFavourite(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "doctor_id" => "numeric",
                "provider_id" => "numeric"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            DB::beginTransaction();
            $user = $this->auth('user-api');

            if (!isset($request->doctor_id) && !isset($request->provider_id))
                return $this->returnError('E001', trans('messages.Please enter doctor id or provider id'));

            $favourite = null;
            if (isset($request->doctor_id) && $request->doctor_id != 0) {
                // $doctor = $this->checkDoctor($request->doctor_id);
                //if($doctor == null)
                //  return $this->returnError('D000', trans("messages.There is no doctor with this id"));

                $favourite = $this->checkDoctorInFavourites($user->id, $request->doctor_id);
                if ($favourite != null)
                    return $this->returnError('E001', trans("messages.This doctor is already in favourite list"));
            }
            if (isset($request->provider_id) && $request->provider_id != 0) {
                //$provider = $this->checkProvider($request->provider_id);
                //if($provider == null)
                //return $this->returnError('D000', trans("messages.There is no provider with this id"));

                $favourite = $this->checkProviderInFavourites($user->id, $request->provider_id);
                if ($favourite != null)
                    return $this->returnError('E001', trans("messages.This provider is already in favourite list"));
            }
            Favourite::create([
                'user_id' => $user->id,
                'doctor_id' => $request->doctor_id ? $request->doctor_id : NULL,
                'provider_id' => $request->provider_id ? $request->provider_id : NULL
            ]);
            DB::commit();
            return $this->returnSuccessMessage(trans('messages.Added to favourite successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function addNewMessages(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "importance" => "numeric|min:1|max:2",
                "type" => "numeric|min:1|max:4",
                "message" => "required",
                "message_id" => "numeric"
            ]);
            DB::beginTransaction();
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $order = 1;
            $user = $this->auth('user-api');
            if (isset($request->message_id) && $request->message_id != 0) {
                $message = $this->getUserMessageByID($request->message_id);
                if ($message == null)
                    return $this->returnError('E001', trans('messages.No message founded'));

                $lastMessage = $this->getLastReplyInMessage($request->message_id);
                if ($lastMessage != null)
                    $order = ($lastMessage->order + 1);
            } else {
                if (!isset($request->title) || empty($request->title))
                    return $this->returnError('D000', trans('messages.Please enter message title'));

                if (!isset($request->type) || $request->type == 0 || !isset($request->importance) || $request->importance == 0)
                    return $this->returnError('D000', trans('messages.Please enter importance and type'));

                $lastMessageForUser = $this->getLastMessageForUser($user->id);
                if ($lastMessageForUser != null)
                    $order = ($lastMessageForUser->order + 1);
            }
            Message::create([
                'title' => $request->title ? $request->title : "",
                'user_id' => $user->id,
                'message_no' => 'M' . $user->id . uniqid(),
                'type' => $request->type,
                'importance' => $request->importance,
                'message' => $request->message,
                'message_id' => $request->message_id != 0 ? $request->message_id : NULL,
                'order' => $order
            ]);
            $appData = $this->getAppInfo();
            // Sending mail to manager
            if (isset($request->message_id) && $request->message_id != 0)
                Mail::to($appData->email)->send(new NewReplyMessageMail($user->name));

            else
                Mail::to($appData->email)->send(new NewUserMessageMail($user->name));

            DB::commit();
            if (isset($request->message_id) && $request->message_id != 0)
                return $this->returnSuccessMessage(trans('messages.Reply send successfully'));

            return $this->returnSuccessMessage(trans('messages.Message sent successfully, you can keep in touch with replies by view messages page'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getUserMessages(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            $messages = $this->getMessages($user->id);
            if (count($messages->toArray()) > 0) {
                $total_count = $messages->total();
                $messages->getCollection()->each(function ($message) {
                    if ($message->importance == 1)
                        $message->importance_text = trans('messages.Quick');
                    else if ($message->importance == 2)
                        $message->importance_text = trans('messages.Normal');

                    if ($message->type == 1)
                        $message->type_text = trans('messages.Inquiry');

                    else if ($message->type == 2)
                        $message->type_text = trans('messages.Suggestion');

                    else if ($message->type == 3)
                        $message->type_text = trans('messages.Complaint');

                    else if ($message->type == 4)
                        $message->type_text = trans('messages.Others');

                    return $message;
                });
                $messages = json_decode($messages->toJson());
                $messagesJson = new \stdClass();
                $messagesJson->current_page = $messages->current_page;
                $messagesJson->total_pages = $messages->last_page;
                $messagesJson->total_count = $total_count;
                $messagesJson->data = $messages->data;
                return $this->returnData('messages', $messagesJson);
            }
            return $this->returnError('E001', trans("messages.No messages founded"));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getUserMessageReplies(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $user = $this->auth('user-api');
            $message = $this->checkUserMessageById($user->id, $request->id);
            if ($message == null)
                return $this->returnError('D000', trans("messages.There is no message with this id"));

            $messages = $message->messages;
            if (count($messages->toArray()) > 0) {
                $total_count = $messages->total();
                $messages = json_decode($messages->toJson());
                $messagesJson = new \stdClass();
                $messagesJson->current_page = $messages->current_page;
                $messagesJson->total_pages = $messages->last_page;
                $messagesJson->total_count = $total_count;
                $messagesJson->data = $messages->data;
                return $this->returnData('messages', $messagesJson);
            }
            return $this->returnError('E001', trans("messages.No replies founded"));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getRecords(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
                "reservation_no" => "sometimes|string"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            // if(!$this->checkLogin($request))
            //   return $this->returnError('E001', trans('auth.failed'));
            $user = $this->auth('user-api');
            $provider = $this->auth('provider-api');
            if ($user) {
                if ($user->id != $request->user_id)
                    return $this->returnError('E001', trans("messages.User not found"));
                $user = User::with('records', 'records.attachments')->find($user->id);
                if ($user == null)
                    return $this->returnError('E001', trans("messages.User not found"));

                $records = $user->records()->with(['attachments', 'provider' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'), 'logo', 'rate');
                }, 'doctor' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'specification' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'attachments.category' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }])->paginate(10);
            } else if ($provider) {
                $resrvation = Reservation::with('doctor')->where('reservation_no', $request->reservation_no)->first();
                if (!$resrvation)
                    return $this->returnError('E001', trans("messages.reservation not found"));
                $user = User::whereHas('records', function ($q) use ($resrvation) {
                    $q->whereHas('reservation', function ($q) use ($resrvation) {
                        $q->whereHas('doctor', function ($q) use ($resrvation) {
                            $q->where('specification_id', $resrvation->doctor->specification_id);
                        });
                    });
                })->find($request->user_id);

                if ($user == null)
                    return $this->returnError('E011', trans("messages.No medical records founded"));

                $records = $user->records()->with(['attachments', 'specification' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'attachments.category' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }])->whereHas('reservation', function ($q) use ($resrvation) {
                    $q->whereHas('doctor', function ($q) use ($resrvation) {
                        $q->where('specification_id', $resrvation->doctor->specification_id);
                    });
                })->paginate(10);
            } else
                return $this->returnError('E001', trans("messages.No medical records founded"));

            if (count($records->toArray()) > 0) {
                $total_count = $records->total();
                $records = json_decode($records->toJson());
                $recordsJson = new \stdClass();
                $recordsJson->current_page = $records->current_page;
                $recordsJson->total_pages = $records->last_page;
                $recordsJson->total_count = $total_count;
                $recordsJson->data = $records->data;
                return $this->returnData('records', $recordsJson);
            }
            return $this->returnError('E001', trans("messages.No medical records founded"));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getRecordsV2(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
                "reservation_no" => "sometimes|string"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            // if(!$this->checkLogin($request))
            //   return $this->returnError('E001', trans('auth.failed'));
            $user = $this->auth('user-api');
            $provider = $this->auth('provider-api');
            if ($user) {
                if ($user->id != $request->user_id)
                    return $this->returnError('E001', trans("messages.User not found"));
                $user = User::with('records', 'records.attachments')->find($user->id);
                if ($user == null)
                    return $this->returnError('E001', trans("messages.User not found"));

                $records = $user->records()->with(['attachments', 'provider' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'), 'logo', 'rate');
                }, 'doctor' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'), \Illuminate\Support\Facades\DB::raw('abbreviation_' . app()->getLocale() . ' as abbreviation'));
                }, 'specification' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'attachments.category' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'reservation' => function ($q) {
                    $q->select('id', 'reservation_no', 'from_time', 'to_time', 'day_date', 'provider_id');
                    $q->with(['provider' => function ($qq) {
                        $qq->select('id', 'name_' . app()->getLocale() . ' as name');
                    }]);
                }])->paginate(10);
            } else if ($provider) {
                $resrvation = Reservation::with('doctor')->where('reservation_no', $request->reservation_no)->first();
                if (!$resrvation)
                    return $this->returnError('E001', trans("messages.reservation not found"));
                $user = User::whereHas('records', function ($q) use ($resrvation) {
                    $q->whereHas('reservation', function ($q) use ($resrvation) {
                        $q->whereHas('doctor', function ($q) use ($resrvation) {
                            $q->where('specification_id', $resrvation->doctor->specification_id);
                        });
                    });
                })->find($request->user_id);

                if ($user == null)
                    return $this->returnError('E011', trans("messages.No medical records founded"));

                $records = $user->records()->with(['attachments', 'specification' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'attachments.category' => function ($q) {
                    $q->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }])->whereHas('reservation', function ($q) use ($resrvation) {
                    $q->whereHas('doctor', function ($q) use ($resrvation) {
                        $q->where('specification_id', $resrvation->doctor->specification_id);
                    });
                })->paginate(10);
            } else
                return $this->returnError('E001', trans("messages.No medical records founded"));

            if (count($records->toArray()) > 0) {
                $total_count = $records->total();
                $records = json_decode($records->toJson());
                $recordsJson = new \stdClass();
                $recordsJson->current_page = $records->current_page;
                $recordsJson->total_pages = $records->last_page;
                $recordsJson->total_count = $total_count;
                $recordsJson->data = $records->data;
                return $this->returnData('records', $recordsJson);
            }
            return $this->returnError('E001', trans("messages.No medical records founded"));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            $token = $request->api_token;
            UserToken::where('api_token', $token)->delete();
            // $token = '';
            // ignore apple test account
            if ($user->mobile != '0123456789') {
                $activationCode = (string)rand(1000, 9999);
                // $user->api_token = $token;
                $user->activation_code = $activationCode;
            }
            $user->token_created_at = null;
            $user->update();
            return $this->returnData('message', trans('messages.Logged out successfully'));
            // return $this->returnSuccessMessage(trans('messages.Logged out successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function AutoUpdateUserLocation(Request $request)
    {

    }


    public function verifyPhone(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "device_token" => "required|min:64|max:255",
                "mobile_id" => "required|numeric"
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }


            if ($request->mobile_id == '0123456789' or $request->mobile_id == '0123456789') { // only for apple test

                $user = $this->authUserByIDOrMobile($request->mobile_id, $request->mobile_id);
                $user->device_token = $request->device_token;
                $user->update();

                $insuranceData = User::where('id', $user->id)
                    ->select('insurance_company_id as id',
                        DB::raw('IFNULL(insurance_image,"") as image'),
                        DB::raw('IFNULL((SELECT name_' . app()->getLocale() . ' FROM insurance_companies WHERE insurance_companies.id = users.insurance_company_id), "") AS name')
                    )->first();

                $cityData = User::where('id', $user->id)
                    ->select('city_id as id',
                        DB::raw('IFNULL((SELECT name_' . app()->getLocale() . ' FROM cities WHERE cities.id = users.city_id), "") AS name')
                    )->first();

                $user->insurance_company = $insuranceData;
                $user->city = $cityData;
                unset($user->insurance_company_id);
                return $this->returnData('user', json_decode(json_encode($user, JSON_FORCE_OBJECT)));
            }

            $user = $this->authUserByIDOrMobile($request->mobile_id, $request->mobile_id);
            if ($user != null) {
                unset($user->id_correct);
                unset($user->email_correct);
                unset($user->mobile2_correct);
                unset($user->mobile_correct);
                DB::beginTransaction();
                $deviceHash = $user->android_device_hasCode === null ? '' : $user->android_device_hasCode;
                $activationCode = (string)rand(1000, 9999);
                $message = trans('messages.Your Activation Code') . "{$activationCode} {$deviceHash}";
                $this->sendSMS($user->mobile, $message);

                $user->activation_code = $activationCode;
                $user->device_token = $request->device_token;
                $user->token_created_at = Carbon::now();
                $android_device_hasCode = '';
                if ($request->has('android_device_hasCode') && $request->android_device_hasCode != null && $request->android_device_hasCode != '') {
                    $android_device_hasCode = $request->android_device_hasCode;
                }
                $user->android_device_hasCode = $android_device_hasCode;
                $user->update();

                $insuranceData = User::where('id', $user->id)
                    ->select('insurance_company_id as id',
                        DB::raw('IFNULL(insurance_image,"") as image'),
                        DB::raw('IFNULL((SELECT name_' . app()->getLocale() . ' FROM insurance_companies WHERE insurance_companies.id = users.insurance_company_id), "") AS name')
                    )->first();

                $cityData = User::where('id', $user->id)
                    ->select('city_id as id',
                        DB::raw('IFNULL((SELECT name_' . app()->getLocale() . ' FROM cities WHERE cities.id = users.city_id), "") AS name')
                    )->first();

                $user->insurance_company = $insuranceData;
                $user->city = $cityData;
                unset($user->insurance_company_id);
                DB::commit();
                return $this->returnData('user', json_decode(json_encode($user, JSON_FORCE_OBJECT)));
            }
            return $this->returnError('F001F', trans('messages.this mobile to belong to any user'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function getInvitationCode(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            if ($user == null)
                return $this->returnError('E001', trans("messages.User not found"));

            if ($user->invitation_code) {
                return $this->returnData('invitation_code', $user->invitation_code);
            }
            $code = $this->getRandomStringForInvitation(6);
            $user->update(['invitation_code' => strtolower($code)]);
            return $this->returnData('invitation_code', strtolower($code));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public
    function RejectReservation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_no" => "required|max:255",
                "user_reject_reason" => "required|max:225"  //
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            \Illuminate\Support\Facades\DB::beginTransaction();
            $user = $this->auth('user-api');
            $reservation = $this->getReservationByNoAndUser($request->reservation_no, $user->id);

            if ($reservation == null)
                return $this->returnError('D000', trans('messages.No reservation with this number'));

            if ($reservation->approved == 1)
                return $this->returnError('E001', trans("messages.You can't reject approved reservation"));

            if ($reservation->approved == 2 or $reservation->approved == 5)
                return $this->returnError('E001', trans('messages.Reservation already rejected'));

            $reservation->update([
                'approved' => 5,
                'user_rejection_reason' => $request->user_reject_reason
            ]);

            DB::commit();
            try {

                $name = 'name_' . app()->getLocale();
                $branch = Provider::find($reservation->provider_id);
                $provider = Provider::find($branch->provider_id);
                $bodyProvider = __('messages.the user') . "  {$reservation->user->name}   " . __('messages.cancel the reservation') . " {$reservation -> reservation_no } " . __('messages.because') . '( ' . $request->user_reject_reason . ' ) ';
                //send push notification
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyProvider]))
                    ->sendProvider($branch);
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyProvider]))
                    ->sendProvider($provider);
            } catch (\Exception $ex) {

            }
            return $this->returnSuccessMessage(trans('messages.Reservation rejected successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    protected function getReservationByNoAndUser($reservation_no, $userId)
    {
        return Reservation::where(function ($q) use ($reservation_no, $userId) {
            $q->where('reservation_no', $reservation_no)->where(function ($qq) use ($userId) {
                $qq->where('user_id', $userId);
            });
        })->with(['user'])->first();
    }


    function test()
    {

        return $this->getRandomStringForInvitation(6);

    }

    function getRandomStringForInvitation($length = 6)
    {
        $charactersNum = '0123456789';
        $charactersChar = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $string = '';
        for ($i = 0; $i < 3; $i++) {
            $string .= $charactersNum[mt_rand(0, strlen($charactersNum) - 1)];
        }

        for ($i = 0; $i < 3; $i++) {
            $string .= $charactersChar[mt_rand(0, strlen($charactersChar) - 1)];

        }


        $randomCode = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $randomCode .= $string[mt_rand(0, strlen($string) - 1)];
        }

        $chkCode = User::where('invitation_code', $randomCode)->first();

        if ($chkCode) {
            $this->getRandomStringForInvitation(6);
        }
        return $randomCode;
    }


    public function notifications(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "type" => "required|in:count,list"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $user = $this->auth('user-api');
            if (!$user) {
                return $this->returnError('E001', trans('messages.There is no user with this id'));
            }

            if ($request->type == 'count') {
                $un_read_notifications = Reciever::where('actor_id', $user->id)
                    ->unseenForUser()
                    ->count();
                return $this->returnData('un_read_notifications', $un_read_notifications);
            }
            ///else get notifications list

            $notifications = Reciever::whereHas('notification')
                ->with(['notification' => function ($q) {
                    $q->select('id', 'photo', 'title', 'content');
                }])->where('actor_id', $user->id)
                ->unseenForUser()
                ->paginate(PAGINATION_COUNT);

            $notifications = new NotificationsResource($notifications);

            return $this->returnData('notifications', $notifications);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function MarknotificationsAsSeen(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "notification_id" => "required|exists:admin_notifications_receivers,id"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $user = $this->auth('user-api');
            if (!$user) {
                return $this->returnError('E001', trans('messages.There is no user with this id'));
            }

            Reciever::where('id', $request->notification_id)->update(['seen' => '1']);

            return $this->returnSuccessMessage('');

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
}
