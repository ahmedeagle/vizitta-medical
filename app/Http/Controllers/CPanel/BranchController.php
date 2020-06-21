<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\BranchResource;
use App\Models\FeaturedBranch;
use App\Models\Provider;
use App\Traits\OdooTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\Dashboard\ProviderTrait;
use App\Traits\Dashboard\PublicTrait;
use App\Traits\CPanel\GeneralTrait;
use function foo\func;

class BranchController extends Controller
{
    use PublicTrait, ProviderTrait, OdooTrait, GeneralTrait;

    public function index()
    {
        if (request('queryStr')) {
            $queryStr = request('queryStr');
            $providers = Provider::whereNotNull('provider_id')
                ->where(function ($q) use ($queryStr) {
                    $q->where('name_ar', 'LIKE', '%' . trim($queryStr) . '%')
                        ->orwhere('name_en', 'LIKE', '%' . trim($queryStr) . '%');
                })
                ->orderBy('id', 'DESC')
                ->paginate(PAGINATION_COUNT);

        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $providers = Provider::whereNotNull('provider_id')
                ->where(function ($query) use($q) {
                    $query->where(function ($qqn) use ($q) {
                        $qqn->where('name_ar', 'LIKE', '%' . trim($q) . '%')
                            ->orWhere('name_en', 'LIKE', '%' . trim($q) . '%');
                    })
                        ->orWhere(function ($qq) use ($q) {
                            if (trim($q) == 'مفعل') {
                                $qq->where('status', 1);
                            } elseif (trim($q) == 'غير مفعل') {
                                $qq->where('status', 0);
                            }
                        })
                        ->orWhere('username', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('mobile', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('balance', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('created_at', 'LIKE binary', '%' . trim($q) . '%')
                        ->orWhereHas('city', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                        })->orWhereHas('district', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                        })->orWhereHas('provider', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                        });

                })
                ->orderBy('id', 'DESC')
                ->paginate(PAGINATION_COUNT);
        } else

            $providers = Provider::whereNotNull('provider_id')->orderBy('id', 'DESC')->paginate(PAGINATION_COUNT);

        $result = new BranchResource($providers);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function show(Request $request)
    {

        $branch = $this->getProviderDetailsById($request->id);
        if ($branch == null)
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

        $allReservationCount = $branch->reservations()->count();
        $acceptanceReservationCount = $branch->reservations()->whereIn('approved', [1, 3])->count();  // accept and complete reservations
        $refusedReservationCount = $branch->reservations()->where('approved', 2)->count();   // refuse reservation

        if ($allReservationCount == 0) {
            $acceptance_rate = __('main.not_counted_yet');
            $refusal_rate = __('main.not_counted_yet');
        } else {
            $acceptance_rate = round(($acceptanceReservationCount / $allReservationCount) * 100) . "%";
            $refusal_rate = round(($refusedReservationCount / $allReservationCount) * 100) . "%";
        }

        $result['branch'] = $branch;
        $result['acceptance_rate'] = $acceptance_rate;
        $result['refusal_rate'] = $refusal_rate;
        $result['allReservationCount'] = $allReservationCount;
        $result['acceptanceReservationCount'] = $acceptanceReservationCount;
        $result['refusedReservationCount'] = $refusedReservationCount;
        $result['branch']['show_delete'] = $branch->reservations->count() > 0 || $branch->doctors->count() > 0 ? 0 : 1;

        return response()->json(['status' => true, 'data' => $result]);
    }

    public function create()
    {
        try {
            $cities = $this->getCities();
            $districts = $this->getDistricts();
            $providers = $this->getMainActiveProviders();

            $result['providers'] = $providers;
            $result['cities'] = $cities;
            $result['districts'] = $districts;

            return response()->json(['status' => true, 'data' => $result]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $branch = $this->getProviderById($request->id);
            if ($branch == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $cities = $this->getCities();
            $districts = $this->getDistricts();
            $providers = $this->getMainActiveProviders();

            $result['branch'] = $branch;
            $result['providers'] = $providers;
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
                "provider_id" => "required|exists:providers,id",
                "name_en" => "max:255",
                "name_ar" => "max:255",
                "username" => "required|string|max:100|unique:providers,username",
                "password" => "required|max:255",
                "mobile" => array(
                    "required",
                    "numeric",
                    //  "unique:providers,mobile",
                    "digits_between:8,10",
                    "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
                ),
                "city_id" => "required|numeric|exists:cities,id",
                "district_id" => "required|numeric|exists:districts,id",
                "street" => "required",
                "status" => "required|in:0,1",
                "has_home_visit" => "required|in:0,1",
                "rate" => "numeric|min:1|max:5"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $exists = $this->checkIfMobileExistsForOtherBranches($request->mobile);
            if ($exists) {
                $result = ['mobile' => __('main.mobile_already_exists')];
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            DB::beginTransaction();

            try {
                $mainProvider = Provider::find($request->provider_id);
                if ($request->filled('branch_no')) {
                    if ($mainProvider->id) {
                        $branchs_no = Provider::where('provider_id', $mainProvider->id)->pluck('branch_no')->toArray();
                        if (!empty($branchs_no) && count($branchs_no) > 0) {
                            if (in_array($request->branch_no, $branchs_no)) {
                                $result = ['branch_no' => __('main.this_branch_number_is_already_in_your_branch')];
                                return response()->json(['status' => false, 'error' => $result], 200);
                            }
                        }
                    }
                } else {
                    $result = ['branch_no' => __('main.branch_number_is_required')];
                    return response()->json(['status' => false, 'error' => $result], 200);
                }

                $request->request->add(['api_token' => $mainProvider->api_token]);
                $this->auth('provider-api', ['city', 'district']);
                $fileName = "";
                if (isset($request->logo) && !empty($request->logo)) {
                    $fileName = $this->saveImage('providers', $request->logo);
                }

                $providerMod = Provider::create([
                    'name_en' => !$request->name_en ? $mainProvider->name_en . ' - ' . $mainProvider->city->name_en . ' - ' . $mainProvider->district->name_en : $request->name_en,
                    'name_ar' => !$request->name_ar ? $mainProvider->name_ar . ' - ' . $mainProvider->city->name_ar . ' - ' . $mainProvider->district->name_ar : $request->name_ar,
                    'username' => $request->username,
                    'password' => $request->password,
                    'mobile' => $request->mobile,
                    'longitude' => $request->longitude ? $request->longitude : 0,
                    'latitude' => $request->latitude ? $request->latitude : 0,
                    'logo' => $fileName,
                    'status' => 1,
                    'activation' => 1,
                    'email' => $request->email ? $request->email : '',
                    'address' => trim($request->address),
                    'address_ar' => trim($request->address_ar),
                    'address_en' => trim($request->address_en),
                    'city_id' => $request->city_id,
                    'district_id' => $request->district_id,
                    'provider_id' => $request->provider_id,
                    'device_token' => '',
                    'street' => trim($request->street),
                    'branch_no' => $request->branch_no,
                    "has_home_visit" => $request->has_home_visit,
                    "rate" => $request->rate,
                ]);

                if ($providerMod->id) {
                    $logo = $providerMod->provider->logo;
                    Provider::find($providerMod->id)->update(['logo' => $logo]);
                }

                // save user  to odoo erp system
                $name = $mainProvider->commercial_ar . ' - ' . $providerMod->name_ar;
                $odoo_provider_id = $this->saveProviderToOdoo($providerMod->mobile, $name);
                $providerMod->update(['odoo_provider_id' => $odoo_provider_id]);

                DB::commit();

                if ($providerMod->id) {
                    return response()->json(['status' => true, 'msg' => __('main.branch_added_successfully')]);
                }
            } catch (\Exception $ex) {
                DB::rollback();
                return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
            }

            return false;

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    protected function auth($guard, $relations = [])
    {
        $user = null;
        if (isset(request()->api_token)) {

            $api_token = request()->api_token;
            if ($guard == 'user-api') {

                $user = User::where('api_token', request()->api_token);

            } else if ($guard == 'provider-api') {

                $user = Provider::whereHas('tokens', function ($q) use ($api_token) {
                    $q->where('api_token', $api_token);
                });

            } else if ($guard == 'manager-api') {
                $user = Manager::where('api_token', request()->api_token);
            }

            if ($relations && is_array($relations))
                $user->with($relations);

            $user = $user->first();
        }
        return $user;
    }

    public function update(Request $request)
    {
        $branch = $this->getProviderById($request->id);
        if ($branch == null)
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

        $validator = Validator::make($request->all(), [
            "provider_id" => "required|exists:providers,id",
            // "branch_no" => "required|unique:providers,branch_no,".$branch -> id,
            "name_en" => "max:255",
            "name_ar" => "max:255",
            "username" => 'required|string|max:100|unique:providers,username,' . $branch->id . ',id',
            "password" => "sometimes|max:255",
            "mobile" => array(
                "required",
                "numeric",
                //  "unique:providers,mobile," . $branch->id,
                "digits_between:8,10",
                "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"
            ),
            "city_id" => "required|numeric|exists:cities,id",
            "district_id" => "required|numeric|exists:districts,id",
            "street" => "required",
            "status" => "required|in:0,1",
            "has_home_visit" => "required|in:0,1",
            "rate" => "numeric|min:1|max:5"
        ]);

        if ($validator->fails()) {
            $result = $validator->messages()->toArray();
            return response()->json(['status' => false, 'error' => $result], 200);
        }

        $exists = $this->checkIfMobileExistsForOtherBranches($request->mobile);
        if ($exists) {
            $proMobile = Provider::whereNotNull('provider_id')->where('mobile', $request->mobile)->first();
            if ($proMobile->id != $request->id) {
                $result = ['mobile' => __('main.mobile_already_exists')];
                return response()->json(['status' => false, 'error' => $result], 200);
            }
        }

        DB::beginTransaction();

        try {

            if ($request->has('latLng') && $request->latLng != null) {
                Provider::find($request->id)->update(['address' => $request->latLng]);
            }

            $mainProvider = Provider::find($branch->provider_id);
            if ($request->filled('branch_no')) {
                if ($mainProvider->id) {
                    $branchs_no = Provider::where('provider_id', $mainProvider->id)->pluck('branch_no')->toArray();
                    if (!empty($branchs_no) && count($branchs_no) > 0) {
                        if (in_array($request->branch_no, $branchs_no) && ($request->branch_no != $branch->branch_no)) {
                            $result = ['branch_no' => __('main.this_branch_number_is_already_in_your_branch')];
                            return response()->json(['status' => false, 'error' => $result], 200);
                        }
                    }
                }
            } else {
                $result = ['branch_no' => __('main.branch_number_is_required')];
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $this->updateProvider($branch, $request);
            DB::commit();
            return response()->json(['status' => true, 'msg' => __('main.branch_updated_successfully')]);
        } catch (\Exception $exception) {
            DB::rollback();
            return $exception;
        }

    }

    public function destroy(Request $request)
    {
        try {
            $branch = $this->getProviderById($request->id);
            if ($branch == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if (count($branch->reservations) > 0) {
                return response()->json(['success' => false, 'error' => __('main.branch_with_reservations_cannot_be_deleted')], 200);
            }
            $branch->delete();
            return response()->json(['status' => true, 'msg' => __('main.branch_deleted_successfully')]);
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

            $branch = $this->getProviderById($request->id);
            if ($branch == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if ($request->status != 0 && $request->status != 1) {
                return response()->json(['status' => false, 'error' => __('main.enter_valid_activation_code')], 200);
            } else {
                $this->changerProviderStatus($branch, $request->status);
                return response()->json(['status' => true, 'msg' => __('main.branch_status_changed_successfully')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function addProviderToFeatured(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "duration" => "required|numeric|min:1",
                "provider_id" => "required|exists:providers,id"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $featuredBranch = FeaturedBranch::where('branch_id', $request->provider_id)->first();
            if (!$featuredBranch) {
                FeaturedBranch::create([
                    'branch_id' => $request->provider_id,
                    'duration' => $request->duration
                ]);
            }

            return response()->json(['status' => true, 'msg' => __('main.the_clinic_was_successfully_installed')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
        }
    }

    public function removeProviderFromFeatured(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|exists:providers,id"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $featuredBranch = FeaturedBranch::where('branch_id', $request->provider_id)->first();
            if ($featuredBranch) {
                $featuredBranch->delete();
            } else {
                return response()->json(['success' => false, 'error' => __('main.the_clinic_is_not_really_installed')], 200);
            }
            return response()->json(['status' => true, 'data' => ['branchId' => $request->provider_id], 'msg' => __('main.the_clinic_was_successfully_uninstalled')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
        }
    }

}
