<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\ProviderResource;
use App\Models\Doctor;
use App\Models\DoctorConsultingReservation;
use App\Models\Provider;
use App\Models\Reservation;
use App\Models\ServiceReservation;
use App\Traits\Dashboard\ProviderTrait;
use App\Traits\Dashboard\PublicTrait;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\OdooTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProviderController extends Controller
{
    use PublicTrait, ProviderTrait, OdooTrait, GeneralTrait;

    public function index()
    {
        if (request('queryStr')) {
            $queryStr = request('queryStr');
            $providers = Provider::where('provider_id', null)
                ->where(function ($q) use ($queryStr) {
                    $q->where('name_ar', 'LIKE', '%' . trim($queryStr) . '%')
                        ->orwhere('name_en', 'LIKE', '%' . trim($queryStr) . '%');
                })
                ->orderBy('id', 'DESC')->paginate(PAGINATION_COUNT);
        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $providers = Provider::where('provider_id', null)
                ->where(function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('name_en', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere(function ($qq) use ($q) {
                            if (trim($q) == 'مفعل') {
                                $qq->where('status', 1);
                            } elseif (trim($q) == 'غير مفعل') {
                                $qq->where('status', 0);
                            }
                        })->orWhere(function ($qq) use ($q) {
                            if (trim($q) == 'نعم') {
                                $qq->where('lottery', 1);
                            } elseif (trim($q) == 'لا') {
                                $qq->where('lottery', 0);
                            }
                        })
                        ->orWhere('username', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('mobile', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('application_percentage', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('application_percentage_bill', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('commercial_no', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('created_at', 'LIKE binary', '%' . trim($q) . '%')
                        ->orWhereHas('city', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                        })->orWhereHas('district', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_ar', 'LIKE', '%' . trim($q) . '%');
                        });
                })
                ->orderBy('id', 'DESC')
                ->paginate(10);
        } else
            $providers = Provider::where('provider_id', null)->orderBy('id', 'DESC')->paginate(PAGINATION_COUNT);

        $result = new ProviderResource($providers);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function show(Request $request)
    {
        $provider = $this->getProviderById($request->id);
        if ($provider == null)
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

        //$branchesId = $provider->providers()->pluck('id')->toArray();

        $branchesId = Provider::where('provider_id', $request->id)->whereNotNull('provider_id')->pluck('id')->toArray();

        //
        $all_Offer_Doctor_reservation_count = Reservation::whereIn('provider_id', $branchesId)->count();

        $all_services_reservation_count = ServiceReservation::whereIn('branch_id', $branchesId)->count();
//        $all_consulting_reservation_count = DoctorConsultingReservation::whereIn('provider_id', $branchesId)->count();

        $approved_Offer_Doctor_reservation_count = Reservation::where('approved', 1)->whereIn('provider_id', $branchesId)->count();
        $approved_services_reservation_count = ServiceReservation::where('approved', 1)->whereIn('branch_id', $branchesId)->count();
//        $approved_consulting_reservation_count = DoctorConsultingReservation::where('approved', 1)->whereIn('provider_id', $branchesId)->count();

        $reject_Offer_Doctor_reservation_count = Reservation::where(function ($q) {
            $q->where('approved', 2);
        })->whereIn('provider_id', $branchesId)->count();

        $reject_services_reservation_count = ServiceReservation::where(function ($q) {
            $q->where('approved', 2);
        })->whereIn('branch_id', $branchesId)->count();

        /*        $reject_consulting_reservation_count = DoctorConsultingReservation::where(function($q){
                    $q -> where('approved', 2)->orwhere('approved', 5);
                })->whereIn('provider_id', $branchesId)->count();*/

        $provider_all_reservation_count = $all_Offer_Doctor_reservation_count + $all_services_reservation_count /*+ $all_consulting_reservation_count*/
        ;
        $provider_all_approved_reservation_count = $approved_Offer_Doctor_reservation_count + $approved_services_reservation_count /*+ $approved_consulting_reservation_count*/
        ;
        $provider_all_refused_reservation_count = $reject_Offer_Doctor_reservation_count + $reject_services_reservation_count /*+ $reject_consulting_reservation_count*/
        ;

        /*  foreach ($branchesId as $branch_id) {
              $reservations = Provider::find($branch_id)->reservations()->select('id', 'approved')->get();
              if (isset($reservations) && $reservations->count() > 0) {
                  foreach ($reservations as $reservation) {
                      $allReservationCount++;
                      if ($reservation->approved == 1 or $reservation->approved == 3)
                          $acceptanceReservationCount++;
                      if ($reservation->approved == 2)
                          $refusedReservationCount++;
                  }
              }
          }*/

        if ($provider_all_reservation_count == 0) {
            $acceptance_rate = __('main.not_counted_yet');
            $refusal_rate = __('main.not_counted_yet');
        } else {
            $acceptance_rate = round(($provider_all_approved_reservation_count / $provider_all_reservation_count) * 100) . "%";
            $refusal_rate = round(($provider_all_refused_reservation_count / $provider_all_reservation_count) * 100) . "%";
        }

        $result['provider'] = $provider;
        $result['provider']['branches'] = $provider->providers()->get(['id', 'name_ar', 'name_en']);

        $doctors = Doctor::whereIn('provider_id', $branchesId)->get(['id', 'name_ar', 'name_en']);
        $doctors = $doctors->transform(function ($data) {
            return [
                'id' => $data->id,
                'name_ar' => $data->name_ar,
                'name_en' => $data->name_en,
            ];
        });
//        $result['provider']['doctors'] = $provider->doctors()->whereIn('provider_id', $branches)->get(['id', 'name_ar', 'name_en']);
        $result['provider']['doctors'] = $doctors;
        $result['provider']['city'] = $provider->city;
        $result['provider']['district'] = $provider->district;
        $result['acceptance_rate'] = $acceptance_rate;
        $result['refusal_rate'] = $refusal_rate;
        $result['allReservationCount'] = $provider_all_reservation_count;
        $result['acceptanceReservationCount'] = $provider_all_approved_reservation_count;
        $result['refusedReservationCount'] = $provider_all_refused_reservation_count;
        $result['provider']['show_delete'] = $provider->branches->count() > 0 ? 0 : 1;

        return response()->json(['status' => true, 'data' => $result]);
    }

    public function create()
    {
        try {
            $types = $this->getProviderTypes();
            $cities = $this->getCities();
            $districts = $this->getDistricts();

            $result['types'] = $types;
            $result['cities'] = $cities;
            $result['districts'] = $districts;

            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_ar" => "required|max:255",
                "name_en" => "required|max:255",
                "commercial_ar" => "required|unique:providers,commercial_ar|max:225",
                "commercial_en" => "required|unique:providers,commercial_en|max:225",
                "username" => "required|string|max:100|unique:providers,username",
                "password" => "required|max:255",
                "mobile" => array(
                    "required",
                    "numeric",
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/",
                    //     "unique:providers,mobile",
                ),
                "commercial_no" => "required|unique:providers,commercial_no",
                "type_id" => "required|exists:provider_types,id",
                "city_id" => "required|exists:cities,id",
                "district_id" => "required|exists:districts,id",
                "status" => "required|in:0,1",
                "application_percentage" => "required|numeric",
                "application_percentage_for_offers" => "required|numeric"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $exists = $this->checkIfMobileExistsForOtherProviders($request->mobile);
            if ($exists) {
                $result = ['mobile' => __('main.mobile_already_exists')];
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            DB::beginTransaction();

            try {

                $fileName = "";
                if (isset($request->logo) && !empty($request->logo)) {
                    $fileName = $this->saveImage('providers', $request->logo);
                }

                $provider = Provider::create([
                    'name_en' => trim($request->name_en),
                    'name_ar' => trim($request->name_ar),
                    'commercial_ar' => trim($request->commercial_ar),
                    'commercial_en' => trim($request->commercial_en),
                    'username' => trim($request->username),
                    'password' => $request->password,
                    'mobile' => $request->mobile,
                    'longitude' => $request->longitude ? $request->longitude : 0,
                    'latitude' => $request->latitude ? $request->latitude : 0,
                    'commercial_no' => $request->commercial_no,
                    'logo' => $fileName,
                    'status' => $request->status,
                    'activation' => 1,
                    'address' => trim($request->latLng),
                    'type_id' => $request->type_id,
                    'city_id' => $request->city_id,
                    'district_id' => $request->district_id,
                    'api_token' => '',
                    'application_percentage' => $request->application_percentage,
                    'application_percentage_for_offers' => $request->application_percentage_for_offers,
                ]);

                // save user  to odoo erp system
                /*  $odoo_provider_id = $this->saveProviderToOdoo($provider->mobile, $provider->username);
                 $provider->update(['odoo_provider_id' => $odoo_provider_id]);*/

                if ($request->has('application_percentage')) {
                    // $provider->providers()->update(['application_percentage' => $request->application_percentage]);
                    $provider->update(['application_percentage' => $request->application_percentage]);
                }

                if ($request->has('application_percentage_bill')) {
                    $provider->update(['application_percentage_bill' => $request->application_percentage_bill]);
                }

                if ($request->has('application_percentage_bill_insurance')) {
                    $provider->update(['application_percentage_bill_insurance' => $request->application_percentage_bill_insurance]);
                }

                DB::commit();
                $this->authProviderByUserName($request->username, $request->password); // jwt token
                return response()->json(['status' => true, 'msg' => __('main.provider_added_successfully')]);
            } catch (\Exception $ex) {
                DB::rollback();
            }

            return false;

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    // jwt auth
    public function authProviderByMobile($mobile, $password)
    {

        $provider = Provider::where('mobile', $mobile)->first();

        $token = Auth::guard('provider-api')->attempt(['mobile' => $mobile, 'password' => $password]);
        //$token = Auth::guard('provider-api') ->tokenById($provider->id);

        // to allow open  app on more device with the same account
        if ($token) {

            $newToken = new \App\Models\Token(['user_id' => $provider->id, 'api_token' => $token]);

            $provider->tokens()->save($newToken);
            //last access token
            $provider->update(['api_token' => $token]);

            return $provider;
        }

        if (preg_match("~^0\d+$~", $mobile)) {
            $mobile = substr($mobile, 1);
        } else {
            $mobile = '0' . $mobile;
        }

        $provider = Provider::where('mobile', $mobile)->first();
        $token = Auth::guard('provider-api')->attempt(['mobile' => $mobile, 'password' => $password]);

        // to allow open  app on more device with the same account

        if ($token) {

            $newToken = new \App\Models\Token(['user_id' => $provider->id, 'api_token' => $token]);
            $provider->tokens()->save($newToken);
            $provider->update(['api_token' => $token]);

            return $provider;
        }

        return null;
    }

    public function authProviderByUserName($username, $password)
    {
        $provider = Provider::where('username', $username)->first();
        if (!$provider) {
            return null;
        }

        $providerId = $provider->id;
        $token = \Illuminate\Support\Facades\Auth::guard('provider-api')->attempt(['username' => $username, 'password' => $password]);
        //$token = Auth::guard('provider-api') ->tokenById($provider->id);
        if (!$provider)
            return null;

        // to allow open  app on more device with the same account
        if ($token) {
            $newToken = new \App\Models\Token(['provider_id' => $provider->id, 'api_token' => $token]);
            $provider->tokens()->save($newToken);
            //last access token
            $provider->update(['api_token' => $token]);
            return $provider;
        }
        // to allow open  app on more device with the same account

        if ($token) {
            $newToken = new \App\Models\Token(['provider_id' => $provider->id, 'api_token' => $token]);
            $provider->tokens()->save($newToken);
            $provider->update(['api_token' => $token]);

            return $provider;
        }

        return null;
    }

    public function edit(Request $request)
    {
        try {
            $provider = $this->getProviderById($request->id);
            if ($provider == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $provider->makeVisible(['application_percentage', 'application_percentage_bill']);
            $types = $this->getProviderTypes();
            $cities = $this->getCities();
            $districts = $this->getDistricts();

            $result['provider'] = $provider;
            $result['types'] = $types;
            $result['cities'] = $cities;
            $result['districts'] = $districts;

            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $provider = $this->getProviderById($request->id);
            if (!$provider) {
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            }

            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "commercial_ar" => 'required|max:225|unique:providers,commercial_ar,' . $provider->id . ',id',
                "commercial_en" => 'required|max:225|unique:providers,commercial_en,' . $provider->id . ',id',
                "username" => 'required|string|max:100|unique:providers,username,' . $provider->id . ',id',
                "password" => "sometimes|max:255",
                "mobile" => array(
                    "required",
                    "numeric",
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/",
                    //    "unique:providers,mobile,".$provider -> id,
                ),
                "commercial_no" => 'required|unique:providers,commercial_no,' . $provider->id . ',id',
                "type_id" => "required|exists:provider_types,id",
                "city_id" => "required|exists:cities,id",
                "district_id" => "required|exists:districts,id",
                'application_percentage' => "required",
                "application_percentage_for_offers" => "required|numeric"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            if ($provider->provider_id != null) {  //branch
                $exists = $this->checkIfMobileExistsForOtherBranches($request->mobile);
                if ($exists) {
                    $proMobile = Provider::whereNotNull('provider_id')->where('mobile', $request->mobile)->first();
                    if ($proMobile->id != $provider->id) {
                        $result = ['mobile' => __('main.mobile_already_exists')];
                        return response()->json(['status' => false, 'error' => $result], 200);
                    }
                }
            }
            if ($provider->provider_id == null) {  //main provider
                $exists = $this->checkIfMobileExistsForOtherProviders($request->mobile);
                if ($exists) {
                    $proMobile = Provider::where('provider_id', null)->where('mobile', $request->mobile)->first();
                    if ($proMobile->id != $provider->id) {
                        $result = ['mobile' => __('main.mobile_already_exists')];
                        return response()->json(['status' => false, 'error' => $result], 200);
                    }
                }
            }

            $fileName = DB::table('providers')->where('id', $provider->id)->first()->logo;

            if (isset($request->logo) && !empty($request->logo)) {
                $fileName = $this->saveImage('providers', $request->logo);
            }

            if ($request->has('latLng') && $request->latLng != null) {
                Provider::find($request->id)->update(['address' => $request->latLng]);
            }

            if ($request->has('application_percentage')) {
                $provider->update(['application_percentage' => $request->application_percentage]);
            }
            if ($request->has('application_percentage_for_offers')) {
                $provider->update(['application_percentage_for_offers' => $request->application_percentage_for_offers]);
            }
            $this->updateProvider($provider, $request);

            $t = $provider->update(['logo' => $fileName]);

            $provider->providers()->update(['logo' => $fileName]);

            return response()->json(['status' => true, 'msg' => __('main.provider_updated_successfully')]);
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    public function destroy(Request $request)
    {
        try {
            $provider = $this->getProviderById($request->id);
            if ($provider == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if (count($provider->reservations) > 0) {
                return response()->json(['success' => false, 'error' => __('main.provider_with_reservations_cannot_be_deleted')], 200);
            }
            foreach ($provider->providers as $branch) {
                if (count($branch->reservations) > 0) {
                    return response()->json(['success' => false, 'error' => __('main.provider_whose_branch_has_reservations_cannot_be_deleted')], 200);
                }
            }
            $provider->providers()->delete();
            $provider->delete();
            return response()->json(['status' => true, 'msg' => __('main.provider_deleted_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
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

            $provider = $this->getProviderById($request->id);
            if ($provider == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if ($request->status != 0 && $request->status != 1) {
                return response()->json(['status' => false, 'error' => __('main.enter_valid_activation_code')], 200);
            } else {
                $this->changerProviderStatus($provider, $request->status);
                return response()->json(['status' => true, 'msg' => __('main.provider_status_changed_successfully')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
        }
    }

    public function addLotteryBranch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|exists:providers,id"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $provider = Provider::find($request->provider_id);
            if ($provider->lottery == 1) {
                return response()->json(['status' => true, 'data' => ['branchId' => $request->provider_id], 'msg' => __('main.add_lottery_branch')], 200);
            }
            $provider->update(['lottery' => 1]);
            return response()->json(['status' => true, 'data' => ['branchId' => $request->provider_id], 'msg' => __('main.add_lottery_branch')], 200);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
        }
    }

    public function removeLotteryBranch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|exists:providers,id"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $provider = Provider::find($request->provider_id);
            if ($provider->lottery == 0) {
                return response()->json(['status' => true, 'data' => ['branchId' => $request->provider_id], 'msg' => __('main.remove_lottery_branch')], 200);
            }
            $provider->update(['lottery' => 0]);
            return response()->json(['status' => true, 'data' => ['branchId' => $request->provider_id], 'msg' => __('main.remove_lottery_branch')], 200);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
        }
    }

    public
    function getProviderRservationByType(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "type" => "required|in:home_services,clinic_services,doctor,offer,all",
                "approved" => "required|in:1,2,all",
                "provider_id" => "required|exists:providers,id",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $type = $request->type;
            $approved = $request->approved;
            $provider = Provider::find($request->provider_id);

            if ($provider->provider_id == null) { //main provider
                $branches = $provider->providers()->pluck('id')->toArray();
                array_unshift($branches, $provider->id);
            } else {
                $branches = [$provider->id];
            }

            $reservations = $this->getReservationsByType($branches, $type, $approved);


            if (count($reservations->toArray()) > 0) {
                $reservations->getCollection()->each(function ($reservation) use ($request) {
                    $reservation->makeHidden(['order', 'rejected_reason_type', 'reservation_total', 'admin_value_from_reservation_price_Tax', 'mainprovider', 'is_reported', 'branch_no', 'for_me', 'rejected_reason_notes', 'rejected_reason_id', 'bill_total', 'is_visit_doctor', 'rejection_reason', 'user_rejection_reason']);
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
                            "paid",
                            "use_insurance",
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


    public function getReservationsByType($providers, $type, $approved)
    {
        if ($type == 'home_services') {
            return $this->getHomeServicesReservationsByType($providers, $approved);
        } elseif ($type == 'clinic_services') {
            return $this->getClinicServicesReservationsByType($providers, $approved);
        } elseif ($type == 'doctor') {
            return $this->getDoctorReservationsByType($providers, $approved);
        } elseif ($type == 'offer') {
            return $this->getOfferReservationsByType($providers, $approved);
        } else {
            // return all reservations
            return $this->getAllReservationsByType($providers, $approved);
        }
    }

    private function getHomeServicesReservationsByType($providers, $approved)
    {

        $reservations = ServiceReservation::whereHas('type', function ($e) {
            $e->where('id', 1);
        })->with(['service' => function ($g) {
            $g->select('id', 'specification_id', \Illuminate\Support\Facades\DB::raw('title_' . app()->getLocale() . ' as title'), 'price','clinic_price', 'home_price')
                ->with(['specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'type', 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'user' => function ($q) {
            $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                ->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'provider' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'type' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
        ])
            ->whereIn('branch_id', $providers);
        if ($approved == 'all')
            $reservations = $reservations->whereIn('approved', [0, 1, 2, 3, 4, 5]);
        else
            $reservations = $reservations->where('approved', $approved);

        return $reservations->
        orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);


    }


    private function getClinicServicesReservationsByType($providers, $approved)
    {

        $reservations = ServiceReservation::whereHas('type', function ($e) {
            $e->where('id', 2);
        })->with(['service' => function ($g) {
            $g->select('id', 'specification_id', \Illuminate\Support\Facades\DB::raw('title_' . app()->getLocale() . ' as title'), 'price','clinic_price', 'home_price')
                ->with(['specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'type', 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'user' => function ($q) {
            $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                ->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'provider' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'type' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
        ])
            ->whereIn('branch_id', $providers);
        if ($approved == 'all')
            $reservations = $reservations->whereIn('approved', [0, 1, 2, 3, 4, 5]);
        else
            $reservations = $reservations->where('approved', $approved);

        return $reservations->
        orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);
    }

    private function getDoctorReservationsByType($providers, $approved)
    {
        $reservations = Reservation::with(['doctor' => function ($g) {
            $g->select('id', 'nickname_id', 'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                ->with(['nickname' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'rejectionResoan' => function ($rs) {
            $rs->select('id', DB::raw('name_' . app()->getLocale() . ' as rejection_reason'));
        }, 'coupon' => function ($qu) {
            $qu->select('id', 'coupons_type_id', DB::raw('title_' . app()->getLocale() . ' as title'), 'code', 'photo', 'price', 'price_after_discount');
        }
            , 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'email', 'address', 'insurance_image', 'insurance_company_id', 'mobile')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'people' => function ($p) {
                $p->select('id', 'name', 'insurance_company_id', 'insurance_image')->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
            }])
            ->whereNotNull('doctor_id')
            ->where('doctor_id', '!=', 0)
            ->whereIn('provider_id', $providers);

        if ($approved == 'all')
            $reservations = $reservations->whereIn('approved', [0, 1, 2, 3, 4, 5]);
        else
            $reservations = $reservations->where('approved', $approved);

        return $reservations->
        orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);
    }

    private function getOfferReservationsByType($providers, $approved)
    {
        $reservations = Reservation::with(['offer' => function ($q) {
            $q->select('id',
                DB::raw('title_' . app()->getLocale() . ' as title'),
                'expired_at',
                'price'
            );
        }, 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'user' => function ($q) {
            $q->select('id', 'name', 'mobile', 'email', 'address', 'insurance_image', 'insurance_company_id', 'mobile')
                ->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }])
            ->whereNotNull('offer_id')
            ->where('offer_id', '!=', 0)
            ->whereIn('provider_id', $providers);

        if ($approved == 'all')
            $reservations = $reservations->whereIn('approved', [0, 1, 2, 3, 4, 5]);
        else
            $reservations = $reservations->where('approved', $approved);

        return $reservations->
        orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);
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


    private function getAllReservationsByType($providers, $approved)
    {

        $doctor_reservations = Reservation::doctorSelection()
            ->whereIn('provider_id', $providers)
            ->whereNotNull('doctor_id')
            ->where('doctor_id', '!=', 0)
            /*  ->whereDate('day_date', '>=', Carbon::now()->format('Y-m-d'))*/
            ->orderBy('id', 'DESC');

        if ($approved == 'all')
            $doctor_reservations = $doctor_reservations->whereIn('approved', [0, 1, 2, 3, 4, 5]);
        else
            $doctor_reservations = $doctor_reservations->where('approved', $approved);


        $home_services_reservations = ServiceReservation::serviceSelection()
            ->serviceSelection()
            ->whereHas('type', function ($e) {
                $e->where('id', 1);
            })
            ->whereIn('branch_id', $providers)
            ->orderBy('id', 'DESC');

        if ($approved == 'all')
            $home_services_reservations = $home_services_reservations->whereIn('approved', [0, 1, 2, 3, 4, 5]);
        else
            $home_services_reservations = $home_services_reservations->where('approved', $approved);

        $clinic_services_reservations = ServiceReservation::serviceSelection()->serviceSelection()->whereHas('type', function ($e) {
            $e->where('id', 2);
        })
            ->whereIn('branch_id', $providers)
            ->orderBy('id', 'DESC');

        if ($approved == 'all')
            $clinic_services_reservations = $clinic_services_reservations->whereIn('approved', [0, 1, 2, 3, 4, 5]);
        else
            $clinic_services_reservations = $clinic_services_reservations->where('approved', $approved);


        $result = Reservation::OfferReservationSelection()->with(['offer' => function ($q) {
            $q->select('id',
                DB::raw('title_' . app()->getLocale() . ' as title'),
                'expired_at',
                'price'
            );
        }, 'doctor' => function ($g) {
            $g->select('id', 'nickname_id', 'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                ->with(['nickname' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'rejectionResoan' => function ($rs) {
            $rs->select('id', DB::raw('name_' . app()->getLocale() . ' as rejection_reason'));
        }, 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'user' => function ($q) {
            $q->select('id', 'name', 'mobile', 'email', 'address', 'insurance_image', 'insurance_company_id', 'mobile')
                ->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'people' => function ($p) {
            $p->select('id', 'name', 'insurance_company_id', 'insurance_image')->with(['insuranceCompany' => function ($qu) {
                $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
            }]);
        }, 'provider' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'service' => function ($g) {
            $g->select('id', 'specification_id', \Illuminate\Support\Facades\DB::raw('title_' . app()->getLocale() . ' as title'), 'price')
                ->with(['specification' => function ($g) {
                    $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'type' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }])
            ->whereIn('provider_id', $providers)
            ->whereNotNull('offer_id')
            ->where('offer_id', '!=', 0);
        /*  ->whereDate('day_date', '>=', Carbon::now()->format('Y-m-d'))*/

        if ($approved == 'all')
            $result = $result->whereIn('approved', [0, 1, 2, 3, 4, 5]);
        else
            $result = $result->where('approved', $approved);

        return $result = $result->orderBy('id', 'DESC')
            ->union($doctor_reservations)
            ->union($home_services_reservations)
            ->union($clinic_services_reservations)
            ->paginate(PAGINATION_COUNT);
    }


    /* private function getAllProviderRservations(Request $request)
     {
         try {
             $validator = Validator::make($request->all(), [
                 "type" => "required|in:home_services,clinic_services,doctor,offer,all",
                 "provider_id" => "required|exists:providers,id",
             ]);


             if ($validator->fails()) {
                 $code = $this->returnCodeAccordingToInput($validator);
                 return $this->returnValidationError($code, $validator);
             }

             $provider = Provider::find($request->provider_id);
             $type = $request->type;

             if ($provider->provider_id == null) { //main provider
                 $branches = $provider->providers()->pluck('id')->toArray();
                 array_unshift($branches, $provider->id);
             } else {
                 $branches = [$provider->id];
             }

             $reservations = $this->AllReservationsByType($branches, $type);

             if (count($reservations->toArray()) > 0) {
                 $reservations->getCollection()->each(function ($reservation) use ($request) {
                     $reservation->makeHidden(['order', 'rejected_reason_type', 'reservation_total', 'admin_value_from_reservation_price_Tax', 'mainprovider', 'is_reported', 'branch_no', 'for_me', 'rejected_reason_notes', 'rejected_reason_id', 'bill_total', 'is_visit_doctor', 'rejection_reason', 'user_rejection_reason']);
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
             return $ex;
             return $this->returnError($ex->getCode(), $ex);
         }
     }*/

    /* private function AllReservationsByType(array $branches, $type)
     {

         if ($type == 'home_services') {
             return $this->getAllServicesReservations($branches);
         } elseif ($type == 'clinic_services') {
             return $this->getAllClinicServicesReservations($branches);
         } elseif ($type == 'doctor') {
             return $this->getAllDoctorReservations($branches);
         } elseif ($type == 'offer') {
             return $this->getNewReservations($branches);
         } else {
             // return all reservations

             return $this->getAllNewReservations($branches);
         }
     }*/


    public function checkProviderHomeService(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "id" => "required|exists:providers,id",
            ]);


            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $provider = Provider::select('id', 'name_' . app()->getLocale() . ' as name', 'has_home_visit')->find($request->id);

            return $this->returnData('provider', $provider);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


      public function getProviderPercentages(Request $request){
          try {
              $validator = Validator::make($request->all(), [
                  "id" => "required|exists:providers,id",
              ]);

              if ($validator->fails()) {
                  $code = $this->returnCodeAccordingToInput($validator);
                  return $this->returnValidationError($code, $validator);
              }

              $provider = Provider::select('application_percentage','application_percentage_for_offers','application_percentage_bill','application_percentage_bill_insurance')->find($request->id);
              $provider -> makevisible(['application_percentage','application_percentage_bill']);
              $provider -> makeHidden(['is_branch','hide','parent_type','provider_has_bill','has_insurance','is_lottery','rate_count']);

              return $this->returnData('provider', $provider);
          } catch (\Exception $ex) {
              return $this->returnError($ex->getCode(), $ex->getMessage());
          }
      }
}
