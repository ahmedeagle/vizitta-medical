<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\PromoCodeCategory;
use App\Traits\Dashboard\PromoCategoriesTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class PromoCategoriesController extends Controller
{
    use PromoCategoriesTrait, PublicTrait;

    public function getDataTable()
    {
        try {
            return $this->getAllPromoCodeCategories();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index()
    {
        $categories = PromoCodeCategory::select('id', 'name_ar','hours','minutes','seconds')->get();
        return view('promoCategories.index', compact('categories'));
    }

    public function add()
    {
        return view('promoCategories.add');
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "name_en" => "required|max:255",
            "name_ar" => "required|max:255",
            "photo" => "required|mimes:jpeg,bmp,jpg,png",
            "status" => "required|in:0,1"
        ]);

        if ($validator->fails()) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }
        $fileName = "";
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->uploadImage('copouns', $request->photo);
        }
        $request->request->add(['photo' => $fileName]);
        $category = $this->createPromoCodeCategory($request->except(['photo']));
        $category->update(['photo' => $fileName]);
        Flashy::success('تم إضافة  القسم  بنجاح');
        return redirect()->route('admin.promoCategories');

    }

    public function edit($id)
    {
        try {
            $category = $this->getPromoCodeCategoryById($id);
            if ($category == null)
                return view('errors.404');

            return view('promoCategories.edit', compact('category'));
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
                "photo" => "sometimes|nullable|mimes:jpeg,bmp,jpg,png",
                "status" => "required|in:0,1"
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $category = $this->getPromoCodeCategoryById($id);
            if ($category == null)
                return view('errors.404');

            $fileName = $category->photo;
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->uploadImage('copouns', $request->photo);
            }
            $category->update(['photo' => $fileName]);

            $this->updatePromoCodeCategory($category, $request->except('photo'));

            Flashy::success('تم تعديل  القسم  بنجاح');
            return redirect()->route('admin.promoCategories');
        } catch (\Exception $ex) {
           // return view('errors.404');
            return $ex;
        }
    }

    public function destroy($id)
    {
        try {
            $category = $this->getPromoCodeCategoryById($id);
            if ($category == null)
                return view('errors.404');
            $category->delete();
            Flashy::success('تم مسح  القسم وعروضه  بنجاح');

            return redirect()->route('admin.promoCategories');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }


    public function addToTimer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "hours" => "required|numeric|min:0",
            "minutes" => "required|numeric|min:0|max:59",
            "seconds" => "required|numeric|min:0|max:59",
            "category_id" => "required|exists:promocodes_categories,id"
        ]);

        if ($validator->fails()) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withErrors($validator)->withInput($request->all())->with(['promoCodeModalId' => $request->category_id]);
        }

        if ($request->hours == 0 && $request->minutes == 0 && $request->seconds == 0) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withInput($request->all())->withErrors(['hours' => 'لابد من ادخال اي من الحقل اولا'])->with(['promoCodeModalId' => $request->category_id]);
        }
        $category = PromoCodeCategory::find($request->category_id);
        $category->update([
            'hastimer' => 1,
            'hours' => $request->hours,
            'minutes' => $request->minutes,
            'seconds' => $request->seconds,
        ]);

        Flashy::success('تمت الاضافة بنجاح');

        return redirect()->route('admin.promoCategories');
    }

    public function reorder()
    {
        $categories = PromoCodeCategory::select('id', 'name_ar')->orderBy('lft')->get();
        return view('promoCategories.reorder', compact('categories'));
    }

    public function saveReorder(Request $request)
    {
        $count = 0;
        $all_entries = $request->input('tree');
        if (count($all_entries)) {
            foreach ($all_entries as $key => $entry) {
                if ($entry['item_id'] != "" && $entry['item_id'] != null) {
                    $item = PromoCodeCategory::find($entry['item_id']);
                    $item->depth = $entry['depth'];
                    $item->lft = $entry['left'];
                    $item->rgt = $entry['right'];
                    $item->save();
                    $count++;
                }
            }
        } else {
            return false;
        }
        return 'success for ' . $count . " items";
    }
}
