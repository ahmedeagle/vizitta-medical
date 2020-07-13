<?php

namespace App\Http\Controllers\CPanel;
use App\Http\Resources\CPanel\MainActiveProvidersResource;
use App\Http\Resources\CPanel\OffersResource;
use App\Http\Resources\CPanel\OfferBranchesResource;
//use App\Http\Resources\OfferUserResource;
use App\Jobs\AddOfferUsers;
use App\Jobs\UpdateOfferUsers;
use App\Models\OfferTime;
use App\Models\Filter;
use App\Models\OfferCategory;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\User;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\Dashboard\PublicTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\OfferTrait;
use App\Models\Provider;
//use App\Models\Doctor;
use App\Models\Offer;
use App\Models\OfferBranch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mockery\Exception;

class OfferController extends Controller
{
    use OfferTrait, GeneralTrait, PublicTrait;

    public function index()
    {

        if (request('queryStr')) {
            $queryStr = request('queryStr');
            $offers = Offer::orderBy('expired_at', 'DESC')
                ->where(function ($q) use ($queryStr) {
                    $q->where('title_ar', 'LIKE', '%' . trim($queryStr) . '%')
                        ->orwhere('title_en', 'LIKE', '%' . trim($queryStr) . '%');
                })
                ->paginate(PAGINATION_COUNT);
        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $offers = Offer::where('title_ar', 'LIKE', '%' . trim($q) . '%')
                ->orwhere('title_en', 'LIKE', '%' . trim($q) . '%')
                ->orWhere(function ($qq) use ($q) {
                    if (trim($q) == 'مفعل') {
                        $qq->where('status', 1);
                    } elseif (trim($q) == 'غير مفعل') {
                        $qq->where('status', 0);
                    }
                })
                ->orWhere('started_at', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('expired_at', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('available_count', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('price', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('price_after_discount', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('created_at', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere(function ($qq) use ($q) {
                    if (trim($q) == 'مميز') {
                        $qq->where('featured', '2');
                    } elseif (trim($q) == 'غير مميز') {
                        $qq->where('featured', 1);
                    }
                })->orWhereHas('provider', function ($query) use ($q) {
                    $query->where(function ($query) use ($q) {
                        $query->where('name_en', 'LIKE', '%' . trim($q) . '%')
                            ->orwhere('name_ar', 'LIKE', '%' . trim($q) . '%');
                    })
                        ->orWhereHas('provider', function ($query) use ($q) {
                            $query->where('name_en', 'LIKE', '%' . trim($q) . '%')
                                ->orwhere('name_ar', 'LIKE', '%' . trim($q) . '%');
                        });

                })
                ->orderBy('id', 'DESC')
                ->paginate(PAGINATION_COUNT);
        } else
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
//        dd($request->all());
        try {
            $rules = [
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "available_count" => "sometimes|nullable|numeric",
                "expired_at" => "required|after_or_equal:" . date('Y-m-d'),
                "provider_id" => "required|exists:providers,id",
                "status" => "required|in:0,1",
                "photo" => "required",
//              "photo" => "required|mimes:jpeg,bmp,jpg,png",
//              "category_ids" => "required|array|min:1",
//              "category_ids.*" => "required|exists:offers_categories,id",
                "featured" => "required|in:1,2",    // 1 -> not featured 2 -> featured
//                "paid_coupon_percentage" => "sometimes|nullable|min:0",
//                "discount" => "sometimes|nullable|min:0",
                "application_percentage" => "required",
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

            $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

            // working times validation
            if (isset($request->branchTimes) && !empty($request->branchTimes)) {
                foreach ($request->branchTimes as $key => $branchInfo) {
                    if (count($branchInfo['day_times']) > 0) {
                        foreach ($branchInfo['day_times'] as $k => $working_day) {

                            if (empty($working_day['from']) or empty($working_day['to'])) {
                                return response()->json(['status' => false, 'error' => __('main.enter_from_or_to')], 200);
                            }

                            $from = Carbon::parse($working_day['from']);
                            $to = Carbon::parse($working_day['to']);
                            if (!in_array(Str::ucfirst($working_day['day']), $days) || $to->diffInMinutes($from) < $branchInfo['reservation_period']) {
                                return response()->json(['status' => false, 'error' => __('main.reservation_period_greater_than_from_and_to')], 200);
                            }
                        }
                    }

                }
            }

            $inputs = $request->only('code', 'application_percentage', 'available_count', 'available_count_type', 'status', 'started_at', 'expired_at', 'provider_id', 'title_ar', 'title_en', 'price',
                'application_percentage', 'featured', 'price_after_discount', 'gender', 'device_type');

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

            if (isset($request->payment_method) && !empty($request->payment_method)) {
                foreach ($request->payment_method as $k => $method) {
                    $offer->paymentMethods()->attach($method['payment_method_id'], ['payment_amount_type' => $method['payment_amount_type'], 'payment_amount' => $method['payment_amount']]);
                }
            }

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
                foreach ($request->branchTimes as $key => $branchInfo) {

                    if (count($branchInfo['day_times']) > 0) {
                        foreach ($branchInfo['day_times'] as $k => $working_day) {

                            $from = Carbon::parse($working_day['from']);
                            $to = Carbon::parse($working_day['to']);

                            $working_days_data = [
                                'offer_id' => $offer->id,
                                'branch_id' => $branchInfo['branch_id'],
                                'day_name' => strtolower($working_day['day']),
                                'day_code' => substr(strtolower($working_day['day']), 0, 3),
                                'from_time' => $from->format('H:i'),
                                'to_time' => $to->format('H:i'),
                                'order' => array_search(strtolower($working_day['day']), $days),
                                'reservation_period' => $branchInfo['reservation_period']
                            ];

                            $times = OfferTime::insert($working_days_data);
                        }
                    }

                }
            }

            ####################################################################################################################

            if (!isset($request->users)) {
                if (!$request->filled('available_count')) {
                    return response()->json(['status' => false, 'error' => __('main.enter_all_validation_inputs')], 200);
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
                    return response()->json(['status' => false, 'error' => __('main.oops_error')], 200);
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
                    dispatch(new AddOfferUsers($offer, $request->users))->onConnection('sync');
                    $offer->update(['general' => 0]);
                } else {
                    //all user can see offer
                    //$offer->users()->attach(User::active() -> pluck('id') -> toArray());
                    $offer->update(['general' => 1]);
                }
            }

            DB::commit();

            return response()->json(['status' => true, 'msg' => __('main.offer_added_successfully')]);

        } catch (Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $data['offer'] = $this->getOfferDetailsById($request->id);
            if ($data['offer'] == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $data['providers'] = $this->getMainActiveProviders(); // active providers list
            $data['categories'] = $this->getOfferCategoriesWithSelected($data['offer']);
            $data['users'] = $this->getAllActiveUsersList(); // active users list
            $data['selected_users'] = $this->getOfferUser($data['offer']); // active users list

            $data['paymentMethods'] = $this->getAllPaymentMethodWithSelectedList($data['offer']); // payment methods to checkboxes
            $data['offerContents'] = $data['offer']->contents;

            $data['offerBranchTimes'] = $this->_group_by($data['offer']->offerBranchTimes, 'branch_id');

            unset($data['offer']['offerBranchTimes']);
            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $offer = Offer::findOrFail($request->id);
            $rules = [
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "available_count" => "sometimes|nullable|numeric",
                "expired_at" => "required|after_or_equal:" . date('Y-m-d'),
                "provider_id" => "required|exists:providers,id",
                "status" => "required|in:0,1",
                "photo" => "sometimes|nullable",
//            "photo" => "sometimes|nullable|mimes:jpeg,bmp,jpg,png",
//            "category_ids" => "required|array|min:1",
//            "category_ids.*" => "required|exists:offers_categories,id",
                "featured" => "required|in:1,2",    // 1 -> not featured 2 -> featured
//                "paid_coupon_percentage" => "sometimes|nullable|min:0",
//                "discount" => "sometimes|nullable|numeric|min:0",
                "application_percentage" => "required",
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

            $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

            // working times validation
            if (isset($request->branchTimes) && !empty($request->branchTimes)) {
                foreach ($request->branchTimes as $key => $branchInfo) {
                    if (count($branchInfo['day_times']) > 0) {
                        foreach ($branchInfo['day_times'] as $k => $working_day) {

                            if (empty($working_day['from']) or empty($working_day['to'])) {
                                return response()->json(['status' => false, 'error' => __('main.enter_from_or_to')], 200);
                            }

                            $from = Carbon::parse($working_day['from']);
                            $to = Carbon::parse($working_day['to']);
                            if (!in_array(Str::ucfirst($working_day['day']), $days) || $to->diffInMinutes($from) < $branchInfo['reservation_period']) {
                                return response()->json(['status' => false, 'error' => __('main.reservation_period_greater_than_from_and_to')], 200);
                            }
                        }
                    }

                }
            }

            $inputs = $request->only('code', 'application_percentage', 'available_count', 'available_count_type', 'status', 'started_at', 'expired_at', 'provider_id', 'title_ar', 'title_en', 'price',
                'application_percentage', 'featured', 'price_after_discount', 'gender', 'device_type');

            $fileName = $offer->photo;
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->saveImage('copouns', $request->photo);
            }
            DB::beginTransaction();

            $inputs['photo'] = $fileName;
            Offer::find($request->id)->update($inputs);

            if ($request->has('branchIds')) {
                $branchIds = array_filter($request->branchIds, function ($val) {
                    return !empty($val);
                });
            }


            ####################################################################################################################

            if (isset($request->payment_method) && !empty($request->payment_method)) {
                $offer->paymentMethods()->detach();
                foreach ($request->payment_method as $k => $method) {
                    $offer->paymentMethods()->attach($method['payment_method_id'], ['payment_amount_type' => $method['payment_amount_type'], 'payment_amount' => $method['payment_amount']]);
                }
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
                $offer->times()->delete();
                foreach ($request->branchTimes as $key => $branchInfo) {
                    if (count($branchInfo['day_times']) > 0) {
                        foreach ($branchInfo['day_times'] as $k => $working_day) {
                            $from = Carbon::parse($working_day['from']);
                            $to = Carbon::parse($working_day['to']);
                            $working_days_data = [
                                'offer_id' => $offer->id,
                                'branch_id' => $branchInfo['branch_id'],
                                'day_name' => strtolower($working_day['day']),
                                'day_code' => substr(strtolower($working_day['day']), 0, 3),
                                'from_time' => $from->format('H:i'),
                                'to_time' => $to->format('H:i'),
                                'order' => array_search(strtolower($working_day['day']), $days),
                                'reservation_period' => $branchInfo['reservation_period']
                            ];

                            $times = OfferTime::insert($working_days_data);
                        }
                    }

                }
            }

            ####################################################################################################################

            if (!isset($request->users)) {
                if (!$request->filled('available_count')) {
                    return response()->json(['status' => false, 'error' => __('main.enter_all_validation_inputs')], 200);
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
                    return response()->json(['status' => false, 'error' => __('main.oops_error')], 200);
                }

                $users = $usersIds;
            }

            if ($request->has('branchIds')) {
                OfferBranch::where('offer_id', $request->id)->delete();
                $this->saveCouponBranchs($request->id, $branchIds, $offer->provider_id);
            }

            //allowed users to use this offer
            if (!empty($users) && count($users) > 0) {
                dispatch(new UpdateOfferUsers($offer, $request->users))->onConnection('sync');
                $offer->update(['general' => 0]);
            } else {
                //all user can see offer
                $offer->update(['general' => 1]);
            }
            $offer->categories()->sync($request->child_category_ids);

            DB::commit();

            return response()->json(['status' => true, 'msg' => __('main.offer_updated_successfully')]);

        } catch (Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function show(Request $request)
    {
        try {
            $data['offer'] = $this->getOfferByIdWithRelation($request->id);
            if ($data['offer'] == null)
                return response()->json(['status' => false, 'error' => __('main.not_found')], 200);

            $data['beneficiaries'] = $this->getAllBeneficiaries($request->id);

            $data['offerBranchTimes'] = $this->_group_by($data['offer']->offerBranchTimes, 'branch_id');

            $selectedChildCat = \Illuminate\Support\Facades\DB::table('offers_categories_pivot')
                ->where('offer_id', $request->id)
                ->pluck('category_id');
            $data['childCats'] = OfferCategory::with('parentCategory')->whereIn('id', $selectedChildCat->toArray())->get(['id', 'parent_id', 'name_ar']);

            unset($data['offer']['offerBranchTimes']);
            unset($data['offer']['branchTimes']);

            //        dd($childCats->toArray());

            return response()->json(['status' => true, 'data' => $data]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $offer = $this->getOfferById($request->id);
            if ($offer == null)
                return response()->json(['status' => false, 'error' => __('main.not_found')], 200);

            if (count($offer->reservations) == 0) {
                $offer->delete();
                return response()->json(['status' => true, 'msg' => __('main.offer_deleted_successfully')]);
            }
                return response()->json(['status' => false, 'error' => __('main.offer_with_reservations_cannot_be_deleted')], 200);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    function _group_by($inputArray, $key)
    {
        $result = [];
        foreach ($inputArray as $val) {
            $result[$val[$key]][] = $val;
        }
        return $result;
    }

}
