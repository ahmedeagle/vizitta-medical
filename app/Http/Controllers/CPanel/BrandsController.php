<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Brand;
use App\Traits\Dashboard\BrandsTrait;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BrandsController extends Controller
{
    use BrandsTrait, GeneralTrait;

    public function index()
    {
        $result = Brand::select('id', DB::raw("CONCAT('" . url('/') . "','/',photo) AS image"))->paginate(PAGINATION_COUNT);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
//                "photo" => "required|mimes:jpg,jpeg,png",
                "photo" => "required",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $this->createCustomBrand($request);
            return response()->json(['status' => true, 'msg' => __('main.brand_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $brand = $this->getbrandById($request->id);
            if ($brand == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            $brand->delete();
            return response()->json(['status' => true, 'msg' => __('main.brand_deleted_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}
