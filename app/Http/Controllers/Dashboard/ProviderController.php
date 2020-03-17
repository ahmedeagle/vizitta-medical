<?php

namespace App\Http\Controllers\Dashboard;

use App\Traits\Dashboard\ProviderTrait;
use App\Traits\Dashboard\PublicTrait;
use App\Models\Provider;
use App\Traits\OdooTrait;
use http\Env\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;
use DB;
use Auth;

class ProviderController extends Controller
{
    use PublicTrait, ProviderTrait, OdooTrait;

    public function getDataTable()
    {
        try {
            $queryStr = request('queryStr');
            return $this->getAllProviders($queryStr);
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
            $data['providers'] = Provider::where('provider_id',null)->where('name_ar', 'LIKE', '%' . trim($queryStr) . '%')->orderBy('id', 'DESC')->paginate(10);
        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $data['providers'] = Provider::where('provider_id',null)
                 ->where('name_ar', 'LIKE', '%' . trim($q) . '%')
                ->orWhere(function ($qq) use ($q) {
                    if (trim($q) == 'مفعل') {
                        $qq->where('status', 1);
                    } elseif (trim($q) == 'غير مفعل') {
                        $qq->where('status', 0);
                    }
                })   ->orWhere(function ($qq) use ($q) {
                    if (trim($q) == 'نعم') {
                        $qq->where('lottery', 1);
                    } elseif (trim($q) == 'لا') {
                        $qq->where('lottery', 0);
                    }
                })
                ->orWhere('name_en', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('username', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('application_percentage', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('application_percentage_bill', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('commercial_no', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('created_at', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhereHas('city', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('district', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                })
                ->orderBy('id', 'DESC')
                ->paginate(10);
        } else
            $data['providers'] = Provider::where('provider_id',null)->orderBy('id', 'DESC')->paginate(10);
        return view('provider.index', $data);
    }

    public function view($id)
    {
        $provider = $this->getProviderById($id);
        if ($provider == null)
            return view('errors.404');

        $branchesId = $provider->providers()->pluck('id')->toArray();
        $allReservationCount = 0;
        $acceptanceReservationCount = 0;
        $refusedReservationCount = 0;
        foreach ($branchesId as $branch_id) {
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
        }

        if ($allReservationCount == 0) {
            $acceptance_rate = 'لم يحسب بعد ';
            $refusal_rate = 'لم يحسب بعد ';
        } else {
            $acceptance_rate = round(($acceptanceReservationCount / $allReservationCount) * 100) . "%";
            $refusal_rate = round(($refusedReservationCount / $allReservationCount) * 100) . "%";
        }


        return view('provider.view', compact('provider', 'acceptance_rate', 'refusal_rate', 'allReservationCount', 'acceptanceReservationCount', 'refusedReservationCount'));

    }

    public function create()
    {
        try {
            $types = $this->getAllTypes();
            $cities = $this->getAllCities();
            $districts = $this->getAllDistricts();
            return view('provider.create', compact('types', 'cities', 'districts'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "commercial_ar" => "required|unique:providers,commercial_ar|max:225",
                "commercial_en" => "required|unique:providers,commercial_en|max:225",
                "name_ar" => "required|max:255",
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
                "application_percentage" => "required|numeric"
            ]);

            if ($validator->fails()) {
                Flashy::error('هناك بعض الاخطاء  الرجاء اصلاحها ');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

            $exists = $this->checkIfMobileExistsForOtherProviders($request->mobile);
            if ($exists) {
                return redirect()->back()->withInput($request->all())->withErrors(['mobile' => 'رقم الهاتف مسجل من قبل ']);
            }

            DB::beginTransaction();

            try {

                $fileName = "";
                if (isset($request->logo) && !empty($request->logo)) {
                    $fileName = $this->uploadImage('providers', $request->logo);
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
                Flashy::success('تم إضافة  مقدم الخدمة بنجاح  ');
                return redirect()->route('admin.provider');
            } catch (\Exception $ex) {
                DB::rollback();
            }

        } catch (\Exception $ex) {
            return view('errors.404');
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

    public function edit($id)
    {
        try {
            $provider = $this->getProviderById($id);
            $provider->makeVisible(['application_percentage']);
            if ($provider == null)
                return view('errors.404');
            // return response() -> json($provider);
            $types = $this->getAllTypes();
            $cities = $this->getAllCities();
            $districts = $this->getAllDistricts();
            return view('provider.edit', compact('provider', 'types', 'cities', 'districts'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function update($id, Request $request)
    {
        try {
            $provider = $this->getProviderById($id);
            if (!$provider) {
                return abort('404');
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
            ]);

            if ($validator->fails()) {
                Flashy::error('هناك بعض الاخطاء  الرجاء اصلاحها ');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
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
                        return redirect()->back()->withInput($request->all())->withErrors(['mobile' => 'رقم الهاتف مسجل من قبل ']);
                }
            }

            $fileName = DB::table('providers')->where('id', $provider->id)->first()->logo;
            if ($request->hasFile('logo')) {
                $fileName = $this->uploadImage('providers', $request->file('logo'));
            }

            if ($request->has('latLng') && $request->latLng != null) {
                Provider::find($id)->update(['address' => $request->latLng]);
            }

            if ($request->has('application_percentage')) {
                $provider->update(['application_percentage' => $request->application_percentage]);
            }
            $this->updateProvider($provider, $request);

            $t = $provider->update(['logo' => $fileName]);

            $provider->providers()->update(['logo' => $fileName]);

            Flashy::success('تم تعديل مقدم الخدمة بنجاح');
            return redirect()->route('admin.provider');
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    public function destroy($id)
    {
        try {
            $provider = $this->getProviderById($id);
            if ($provider == null)
                return view('errors.404');

            if (count($provider->reservations) > 0) {
                Flashy::error('لا يمكن مسح مقدم خدمة لديه حجوزات');
                return redirect()->back();
            }
            foreach ($provider->providers as $branch) {
                if (count($branch->reservations) > 0) {
                    Flashy::error('لا يمكن مسح مقدم خدمة أحد فروعه لديه حجوزات');
                    return redirect()->back();
                }
            }
            $provider->providers()->delete();
            $provider->delete();
            Flashy::success('تم مسح مقدم الخدمة وفروعه بنجاح');
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function changeStatus($id, $status)
    {
        try {
            $provider = $this->getProviderById($id);
            if ($provider == null)
                return view('errors.404');

            if ($status != 0 && $status != 1) {
                Flashy::error('إدخل كود التفعيل صحيح');
            } else {
                $this->changerProviderStatus($provider, $status);
                Flashy::success('تم تغيير حالة مقدم الخدمة بنجاح');
            }
            return redirect()->route('admin.provider');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }
}
