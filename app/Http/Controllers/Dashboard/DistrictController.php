<?php

namespace App\Http\Controllers\Dashboard;

use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\DistrictTrait;
use Validator;
use Flashy;

class DistrictController extends Controller
{
    use DistrictTrait, PublicTrait;

    public function getDataTable(){
        try{
            return $this->getAll();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index(){
        return view('district.index');
    }

    public function add(){
        $cities = $this->getAllCities();
        return view('district.add', compact('cities'));
    }

    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "city_id" => "required|numeric"
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $this->createDistrict($request);
            Flashy::success('تم إضافة الحى بنجاح');
            return redirect()->route('admin.district');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function edit($id){
        try{
            $district = $this->getDistrictById($id);
            if($district == null)
                return view('errors.404');

            $cities = $this->getAllCities();
            return view('district.edit', compact('district', 'cities'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function update($id, Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "city_id" => "required|numeric"
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $district = $this->getDistrictById($id);
            if($district == null)
                return view('errors.404');

            $this->updateDistrict($district, $request);
            Flashy::success('تم تعديل الحى بنجاح');
            return redirect()->route('admin.district');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id){
        try{
            $district = $this->getDistrictById($id);
            if($district == null)
                return view('errors.404');

            if(count($district->providers) == 0){
                $district->delete();
                Flashy::success('تم مسح الحى بنجاح');
            } else {
                Flashy::error('لا يمكن مسح حى مرتبط بمقدمى الخدمات');
            }
            return redirect()->route('admin.district');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}
