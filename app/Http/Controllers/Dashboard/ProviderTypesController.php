<?php

namespace App\Http\Controllers\Dashboard;

use App\Traits\Dashboard\ProviderTypesTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class ProviderTypesController extends Controller
{
    use ProviderTypesTrait;

    public function getDataTable()
    {

        return $this->getAllProviderTypes();

    }

    public function index()
    {
        return view('types.index');
    }

    public function add()
    {
        return view('types.add');
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $this->createProviderType($request);
            Flashy::success('تم إضافة  النوع  بنجاح');
            return redirect()->route('admin.types');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function edit($id)
    {
        try {
            $type = $this->getProviderTypeById($id);
            if ($type == null)
                return view('errors.404');

            return view('types.edit', compact('type'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function update($id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $types = $this->getProviderTypeById($id);
            if ($types == null)
                return view('errors.404');

            $this->updateProviderType($types, $request);
            Flashy::success('تم تعديل نوع مقدم الخدمة  بنجاح');
            return redirect()->route('admin.types');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id)
    {

    }

}
