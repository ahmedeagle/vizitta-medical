<?php

namespace App\Http\Controllers\Dashboard;

use App\Traits\Dashboard\NationalityTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class NationalityController extends Controller
{
    use NationalityTrait;

    public function getDataTable(){
        try{
            return $this->getAll();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index(){
        return view('nationality.index');
    }

    public function add(){
        return view('nationality.add');
    }

    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255"
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $this->createNationality($request);
            Flashy::success('تم إضافة الجنسية بنجاح');
            return redirect()->route('admin.nationality');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function edit($id){
        try{
            $nationality = $this->getNationalityById($id);
            if($nationality == null)
                return view('errors.404');

            return view('nationality.edit', compact('nationality'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function update($id, Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255"
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $nationality = $this->getNationalityById($id);
            if($nationality == null)
                return view('errors.404');

            $this->updateNationality($nationality, $request);
            Flashy::success('تم تعديل الجنسية بنجاح');
            return redirect()->route('admin.nationality');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id){
        try{
            $nationality = $this->getNationalityById($id);
            if($nationality == null)
                return view('errors.404');

            if(count($nationality->doctors) == 0){
                $nationality->delete();
                Flashy::success('تم مسح الجنسيه بنجاح');
            } else {
                Flashy::error('لا يمكن مسح جنسية مرتبطه بدكاترة');
            }
            return redirect()->route('admin.nationality');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}
