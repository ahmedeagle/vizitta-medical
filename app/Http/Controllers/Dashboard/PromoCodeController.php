<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Filter;
use App\Models\Reservation;
use App\Models\User;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\PromoCodeTrait;
use App\Models\Provider;
use App\Models\Doctor;
use App\Models\PromoCode;
use App\Models\PromoCode_branch;
use App\Models\PromoCode_Doctor;
use Validator;
use Flashy;
use DB;

class PromoCodeController extends Controller
{
    use PromoCodeTrait, PublicTrait;

    public function getDataTable()
    {
        return $this->getAll();
    }

    public function index()
    {
        return view('promoCode.index');
    }


    public function mostReserved()
    {

         $reservations =  Reservation::with(['coupon' => function ($qu) {
            $qu->select('id','provider_id','coupons_type_id', DB::raw('title_' . app()->getLocale() . ' as title'), 'code', 'photo', 'price', 'price_after_discount');
            $qu -> with(['provider' => function($q){
                $q->select('id','name_ar');
            }]);
        }])
            ->whereNotNull('promocode_id')->groupBy('promocode_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get(['promocode_id', DB::raw('count(promocode_id) as count')]);

        return view('promoCode.mostreserved',compact('reservations'));
    }

    public function getDataTablePromoCodeBranches($promoId)
    {
        try {
            return $this->getBranchTable($promoId);
        } catch (\Exception $ex) {
            return $ex;
        }
    }


    public function getDataTablePromoCodeDoctors($promoId)
    {
        try {
            return $this->getDoctorTable($promoId);
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function add()
    {
        $data['providers'] = $this->getAllMainActiveProviders();
        // $specifications = $this->getAllSpecifications();
        $data['categories'] = $this->getAllCategoriesCollection();    // categories
        $data['users'] = $this->getAllActiveUsers();
        $data['featured'] = collect(['1' => 'غير مميز', '2' => 'مميز']);
        return view('promoCode.add', $data);
    }

    public function getProviderBranches(Request $request)
    {
        $parent_id = 0;
        if ($request->parent_id) {
            $parent_id = $request->parent_id;
        }
        /* $couponBranches =[];
         if(isset($request -> couponId)){
             $couponBranches = PromoCode_branch::where('promocodes_id',$request -> couponId) -> pulck('branch_id') -> toArray();
         }*/

        if (isset($request->couponId) && $request->couponId != null)
            $branches = Provider::where('provider_id', $parent_id)->select('name_ar', 'id', 'provider_id', DB::raw('IF ((SELECT count(id) FROM promocodes_branches WHERE promocodes_branches.promocodes_id = ' . $request->couponId . ' AND providers.id = promocodes_branches.branch_id) > 0, 1, 0) as selected'))->get();
        else
            $branches = Provider::where('provider_id', $parent_id)->select('name_ar', 'id', 'provider_id', DB::raw('0 as selected'))->get();

        $view = view('includes.loadbranches', compact('branches'))->renderSections();
        return response()->json([
            'content' => $view['main'],
        ]);
    }


    public function getBranchDoctors(Request $request)
    {
        $parent_id = [];
        if ($request->branche_id && count($request->branche_id) > 0) {
            $parent_id = $request->branche_id;
        }
        if (isset($request->couponId) && $request->couponId != null)
            $doctors = Doctor::whereIn('provider_id', $parent_id)->select('name_ar', 'id', 'provider_id', DB::raw('IF ((SELECT count(id) FROM promocodes_doctors WHERE promocodes_doctors.promocodes_id = ' . $request->couponId . ' AND doctors.id = promocodes_doctors.doctor_id) > 0, 1, 0) as selected'))->get();
        else
            $doctors = Doctor::whereIn('provider_id', $parent_id)->select('name_ar', 'id', 'provider_id', DB::raw('0 as selected'))->get();

        $view = view('includes.loaddoctors', compact('doctors'))->renderSections();
        return response()->json([
            'content' => $view['main'],
        ]);
    }

    public function branches($promoCodeId)
    {
        return view('promoCode.branches')->with('promoCodeId', $promoCodeId);
    }

    public function doctors($promoCodeId)
    {

        /* return  promoCode::where('id',$promoCodeId)-> with(['PromoCodeDoctors' => function($q){
                $q -> select('*')
                   ->with(['doctor' => function($qq){
                     $qq -> select('id','name_ar') ;
                   }]);
          }]) -> get(); */

        return view('promoCode.doctors')->with('promoCodeId', $promoCodeId);

    }

    public function store(Request $request)
    {
        $rules = [
            "title_ar" => "required|max:255",
            "title_en" => "required|max:255",
            "features_ar" => "required",
            "features_en" => "required",
            "available_count" => "sometimes|nullable|numeric",
            "expired_at" => "required|after_or_equal:" . date('Y-m-d'),
            "provider_id" => "required|exists:providers,id",
            "coupons_type_id" => "required|exists:coupons_type,id",
            "status" => "required|in:0,1",
            // "branchIds"       => "required_with:provider_id"
            "photo" => "required|mimes:jpeg,bmp,jpg,png",
            "category_ids" => "required|array|min:1",
            "category_ids.*" => "required|exists:promocodes_categories,id",
            "featured" => "required|in:1,2",    // 1 -> not featured 2 -> featured
            "paid_coupon_percentage" => "sometimes|nullable|min:0",
            "discount" => "sometimes|nullable|min:0",
            "price" => "required|min:0",
            "price_after_discount" => "required|min:0"

        ];

        if ($request->coupons_type_id == 1) {
            $rules['discount'] = "required";
            $rules['code'] = "required|unique:promocodes,code|max:255";
        }

        if ($request->coupons_type_id == 2) {
            $rules['price'] = "required";
            $rules['paid_coupon_percentage'] = "required";
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            Flashy::error($validator->errors()->first());
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }
        $inputs = $request->only('coupons_type_id', 'code', 'discount', 'available_count', 'status', 'expired_at', 'provider_id', 'title_ar', 'title_en', 'features_ar', 'features_en', 'price', 'application_percentage', 'featured', 'paid_coupon_percentage', 'price_after_discount');

        $fileName = "";
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->uploadImage('copouns', $request->photo);
        }

        $inputs['photo'] = $fileName;
        $promoCode = $this->createPromoCode($inputs);

        $promoCode->categories()->attach($request->category_ids);

        if ($request->has('branchIds')) {
            $branchIds = array_filter($request->branchIds, function ($val) {
                return !empty($val);
            });
        }

        if ($request->has('doctorsIds')) {
            $doctorsIds = array_filter($request->doctorsIds, function ($val) {
                return !empty($val);
            });
        }

        if (!isset($request->users)) {
            if (!$request->filled('available_count')) {
                Flashy::error("لأابد من ادخال العدد المتاح للعرض");
                return redirect()->back()->withErrors(['available_count' => 'لأابد من ادخال العدد المتاح للعرض'])->withInput($request->all());
            }
        }
        DB::beginTransaction();
        $users = [];  // allowed users to use this offer
        if ($request->has('users') && is_array($request->users)) {
            $usersIds = array_filter($request->users, function ($val) {
                return !empty($val);
            });
            //check if all ids exists in user table
            $count = count($usersIds);
            $usersIdCount = User::whereIn('id', $usersIds)->count('id');
            if ($count != $usersIdCount) {
                Flashy::error("بعض من المستخدمين غير موجودين لدينا");
                return redirect()->back()->withErrors(['users' => 'بعض من المستخدمين غير موجودين لدينا'])->withInput($request->all());
            }

            $users = $usersIds;
        }

        if ($promoCode->id) {
            if ($request->has('branchIds')) {
                $this->saveCouponBranchs($promoCode->id, $branchIds, $promoCode->provider_id);
            }

            if ($request->has('doctorsIds')) {
                // save doctors for  only previous branches
                $this->saveCouponDoctors($promoCode->id, $doctorsIds, $promoCode->provider_id);
            }

            $offer = PromoCode::find($promoCode->id);
            //allowed users to use this offer
            if (!empty($users) && count($users) > 0) {
                $offer->users()->attach($request->users);
                $offer->update(['general' => 0]);
            } else {
                //all user can see offer
                //$offer->users()->attach(User::active() -> pluck('id') -> toArray());
                $offer->update(['general' => 1]);
            }
        }

        DB::commit();

        Flashy::success('تم إضافة الكوبون  بنجاح');
        return redirect()->route('admin.promoCode');
    }


    public function edit($id)
    {
        $data['promoCode'] = $this->getPromoCodeByIdWithRelations($id);
        if ($data['promoCode'] == null)
            return view('errors.404');
        $data['providers'] = $this->getAllMainActiveProviders();
        $data['categories'] = $this->getAllCategoriesWithCurrentOfferSelected($data['promoCode']);
        $data['users'] = $this->getAllActiveUsersWithCurrentOfferSelected($data['promoCode']);
        $data['featured'] = collect(['1' => 'غير مميز', '2' => 'مميز']);
        return view('promoCode.edit', $data);
    }

    public function update($id, Request $request)
    {
        $promoCode = PromoCode::findOrFail($id);
        $rules = [
            "title_ar" => "required|max:255",
            "title_en" => "required|max:255",
            "features_ar" => "required",
            "features_en" => "required",
            "available_count" => "sometimes|nullable|numeric",
            "expired_at" => "required|after_or_equal:" . date('Y-m-d'),
            "provider_id" => "required|exists:providers,id",
            "coupons_type_id" => "required|exists:coupons_type,id",
            "status" => "required|in:0,1",
            "photo" => "sometimes|nullable|mimes:jpeg,bmp,jpg,png",
            "category_ids" => "required|array|min:1",
            "category_ids.*" => "required|exists:promocodes_categories,id",
            "featured" => "required|in:1,2",    // 1 -> not featured 2 -> featured
            "paid_coupon_percentage" => "sometimes|nullable|min:0",
            "discount" => "sometimes|nullable|numeric|min:0",
            "price" => "required|min:0",
            "price_after_discount" => "required|min:0"
        ];

        if ($request->coupons_type_id == 1) {
            $rules['discount'] = "required";
            $rules['code'] = "required|max:255|unique:promocodes,code," . $id;
        }

        if ($request->coupons_type_id == 2) {
            //  $rules['price'] = "required";
            $rules['paid_coupon_percentage'] = "required";
        }

        $validator = Validator::make($request->all(), $rules
        );

        if ($validator->fails()) {
            Flashy::error($validator->errors()->first());
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }
        $inputs = $request->only('coupons_type_id', 'code', 'discount', 'available_count', 'status', 'expired_at', 'provider_id', 'title_ar', 'title_en', 'features_ar', 'features_en', 'price', 'price_after_discount', 'application_percentage', 'featured', 'paid_coupon_percentage');

        $fileName = $promoCode->photo;
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->uploadImage('copouns', $request->photo);
        }
        DB::beginTransaction();

        $inputs['photo'] = $fileName;
        PromoCode::find($id)->update($inputs);

        if ($request->has('branchIds')) {
            $branchIds = array_filter($request->branchIds, function ($val) {
                return !empty($val);
            });
        }

        if ($request->has('doctorsIds')) {
            $doctorsIds = array_filter($request->doctorsIds, function ($val) {
                return !empty($val);
            });
        }

        if (!isset($request->users)) {
            if (!$request->filled('available_count')) {
                Flashy::error("لأابد من ادخال العدد المتاح للعرض");
                return redirect()->back()->withErrors(['available_count' => 'لأابد من ادخال العدد المتاح للعرض'])->withInput($request->all());
            }
        }

        $users = [];  // allowed users to use this offer
        if ($request->has('users') && is_array($request->users)) {
            $usersIds = array_filter($request->users, function ($val) {
                return !empty($val);
            });
            //check if all ids exists in user table
            $count = count($usersIds);
            $usersIdCount = User::whereIn('id', $usersIds)->count('id');
            if ($count != $usersIdCount) {
                Flashy::error("بعض من المستخدمين غير موجودين لدينا");
                return redirect()->back()->withErrors(['users' => 'بعض من المستخدمين غير موجودين لدينا'])->withInput($request->all());
            }

            $users = $usersIds;
        }


        if ($request->has('branchIds')) {
            PromoCode_branch::where('promocodes_id', $id)->delete();
            $this->saveCouponBranchs($id, $branchIds, $promoCode->provider_id);
        }

        if ($request->has('doctorsIds')) {
            // save doctors for  only previous branches
            PromoCode_Doctor::where('promocodes_id', $id)->delete();
            $this->saveCouponDoctors($id, $doctorsIds, $promoCode->provider_id);
        }

        //allowed users to use this offer
        if (!empty($users) && count($users) > 0) {
            $promoCode->users()->sync($request->users);
            $promoCode->update(['general' => 0]);
        } else {
            //all user can see offer
            // $promoCode->users()->sync(User::active()-> pluck('id') -> toArray());
            $promoCode->update(['general' => 1]);
        }
        $promoCode->categories()->sync($request->category_ids);

        DB::commit();
        Flashy::success('تم  تحديث الكوبون بنجاح');
        return redirect()->route('admin.promoCode');
    }

    public function destroy($id)
    {

        $promoCode = $this->getPromoCodeById($id);
        if ($promoCode == null)
            return view('errors.404');

        if (count($promoCode->reservations) == 0) {
            $promoCode->deleteWithRelations();
            Flashy::success('تم مسح الرمز بنجاح');
        } else {
            Flashy::error('لا يمكن مسح رمز مرتبط بحجوزات');
        }
        return redirect()->route('admin.promoCode');

    }

    public function view($id)
    {
        $promoCode = $this->getPromoCodeByIdWithRelation($id);
        if ($promoCode == null)
            return view('errors.404');
        $beneficiaries = $this->getAllBeneficiaries($id);
        return view('promoCode.view', compact('promoCode', 'beneficiaries'));
    }

    public function filters()
    {
        $filters = Filter::adminSelection()->get();
        return view('promoCode.filters.index', compact('filters'));
    }

    public function addFilter()
    {
        $filters = Filter::active()->adminSelection()->get();
        return view('promoCode.filters.add', compact('filters'));
    }

    public function storeFilters(Request $request)
    {
        $rules = [
            "title_ar" => "required|max:255",
            "title_en" => "required|max:255",
            "status" => "required|in:0,1",
            "operation" => 'required|in:0,1,2,3,4,5'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            Flashy::error($validator->errors()->first());
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }

        if (in_array($request->operation, [0, 1, 2])) {    // 0-> less than 1-> greater than 2-> equal to
            if (!$request->price or !is_numeric($request->price) or !$request->price > 0) {
                return redirect()->back()->withErrors(['price' => 'السعر مطلوب  مع  هذا النوع من الفلتر  '])->withInput($request->all());
            }
        }
        Filter::create($request->all());
        Flashy::success('تم اضافه عمليه الفلتره بنجاح ');
        return redirect()->route('admin.promoCode.filters');
    }

    public function editFilter($filterId)
    {
        $filter = Filter::find($filterId);
        if (!$filter)
            return abort('404');
        return view('promoCode.filters.edit', compact('filter'));
    }

    public function updateFilter($filterId, Request $request)
    {
        $rules = [
            "title_ar" => "required|max:255",
            "title_en" => "required|max:255",
            "status" => "required|in:0,1",
            "operation" => 'required|in:0,1,2,3,4,5'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            Flashy::error($validator->errors()->first());
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }

        if (in_array($request->operation, [0, 1, 2])) {    // 0-> less than 1-> greater than 2-> equal to
            if (!$request->price or !is_numeric($request->price) or !$request->price > 0) {
                return redirect()->back()->withErrors(['price' => 'السعر مطلوب في هذه الحاله '])->withInput($request->all());
            }
        }
        $filter = Filter::find($filterId);
        if (!$filter)
            return abort('404');
        $filter->update($request->all());
        Flashy::success('تم التحديث بنجاح ');
        return redirect()->route('admin.promoCode.filters');
    }

    public function deleteFilter($filterId)
    {
        $filter = Filter::find($filterId);
        if (!$filter)
            return abort('404');
        $filter->delete();
        Flashy::success('تم حذف الفلتر  بنجاح ');
        return redirect()->route('admin.promoCode.filters');
    }

}
