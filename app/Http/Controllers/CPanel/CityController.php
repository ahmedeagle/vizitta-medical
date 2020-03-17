<?php

namespace App\Http\Controllers\CPanel;

use App\Models\City;
use App\Traits\Dashboard\CityTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    use CityTrait;

    public function index()
    {
        $result = City::paginate(PAGINATION_COUNT);
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
            $this->createCity($request);
            return response()->json(['status' => true, 'msg' => __('main.city_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $city = $this->getCityById($request->id);
            if ($city == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            return response()->json(['status' => true, 'data' => $city]);
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
            $city = $this->getCityById($request->id);
            if ($city == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $this->updateCity($city, $request);
            return response()->json(['status' => true, 'msg' => __('main.city_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $city = $this->getCityById($request->id);
            if ($city == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if (count($city->providers) == 0) {
                $city->delete();
                return response()->json(['status' => true, 'msg' => __('main.city_deleted_successfully')]);
            } else {
                return response()->json(['success' => false, 'error' => __('main.city_with_providers_cannot_be_deleted')], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}
