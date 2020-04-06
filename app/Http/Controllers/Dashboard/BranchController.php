<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\FeaturedBranch;
use App\Models\Reservation;
use App\Traits\Dashboard\ProviderTrait;
use App\Traits\Dashboard\PublicTrait;
use App\Traits\OdooTrait;
use Illuminate\Http\Request;
use App\Models\Provider;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;
use DB;

class BranchController extends Controller
{
    use PublicTrait, ProviderTrait, OdooTrait;

    public function getDataTable()
    {
        try {
            $queryStr = request('queryStr');
            return $this->getAllBranches($queryStr);
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index()
    {
        $data = [];
        $queryStr = '';
        if (request('queryStr')) {
            $queryStr = request('queryStr');
            $data['branches'] = Provider::whereNotNull('provider_id')
                ->where('name_ar', 'LIKE', '%' . trim($queryStr) . '%')
                ->orderBy('id', 'DESC')
                ->paginate(10);

        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $data['branches'] = Provider::whereNotNull('provider_id')
                ->where('name_ar', 'LIKE', '%' . trim($q) . '%')
                ->orWhere(function ($qq) use ($q) {
                    if (trim($q) == 'مفعل') {
                        $qq->where('status', 1);
                    } elseif (trim($q) == 'غير مفعل') {
                        $qq->where('status', 0);
                    }
                })
                ->orWhere('name_en', 'LIKE', '%' . trim($q) . '%')
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
                })
                ->orderBy('id', 'DESC')
                ->paginate(10);
        } else
                  $data['branches'] = Provider::whereNotNull('provider_id')->orderBy('id', 'DESC')->paginate(10);

        return view('branch.index', $data);
    }

    public function view($id)
    {

        $branch = $this->getProviderById($id);
        if ($branch == null)
            return view('errors.404');

        $allReservationCount = $branch->reservations()->count();
        $acceptanceReservationCount = $branch->reservations()->whereIn('approved', [1,3])->count();  // accept and complete reservations
        $refusedReservationCount = $branch->reservations() -> where('approved', 2)->where('rejection_reason', '!=', 0)->where('rejection_reason', '!=', '')->where('rejection_reason', '!=', 0)->whereNotNull('rejection_reason')->count(); //rejected  reservations  by providers

        if ($allReservationCount == 0) {
            $acceptance_rate = 'لم يحسب بعد ';
            $refusal_rate = 'لم يحسب بعد ';
        } else {
            $acceptance_rate = round(($acceptanceReservationCount / $allReservationCount) * 100) . "%";
            $refusal_rate = round(($refusedReservationCount / $allReservationCount) * 100) . "%";
        }

        return view('branch.view', compact('branch', 'acceptance_rate', 'refusal_rate', 'allReservationCount', 'acceptanceReservationCount', 'refusedReservationCount'));

    }

    public function create()
    {
        try {
            $cities = $this->getAllCities();
            $districts = $this->getAllDistricts();
            $providers = $this->getAllMAinActiveProviders();
            return view('branch.create', compact('cities', 'districts', 'providers'));
        } catch (\Exception $ex) {
            return view('errors.404');
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
                "has_home_visit"  => "required|in:0,1"
            ]);

            if ($validator->fails()) {
                Flashy::error('هناك بعض الاخطاء  الرجاء اصلاحها ');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

            $exists = $this->checkIfMobileExistsForOtherBranches($request->mobile);
            if ($exists) {
                return redirect()->back()->withInput($request->all())->withErrors(['mobile' => trans("messages.phone number used before")]);
            }

            DB::beginTransaction();

            try {
                $mainProvider = Provider::find($request->provider_id);
                if ($request->filled('branch_no')) {
                    if ($mainProvider->id) {
                        $branchs_no = Provider::where('provider_id', $mainProvider->id)->pluck('branch_no')->toArray();
                        if (!empty($branchs_no) && count($branchs_no) > 0) {
                            if (in_array($request->branch_no, $branchs_no)) {
                                Flashy::error('رقم الفرع هذا موجود بالفعل من ضمن افرعك  ');
                                return redirect()->back()->withInput($request->all())->withErrors(['branch_no' => 'رقم الفرع هذا موجود بالفعل من ضمن افرعك ']);
                            }
                        }
                    }
                } else {
                    Flashy::error('رقم الفرع مطلوب  ');
                    return redirect()->back()->withInput($request->all())->withErrors(['branch_no' => ' رقم الفرع مطلوب  ']);
                }

                $request->request->add(['api_token' => $mainProvider->api_token]);
                $this->auth('provider-api', ['city', 'district']);
                $fileName = "";
                if (isset($request->logo) && !empty($request->logo)) {
                    $fileName = $this->uploadImage('providers', $request->logo);
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
                    'email' => $request->email,
                    'address' => trim($request->latLng),
                    'city_id' => $request->city_id,
                    'district_id' => $request->district_id,
                    'provider_id' => $request->provider_id,
                    'device_token' => '',
                    'street' => trim($request->street),
                    'branch_no' => $request->branch_no,
                    "has_home_visit"  =>  $request->has_home_visit,
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
                    Flashy::success('تم الحفظ بنجاح ');
                    return redirect()->route('admin.branch');
                }
            } catch (\Exception $ex) {
                DB::rollback();
                return view('errors.404');
            }

        } catch (\Exception $ex) {
            return view('errors.404');
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

    public function edit($id)
    {
        try {
            $branch = $this->getProviderById($id);
            if ($branch == null)
                return view('errors.404');
            $cities = $this->getAllCities();
            $districts = $this->getAllDistricts();
            $providers = $this->getAllMAinActiveProviders();
            return view('branch.edit', compact('branch', 'cities', 'districts', 'providers'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function update($id, Request $request)
    {

        // return response() -> json($request);
        $branch = $this->getProviderById($id);
        if ($branch == null)
            return view('errors.404');

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
            "rate" => "numeric|min:1|max:5",
            "has_home_visit"  => "required|in:0,1"
        ]);

        if ($validator->fails()) {
            Flashy::error('هناك بعض الاخطاء  الرجاء اصلاحها ');
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }

        $exists = $this->checkIfMobileExistsForOtherBranches($request->mobile);
        if ($exists) {
            $proMobile = Provider::whereNotNull('provider_id')->where('mobile', $request->mobile)->first();
            if ($proMobile->id != $request->id)
                return redirect()->back()->withInput($request->all())->withErrors(['mobile' => 'رقم الهاتف مسجل من قبل ']);
        }

        DB::beginTransaction();

        try {

            if ($request->has('latLng') && $request->latLng != null) {
                Provider::find($id)->update(['address' => $request->latLng]);
            }

            $mainProvider = Provider::find($branch->provider_id);
            if ($request->filled('branch_no')) {
                if ($mainProvider->id) {
                    $branchs_no = Provider::where('provider_id', $mainProvider->id)->pluck('branch_no')->toArray();
                    if (!empty($branchs_no) && count($branchs_no) > 0) {
                        if (in_array($request->branch_no, $branchs_no) && ($request->branch_no != $branch->branch_no)) {
                            Flashy::error('رقم الفرع هذا موجود بالفعل من ضمن افرعك  ');
                            return redirect()->back()->withInput($request->all())->withErrors(['branch_no' => 'رقم الفرع هذا موجود بالفعل من ضمن افرعك ']);
                        }
                    }
                }
            } else {
                Flashy::error('رقم الفرع مطلوب  ');
                return redirect()->back()->withInput($request->all())->withErrors(['branch_no' => ' رقم الفرع مطلوب  ']);
            }

            $this->updateProvider($branch, $request);
            DB::commit();
            Flashy::success('تم تعديل الفرع بنجاح');
            return redirect()->route('admin.branch');
        } catch (\Exception $exception) {
            DB::rollback();
        }

    }

    public function destroy($id)
    {
        try {
            $branch = $this->getProviderById($id);
            if ($branch == null)
                return view('errors.404');

            if (count($branch->reservations) > 0) {
                Flashy::error('لا يمكن مسح فرع لديه حجوزات');
                return redirect()->back();
            }
            $branch->delete();
            Flashy::success('تم مسح الفرع بنجاح');
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function changeStatus($id, $status)
    {
        try {
            $branch = $this->getProviderById($id);
            if ($branch == null)
                return view('errors.404');

            if ($status != 0 && $status != 1) {
                Flashy::error('إدخل كود التفعيل صحيح');
            } else {
                $this->changerProviderStatus($branch, $status);
                Flashy::success('تم تغيير حالة الفرع بنجاح');
            }
            return redirect()->route('admin.branch');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }


    public function addProviderTOFeatured(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "duration" => "required|numeric|min:1",
                "provider_id" => "required|exists:providers,id"
            ]);

            if ($validator->fails()) {
                Flashy::error($validator->errors()->first());
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            FeaturedBranch::create([
                'branch_id' => $request->provider_id,
                'duration' => $request->duration
            ]);

            Flashy::success('تم تثبيت العياده  بنجاح');
            return redirect()->route('admin.branch');
        } catch (\Exception $ex) {
            return abort('404');
        }
    }

    public function removeProviderFromFeatured(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|exists:providers,id"
            ]);

            if ($validator->fails()) {
                Flashy::error($validator->errors()->first());
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            FeaturedBranch::where('branch_id', $request->provider_id)->delete();
            return response()->json(['branchId' => $request->provider_id], 200);
        } catch (\Exception $ex) {
            return abort('404');
        }
    }
}
