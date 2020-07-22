<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationsResource;
use App\Http\Resources\SingleNotificationResource;
use App\Mail\AcceptReservationMail;
use App\Mail\RejectReservationMail;
use App\Models\CommentReport;
use App\Models\Doctor;
use App\Models\PromoCode;
use App\Models\Reason;
use App\Models\Reciever;
use App\Models\ServiceReservation;
use App\Models\Ticket;
use App\Models\Replay;
use App\Models\Provider;
use App\Models\ReportingType;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Token;
use App\Models\UserAttachment;
use App\Models\UserRecord;
use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use App\Traits\OdooTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\ProviderTrait;
use App\Mail\NewReplyMessageMail;
use App\Mail\NewUserMessageMail;
use Illuminate\Support\Facades\DB;
use Validator;
use Auth;
use Mail;
use JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use DateTime;

class ProviderController extends Controller
{
    use ProviderTrait, GlobalTrait, DoctorTrait, SMSTrait, OdooTrait;

    public function __construct(Request $request)
    {

        //
    }

    public function featuredProviders(Request $request)
    {
        $validation = $this->checkValidationFields($request->specification_id, '', '', '',
            '', '');

        if (isset($request->specification_id) && $request->specification_id != 0) {
            if ($validation->specification_found == 0)
                return $this->returnError('D000', trans('messages.There is no specification with this id'));
        }
        $user = null;
        if ($request->api_token)
            $user = User::where('api_token', $request->api_token)->first();
        $providers = $this->getProvidersFeaturedBranch(($user != null ? $user->id : null), ($user != null && strlen($user->longitude) > 6 ? $user->longitude : $request->longitude), ($user != null && strlen($user->latitude) > 6 ? $user->latitude : $request->latitude),
            0);

        if ($providers->count() > 0) {
            $providers = $this->addProviderNameToCollectionResults($providers);
            $collection = collect($providers);
            $filtered = $collection->filter(function ($provider, $key) {
                $provider->favourite = count($provider->favourites) > 0 ? 1 : 0;
                $provider->distance = (string)number_format($provider->distance * 1.609344, 2);
                $provider->has_home_services = $provider->homeServices()->count() > 0 ? 1 : 0;
                $provider->has_clinic_services = $provider->clinicServices()->count() > 0 ? 1 : 0;
                unset($provider->favourites);
                // branches that its featured time passes must not return
                $to = \Carbon\Carbon::now('Asia/Riyadh');
                $from = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', $provider->subscriptions->created_at);
                $diff_in_days = $to->diffInDays($from);
                return $diff_in_days <= $provider->subscriptions->duration;
            });

            $providers = array_values($filtered->all());

            return $this->returnData('featured_providers', $providers);
        }

        return $this->returnError('E001', trans('messages.No data founded'));
    }

    public function branchesByProviderID(Request $request, $id = 0)
    {
        try {

            $validator = Validator::make($request->all(), [
                "rate" => "boolean",
                "type_id" => "array"
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = null;
            if ($request->api_token)
                $user = User::where('api_token', $request->api_token)->first();

            $order = (isset($request->order) && strtolower($request->order) == "desc") ? "DESC" : "ASC";
            $validation = $this->checkValidationFields('', '', '', $request->type_id);
            if (is_array($request->type_id) && count($request->type_id) > 0) {
                if (count($request->type_id) != $validation->type_found)
                    return $this->returnError('D000', trans('messages.There is no type with this id'));
            }

            if (isset($request->nearest_date) && $request->nearest_date != 0) {
                $nearest_date = $request->nearest_date;
                if (isset($request->specification_id) && $request->specification_id != 0) {
                    $specification_id = $request->specification_id;
                    $providers = $this->getSortedByDoctorDates(($user != null ? $user->id : null), ($user != null && strlen($user->longitude) > 6 ? $user->longitude : $request->longitude), ($user != null && strlen($user->latitude) > 6 ? $user->latitude : $request->latitude),
                        $order, $request->rate, $request->type_id, $nearest_date, $specification_id);

                    $providers1 = $providers[0];
                    if (count($providers1->toArray()) > 0) {
                        $providers = $providers[1];
                        if (!empty($providers) && count($providers) > 0) {
                            $providers = $this->addProviderNameToresults($providers);
                        }
                        // used to get branches by main provider id
                        if ($id != 0) {
                            return $providers->where('provider_id', $id);
                        }
                    }
                } else
                    return $this->returnError('E001', trans('messages.you must choose doctor specification'));
            } else {
                $providers = $this->getProvidersBranch(($user != null ? $user->id : null), ($user != null && strlen($user->longitude) > 6 ? $user->longitude : $request->longitude), ($user != null && strlen($user->latitude) > 6 ? $user->latitude : $request->latitude),
                    $order, $request->rate, $request->type_id);

                if (count($providers->toArray()) > 0) {
                    $providers->getCollection()->each(function ($provider) {
                        $provider->favourite = count($provider->favourites) > 0 ? 1 : 0;
                        $provider->distance = (string)number_format($provider->distance * 1.609344, 2);
                        unset($provider->favourites);
                        return $provider;
                    });

                    // used to get branches by main provider id
                    if ($id != 0) {
                        return $providers->where('provider_id', $id);
                    }
                }
            }
            return $this->returnError('E001', trans('messages.No data founded'));
        } catch (\Exception $ex) {
            return $this->returnError('E1' . $ex->getCode(), $ex->getMessage());
        }
    }


    protected function addProviderNameToresults($providersJson)
    {
        foreach ($providersJson->data as $key => $branch) {
            $branche = Provider::find($branch->id);
            $provider = $branche->provider()->select('name_' . app()->getLocale() . ' as name', 'logo')->first();
            //set provider name to results
            $branch->provider = $provider;
        }
        return $providersJson;
    }

    protected function addProviderNameToCollectionResults($providers)
    {
        foreach ($providers as $key => $branch) {
            $branche = Provider::find($branch->id);
            $provider = $branche->provider()->select('name_' . app()->getLocale() . ' as name', 'logo')->first();
            //set provider name to results
            $branch->provider = $provider;
        }
        return $providers;
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "device_token" => "sometimes|nullable|max:255",
                "web_token" => "sometimes|nullable|max:255",
                "password" => "required|max:255",
                "mobile" => array(
                    "required",
                    "numeric",
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                ),
                "username" => "required|string|max:100|unique:providers,username",
                "agreement" => "required|boolean",
                "email" => "email|max:255|unique:managers,email",
                "commercial_no" => "required|unique:providers,commercial_no",
                "type_id" => "required|numeric|min:1",
                "city_id" => "required|exists:cities,id",
                "district_id" => "required|exists:districts,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $validation = $this->validateFields(['mobile' => $request->mobile, 'email' => $request->email, 'city_id' => $request->city_id,
                'district_id' => $request->district_id, 'typeId' => [$request->type_id]]);

            $exists = $this->checkIfMobileExistsForOtherProviders($request->mobile);
            if ($exists) {
                return $this->returnError('D000', trans("messages.phone number used before"));
            }

            /*if($validation->mobile_found != 0)
                return $this->returnError('E003', trans('messages.This provider already exists'));*/

            if (isset($request->email)) {
                if ($validation->email_found != 0)
                    return $this->returnError('E007', trans('messages.This provider already exists'));
            }
            if (!$request->agreement)
                return $this->returnError('E006', trans('messages.Agreement is required'));
            /*
                        if (isset($request->city_id) && $validation->city_found == 0)
                            return $this->returnError('D000', trans('messages.Invalid city_id'));*/
            /*
                        if (isset($request->district_id) && $validation->district_found == 0)
                            return $this->returnError('D000', trans('messages.Invalid district_id'));*/

            if (isset($request->type_id) && $validation->type_found == 0)
                return $this->returnError('D000', trans('messages.Invalid type_id'));

            DB::beginTransaction();
            try {

                $activationCode = (string)rand(1000, 9999);
                $fileName = "";
                if (isset($request->logo) && !empty($request->logo)) {
                    $fileName = $this->saveImage('providers', $request->logo);
                }

                $android_device_hasCode = '';
                if ($request->has('android_device_hasCode') && $request->android_device_hasCode != null && $request->android_device_hasCode != '') {
                    $android_device_hasCode = $request->android_device_hasCode;
                }

                $provider = Provider::create([
                    'name_en' => trim($request->name_en),
                    'name_ar' => trim($request->name_ar),
                    'username' => trim($request->username),
                    'password' => $request->password,
                    'mobile' => $request->mobile,
                    'longitude' => $request->longitude,
                    'latitude' => $request->latitude,
                    'commercial_no' => $request->commercial_no,
                    'logo' => $fileName,
                    'status' => 0,
                    'activation' => 0,
                    'device_token' => $request->has('device_token') ? $request->device_token : null,
                    'web_token' => $request->has('web_token') ? $request->web_token : null,
                    'email' => $request->email,
                    'address' => trim($request->address),
                    'type_id' => $request->type_id,
                    'city_id' => $request->city_id,
                    'district_id' => $request->district_id,
                    'api_token' => '',
                    'activation_code' => $activationCode,
                    'android_device_hasCode' => $android_device_hasCode,
                ]);

                // save user  to odoo erp system
                /*  $odoo_provider_id = $this->saveProviderToOdoo($provider->mobile, $provider->username);
                  $provider->update(['odoo_provider_id' => $odoo_provider_id]);*/

                $deviceHash = $provider->android_device_hasCode === null ? '' : $provider->android_device_hasCode;
                $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
                $this->sendSMS($provider->mobile, $message);
                $provider->name = $provider->getTranslatedName();
                $provider->makeVisible(['activation_code', 'activation', 'status', 'name_en', 'name_ar']);
                DB::commit();
                //$this->authProviderByEmail($request->email, $request->password);
//              return   $this->authProviderByMobile($request->mobile, $request->password ,1);
                return $this->returnData('provider', json_decode(json_encode($this->authProviderByUserName($request->username, $request->password), JSON_FORCE_OBJECT)));
            } catch (\Exception $ex) {
                DB::rollback();
            }
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function forgetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "mobile" => array(
                    "required",
                    "numeric",
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/",
                    "exists:providers,mobile"
                ),
                "type" => "required|in:0,1"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $provider = $this->getProviderByMobileOrEmailOrID($request->mobile, '', '', $request->type);
            if (!$provider) {
                return $this->returnError('E001', trans('messages.No provider with this id'));
            }
            DB::beginTransaction();
            try {
                $activationCode = (string)rand(1000, 9999);
                $deviceHash = $provider->android_device_hasCode === null ? '' : $provider->android_device_hasCode;
                $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
                $this->sendSMS(!empty($request->mobile) ? $request->mobile : $provider->mobile, $message);

                $provider->update([
                    'activation_code' => $activationCode,
                    //'activation' => 0,
                ]);
                DB::commit();

                if ($provider->api_token == null or $provider->api_token == '' or !$provider->api_token) {
                    $tempToken = $this->getRandomString(250);
                    $provider->update(['api_token' => $tempToken]);
                }
                return $this->returnData('provider', json_decode(json_encode($provider, JSON_FORCE_OBJECT)), trans('messages.confirm code send'));

            } catch (\Exception $ex) {
                DB::rollback();
                return $this->returnError($ex->getCode(), $ex->getMessage());
            }

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }

    }


    function getRandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        $chkCode = Provider::where('api_token', $string)->first();
        if ($chkCode) {
            $this->getRandomString(250);
        }
        return $string;
    }

    public function resetPassword(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "password" => "required|max:255|confirmed",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->getData($request->api_token);
            if ($provider == null)
                $provider = $this->getDataByLastToken($request->api_token);
            if ($provider == null)
                return $this->returnError('E001', trans('messages.Provider not found'));

            DB::beginTransaction();

            try {
                $provider->update([
                    'password' => $request->password,
                ]);

                DB::commit();
                return $this->returnSuccessMessage(trans('messages.password reset Successfully'));
            } catch (\Exception $ex) {
                DB::rollback();
                return $this->returnError($ex->getCode(), $ex->getMessage());
            }
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
            $provider = $this->getProviderByID($request->id);
            if ($provider != null)
                return $this->returnData('provider', json_decode(json_encode($provider, JSON_FORCE_OBJECT)));
            return $this->returnError('E001', trans('messages.No provider with this id'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function prepare_update_provider_profile(Request $request)
    {
        try {
            $provider = $this->auth('provider-api');
            $provider_relation = Provider::with(['city' => function ($city) {
                $city->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'district' => function ($distric) {
                $distric->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])->find($provider->id);

            $provider_relation->makeHidden(['provider_has_bill', 'adminprices', 'longitude', 'latitude', 'email', 'address', 'street', 'provider_id', 'branch_no', 'paid_balance', 'unpaid_balance', 'rate', 'hide', 'parent_type'])->toArray();
            $provider_relation->makeVisible(['type_id']);

            if (!$provider_relation) {
                return $this->returnError('D000', trans('messages.User not found'));
            }
            return $this->returnData('provider', json_decode(json_encode($provider_relation, JSON_FORCE_OBJECT)));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function update_provider_profile(Request $request)
    {
        $provider = $this->auth('provider-api');

        if (!$provider) {
            return $this->returnError('D000', trans('messages.User not found'));
        }

        //main provider
        if ($provider->provider_id == null) {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "username" => 'required|string|max:100|unique:providers,username,' . $provider->id . ',id',
                "commercial_no" => 'required|unique:providers,commercial_no,' . $provider->id,
                "password" => "max:255",
                "old_password" => "required_with:password",
                "city_id" => "required|exists:cities,id",
                "district_id" => "required|exists:districts,id",
                "mobile" => array(
                    "required",
                    "numeric",
                    //   Rule::unique('providers', 'mobile')->ignore($provider->id),
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                )
            ]);
        } else {
            //branch
            $validator = Validator::make($request->all(), [
                "password" => "required|max:255",
                "old_password" => "required_with:password",
                "username" => 'required|string|max:100|unique:providers,username,' . $provider->id . ',id',
                "mobile" => array(
                    "required",
                    "numeric",
                    // Rule::unique('providers', 'mobile')->ignore($provider->id),
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                )
            ]);
        }
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        DB::beginTransaction();

        if (isset($request->mobile)) {
            if ($provider->provider_id != null) {  //branch
                $exists = $this->checkIfMobileExistsForOtherBranches($request->mobile);
                if ($exists) {
                    $proMobile = Provider::whereNotNull('provider_id')->where('mobile', $request->mobile)->first();
                    if ($proMobile->id != $provider->id)
                        return $this->returnError('D000', trans("messages.phone number used before"));
                }
            }
            if ($provider->provider_id == null) {  //main provider
                $exists = $this->checkIfMobileExistsForOtherProviders($request->mobile);
                if ($exists) {
                    $proMobile = Provider::where('provider_id', null)->where('mobile', $request->mobile)->first();
                    if ($proMobile->id != $provider->id)
                        return $this->returnError('D000', trans("messages.phone number used before"));
                }
            }

            if ($request->mobile != $provider->mobile) {
                $activationCode = (string)rand(1000, 9999);
                $deviceHash = $provider->android_device_hasCode === null ? '' : $provider->android_device_hasCode;
                $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
                $this->sendSMS(!empty($request->mobile) ? $request->mobile : $provider->mobile, $message);
                $provider->update([
                    'mobile' => !empty($request->mobile) ? $request->mobile : $provider->mobile,
                    'activation' => 0,
                    'activation_code' => $activationCode,
                ]);
            }
        }
        $fileName = $provider->logo;
        if (isset($request->logo) && !empty($request->logo)) {
            $fileName = $this->saveImage('providers', $request->logo);
        }
        $commercial_no = $request->commercial_no ? $request->commercial_no : $provider->commercial_no;
        if ($request->password) {

            //check for old password
            if (Hash::check($request->old_password, $provider->password)) {
                $provider->update([
                    'name_en' => $request->name_en ? $request->name_en : $provider->name_en,
                    'name_ar' => $request->name_ar ? $request->name_ar : $provider->name_ar,
                    "username" => $request->username,
                    'commercial_no' => $commercial_no,
                    "city_id" => $request->city_id,
                    "district_id" => $request->district_id,
                    'password' => $request->password,
                    'logo' => $fileName,
                ]);
            } else {

                return $this->returnError('E002', trans('messages.invalid old password'));
            }

        } else {
            $provider->update([
                'name_en' => $request->name_en ? $request->name_en : $provider->name_en,
                'name_ar' => $request->name_ar ? $request->name_ar : $provider->name_ar,
                "username" => $request->username,
                'commercial_no' => $commercial_no,
                "city_id" => $request->city_id,
                "district_id" => $request->district_id,
                'logo' => $fileName,
            ]);
        }

        $provider->makeVisible(['activation', 'status']);
        //update all brnaches with provider logo
        $provider->providers()->update(['logo' => $fileName]);
        DB::commit();
        return $this->returnData('provider', json_decode(json_encode($provider, JSON_FORCE_OBJECT)),
            trans('messages.Provider data updated successfully'));

        // $provider = $this->getAllData($provider->api_token, $activation);

    }

    // for front end api
    public function update_provider_profile_general(Request $request)
    {
        $provider = $this->auth('provider-api');

        if (!$provider) {
            return $this->returnError('D000', trans('messages.User not found'));
        }

        //main provider
        if ($provider->provider_id == null) {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "username" => 'required|string|max:100|unique:providers,username,' . $provider->id . ',id',
                "commercial_no" => 'required|unique:providers,commercial_no,' . $provider->id,
                "provider_type" => 'required|numeric|exists:provider_types,id',
                "city_id" => "required|exists:cities,id",
                "district_id" => "required|exists:districts,id",
            ]);
        } else
            return $this->returnError('D000', trans('messages.User not found'));


        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        DB::beginTransaction();


        $fileName = $provider->logo;
        if (isset($request->logo) && !empty($request->logo)) {
            $fileName = $this->saveImage('providers', $request->logo);
        }
        $commercial_no = $request->commercial_no ? $request->commercial_no : $provider->commercial_no;

        $provider->update([
            'name_en' => $request->name_en ? $request->name_en : $provider->name_en,
            'name_ar' => $request->name_ar ? $request->name_ar : $provider->name_ar,
            "username" => $request->username,
            'commercial_no' => $commercial_no,
            'logo' => $fileName,
            'type_id' => $request->provider_type,
            "city_id" => $request->city_id,
            "district_id" => $request->district_id,
        ]);

        $provider->makeVisible(['activation', 'status']);
        //update all brnaches with provider logo
        $provider->providers()->update(['logo' => $fileName]);
        DB::commit();


        $provider_relation = Provider::with(['city' => function ($city) {
            $city->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'district' => function ($distric) {
            $distric->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }])->find($provider->id);

        $provider_relation->makeHidden(['provider_has_bill', 'adminprices', 'longitude', 'latitude', 'email', 'address', 'street', 'provider_id', 'branch_no', 'paid_balance', 'unpaid_balance', 'rate', 'hide', 'parent_type'])->toArray();
        $provider_relation->makeVisible(['type_id']);

        return $this->returnData('provider', json_decode(json_encode($provider_relation, JSON_FORCE_OBJECT)),
            trans('messages.Provider data updated successfully'));
    }

    public function update_provider_profile_mobile(Request $request)
    {
        $provider = $this->auth('provider-api');

        if (!$provider) {
            return $this->returnError('D000', trans('messages.User not found'));
        }

        $validator = Validator::make($request->all(), [
            "current_mobile" => "required",
            "mobile" => array(
                "required",
                "numeric",
                // Rule::unique('providers', 'mobile')->ignore($provider->id),
                "digits_between:8,10",
                "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
            )
        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        if ($provider->mobile != $request->current_mobile) {
            return $this->returnError('D000', trans('messages.Current Mobile Not Correct'));
        }

        if ($request->mobile == $request->current_mobile) {
            return $this->returnError('D000', trans('messages.Current And New Mobile Is Same'));
        }

        if ($provider->provider_id != null) {  //branch
            $exists = $this->checkIfMobileExistsForOtherBranches($request->mobile);
            if ($exists) {
                $proMobile = Provider::whereNotNull('provider_id')->where('mobile', $request->mobile)->first();
                if ($proMobile->id != $provider->id)
                    return $this->returnError('D000', trans("messages.phone number used before"));
            }

        }

        if ($provider->provider_id == null) {  //main provider
            $exists = $this->checkIfMobileExistsForOtherProviders($request->mobile);
            if ($exists) {
                $proMobile = Provider::where('provider_id', null)->where('mobile', $request->mobile)->first();
                if ($proMobile->id != $provider->id)
                    return $this->returnError('D000', trans("messages.phone number used before"));
            }
        }

        $activationCode = (string)rand(1000, 9999);
        $deviceHash = $provider->android_device_hasCode === null ? '' : $provider->android_device_hasCode;
        $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
        $this->sendSMS(!empty($request->mobile) ? $request->mobile : $provider->mobile, $message);
        $provider->update([
            'mobile' => !empty($request->mobile) ? $request->mobile : $provider->mobile,
            'activation' => 0,
            'activation_code' => $activationCode,
        ]);

        DB::beginTransaction();

        $provider_relation = Provider::with(['city' => function ($city) {
            $city->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'district' => function ($distric) {
            $distric->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }])->find($provider->id);
        $provider_relation->makeVisible(['activation', 'status']);
        $provider_relation->makeHidden(['provider_has_bill', 'adminprices', 'longitude', 'latitude', 'email', 'address', 'street', 'provider_id', 'branch_no', 'paid_balance', 'unpaid_balance', 'rate', 'hide', 'parent_type'])->toArray();
        $provider_relation->makeVisible(['type_id']);

        DB::commit();
        return $this->returnData('provider', json_decode(json_encode($provider_relation, JSON_FORCE_OBJECT)),
            trans('messages.Provider data updated successfully'));
    }

    public function update_provider_profile_password(Request $request)
    {
        $provider = $this->auth('provider-api');
        if (!$provider) {
            return $this->returnError('D000', trans('messages.User not found'));
        }

        $validator = Validator::make($request->all(), [
            "password" => "required|max:255",
            "old_password" => "required|max:225",
        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        try {
            //check for old password
            if (Hash::check($request->old_password, $provider->password)) {
                $provider->update([
                    'password' => $request->password,
                ]);
                return $this->returnSuccessMessage(trans('messages.password reset Successfully'));
            } else {
                return $this->returnError('E002', trans('messages.invalid old password'));
            }
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getProviderDoctors(Request $request)
    {  // main provider
        $validator = Validator::make($request->all(), [
            "id" => "required|numeric",
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        $validation = $this->validateFields(['specification_id' => $request->specification_id, 'nickname_id' => $request->nickname_id,
            'provider_id' => $request->provider_id, 'branch' => ['main_provider_id' => $request->id, 'provider_id' => $request->provider_id, 'branch_no' => 0]]);

        $provider = $this->getProviderByID($request->id);

        if ($provider != null) {
            if ($provider->provider_id != null) {
                $request->provider_id = 0;
                $branchesIDs = [$provider->id];
            } else {
                $branchesIDs = $provider->providers()->pluck('id');
            }

            if (count($branchesIDs) > 0) {
                if (isset($request->specification_id) && $request->specification_id != 0) {
                    if ($validation->specification_found == null)
                        return $this->returnError('D000', trans('messages.There is no specification with this id'));
                }
                if (isset($request->nickname_id) && $request->nickname_id != 0) {
                    if ($validation->nickname_found == null)
                        return $this->returnError('D000', trans('messages.There is no nickname with this id'));
                }
                if (isset($request->provider_id) && $request->provider_id != 0) {
                    if ($validation->provider_found == null)
                        return $this->returnError('D000', trans('messages.There is no branch with this id'));

                    if ($validation->branch_found)
                        return $this->returnError('D000', trans("messages.This branch isn't in your branches"));
                }
                if (isset($request->gender) && $request->gender != 0 && !in_array($request->gender, [1, 2])) {
                    return $this->returnError('D000', trans("messages.This is invalid gender"));
                }

                $front = $request->has('show_front') ? 1 : 0;
                $doctors = $this->getDoctors($branchesIDs, $request->specification_id, $request->nickname_id, $request->provider_id, $request->gender, $front);

                if (count($doctors) > 0) {
                    foreach ($doctors as $key => $doctor) {

                        $doctor->time = "";
                        $days = $doctor->times;
                        $match = $this->getMatchedDateToDays($days);

                        if (!$match || $match['date'] == null) {
                            $doctor->time = new \stdClass();;
                            continue;
                        }
                        $doctorTimesCount = $this->getDoctorTimePeriodsInDay($match['day'], $match['day']['day_code'], true);
                        $availableTime = $this->getFirstAvailableTime($doctor->id, $doctorTimesCount, $days, $match['date'], $match['index']);
                        $doctor->time = $availableTime;


                        $doctor->branch_name = Doctor::find($doctor->id)->provider->{'name_' . app()->getLocale()};
                    }
                    $total_count = $doctors->total();
                    $doctors->getCollection()->each(function ($doctor) {
                        $doctor->makeVisible(['name_en', 'name_ar', 'information_en', 'information_ar']);
                        return $doctor;
                    });


                    $doctors = json_decode($doctors->toJson());
                    $doctorsJson = new \stdClass();
                    $doctorsJson->current_page = $doctors->current_page;
                    $doctorsJson->total_pages = $doctors->last_page;
                    $doctorsJson->total_count = $total_count;
                    $doctorsJson->data = $doctors->data;
                    return $this->returnData('doctors', $doctorsJson);
                }
                return $this->returnData('doctors', $doctors);
            }
            return $this->returnError('E001', trans('messages.There are no branches for this provider'));
        }
        return $this->returnError('E001', trans('messages.There is no provider with this id'));

    }

    public
    function getProviderTypes()
    {
        try {
            $types = $this->getAllProviderTypes();
            if (count($types) > 0)
                return $this->returnData('types', $types);

            return $this->returnError('E001', trans('messages.There are no provider types found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function activateAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "activation_code" => "required|max:255",
                "api_token" => "required",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->getData($request->api_token);

            if ($provider == null)
                $provider = $this->getDataByLastToken($request->api_token);
            if ($provider == null)
                return $this->returnError('E001', trans('messages.Provider not found'));

            //if ($provider->activation)
            //  return $this->returnError('E0103', trans("messages.This provider already activated"));

            if ($provider->activation_code != $request->activation_code)
                return $this->returnError('E001', trans('messages.This code is not valid please enter it again'));

            $provider->activation = 1;
            // $provider->status = 0;   // need to approved by admin
            $provider->update();
            $provider->name = $provider->getTranslatedName();
            $provider->makeVisible(['api_token', 'activation', 'status', 'name_en', 'name_ar']);
            if ($provider->status == 0 && $provider->provider_id == null)
                return $this->returnData('provider', json_decode(json_encode($provider, JSON_FORCE_OBJECT)), app('settings')->{'approve_message_' . app()->getLocale()});
            else
                return $this->returnData('provider', json_decode(json_encode($provider, JSON_FORCE_OBJECT)), ' تم تفعيل  رقم الهاتف بنجاح ');

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "web_token" => "sometimes|nullable|max:255",
            "device_token" => "sometimes|nullable|max:255",
            "password" => "required|max:255",
            //"mobile" => "required|numeric",
            "username" => "required",
            "type" => "required|in:0,1"
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        //$provider = $this->authProviderByMobile($request->mobile, $request->password, $request->type);
        $provider = $this->authProviderByUserName($request->username, $request->password, $request->type);
        $user = auth()->user();

        if (!$request->has('web_token')) {
            if (!$request->filled('device_token')) {
                return $this->returnError('E001', 'لابد من  ارسال  توكن الموبيل ');
            }
        }

        if ($provider != null) {
            if (($provider->activation == '0' or $provider->activation == 0) && $provider->provider_id == null) { // only main provider needed to activate account
                DB::beginTransaction();
                $activationCode = (string)rand(1000, 9999);
                $provider->activation_code = $activationCode;
                $provider->activation = 0;
                $deviceHash = $provider->android_device_hasCode === null ? '' : $provider->android_device_hasCode;
                $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
                $this->sendSMS($provider->mobile, $message);
                if ($request->device_token != null) {
                    $provider->device_token = $request->device_token;
                }
                if ($request->web_token != null) {
                    $provider->web_token = $request->web_token;
                }
                if ($request->has('android_device_hasCode')) {
                    $provider->android_device_hasCode = $request->android_device_hasCode;
                }
                $provider->update();
                $provider->name = $provider->getTranslatedName();
                DB::commit();
            } elseif (($provider->status == '0' or $provider->status == 0) && $provider->provider_id == null) {
                if ($request->device_token != null) {
                    $provider->device_token = $request->device_token;
                }
                if ($request->web_token != null) {
                    $provider->web_token = $request->web_token;
                }

                $android_device_hasCode = '';
                if ($request->has('android_device_hasCode') && $request->android_device_hasCode != null && $request->android_device_hasCode != '') {
                    $android_device_hasCode = $request->android_device_hasCode;
                }

                $provider->android_device_hasCode = $android_device_hasCode;
                $provider->update();

                return $this->returnError('E001', app('settings')->{'approve_message_' . app()->getLocale()});
            } else {
                DB::beginTransaction();
                if ($request->device_token != null) {
                    $provider->device_token = $request->device_token;
                }
                if ($request->web_token != null) {
                    $provider->web_token = $request->web_token;
                }
                if ($request->has('android_device_hasCode')) {
                    $provider->android_device_hasCode = $request->android_device_hasCode;
                }
                $provider->update();
                DB::commit();
            }
            $provider->makeVisible(['activation_code', 'activation', 'status', 'name_en', 'name_ar']);

            return $this->returnData('provider', json_decode(json_encode($provider, JSON_FORCE_OBJECT)));
        }
        return $this->returnError('E001', trans('messages.No result, please check your registration before'));
    }

    public
    function resendActivation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "api_token" => "required"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->getData($request->api_token);
            if ($provider == null)
                return $this->returnError('E001', trans('messages.Provider not found'));

            if ($provider->activation)
                return $this->returnError('E0103', trans("messages.This provider already activated"));

            if ($provider->no_of_sms == 3)
                return $this->returnError('E001', trans('messages.You exceed the limit of resending activation messages'));

            // resend code again
            $activationCode = (string)rand(1000, 9999);
            $provider->activation_code = $activationCode;
            $provider->no_of_sms = ($provider->no_of_sms + 1);
            $provider->update();
            $provider->name = $provider->getTranslatedName();
            $provider->makeVisible(['activation_code', 'activation', 'status', 'name_en', 'name_ar']);
            $deviceHash = $provider->android_device_hasCode === null ? '' : $provider->android_device_hasCode;
            $message = trans('messages.Your Activation Code') . ' ' . $activationCode . ' ' . $deviceHash;
            $this->sendSMS($provider->mobile, $message);

            return $this->returnData('provider', json_decode(json_encode($provider, JSON_FORCE_OBJECT)));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function reportingComment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "reservation_no" => "required|max:191",
            "reporting_type_id" => "required",
        ]);

        $provider = $this->auth('provider-api');
        if ($provider == null)
            return $this->returnError('E001', trans("messages.Provider not found"));

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

        CommentReport::create(['provider_id' => $provider->id, 'reservation_no' => $request->reservation_no, 'reporting_type_id' => $request->reporting_type_id]);
        return $this->returnSuccessMessage(trans('messages.Comment Reported  successfully'));
    }

    public
    function getCurrentReservations()
    {
        try {
            $provider = $this->auth('provider-api');
            $provider->makeVisible(['application_percentage_bill']);
            $provider_has_bill = 0;
            if ($provider->provider_id == null) { // provider
                if (!is_numeric($provider->application_percentage_bill) || $provider->application_percentage_bill == 0) {
                    $provider_has_bill = 0;
                } else {
                    $provider_has_bill = 1;
                }
                $branches = $provider->providers()->pluck('id')->toArray();
                array_unshift($branches, $provider->id);

            } else {
                $branches = [$provider->id];
                $mainProv = Provider::find($provider->provider_id);

                if (!is_numeric($mainProv->application_percentage_bill) || $mainProv->application_percentage_bill == 0) {
                    $provider_has_bill = 0;
                } else {
                    $provider_has_bill = 1;
                }
            }

            $reservations = $this->AcceptedReservations($branches);
            $reservation_need_to_complete = $this->checkIfThereReservationsNeedToClosed(0, $provider->id);

            if (count($reservations->toArray()['data']) > 0) {
                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $end_status = 0;

                foreach ($reservations->data as $reservation) {   // toggle to know if provider has bill tax to apply
                    $reservation->provider_has_bill = $provider_has_bill;

                    $end_status = 0;
                    if (date('Y-m-d', strtotime($reservation->day_date)) <= date('Y-m-d')) {
                        $day_date = $reservation->day_date . ' ' . $reservation->from_time;
                        $reservation_date = date('Y-m-d H:i:s', strtotime($day_date));
                        $currentDate = date('Y-m-d H:i:s');
                        $fdate = $reservation_date;
                        $tdate = $currentDate;
                        $datetime1 = new DateTime($fdate);
                        $datetime2 = new DateTime($tdate);
                        $interval = $datetime1->diff($datetime2);
                        $days = $interval->format('%a');
                        if ($days >= 1) {// there are  24 and more hours between now and reservation date
                            $end_status = 1;  // 1-> mean need to close because 24 h  passed
                        } elseif ($days < 1) {  // no 24 hours between now and reservation date
                            if (date('Y-m-d', strtotime($reservation->day_date)) < date('Y-m-d')) {
                                $end_status = 2;  //  2-> mean the reservation date passed but not complete 24 hours
                            } else {  // reservation is today
                                if (date('H:i:s', strtotime($reservation->from_time)) < date('H:i:s')) {
                                    //date passed but not complete 24 hours
                                    $end_status = 2;
                                } else {
                                    $end_status = 0;
                                }
                            }
                        } else {
                            $end_status = 0;
                        }
                    } else {
                        $end_status = 0;
                    }
                    $reservation->end_status = $end_status;
                }

                $reservationsJson = new \stdClass();
                $reservationsJson->current_page = $reservations->current_page;
                $reservationsJson->total_pages = $reservations->last_page;
                $reservationsJson->total_count = $total_count;
                $reservationsJson->reservation_need_to_complete = $reservation_need_to_complete;
                $reservationsJson->data = $reservations->data;

                return $this->returnData('reservations', $reservationsJson);
            }
            return $this->returnData('reservations', $reservations);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getNewReservations()
    {
        try {
            $provider = $this->auth('provider-api');

            if ($provider->provider_id == null) { // provider
                $branches = $provider->providers()->pluck('id')->toArray();
                array_unshift($branches, $provider->id);
            } else {
                $branches = [$provider->id];
            }

            $reservations = $this->NewReservations($branches);

            if (count($reservations->toArray()['data']) > 0) {
                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $reservationsJson = new \stdClass();
                $reservationsJson->current_page = $reservations->current_page;
                $reservationsJson->total_pages = $reservations->last_page;
                $reservationsJson->total_count = $total_count;
                $reservationsJson->data = $reservations->data;

                return $this->returnData('reservations', $reservationsJson);
            }
            return $this->returnData('reservations', $reservations);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getNewReservationsBytype(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "type" => "required|in:home_services,clinic_services,doctor,offer,all",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $type = $request->type;
            $provider = $this->auth('provider-api');
            if ($provider->provider_id == null) { //main provider
                $branches = $provider->providers()->pluck('id')->toArray();
                array_unshift($branches, $provider->id);
            } else {
                $branches = [$provider->id];
            }

            $reservations = $this->NewReservationsByType($branches, $type);

            if (count($reservations->toArray()) > 0) {
                $reservations->getCollection()->each(function ($reservation) use ($request) {
                    $reservation->makeHidden(['order', 'rejected_reason_type', 'reservation_total', 'admin_value_from_reservation_price_Tax', 'mainprovider', 'is_reported', 'branch_no', 'for_me', 'rejected_reason_notes', 'rejected_reason_id', 'is_visit_doctor', 'rejection_reason', 'user_rejection_reason']);
                    if ($request->type == 'home_services') {
                        $reservation->reservation_type = 'home_services';
                    } elseif ($request->type == 'clinic_services') {
                        $reservation->reservation_type = 'clinic_services';
                    } elseif ($request->type == 'doctor') {
                        $reservation->reservation_type = 'doctor';
                    } elseif ($request->type == 'offer') {
                        $reservation->reservation_type = 'offer';
                    } elseif ($request->type == 'all') {

                        $this->addReservationTypeToResult($reservation);
                        $reservation->makeHidden(["offer_id", "doctor_id", "service_id", "doctor_rate",
                            "service_rate",
                            "provider_rate",
                            "offer_rate",
                            "paid", "use_insurance",
                            "promocode_id",
                            "provider_id",
                            "branch_id", "rate_comment",
                            "rate_date",
                            "address", "latitude",
                            "longitude"]);

                        $reservation->doctor->makeHidden(['available_time', 'times']);
                        $reservation->provider->makeHidden(["provider_has_bill",
                            "has_insurance",
                            "is_lottery",
                            "rate_count"]);

                    } else {
                        $reservation->reservation_type = 'undefined';
                    }
                    return $reservation;
                });

                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $reservationsJson = new \stdClass();
                $reservationsJson->current_page = $reservations->current_page;
                $reservationsJson->total_pages = $reservations->last_page;
                $reservationsJson->total_count = $total_count;
                $reservationsJson->per_page = PAGINATION_COUNT;
                $reservationsJson->data = $reservations->data;

                return $this->returnData('reservations', $reservationsJson);
            }
            return $this->returnData('reservations', $reservations);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex);
        }
    }

    public
    function getCurrentReservationsBytype(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "type" => "required|in:home_services,clinic_services,doctor,offer,all",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $type = $request->type;
            $provider = $this->auth('provider-api');
            if ($provider->provider_id == null) { //main provider
                $branches = $provider->providers()->pluck('id')->toArray();
                array_unshift($branches, $provider->id);
            } else {
                $branches = [$provider->id];
            }

            $reservations = $this->currentReservationsByType($branches, $type);

            if (count($reservations->toArray()) > 0) {
                $reservations->getCollection()->each(function ($reservation) use ($provider, $request) {
                    $reservation->makeHidden(['order', 'rejected_reason_type', 'reservation_total', 'admin_value_from_reservation_price_Tax', 'mainprovider', 'is_reported', 'branch_no', 'for_me', 'rejected_reason_notes', 'rejected_reason_id', 'rejection_reason', 'user_rejection_reason']);
                    if ($request->type == 'home_services') {
                        $reservation->reservation_type = 'home_services';
                    } elseif ($request->type == 'clinic_services') {
                        $reservation->reservation_type = 'clinic_services';
                    } elseif ($request->type == 'offer') {
                        $reservation->reservation_type = 'offer';
                    } elseif ($request->type == 'doctor') {
                        $reservation->reservation_type = 'doctor';
                        $provider_has_bill = 0;
                        if ($provider->provider_id == null) { // provider
                            if (!is_numeric($provider->application_percentage_bill) || $provider->application_percentage_bill == 0) {
                                $provider_has_bill = 0;
                            } else {
                                $provider_has_bill = 1;
                            }

                        } else {
                            $branches = [$provider->id];
                            $mainProv = Provider::find($provider->provider_id);
                            if (!is_numeric($mainProv->application_percentage_bill) || $mainProv->application_percentage_bill == 0) {
                                $provider_has_bill = 0;
                            } else {
                                $provider_has_bill = 1;
                            }
                        }
                        $reservation->provider_has_bill = $provider_has_bill;
                    } elseif ($request->type == 'all') {

                        $this->addReservationTypeToResult($reservation);
                        if ($provider->provider_id == null) { // provider
                            if (!is_numeric($provider->application_percentage_bill) || $provider->application_percentage_bill == 0) {
                                $provider_has_bill = 0;
                            } else {
                                $provider_has_bill = 1;
                            }

                        } else {
                            $branches = [$provider->id];
                            $mainProv = Provider::find($provider->provider_id);
                            if (!is_numeric($mainProv->application_percentage_bill) || $mainProv->application_percentage_bill == 0) {
                                $provider_has_bill = 0;
                            } else {
                                $provider_has_bill = 1;
                            }
                        }
                        $reservation->provider_has_bill = $provider_has_bill;
                        $reservation->makeHidden(["offer_id", "doctor_id", "service_id", "doctor_rate",
                            "service_rate",
                            "provider_rate",
                            "offer_rate",
                            "paid", "use_insurance",
                            "promocode_id",
                            "provider_id",
                            "branch_id", "rate_comment",
                            "rate_date",
                            "address", "latitude",
                            "longitude"]);

                        $reservation->doctor->makeHidden(['available_time', 'times']);
                        $reservation->provider->makeHidden(["provider_has_bill",
                            "has_insurance",
                            "is_lottery",
                            "rate_count"]);

                    } else {
                        $reservation->reservation_type = 'undefined';
                    }

                    ############################## check if reservation passed by day ###########

//                    $reservation->provider_has_bill = $provider_has_bill;

                    $end_status = 0;
                    if (date('Y-m-d', strtotime($reservation->day_date)) <= date('Y-m-d')) {
                        $day_date = $reservation->day_date . ' ' . $reservation->from_time;
                        $reservation_date = date('Y-m-d H:i:s', strtotime($day_date));
                        $currentDate = date('Y-m-d H:i:s');
                        $fdate = $reservation_date;
                        $tdate = $currentDate;
                        $datetime1 = new DateTime($fdate);
                        $datetime2 = new DateTime($tdate);
                        $interval = $datetime1->diff($datetime2);
                        $days = $interval->format('%a');
                        if ($days >= 1) {// there are  24 and more hours between now and reservation date
                            $end_status = 1;  // 1-> mean need to close because 24 h  passed
                        } elseif ($days < 1) {  // no 24 hours between now and reservation date
                            if (date('Y-m-d', strtotime($reservation->day_date)) < date('Y-m-d')) {
                                $end_status = 2;  //  2-> mean the reservation date passed but not complete 24 hours
                            } else {  // reservation is today
                                if (date('H:i:s', strtotime($reservation->from_time)) < date('H:i:s')) {
                                    //date passed but not complete 24 hours
                                    $end_status = 2;
                                } else {
                                    $end_status = 0;
                                }
                            }
                        } else {
                            $end_status = 0;
                        }
                    } else {
                        $end_status = 0;
                    }
                    $reservation->end_status = $end_status;

                    #############################################################################
                    return $reservation;
                });

                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $reservationsJson = new \stdClass();
                $reservationsJson->current_page = $reservations->current_page;
                $reservationsJson->total_pages = $reservations->last_page;
                $reservationsJson->total_count = $total_count;
                $reservationsJson->per_page = PAGINATION_COUNT;
                $reservationsJson->data = $reservations->data;
                return $this->returnData('reservations', $reservationsJson);
            }
            return $this->returnData('reservations', $reservations);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public
    function AcceptReservation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_no" => "required|max:255",
                "price" => "numeric"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            DB::beginTransaction();
            $provider = $this->auth('provider-api');

            $reservation = $this->getReservationByNo($request->reservation_no, $provider->id);
            if ($reservation == null)
                return $this->returnError('D000', trans('messages.No reservation with this number'));

            if ($reservation->approved == 1)
                return $this->returnError('E001', trans('messages.Reservation already approved'));

            if ($reservation->approved == 2)
                return $this->returnError('E001', trans('messages.Reservation already rejected'));

            /*   if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                   (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                       strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))
               ) {

                   return $this->returnError('E001', trans("messages.You can't take action to a reservation passed"));
               }*/

            $ReservationsNeedToClosed = $this->checkIfThereReservationsNeedToClosed($request->reservation_no, $provider->id);

            if ($ReservationsNeedToClosed > 0) {
                return $this->returnError('AM01', trans("messages.there are reservations need to be closed first"));
            }

            if ($reservation->use_insurance) {
                if (!isset($request->price) || $request->price == 0 || empty($request->price))
                    return $this->returnError('E001', trans("messages.Price is required"));

                if ($reservation->price < $request->price)
                    return $this->returnError('E001', trans("messages.New price is larger than reservation price"));

                $reservation->update([
                    'price' => $request->price,
                    'approved' => 1
                ]);
            } else {
                $reservation->update([
                    'approved' => 1
                ]);
            }

            if ($reservation->user->email != null)
                Mail::to($reservation->user->email)->send(new AcceptReservationMail($reservation->reservation_no));
            DB::commit();
            try {
                $name = 'name_' . app()->getLocale();

                $bodyProvider = __('messages.approved user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";

                $bodyUser = __('messages.approved your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";

                //send push notification
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyProvider]))->sendProvider(Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id));

                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyUser]))->sendUser($reservation->user);

                //send mobile sms
//                $message = $bodyUser;

                if ($provider->provider_id != null) {
                    $message = __('messages.your_reservation_has_been_accepted_from') . ' ( ' . "{$provider->provider->$name}" . ' ) ' .
                        __('messages.branch') . ' ( ' . " {$provider->getTranslatedName()} " . ' ) ' . __('messages.if_you_wish_to_change_reservations');
                } else {
                    $message = __('messages.your_reservation_has_been_accepted_from') . ' ( ' . "{$provider->$name}" . ' ) ' .
                        __('messages.branch') . ' ( ' . " {$reservation->branch->$name} " . ' ) ' . __('messages.if_you_wish_to_change_reservations');
                }

                // $this->sendSMS($reservation->user->mobile, $message);

            } catch (\Exception $ex) {

            }
            return $this->returnSuccessMessage(trans('messages.Reservation approved successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public
    function getBalance()
    {
        try {
            $provider = $this->auth('provider-api');
            if ($provider->provider_id == null) {
                $branches = $provider->providers()->pluck('id')->toArray();
                $balance = $this->getProviderBalance($branches);
                $allBalance = $this->sumBalance($branches);
            } else {
                $balance = $this->getProviderBalance([$provider->id]);
                $allBalance = $this->sumBalance([$provider->id]);
            }
            if (count($balance->toArray()) > 0) {
                $total_count = $balance->total();
                $balance->getCollection()->each(function ($bala) {
                    $bala = $bala->makeVisible(['balance']);
                    $bala->balance = number_format((float)$bala->balance, 2, '.', '');
                    return $bala;
                });

                $balance = json_decode($balance->toJson());
                $balanceJson = new \stdClass();
                $balanceJson->current_page = $balance->current_page;
                $balanceJson->total_pages = $balance->last_page;
                $balanceJson->total_count = $total_count;
                $balanceJson->total_balance = number_format((float)$allBalance, 2, '.', '');
                $balanceJson->data = $balance->data;
                return $this->returnData('balances', $balanceJson);
            }
            return $this->returnError('E001', trans("messages.No balance founded"));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function completeReservation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "reservation_no" => "required|max:255",
            "arrived" => "required|in:0,1"
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        DB::beginTransaction();
        $provider = $this->auth('provider-api');
        $provider->makeVisible(['application_percentage', 'application_percentage_bill_insurance']);
        $reservation = $this->getReservationByNo($request->reservation_no, $provider->id);
        if ($reservation == null)
            return $this->returnError('D000', trans('messages.No reservation with this number'));

        if ($reservation->approved == 3)
            return $this->returnError('E001', trans('messages.Reservation already Completed'));

        if ($reservation->approved == 2)
            return $this->returnError('E001', trans('messages.Reservation already rejected'));

        $complete = $request->arrived == 1 ? 1 : 0;

        if ($complete == 1) {
            $reservation->update([
                'approved' => 3,
                'is_visit_doctor' => $complete
            ]);

            $totalBill = 0;
            $comment = " نسبة ميدكال كول من كشف حجز نقدي";
            $invoice_type = 0;
            $mainProv = Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id);
            if (!is_numeric($mainProv->application_percentage_bill) || $mainProv->application_percentage_bill == 0) {
                $provider_has_bill = 0;
            } else {
                $provider_has_bill = 1;

            }

            if (!is_numeric($mainProv->application_percentage_bill_insurance) || $mainProv->application_percentage_bill_insurance == 0) {
                $provider_has_bill_insurance = 0;
            } else {
                $provider_has_bill_insurance = 1;
            }

            // get bill total only if discount apply to this provider  on bill and the reservation without coupons "bill case"
            if ($provider_has_bill == 1 && $reservation->promocode_id == null && $reservation->use_insurance == 0) {
                if (!$request->has('bill_total')) {
                    if ($request->bill_total <= 0) {
                        return $this->returnError('E001', trans('messages.Must add Bill Total'));
                    } else {
                        $totalBill = $request->bill_total;
                    }
                }
            }

            // get bill total only if discount apply to this provider  on insurance_bill and the reservation without coupons "bill case"
            if ($provider_has_bill_insurance == 1 && $reservation->promocode_id == null && $reservation->use_insurance == 1) {
                if (!$request->has('bill_total')) {
                    if ($request->bill_total <= 0) {
                        return $this->returnError('E001', trans('messages.Must add Bill Total'));
                    } else {
                        $totalBill = $request->bill_total;
                    }
                }
            }
            $reservation->update([
                'approved' => 3,
                'bill_total' => $request->bill_total,
                //'discount_type'   =>  $discountType
            ]);

            $data = [];
            $promoCode = PromoCode::find($reservation->promocode_id);
            if ($reservation->promocode_id == null /*or (isset($promoCode) && $promoCode->coupons_type_id == 2)*/) { //change balance here
                // Calculate the balance if reservation without any coupon
                $this->calculateBalance($provider, $reservation->payment_method_id, $reservation, $request);
            }

            $manager = $this->getAppInfo();
            $mainprov = Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id);
            $mainprov->makeVisible(['application_percentage_bill', 'application_percentage', 'application_percentage_bill_insurance']);

            // save odoo invoice with details  to odoo erp system on case cash "note uptill now only cash payment allowed "

            if ($reservation->use_insurance == 1 && $reservation->promocode_id == null) {
                $data['payment_term'] = 5;
                $data['sales_account'] = 580;
                $comment = "  نسبة ميدكال كول من  فاتورة حجز نقدي بتأمين   ";
                $invoice_type = 2;   // with insurance
                $data['product_id'] = 4;
                $data['total_amount'] = ($provider_has_bill == 1 or $provider_has_bill_insurance == 1) && ($reservation->promocode_id == null) ? $request->bill_total : $reservation->price;
                $data['MC_percentage'] = $reservation->use_insurance == 0 ? $mainprov->application_percentage_bill + $mainprov->application_percentage : $mainprov->application_percentage_bill_insurance + $mainprov->application_percentage;
                $data['invoice_type_id'] = $invoice_type;
                $data['cost_center_id'] = 510;
                $data['origin'] = $reservation->reservation_no;
                $data['comment'] = $comment;
                $data['sales_journal'] = 1;
                $data['Receivables_account'] = 8;
            } elseif ($reservation->use_insurance == 0 && $reservation->promocode_id == null) {
                $data['payment_term'] = 4; //edit
                $data['sales_account'] = 19;
                $comment = "  نسبة ميدكال كول من  فاتورة حجز نقدي عادية ";
                $invoice_type = 1;   // without insurance
                $data['product_id'] = 5;
                $data['total_amount'] = ($provider_has_bill == 1 or $provider_has_bill_insurance == 1) && ($reservation->promocode_id == null) ? $request->bill_total : $reservation->price;
                $data['MC_percentage'] = $reservation->use_insurance == 0 ? $mainprov->application_percentage_bill + $mainprov->application_percentage : $mainprov->application_percentage_bill_insurance + $mainprov->application_percentage;
                $data['invoice_type_id'] = $invoice_type;
                $data['cost_center_id'] = 510;
                $data['origin'] = $reservation->reservation_no;
                $data['comment'] = $comment;
                $data['sales_journal'] = 1;
                $data['Receivables_account'] = 8;
            }

            $branchOfReservation = $reservation->provider;

            if ($branchOfReservation->odoo_provider_id) {
                $partner_id = $branchOfReservation->odoo_provider_id;
                $data['partner_id'] = $partner_id;
            } else {
                // if provider not has an account on odoo , create new account
                $name = $mainProv->commercial_ar . ' - ' . $branchOfReservation->name_ar;
                $odoo_provider_id = $this->saveProviderToOdoo($branchOfReservation->mobile, $name);
                $branchOfReservation->update(['odoo_provider_id' => $odoo_provider_id]);
                $partner_id = $odoo_provider_id;
                $data['partner_id'] = $partner_id;
            }

            // if reservation is cash with insurance or without insurance
            if ($reservation->promocode_id == null) {
                $odoo_invoice_id = $this->createInvoice_CashReservation($data);
                $reservation->update(['odoo_invoice_id' => $odoo_invoice_id]);
            } elseif ($reservation->promocode_id != null) { //discount coupon
                //calculate balance if reservation with coupon  discount / prepaid
                $this->calculateBalance($provider, $reservation->payment_method_id, $reservation, $request);
                $data = [];
                if ($branchOfReservation->odoo_provider_id) {
                    $partner_id = $branchOfReservation->odoo_provider_id;
                    $data['partner_id'] = $partner_id;
                } else {
                    // if provider not has an account on odoo , create new account
                    $name = $mainProv->commercial_ar . ' - ' . $branchOfReservation->name_ar;
                    $odoo_provider_id = $this->saveProviderToOdoo($branchOfReservation->mobile, $name);
                    $branchOfReservation->update(['odoo_provider_id' => $odoo_provider_id]);
                    $partner_id = $odoo_provider_id;
                    $data['partner_id'] = $partner_id;
                }

                //if reservation with coupon and coupon is discount
                $promoCode = PromoCode::find($reservation->promocode_id);
                $data['product_id'] = $promoCode->coupons_type_id == 1 ? 6 : 2;

                if ($promoCode->coupons_type_id == 1) {  //discount coupon
                    //get discount coupon total amount    step 1
                    $totalCouponPrice = $promoCode->price;   //1000
                    $coupounDiscountPercentage = $promoCode->discount;  //20
                    $amountAfterDiscount = $coupounDiscountPercentage > 0 ? ($totalCouponPrice - (($totalCouponPrice * $coupounDiscountPercentage) / 100)) : $totalCouponPrice; //200
                    $medicalPercentage = $promoCode->application_percentage;
                    $medicalAmount = $medicalPercentage > 0 ? ($amountAfterDiscount * $medicalPercentage) / 100 : 0;

                    //get amount after coupoun discount applied   step 2
                    //calculate admin percentage of step 2
                    $data['payment_term'] = 4;
                    $data['sales_account'] = 438;
                    $comment = "  نسبة ميدكال كول من  فاتورة حجز بكوبون خصم  ";
                    $data['total_amount'] = $amountAfterDiscount;
                    $data['MC_percentage'] = $medicalPercentage;
                    $data['invoice_type_id'] = 3;
                    $data['cost_center_id'] = 510;
                    $data['origin'] = $reservation->reservation_no;
                    $data['comment'] = $comment;
                    $data['sales_journal'] = 1;
                    $data['Receivables_account'] = 8;
                    $odoo_invoice_id = $this->createInvoice_CashReservation($data);  // save to odoo
                } else {   //prepaid coupon
                    // $partner_id ===> provider
                    $data['bank_journal'] = 8;
                    $data['sales_account'] = 581;
                    $data['Receivables_account'] = 8;
                    $data['prepayments_account'] = 420;
                    $data['offer_amount_WithoutVAT'] = $promoCode->price;
                    $data['MC_percentage'] = isset($promoCode->paid_coupon_percentage) ? $promoCode->paid_coupon_percentage : 0; //15
                    $data['comment'] = " حجز بكوبون مسبق الدفع  " . $promoCode->title_ar;
                    $data['PromoCode'] = $promoCode->code;
                    $data['cost_center_id'] = 510;
                    $odoo_invoice_id = $this->UseOffer($data);  // save to odoo
                }

                $reservation->update(['odoo_invoice_id' => $odoo_invoice_id]);
            }


            if ($reservation->user->email != null)
                Mail::to($reservation->user->email)->send(new AcceptReservationMail($reservation->reservation_no));
        } else {
            $reservation->update([
                'approved' => 2
            ]);

            if ($reservation->user->email != null)
                Mail::to($reservation->user->email)->send(new   RejectReservationMail($reservation->reservation_no));
        }

        DB::commit();
        $message = '';
        try {
            $name = 'name_' . app()->getLocale();

            $bill = false; // toggle to send true or false bill to fcm notification to redirect user after click notification to specific screen

            if ($provider->provider_id != null) {
                if ($complete == 1 && $provider_has_bill == 1 && $reservation->promocode_id == null) {
                    $bodyProvider = __('messages.complete user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()}  ";
                    $bodyUser = __('messages.complete your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()}  - " . __('messages.rate provider and doctor and upload the bill');
                    $bill = false;
                } elseif ($complete == 1) { //when reservation complete and user arrivred to branch and bill total entered
                    $bodyProvider = __('messages.complete user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()}  ";
                    $bodyUser = __('messages.complete your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()}  - ";
                } else {
                    $bodyProvider = __('messages.canceled your reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.canceled your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";
                }

                $message = __('messages.we_are_delighted_to_complete') . ' ( ' . "{$provider -> provider ->  $name}" . ' ) ' . __('messages.branch') .
                    ' ( ' . "{$provider->getTranslatedName()}" . ' ) ' . __('messages.please_go_to_the_application');
            } else {
                if ($complete == 1 && $provider_has_bill == 1 && $reservation->promocode_id == null) {
                    $bodyProvider = __('messages.complete user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider  ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()}  ";
                    $bodyUser = __('messages.complete your reservation') . " " . "{$provider  ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()}  - " . __('messages.rate provider and doctor and upload the bill');
                    $bill = false;
                } elseif ($complete == 1) { //when reservation complete and user arrivred to branch and bill total entered
                    $bodyProvider = __('messages.complete user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()}  ";
                    $bodyUser = __('messages.complete your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()}  - ";
                } else {
                    $bodyProvider = __('messages.canceled your reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider  ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.canceled your reservation') . " " . "{$provider  ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";
                }

                $message = __('messages.we_are_delighted_to_complete') . ' ( ' . "{$provider->$name}" . ' ) ' .
                    __('messages.branch') . ' ( ' . "{$reservation->branch->$name}" . ' ) ' . __('messages.please_go_to_the_application');
            }

            //send push notification
            (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyProvider]))->sendProvider(Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id));

            (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyUser]))->sendUser($reservation->user, $bill, $reservation->id);

            //send mobile sms
//            $message = $bodyUser;

            // $this->sendSMS($reservation->user->mobile, $message);

        } catch (\Exception $ex) {

        }
        return $this->returnSuccessMessage(trans('messages.Reservation approved successfully'));

    }

    public
    function RejectReservation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_no" => "required|max:255",
                "reason" => "required|exists:reasons,id"  // id of the reason
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            DB::beginTransaction();
            $provider = $this->auth('provider-api');
            $reservation = $this->getReservationByNo($request->reservation_no, $provider->id);

            if ($reservation == null)
                return $this->returnError('D000', trans('messages.No reservation with this number'));

            if ($reservation->approved == 1)
                return $this->returnError('E001', trans("messages.You can't reject approved reservation"));

            if ($reservation->approved == 2)
                return $this->returnError('E001', trans('messages.Reservation already rejected'));

            $reservation->update([
                'approved' => 2,
                'rejection_reason' => $request->reason
            ]);

            if ($reservation->user->email != null)
                Mail::to($reservation->user->email)->send(new RejectReservationMail($reservation->reservation_no, $reservation->rejection_reason));

            DB::commit();
            try {
                $reserv = $this->getReservationByNo($request->reservation_no, $provider->id);

                $name = 'name_' . app()->getLocale();
                $bodyProvider = __('messages.canceled user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";

                $bodyUser = __('messages.canceled your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";

                //send push notification
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyProvider]))->sendProvider(Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id));

                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyUser]))->sendUser($reservation->user);

                //send mobile sms
//                $message = $bodyUser;

//                $rejected_reason = 'name_' . app()->getLocale();

                if ($provider->provider_id != null) {
                    $message = __('messages.reject_reservations') . ' ( ' . "{$provider->provider->$name} - {$provider->getTranslatedName()}" . ' ) ' .
                        __('messages.because') . '( ' . $reserv->rejectionResoan->rejection_reason . ' ) ' . __('messages.can_re_book');
                } else {
//                    $message = __('messages.reject_reservations') . ' ( ' . "{$provider->$name}" . ' ) ' .
//                        __('messages.because') . '( ' . "{$reservation->rejectionResoan->$rejected_reason}" . ' ) ' . __('messages.can_re_book');

                    $message = __('messages.reject_reservations') . ' ( ' . "{$provider->$name} - {$reservation->branch->$name}" . ' ) ' .
                        __('messages.because') . '( ' . $reserv->rejectionResoan->rejection_reason . ' ) ' . __('messages.can_re_book');
                }
                //     $this->sendSMS($reservation->user->mobile, $message);

            } catch (\Exception $ex) {

            }
            return $this->returnSuccessMessage(trans('messages.Reservation rejected successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function ReservationDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_no" => "required|max:255"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->auth('provider-api');
            $reservation = $this->getReservationByNoWihRelation($request->reservation_no, $provider->id);
            if ($reservation == null)
                return $this->returnError('E001', trans('messages.No reservation with this number'));

            return $this->returnData('reservation', $reservation);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


//method for front end only allow main provider to show reservation details
    public
    function ReservationDetailsFront(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_no" => "required|max:255"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->auth('provider-api');
            $reservation = Reservation::where('reservation_no', $request->reservation_no)->first();
            if (!$reservation)
                return $this->returnError('E001', trans('messages.No reservation with this number'));

            // reservation branch and provider
            $branch = Provider::find($reservation->provider_id);
            $branchId = $branch->id;
            $mainProviderId = $branch->provider->id;

            //check if auth provider is own of this reservation or not
            if ($provider->id != $mainProviderId) {
                return $this->returnError('E001', trans('messages.Cannot view reservation details'));
            }

            $reservation = $this->getReservationByNoWihRelationFront($request->reservation_no, $branchId);


            if ($reservation == null)
                return $this->returnError('E001', trans('messages.No reservation with this number'));


            if ($provider->provider_id == null) { // provider
                if (!is_numeric($provider->application_percentage_bill) || $provider->application_percentage_bill == 0) {
                    $provider_has_bill = 0;
                } else {
                    $provider_has_bill = 1;
                }
                $branches = $provider->providers()->pluck('id')->toArray();
                array_unshift($branches, $provider->id);

            } else {
                $branches = [$provider->id];
                $mainProv = Provider::find($provider->provider_id);

                if (!is_numeric($mainProv->application_percentage_bill) || $mainProv->application_percentage_bill == 0) {
                    $provider_has_bill = 0;
                } else {
                    $provider_has_bill = 1;
                }
            }
            $reservation->provider_has_bill = $provider_has_bill;
            return $this->returnData('reservation', $reservation);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

//
    public
    function getTickets(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "actor_type" => "required|in:1,2",
                "type" => "sometimes|nullable"
            ]);

            DB::beginTransaction();
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $type = null;
            if ($request->has('type')) {
                if ($request->type != 1 && $request->type != 2 && $request->type != 3 && $request->type != 4) {
                    return $this->returnError('D000', trans('messages.Type Not Found'));
                }
                $type = $request->type;
            }

            $actor_type = $request->actor_type;

            if ($actor_type == 1 or $actor_type == '1') {
                $user = $this->auth('provider-api');
                if (!$user) {
                    return $this->returnError('D000', trans('messages.User not found'));
                }
                $messages = $this->getProviderMessages($user->id, $type);
            } else {
                $user = $this->auth('user-api');
                if (!$user) {
                    return $this->returnError('D000', trans('messages.User not found'));
                }
                $messages = $this->getUserMessages($user->id, $type);
            }


            if (count($messages->toArray()) > 0) {
                $total_count = $messages->total();
                $messages->getCollection()->each(function ($message) {

                    $replayCount = Replay::where('ticket_id', $message->id)->where('FromUser', 0)->count();   // user 0 means replay from admin
                    $lastReplay = Replay::where('ticket_id', $message->id)->orderBy('created_at', 'DESC')->first();   // user 0 means replay from admin

                    if ($replayCount == 0) {
                        $message->replay_status = 0;  // بانتظار الرد
                    } else {
                        $message->replay_status = 1;    //   تم الرد
                    }


                    if ($message->solved == 0) {
                        $message->solved = 0;
                    } else {
                        $message->solved = 1;
                    }

                    $message->last_replay = $lastReplay->message;
                    unset($message->actor_type);


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


    public
    function newTicket(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "importance" => "numeric|min:1|max:2",
            "type" => "numeric|min:1|max:4",
            "message" => "required",
            "title" => 'required',
            "actor_type" => "required|in:1,2"
        ]);

        DB::beginTransaction();
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $actor_type = $request->actor_type;

        if ($actor_type == 1 or $actor_type == '1') {
            $user = $this->auth('provider-api');
        } else if ($actor_type == 2 or $actor_type == '2') {
            $user = $this->auth('user-api');
        }

        if (!$user) {
            return $this->returnError('D000', trans('messages.User not found'));
        }

        if (!isset($request->title) || empty($request->title))
            return $this->returnError('D000', trans('messages.Please enter message title'));

        if (!isset($request->type) || $request->type == 0 || !isset($request->importance) || $request->importance == 0)
            return $this->returnError('D000', trans('messages.Please enter importance and type'));

        $ticket = Ticket::create([

            'title' => $request->title ? $request->title : "",
            'actor_id' => $user->id,
            'actor_type' => $actor_type,
            'message_no' => $this->getRandomUniqueNumberTicket(8),
            'type' => $request->type,
            'importance' => $request->importance,
            'message' => $request->message,
            //'message_id' => $request->message_id != 0 ? $request->message_id : NULL,
            //'order' => $order
        ]);


        $replay = [
            "ticket_id" => $ticket->id,
            "message" => $request->message,
            "FromUser" => $actor_type
        ];

        $replay = new Replay($replay);

        $ticket->replays()->save($replay);

        $appData = $this->getAppInfo();
        // Sending mail to manager
        /* if($request->message_id != null && $request->message_id != 0)
             Mail::to($appData->email)->send(new NewReplyMessageMail($user->name_ar));*/

        //  Mail::to($appData->email)->send(new NewUserMessageMail($user->name_ar));

        DB::commit();
        /* if($request->message_id != null && $request->message_id != 0)
             return $this->returnSuccessMessage(trans('messages.Reply send successfully'));*/

        return $this->returnSuccessMessage(trans('messages.Message sent successfully, you can keep in touch with replies by view messages page'));


    }


    protected function getRandomUniqueNumberTicket($length = 8)
    {

        $characters = '0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        $chkCode = Ticket::where('message_no', $string)->first();
        if ($chkCode) {
            $this->getRandomString($length);
        }
        return $string;
    }

    public
    function AddMessage(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|exists:tickets,id",
                "message" => "required",
                "actor_type" => "required|in:1,2"
            ]);

            DB::beginTransaction();
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $actor_type = $request->actor_type;

            if ($actor_type == 1 or $actor_type == '1')
                $user = $this->auth('provider-api');


            if ($actor_type == 2 or $actor_type == '2')
                $user = $this->auth('user-api');

            $id = $request->id;
            $message = $request->message;
            $ticket = Ticket::find($id);

            if (!$user) {
                return $this->returnError('D000', trans('messages.User not found'));
            }

            if ($ticket) {
                if ($ticket->actor_id != $user->id) {
                    return $this->returnError('D000', trans('messages.cannot replay for this converstion'));
                }
            }

            Replay::create([
                'message' => $message,
                "ticket_id" => $id,
                "FromUser" => $actor_type
            ]);


            $appData = $this->getAppInfo();
            // Sending mail to manager
            /* if($request->message_id != null && $request->message_id != 0)
                 Mail::to($appData->email)->send(new NewReplyMessageMail($user->name_ar));*/

            //  Mail::to($appData->email)->send(new NewUserMessageMail($user->name_ar));

            DB::commit();
            /* if($request->message_id != null && $request->message_id != 0)
                 return $this->returnSuccessMessage(trans('messages.Reply send successfully'));*/

            return $this->returnSuccessMessage(trans('messages.Reply send successfully'));


        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }

    }


    public
    function GetTicketMessages(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|exists:tickets,id",
                "actor_type" => "required|in:1,2"
            ]);

            DB::beginTransaction();
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $actor_type = $request->actor_type;

            if ($actor_type == 1 or $actor_type == '1')
                $user = $this->auth('provider-api');


            if ($actor_type == 2 or $actor_type == '2')
                $user = $this->auth('user-api');

            $id = $request->id;
            $ticket = Ticket::find($id);
            if (!$user) {
                return $this->returnError('D000', trans('messages.User not found'));
            }

            if ($ticket) {
                if ($ticket->actor_id != $user->id) {
                    return $this->returnError('D000', trans('messages.cannot access this converstion'));
                }
            }

            $messages = Replay::where('ticket_id', $id)->paginate(10);

            if (count($messages->toArray()) > 0) {

                $total_count = $messages->total();

                $messages = json_decode($messages->toJson());
                $messagesJson = new \stdClass();
                $messagesJson->current_page = $messages->current_page;
                $messagesJson->total_pages = $messages->last_page;
                $messagesJson->total_count = $total_count;
                $messagesJson->data = $messages->data;
                //add photo
                foreach ($messages->data as $message) {
                     if ($message->FromUser == 0) {//admin
                        $message->logo = url('/') . '/images/admin.png';
                    } elseif ($message->FromUser == 1) { //provider
                        $ticket = Ticket::find($id);
                        if ($ticket) {
                            $logo = Provider::where('id', $ticket->actor_id)->value('logo');
                            $message->logo = $logo;
                        } else {
                            $message->logo = url('/') . '/images/admin.png';  // default image
                        }
                    } elseif ($message->FromUser == 2) { //user
                        $message->logo = url('/') . '/images/male.png';
                    } else {
                        $message->logo = url('/') . '/images/admin.png';  // default image
                    }
                }
                return $this->returnData('messages', $messagesJson);
            }

            return $this->returnError('E001', trans("messages.No messages founded"));

            DB::commit();


        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }


    }

    public
    function addUserRecord(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "reservation_no" => "required|max:255",
            "attachments" => "required|array|between:1,10",
            "attachments.*.file" => "required",
            "attachments.*.category_id" => "required|exists:categories,id",
            "summary" => "required"
        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        DB::beginTransaction();
        $provider = $this->auth('provider-api');
        $reservation = $this->getReservationByNo($request->reservation_no, $provider->id);

        if ($reservation == null)
            return $this->returnError('D000', trans('messages.There is no reservation with this id'));

        if ($reservation->for_me == '0')
            return $this->returnError('E001', trans("messages.You can't add record to person related to user"));

        $provider_id = $this->getMainProvider($provider->id);

        $record = UserRecord::create([
            'user_id' => $reservation->user_id,
            'reservation_no' => $reservation->reservation_no,
            'specification_id' => $reservation->doctor->specification_id,
            'day_date' => $reservation->day_date,
            'summary' => $request->summary,
            'provider_id' => $provider_id,
            'doctor_id' => $reservation->doctor_id
        ]);
        $user_attachments = [];
        foreach ($request->attachments as $attachment) {
            $path = $this->saveImage('users', $attachment['file']);
            $user_attachments[] = [
                'user_id' => $reservation->user_id,
                'record_id' => $record->id,
                'category_id' => $attachment['category_id'],
                'attachment' => $path
            ];
        }
        UserAttachment::insert($user_attachments);
        DB::commit();
        return $this->returnSuccessMessage(trans('messages.User record uploaded successfully'));

    }


    protected
    function getMainProvider($id)
    {


        $branch = Provider::where('id', $id)->branch()->first();

        if ($branch) {

            return $branch->provider_id;
        }

        return $id;
    }


    public
    function logout(Request $request)
    {

        try {
            $provider = $this->auth('provider-api');
            $token = $request->api_token;
            Token::where('api_token', $token)->delete();
            $activationCode = (string)rand(1000, 9999);
            $provider->activation_code = $activationCode;
            $provider->web_token = '';
            $provider->update();
            return $this->returnData('message', trans('messages.Logged out successfully'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }

    }


    //////////////////////////v2 api functions /////////////////////////////////

    public
    function getProviderDoctorsV2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => "required|numeric",
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        $validation = $this->validateFields(['specification_id' => $request->specification_id, 'nickname_id' => $request->nickname_id,
            'provider_id' => $request->provider_id, 'branch' => ['main_provider_id' => $request->id, 'provider_id' => $request->provider_id, 'branch_no' => 0]]);

        $provider = $this->getProviderByID($request->id);

        if ($provider != null) {
            if ($provider->provider_id != null) {
                $request->provider_id = 0;
                $branchesIDs = [$provider->id];
            } else {
                $branchesIDs = $provider->providers()->pluck('id');
            }

            if (count($branchesIDs) > 0) {
                if (isset($request->specification_id) && $request->specification_id != 0) {
                    if ($validation->specification_found == null)
                        return $this->returnError('D000', trans('messages.There is no specification with this id'));
                }
                if (isset($request->nickname_id) && $request->nickname_id != 0) {
                    if ($validation->nickname_found == null)
                        return $this->returnError('D000', trans('messages.There is no nickname with this id'));
                }
                if (isset($request->provider_id) && $request->provider_id != 0) {
                    if ($validation->provider_found == null)
                        return $this->returnError('D000', trans('messages.There is no branch with this id'));

                    if ($validation->branch_found)
                        return $this->returnError('D000', trans("messages.This branch isn't in your branches"));
                }
                if (isset($request->gender) && $request->gender != 0 && !in_array($request->gender, [1, 2])) {
                    return $this->returnError('D000', trans("messages.This is invalid gender"));
                }

                $front = $request->has('show_front') ? 1 : 0;
                $doctors = $this->getDoctorsV2($branchesIDs, $request->specification_id, $request->nickname_id, $request->provider_id, $request->gender, $front, $request->doctor_name);

                if (count($doctors) > 0) {
                    foreach ($doctors as $key => $doctor) {
                        $doctor->time = "";
                        $days = $doctor->times;
                        $match = $this->getMatchedDateToDays($days);

                        if (!$match || $match['date'] == null) {
                            $doctor->time = new \stdClass();;
                            continue;
                        }
                        $doctorTimesCount = $this->getDoctorTimePeriodsInDay($match['day'], $match['day']['day_code'], true);
                        $availableTime = $this->getFirstAvailableTime($doctor->id, $doctorTimesCount, $days, $match['date'], $match['index']);
                        $doctor->time = $availableTime;

                        $doctor->branch_name = Doctor::find($doctor->id)->provider->{'name_' . app()->getLocale()};
                        $countRate = Doctor::find($doctor->id)->reservations()
                            ->Where('doctor_rate', '!=', null)
                            ->Where('doctor_rate', '!=', 0)
                            ->Where('provider_rate', '!=', null)
                            ->Where('provider_rate', '!=', 0)
                            ->count();
                        $doctor->rate_count = $countRate;
                    }
                    $total_count = $doctors->total();
                    $doctors->getCollection()->each(function ($doctor) {
                        $doctor->makeVisible(['name_en', 'name_ar', 'information_en', 'information_ar']);
                        return $doctor;
                    });


                    $doctors = json_decode($doctors->toJson());
                    $doctorsJson = new \stdClass();
                    $doctorsJson->current_page = $doctors->current_page;
                    $doctorsJson->total_pages = $doctors->last_page;
                    $doctorsJson->total_count = $total_count;
                    $doctorsJson->data = $doctors->data;
                    return $this->returnData('doctors', $doctorsJson);
                }
                return $this->returnData('doctors', $doctors);
            }
            return $this->returnError('E001', trans('messages.There are no branches for this provider'));
        }
        return $this->returnError('E001', trans('messages.There is no provider with this id'));

    }


    public function getProviderPercentages(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                "id" => "required|exists:providers,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $provider = Provider::select('application_percentage', 'application_percentage_for_offers', 'application_percentage_bill', 'application_percentage_bill_insurance')->find($request->id);
            $provider->makevisible(['application_percentage', 'application_percentage_bill']);
            $provider->makeHidden(['is_branch', 'hide', 'parent_type', 'provider_has_bill', 'has_insurance', 'is_lottery', 'rate_count']);

            return $this->returnData('provider', $provider);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    private function addReservationTypeToResult($reservation)
    {
        if ($reservation->doctor_id != null && $reservation->doctor_id != 0 && $reservation->doctor_id != "") {
            $reservation->reservation_type = "doctor";
        } elseif ($reservation->offer_id != null && $reservation->offer_id != 0 && $reservation->offer_id != "") {
            $reservation->reservation_type = "offer";
        } elseif (isset($reservation->type->id) && $reservation->type->id = 1) {
            $reservation->reservation_type = "home_services";
        } elseif (isset($reservation->type->id) && $reservation->type->id = 2) {
            $reservation->reservation_type = "clinic_services";
        } else {
            $reservation->reservation_type = "undefined";
        }
    }

    //get all reservation doctor - services - offers which cancelled [2 by branch ,5 by user] or complete [3]
    public function getReservationsRecodes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "type" => "required|in:home_services,clinic_services,doctor,offer,all",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $type = $request->type;
            $provider = $this->auth('provider-api');
            if ($provider->provider_id == null) { //main provider
                $branches = $provider->providers()->pluck('id')->toArray();
                array_unshift($branches, $provider->id);
            } else {
                $branches = [$provider->id];
            }

            $reservations = $this->recordReservationsByType($branches, $type);

            if (count($reservations->toArray()) > 0) {

                $balance = $this->getCompletedReservationRecordsTotalAndAmount($branches, $request->type);
                $complete_reservation__count = $balance->total;
                $complete_reservation__amount = $balance->amount;


                $reservations->getCollection()->each(function ($reservation) use ($request) {
                    $reservation->makeHidden(['order', 'reservation_total', 'admin_value_from_reservation_price_Tax', 'mainprovider', 'is_reported', 'branch_no', 'for_me', 'rejected_reason_id', 'is_visit_doctor', 'rejection_reason', 'user_rejection_reason']);
                    if ($request->type == 'home_services') {
                        $reservation->reservation_type = 'home_services';
                    } elseif ($request->type == 'clinic_services') {
                        $reservation->reservation_type = 'clinic_services';
                    } elseif ($request->type == 'doctor') {
                        $reservation->reservation_type = 'doctor';
                    } elseif ($request->type == 'offer') {
                        $reservation->reservation_type = 'offer';
                    } elseif ($request->type == 'all') {

                        $this->addReservationTypeToResult($reservation);
                        $reservation->makeVisible(["bill_total"]);
                        $reservation->makeHidden(["offer_id",
                            "doctor_id",
                            "service_id",
                            "doctor_rate",
                            "service_rate",
                            "provider_rate",
                            "offer_rate",
                            "paid",
                            "use_insurance",
                            "promocode_id",
                            "provider_id",
                            "branch_id",
                            "rate_comment",
                            "rate_date",
                            "address",
                            "latitude",
                            "longitude"]);

                        $reservation->doctor->makeHidden(['available_time', 'times']);
                        $reservation->provider->makeHidden(["provider_has_bill",
                            "has_insurance",
                            "is_lottery",
                            "rate_count"]);

                    } else {
                        $reservation->reservation_type = 'undefined';
                    }
                    return $reservation;
                });

                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $reservationsJson = new \stdClass();
                $reservationsJson->current_page = $reservations->current_page;
                $reservationsJson->total_pages = $reservations->last_page;
                $reservationsJson->total_count = $total_count;
                $reservationsJson->complete_reservation__count = $complete_reservation__count;
                $reservationsJson->complete_reservation__amount = $complete_reservation__amount;
                $reservationsJson->per_page = PAGINATION_COUNT;
                $reservationsJson->data = $reservations->data;

                return $this->returnData('reservations', $reservationsJson);
            }
            return $this->returnData('reservations', $reservations);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    private function getCompletedReservationRecordsTotalAndAmount(array $branches, $type)
    {
        $balance = new \stdClass();
        $balance->total = 0;
        $balance->amount = 0;

        if ($type == 'home_services') {
            $balance->total = ServiceReservation::whereHas('type', function ($e) {
                $e->where('id', 1);
            })
                ->whereIn('branch_id', $branches)
                ->whereIn('approved', [3])
                ->where('is_visit_doctor', 1)
                ->count();

            $amount_if_reservation_with_bill = ServiceReservation::whereHas('type', function ($e) {
                $e->where('id', 1);
            })
                ->whereIn('branch_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('bill_total')
                ->where('is_visit_doctor', 1)
                ->where('bill_total', '!=', '0')
                ->sum('bill_total');

            $amount_if_reservation_withOut_bill = ServiceReservation::whereHas('type', function ($e) {
                $e->where('id', 1);
            })
                ->whereIn('branch_id', $branches)
                ->whereIn('approved', [3])
                ->where('is_visit_doctor', 1)
                ->where(function ($q) {
                    $q->whereNull('bill_total')
                        ->orWhere('bill_total', '0');
                })
                ->sum('total_price');

            $balance->amount = $amount_if_reservation_with_bill + $amount_if_reservation_withOut_bill;

        } elseif ($type == 'clinic_services') {
            $balance->total = ServiceReservation::whereHas('type', function ($e) {
                $e->where('id', 2);
            })
                ->whereIn('branch_id', $branches)
                ->whereIn('approved', [3])
                ->where('is_visit_doctor', 1)
                ->count();
            $amount_if_reservation_with_bill = ServiceReservation::whereHas('type', function ($e) {
                $e->where('id', 2);
            })
                ->whereIn('branch_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('bill_total')
                ->where('bill_total', '!=', '0')
                ->where('is_visit_doctor', 1)
                ->sum('bill_total');

            $amount_if_reservation_withOut_bill = ServiceReservation::whereHas('type', function ($e) {
                $e->where('id', 2);
            })
                ->whereIn('branch_id', $branches)
                ->whereIn('approved', [3])
                ->where('is_visit_doctor', 1)
                ->where(function ($q) {
                    $q->whereNull('bill_total')
                        ->orWhere('bill_total', '0');
                })
                ->sum('total_price');
            $balance->amount = $amount_if_reservation_with_bill + $amount_if_reservation_withOut_bill;

        } elseif ($type == 'doctor') {
            $balance->total = Reservation::whereIn('provider_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('doctor_id')
                ->where('doctor_id', '!=', 0)
                ->count();
            $amount_if_reservation_with_bill = Reservation::whereIn('provider_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('doctor_id')
                ->where('doctor_id', '!=', 0)
                ->whereNotNull('bill_total')
                ->where('bill_total', '!=', '0')
                ->sum('bill_total');

            $amount_if_reservation_withOut_bill = Reservation::whereIn('provider_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('doctor_id')
                ->where('doctor_id', '!=', 0)
                ->where(function ($q) {
                    $q->whereNull('bill_total')
                        ->orWhere('bill_total', '0');
                })
                ->sum('price');

            $balance->amount = $amount_if_reservation_with_bill + $amount_if_reservation_withOut_bill;

        } elseif ($type == 'offer') {
            $balance->total = Reservation::whereIn('provider_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->count();

            $amount_if_reservation_with_bill = Reservation::whereIn('provider_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->whereNotNull('bill_total')
                ->where('bill_total', '!=', '0')
                ->sum('bill_total');

            $amount_if_reservation_withOut_bill = Reservation::whereIn('provider_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->where(function ($q) {
                    $q->whereNull('bill_total')
                        ->orWhere('bill_total', '0');
                })
                ->sum('price');

            $balance->amount = $amount_if_reservation_with_bill + $amount_if_reservation_withOut_bill;


        } elseif ($type == 'all') {
            $services_total = ServiceReservation::whereHas('type')
                ->whereIn('branch_id', $branches)
                ->whereIn('approved', [3])
                ->count();

            $services_amount_if_reservation_with_bill = ServiceReservation::whereHas('type')
                ->whereIn('branch_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('bill_total')
                ->where('bill_total', '!=', '0')
                ->sum('bill_total');

            $services_amount_if_reservation_withOut_bill = ServiceReservation::whereHas('type')
                ->whereIn('branch_id', $branches)
                ->whereIn('approved', [3])
                ->where(function ($q) {
                    $q->whereNull('bill_total')
                        ->orWhere('bill_total', '0');
                })
                ->sum('total_price');

            $services_amount = $services_amount_if_reservation_with_bill + $services_amount_if_reservation_withOut_bill;


            $doctor_offers_total = Reservation::whereIn('provider_id', $branches)
                ->whereIn('approved', [3])
                ->count();

            $amount_if_doctor_offer_with_bill = Reservation::whereIn('provider_id', $branches)
                ->whereIn('approved', [3])
                ->whereNotNull('bill_total')
                ->where('bill_total', '!=', '0')
                ->sum('bill_total');

            $amount_if_doctor_offer_withOut_bill = Reservation::whereIn('provider_id', $branches)
                ->whereIn('approved', [3])
                ->where(function ($q) {
                    $q->whereNull('bill_total')
                        ->orWhere('bill_total', '0');
                })
                ->sum('price');

            $doctor_offers_amount = $amount_if_doctor_offer_with_bill + $amount_if_doctor_offer_withOut_bill;

            $balance->total = $services_total + $doctor_offers_total;
            $balance->amount = $services_amount + $doctor_offers_amount;

        }

        return $balance;
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
            $provider = $this->auth('provider-api');
            if (!$provider) {
                return $this->returnError('E001', trans('messages.Provider not found'));
            }
            if ($provider->provider_id != null) {
                $request->provider_id = 0;
                $branchesIDs = [$provider->id];
            } else {
                $branchesIDs = $provider->providers()->pluck('id');
            }

            if ($request->type == 'count') {
                $un_read_notifications = Reciever::whereIn('actor_id', $branchesIDs)
                    ->unseenForProvider()
                    ->count();
                return $this->returnData('un_read_notifications', $un_read_notifications);
            }
            ///else get notifications list

            $notifications = Reciever::whereHas('notification')
                ->with(['notification' => function ($q) {
                    $q->select('id', 'photo', 'title', 'content');
                }])->whereIn('actor_id', $branchesIDs)
                ->forProvider()
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
            $provider = $this->auth('provider-api');
            if (!$provider) {
                return $this->returnError('E001', trans('messages.Provider not found'));
            }

            Reciever::where('id', $request->notification_id)->update(['seen' => '1']);

            $id = $request->notification_id;

            $notification = Reciever::whereHas('notification')
                ->with(['notification' => function ($q) use ($id) {
                    $q->select('id', 'photo', 'title', 'content');
                }])->where('id', $id)
                ->first();

            $notifications = new SingleNotificationResource($notification);

            return $this->returnData('notifications', $notifications);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}
