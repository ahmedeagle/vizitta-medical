<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\OfferCategoryResource;
use App\Models\OfferCategory;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class OfferCategoriesController extends Controller
{
    use GeneralTrait;

    public function index()
    {
        $offersCats = OfferCategory::with('parentCategory')->paginate(PAGINATION_COUNT);
        $result = new OfferCategoryResource($offersCats);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function create()
    {
        try {
            $result['parentCategories'] = OfferCategory::parentCategories()->active()->get(['id', 'name_' . app()->getLocale() . ' as name']);
            return response()->json(['status' => true, 'data' => $result]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                "name_ar" => "required|max:255",
                "name_en" => "required|max:255",
                "parent_id" => "nullable|exists:offers_categories,id",
                "photo" => "required",
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $fileName = "";
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->saveImage('copouns', $request->photo);
            }

            $requestData = $request->only(['parent_id', 'name_en', 'name_ar']);
            $requestData['photo'] = $fileName;
            OfferCategory::create($requestData);
            return response()->json(['status' => true, 'msg' => __('main.offer_category_added_successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $result['category'] = OfferCategory::find($request->id);
            if (!$result['category'])
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $result['parentCategories'] = OfferCategory::parentCategories()->active()->get(['id', 'name_' . app()->getLocale() . ' as name']);

            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $rules = [
                "name_ar" => "required|max:255",
                "name_en" => "required|max:255",
                "parent_id" => "nullable",
                "photo" => "sometimes|nullable",
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $category = OfferCategory::find($request->id);
            if (!$category)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $requestData = $request->only(['parent_id', 'name_en', 'name_ar']);
            if (isset($request->photo) && !empty($request->photo)) {
                $requestData['photo'] = $this->saveImage('copouns', $request->photo);
            }

            $category->update($requestData);

            return response()->json(['status' => true, 'msg' => __('main.offer_category_updated_successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $offerCat = OfferCategory::find($request->id);
            if (!$offerCat)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if ($offerCat -> offers() -> count() > 0)
                return response()->json(['success' => false, 'error' => __('main.cannot delete has offers')], 200);

            $offerCat->delete();
            return response()->json(['status' => true, 'msg' => __('main.offer_category_deleted_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function getTime(Request $request)
    {
        try {
            $result['category'] = OfferCategory::find($request->id, ['hours', 'minutes', 'seconds']);
            if (!$result['category'])
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function addToTimer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "hours" => "required|numeric|min:0",
            "minutes" => "required|numeric|min:0|max:59",
            "seconds" => "required|numeric|min:0|max:59",
            "category_id" => "required|exists:offers_categories,id"
        ]);

        if ($validator->fails()) {
            $result = $validator->messages()->toArray();
            return response()->json(['status' => false, 'error' => $result], 200);
        }

        if ($request->hours == 0 && $request->minutes == 0 && $request->seconds == 0) {
            return response()->json(['status' => false, 'error' => __('main.enter_all_validation_inputs')], 200);
        }

        $category = OfferCategory::find($request->category_id);
        $category->update([
            'hastimer' => 1,
            'hours' => $request->hours,
            'minutes' => $request->minutes,
            'seconds' => $request->seconds,
        ]);

        return response()->json(['status' => true, 'msg' => __('main.added_successfully')]);
    }

    public function reorderCategories()
    {
        $categories = OfferCategory::select('id', 'parent_id', 'name_' . app()->getLocale() . ' as name')->orderBy('lft')->get();
        return response()->json(['status' => true, 'data' => $categories]);
    }

    public function saveReorderCategories(Request $request)
    {
        $count = 0;
        $all_entries = $request->input('tree');
        if (count($all_entries)) {
            foreach ($all_entries as $key => $entry) {
                if ($entry['item_id'] != "" && $entry['item_id'] != null) {
                    $item = OfferCategory::find($entry['item_id']);
                    $item->depth = $entry['depth'];
                    $item->lft = $entry['left'];
                    $item->rgt = $entry['right'];
                    $item->save();
                    $count++;
                }
            }
        } else {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
        return response()->json(['status' => true, 'msg' => __('main.saved_successfully')]);
    }

}
