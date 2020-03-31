<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\FilterResource;
use App\Models\Filter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class OfferFilterController extends Controller
{

    public function index()
    {
        $filters = Filter::paginate(PAGINATION_COUNT);
        $result = new FilterResource($filters);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "status" => "required|in:0,1",
                "operation" => 'required|in:0,1,2,3,4,5'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            if (in_array($request->operation, [0, 1, 2])) {    // 0-> less than 1-> greater than 2-> equal to
                if (!$request->price or !is_numeric($request->price) or !$request->price > 0) {
                    $result = ['price' => __('main.required_price_with_this_filter_type')];
                    return response()->json(['status' => false, 'error' => $result], 200);
                }
            }
            Filter::create($request->all());
            return response()->json(['status' => true, 'msg' => __('main.filter_type_added_successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $filter = Filter::find($request->id);
            if (!$filter)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            return response()->json(['status' => true, 'data' => $filter]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $rules = [
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "status" => "required|in:0,1",
                "operation" => 'required|in:0,1,2,3,4,5'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            if (in_array($request->operation, [0, 1, 2])) {    // 0-> less than 1-> greater than 2-> equal to
                if (!$request->price or !is_numeric($request->price) or !$request->price > 0) {
                    $result = ['price' => __('main.required_price_with_this_filter_type')];
                    return response()->json(['status' => false, 'error' => $result], 200);
                }
            }
            $filter = Filter::find($request->id);
            if (!$filter)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            $filter->update($request->all());
            return response()->json(['status' => true, 'msg' => __('main.filter_type_updated_successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $filter = Filter::find($request->id);
            if (!$filter)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            $filter->delete();
            return response()->json(['status' => true, 'msg' => __('main.filter_type_deleted_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}
