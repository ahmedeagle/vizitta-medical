<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Reason;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class RefusalReasonsController extends Controller
{
    use  PublicTrait;

    public function index()
    {
        $result = Reason::paginate(PAGINATION_COUNT);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            Reason::create($request->all());
            return response()->json(['status' => true, 'msg' => __('main.refusal_reason_added_successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }

    }

    public function edit(Request $request)
    {
        try {
            $reason = Reason::find($request->id);
            if ($reason == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            return response()->json(['status' => true, 'data' => $reason]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $reason = Reason::find($request->id);
            if ($reason == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $reason->update($request->all());
            return response()->json(['status' => true, 'msg' => __('main.refusal_reason_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $reason = Reason::find($request->id);
            if ($reason == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $reason->delete();
            return response()->json(['status' => true, 'msg' => __('main.refusal_reason_deleted_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}
