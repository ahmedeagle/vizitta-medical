<?php

namespace App\Http\Controllers\Dashboard;

use App\Traits\Dashboard\SpecificationTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class SpecificationController extends Controller
{
    use SpecificationTrait;

    public function getDataTable(){
        try{
            return $this->getAllSpecifications();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index(){
        return view('specification.index');
    }

    public function add(){
        return view('specification.add');
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
            $this->createSpecification($request);
            Flashy::success('تم إضافة التخصص بنجاح');
            return redirect()->route('admin.specification');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function edit($id){
        try{
            $specification = $this->getSpecificationById($id);
            if($specification == null)
                return view('errors.404');

            return view('specification.edit', compact('specification'));
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
            $specification = $this->getSpecificationById($id);
            if($specification == null)
                return view('errors.404');

            $this->updateSpecification($specification, $request);
            Flashy::success('تم تعديل التخصص بنجاح');
            return redirect()->route('admin.specification');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id){
        try{
            $specification = $this->getSpecificationById($id);
            if($specification == null)
                return view('errors.404');

            if(count($specification->doctors) == 0){
                $specification->delete();
                Flashy::success('تم مسح التخصص بنجاح');
            } else {
                Flashy::error('لا يمكن مسح تخصص مرتبط بدكاترة');
            }
            return redirect()->route('admin.specification');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}
