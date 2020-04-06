<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\MainActiveProvidersResource;
use App\Http\Resources\CPanel\OffersResource;
use App\Http\Resources\CPanel\OfferBranchesResource;
use App\Models\Filter;
use App\Models\OfferCategory;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\User;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\OfferTrait;
use App\Models\Provider;
//use App\Models\Doctor;
use App\Models\Offer;
use App\Models\OfferBranch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OfferController extends Controller
{
    use OfferTrait, GeneralTrait, PublicTrait;

    public function index()
    {
        $offers = Offer::orderBy('expired_at', 'DESC')->paginate(PAGINATION_COUNT);
        $result = new OffersResource($offers);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function mostReserved()
    {
        try {
            $reservations = Reservation::with(['coupon' => function ($qu) {
                $qu->select('id', 'provider_id', DB::raw('title_' . app()->getLocale() . ' as title'), 'code', 'photo', 'price', 'price_after_discount');
                $qu->with(['provider' => function ($q) {
                    $q->select('id', 'name_ar');
                }]);
            }])
                ->whereNotNull('promocode_id')->groupBy('promocode_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(['promocode_id', DB::raw('count(promocode_id) as count')]);

            return response()->json(['status' => true, 'data' => $reservations]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function getOfferBranches(Request $request)
    {
        try {
            $offerBranches = OfferBranch::where('offer_id', $request->id)->paginate(PAGINATION_COUNT);
            $result = new OfferBranchesResource($offerBranches);
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function create()
    {
        try {
            $data['categories'] = $this->getAllOfferParentCategoriesList();    // parent categories
            $data['users'] = $this->getAllActiveUsersList(); // active users list
            $data['providers'] = $this->getMainActiveProviders(); // active providers list
            $data['paymentMethods'] = $this->getAllPaymentMethodWithSelectedList(); // payment methods to checkboxes

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function getProviderBranchesList(Request $request)
    {
        try {
            $provider_id = $request->id ? $request->id : 0;

            if (isset($request->couponId) && $request->couponId != null)
                $branches = Provider::where('provider_id', $provider_id)->select('name_ar', 'id', 'provider_id', DB::raw('IF ((SELECT count(id) FROM offers_branches WHERE offers_branches.offer_id = ' . $request->couponId . ' AND providers.id = offers_branches.branch_id) > 0, 1, 0) as selected'))->get();
            else
                $branches = Provider::where('provider_id', $provider_id)->select('name_ar', 'id', 'provider_id', DB::raw('0 as selected'))->get();

            $result = MainActiveProvidersResource::collection($branches);
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function getChildCategoriesByParentId(Request $request)
    {
        try {
            if (isset($request->id) && !empty($request->id))
                $childCategories = $this->getChildCategoriesListByParentCategory($request->id);
            else
                $childCategories = null;

            return response()->json(['status' => true, 'data' => $childCategories]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function store(Request $request)
    {
        dd($request->all());
        $rules = [
            "title_ar" => "required|max:255",
            "title_en" => "required|max:255",
            "available_count" => "sometimes|nullable|numeric",
            "expired_at" => "required|after_or_equal:" . date('Y-m-d'),
            "provider_id" => "required|exists:providers,id",
            "status" => "required|in:0,1",
            "photo" => "required|mimes:jpeg,bmp,jpg,png",
            "category_ids" => "required|array|min:1",
            "category_ids.*" => "required|exists:offers_categories,id",
            "featured" => "required|in:1,2",    // 1 -> not featured 2 -> featured
            "paid_coupon_percentage" => "sometimes|nullable|min:0",
            "discount" => "sometimes|nullable|min:0",
            "price" => "required|min:0",
            "price_after_discount" => "required|min:0",

            "available_count_type" => "required|in:once,more_than_once",
            "started_at" => "required|date",
            "gender" => "required|in:all,males,females",
            "payment_method" => "required|array|min:1",
            "child_category_ids" => "required|array|min:1",
            "child_category_ids.*" => "required|exists:offers_categories,id",
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $result = $validator->messages()->toArray();
            return response()->json(['status' => false, 'error' => $result], 200);
        }
        $inputs = $request->only('code', 'discount', 'available_count', 'available_count_type', 'status', 'started_at', 'expired_at', 'provider_id', 'title_ar', 'title_en', 'price',
            'application_percentage', 'featured', 'paid_coupon_percentage', 'price_after_discount', 'gender', 'device_type');

        $fileName = "";
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->saveImage('copouns', $request->photo);
        }

        $inputs['photo'] = $fileName;
        $offer = $this->createOffer($inputs);

        $offer->categories()->attach($request->child_category_ids);

        if ($request->has('branchIds')) {
            $branchIds = array_filter($request->branchIds, function ($val) {
                return !empty($val);
            });
        }


        ####################################################################################################################

        /*if (isset($request->payment_method) && !empty($request->payment_method)) {

            $methods = [];
            foreach ($request->payment_method as $k => $v) {
                if ($v == 6) {
                    $methods[$k]['payment_method_id'] = $v;
                    $methods[$k]['payment_amount_type'] = $request->payment_amount_type;
                    $methods[$k]['payment_amount'] = $request->payment_amount;
                } else {
                    $methods[$k]['payment_method_id'] = $v;
                    $methods[$k]['payment_amount_type'] = null;
                    $methods[$k]['payment_amount'] = null;
                }
            }
            $offer->paymentMethods()->attach($methods);
        }*/

        if (isset($request->offer_content) && !empty($request->offer_content)) {
            foreach ($request->offer_content['ar'] as $key => $value) {
                if (!empty($value)) {
                    $offer->contents()->create([
                        'content_ar' => $value,
                        'content_en' => $request->offer_content['en'][$key],
                    ]);
                }
            }
        }

        if (isset($request->branchTimes) && !empty($request->branchTimes)) {
            $branches = $request->branchTimes;
            foreach ($branches as $branchId => $branch) {
                foreach ($branch['days'] as $dayCode => $time) {
                    $returnTimes = $this->splitTimes($time['from'], $time['to'], $branch['duration']);
                    foreach ($returnTimes as $key => $value) {
                        $offer->branchTimes()->attach($branchId, [
                            'day_code' => $dayCode,
                            'time_from' => $value['from'],
                            'time_to' => $value['to'],
                            'duration' => $branch['duration'],
                            'start_from' => $time['from'],
                            'end_to' => $time['to'],
                        ]);
                    }
                }
            }
        }

        ####################################################################################################################

        if (!isset($request->users)) {
            if (!$request->filled('available_count')) {
                Flashy::error("لابد من ادخال العدد المتاح للعرض");
                return redirect()->back()->withErrors(['available_count' => 'لابد من ادخال العدد المتاح للعرض'])->withInput($request->all());
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

        if ($offer->id) {
            if ($request->has('branchIds')) {
                $this->saveCouponBranchs($offer->id, $branchIds, $offer->provider_id);
            }

            $offer = Offer::find($offer->id);
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

        Flashy::success('تم إضافة الكوبون بنجاح');
        return redirect()->route('admin.offers');
    }

    public function edit($id)
    {
        $data['offer'] = $this->getOfferByIdWithRelations($id);
        if ($data['offer'] == null)
            return view('errors.404');
        $data['providers'] = $this->getAllMainActiveProviders();
        $data['categories'] = $this->getCategoriesWithCurrentOfferSelected($data['offer']);
        $data['users'] = $this->getActiveUsersWithCurrentOfferSelected($data['offer']);
        $data['featured'] = collect(['1' => 'غير مميز', '2' => 'مميز']);
        $data['paymentMethods'] = $this->getAllPaymentMethodWithSelected($data['offer']);
        $data['offerContents'] = $data['offer']->contents;

//        dd($data['categories']->toArray());

        return view('offers.edit', $data);
    }

    public function update($id, Request $request)
    {
//        dd($request->payment_amount_type, $request->payment_amount);
        $offer = Offer::findOrFail($id);
        $rules = [
            "title_ar" => "required|max:255",
            "title_en" => "required|max:255",
            "available_count" => "sometimes|nullable|numeric",
            "expired_at" => "required|after_or_equal:" . date('Y-m-d'),
            "provider_id" => "required|exists:providers,id",
            "status" => "required|in:0,1",
            "photo" => "sometimes|nullable|mimes:jpeg,bmp,jpg,png",
            "category_ids" => "required|array|min:1",
            "category_ids.*" => "required|exists:offers_categories,id",
            "featured" => "required|in:1,2",    // 1 -> not featured 2 -> featured
            "paid_coupon_percentage" => "sometimes|nullable|min:0",
            "discount" => "sometimes|nullable|numeric|min:0",
            "price" => "required|min:0",
            "price_after_discount" => "required|min:0",

            "available_count_type" => "required|in:once,more_than_once",
            "started_at" => "required|date",
            "gender" => "required|in:all,males,females",
            "payment_method" => "required|array|min:1",
            "child_category_ids" => "required|array|min:1",
            "child_category_ids.*" => "required|exists:offers_categories,id",

        ];

        /*if ($request->coupons_type_id == 1) {
            $rules['discount'] = "required";
            $rules['code'] = "required|max:255|unique:promocodes,code," . $id;
        }

        if ($request->coupons_type_id == 2) {
            //  $rules['price'] = "required";
            $rules['paid_coupon_percentage'] = "required";
        }*/

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $result = $validator->messages()->toArray();
            return response()->json(['status' => false, 'error' => $result], 200);
        }
        $inputs = $request->only('code', 'discount', 'available_count', 'available_count_type', 'status', 'started_at', 'expired_at', 'provider_id', 'title_ar', 'title_en', 'price',
            'application_percentage', 'featured', 'paid_coupon_percentage', 'price_after_discount', 'gender', 'device_type');

        $fileName = $offer->photo;
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->saveImage('copouns', $request->photo);
        }
        DB::beginTransaction();

        $inputs['photo'] = $fileName;
        Offer::find($id)->update($inputs);

        if ($request->has('branchIds')) {
            $branchIds = array_filter($request->branchIds, function ($val) {
                return !empty($val);
            });
        }


        ####################################################################################################################

        if (isset($request->payment_method) && !empty($request->payment_method)) {

            $methods = [];
            foreach ($request->payment_method as $k => $v) {
                if ($v == 6) {
                    $methods[$k]['payment_method_id'] = $v;
                    $methods[$k]['payment_amount_type'] = $request->payment_amount_type;
                    $methods[$k]['payment_amount'] = $request->payment_amount;
                } else {
                    $methods[$k]['payment_method_id'] = $v;
                    $methods[$k]['payment_amount_type'] = null;
                    $methods[$k]['payment_amount'] = null;
                }
            }
            $offer->paymentMethods()->sync($methods);
        }

        if (isset($request->offer_content) && !empty($request->offer_content)) {
            $offer->contents()->delete();
            foreach ($request->offer_content['ar'] as $key => $value) {
                if (!empty($value)) {
                    $offer->contents()->create([
                        'content_ar' => $value,
                        'content_en' => $request->offer_content['en'][$key],
                    ]);
                }
            }
        }

        if (isset($request->branchTimes) && !empty($request->branchTimes)) {
            $branches = $request->branchTimes;
            //// delete all branch times of the current offer
            $offer->branchTimes()->detach();
            foreach ($branches as $branchId => $branch) {
                foreach ($branch['days'] as $dayCode => $time) {
                    $returnTimes = $this->splitTimes($time['from'], $time['to'], $branch['duration']);
                    foreach ($returnTimes as $key => $value) {
                        $offer->branchTimes()->attach($branchId, [
                            'day_code' => $dayCode,
                            'time_from' => $value['from'],
                            'time_to' => $value['to'],
                            'duration' => $branch['duration'],
                            'start_from' => $time['from'],
                            'end_to' => $time['to'],
                        ]);
                    }
                }
            }
        }

        ####################################################################################################################

        if (!isset($request->users)) {
            if (!$request->filled('available_count')) {
                Flashy::error("لابد من ادخال العدد المتاح للعرض");
                return redirect()->back()->withErrors(['available_count' => 'لابد من ادخال العدد المتاح للعرض'])->withInput($request->all());
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
            OfferBranch::where('offer_id', $id)->delete();
            $this->saveCouponBranchs($id, $branchIds, $offer->provider_id);
        }

        /*if ($request->has('doctorsIds')) {
            // save doctors for  only previous branches
            PromoCode_Doctor::where('promocodes_id', $id)->delete();
            $this->saveCouponDoctors($id, $doctorsIds, $offer->provider_id);
        }*/

        //allowed users to use this offer
        if (!empty($users) && count($users) > 0) {
            $offer->users()->sync($request->users);
            $offer->update(['general' => 0]);
        } else {
            //all user can see offer
            // $promoCode->users()->sync(User::active()-> pluck('id') -> toArray());
            $offer->update(['general' => 1]);
        }
        $offer->categories()->sync($request->child_category_ids);
//        $offer->categories()->sync($request->category_ids);

        DB::commit();
        Flashy::success('تم تحديث الكوبون بنجاح');
        return redirect()->route('admin.offers');
    }

    public function destroy($id)
    {
        $offer = $this->getOfferById($id);
        if ($offer == null)
            return view('errors.404');

        if (count($offer->reservations) == 0) {
            $offer->deleteWithRelations();
            Flashy::success('تم مسح العرض بنجاح');
        } else {
            Flashy::error('لا يمكن مسح عرض مرتبط بحجوزات');
        }
        return redirect()->route('admin.offers');

    }

    public function view($id)
    {
        $offer = $this->getOfferByIdWithRelation($id);
        if ($offer == null)
            return view('errors.404');
        $beneficiaries = $this->getAllBeneficiaries($id);

        $offerBranchTimes = [];
        foreach ($offer->offerBranches as $key => $value) {
            $offerBranchTimes[$value->branch_id]['branch_name'] = Provider::find($value->branch_id)->name_ar;
            $offerBranchTimes[$value->branch_id]['duration'] = $offer->branchTimes()->where('branch_id', $value->branch_id)->value('duration');
            $offerBranchTimes[$value->branch_id]['days'] = $offer->branchTimes()->orderBy('offers_branches_times.id')->groupBy('day_code')->where('branch_id', $value->branch_id)->get(['day_code', 'start_from', 'end_to']);
        }

        $selectedChildCat = \Illuminate\Support\Facades\DB::table('offers_categories_pivot')
            ->where('offer_id', $id)
            ->pluck('category_id');
        $childCats = OfferCategory::with('parentCategory')->whereIn('id', $selectedChildCat->toArray())->get(['id', 'parent_id', 'name_ar']);

//        dd($childCats->toArray());

        return view('offers.view', compact('offer', 'beneficiaries', 'offerBranchTimes', 'childCats'));
    }

    public function filters()
    {
        $filters = Filter::adminSelection()->get();
        return view('offers.filters.index', compact('filters'));
    }

    public function addFilter()
    {
        $filters = Filter::active()->adminSelection()->get();
        return view('offers.filters.add', compact('filters'));
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
        return redirect()->route('admin.offers.filters');
    }

    public function editFilter($filterId)
    {
        $filter = Filter::find($filterId);
        if (!$filter)
            return abort('404');
        return view('offers.filters.edit', compact('filter'));
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
        return redirect()->route('admin.offers.filters');
    }

    public function deleteFilter($filterId)
    {
        $filter = Filter::find($filterId);
        if (!$filter)
            return abort('404');
        $filter->delete();
        Flashy::success('تم حذف الفلتر بنجاح ');
        return redirect()->route('admin.offers.filters');
    }

    public function splitTimes($StartTime, $EndTime, $Duration = "60")
    {
        $returnArray = [];// Define output
        $StartTime = strtotime($StartTime); //Get Timestamp
        $EndTime = strtotime($EndTime); //Get Timestamp

        $addMinutes = $Duration * 60;

        for ($i = 0; $StartTime <= $EndTime; $i++) //Run loop
        {
            $from = date("G:i", $StartTime);
            $StartTime += $addMinutes; //End time check
            $to = date("G:i", $StartTime);
            if ($EndTime >= $StartTime) {
                $returnArray[$i]['from'] = $from;
                $returnArray[$i]['to'] = $to;
            }
        }
        return $returnArray;
    }

}
