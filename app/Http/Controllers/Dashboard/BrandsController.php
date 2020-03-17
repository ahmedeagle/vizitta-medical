<?php

namespace App\Http\Controllers\Dashboard;

use App\Traits\Dashboard\BrandsTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class BrandsController extends Controller
{
    use BrandsTrait;

    public function getDataTable()
    {
        try {
            return $this->getAll();
        } catch (\Exception $ex) {

            return view('errors.404');
        }
    }

    public function index()
    {
        return view('brands.index');
    }

    public function add()
    {
        return view('brands.add');
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "photo" => "required|mimes:jpg,jpeg,png",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $this->createbrand($request);
            Flashy::success('تم إضافة  الشعار بنجاح');
            return redirect()->route('admin.brands');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id)
    {
        try {
            $brand = $this->getbrandById($id);
            if ($brand == null)
                return view('errors.404');
            $brand->delete();
            Flashy::success('تم مسح  الشعار بنجاح');
            return redirect()->route('admin.brands');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}
