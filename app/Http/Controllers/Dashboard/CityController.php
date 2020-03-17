<?php

namespace App\Http\Controllers\Dashboard;

use App\Traits\Dashboard\CityTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class CityController extends Controller
{
    use CityTrait;

    public function getDataTable(){
        try{
            return $this->getAll();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index(){
        return view('city.index');
    }

    public function add(){
        return view('city.add');
    }

    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $this->createCity($request);
            Flashy::success('تم إضافة المدينة بنجاح');
            return redirect()->route('admin.city');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function edit($id){
        try{
            $city = $this->getCityById($id);
            if($city == null)
                return view('errors.404');

            return view('city.edit', compact('city'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function update($id, Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $city = $this->getCityById($id);
            if($city == null)
                return view('errors.404');

            $this->updateCity($city, $request);
            Flashy::success('تم تعديل المدينة بنجاح');
            return redirect()->route('admin.city');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id){
        try{
            $city = $this->getCityById($id);
            if($city == null)
                return view('errors.404');

            if(count($city->providers) == 0){
                $city->delete();
                Flashy::success('تم مسح المدينة بنجاح');
            } else {
                Flashy::error('لا يمكن مسح مدينة مرتبط بمقدمى الخدمات');
            }
            return redirect()->route('admin.city');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}
