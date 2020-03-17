<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Nationality;
use App\Traits\Dashboard\NationalityTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class NationalityController extends Controller
{
    use NationalityTrait;

    public function index()
    {
        $result = Nationality::paginate(PAGINATION_COUNT);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255"
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $this->createNationality($request);
            return response()->json(['status' => true, 'msg' => __('main.nationality_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $nationality = $this->getNationalityById($request->id);
            if ($nationality == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            return response()->json(['status' => true, 'data' => $nationality]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255"
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $nationality = $this->getNationalityById($request->id);
            if ($nationality == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $this->updateNationality($nationality, $request);
            return response()->json(['status' => true, 'msg' => __('main.nationality_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $nationality = $this->getNationalityById($request->id);
            if ($nationality == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if (count($nationality->doctors) == 0) {
                $nationality->delete();
                return response()->json(['status' => true, 'msg' => __('main.nationality_deleted_successfully')]);
            } else {
                return response()->json(['success' => false, 'error' => __('main.nationality_with_doctors_cannot_be_deleted')], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}
