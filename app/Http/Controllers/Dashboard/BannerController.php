<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Banner;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Traits\Dashboard\OfferCategoriesTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class BannerController extends Controller
{
    use  PublicTrait;

    public function index()
    {
        $data = [];
        $data['banners'] = Banner::select('id', 'photo', 'bannerable_type', 'bannerable_id')->paginate(10);
        return view('banners.index', $data);
    }

    public function create()
    {
        $data = [];
        $data['categories'] = $this->getAllCategoriesCollectionV2();
        $data['offers'] = $this->getAllOffersCollectionV2();
        return view('banners.create', $data);
    }

    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            "type" => "required|in:App\Models\OfferCategory,App\Models\Offer'",
            "offer_id" => "required",
            "category_id" => "required",
            "photo" => "required|mimes:jpeg,jpg,png"
        ]);


        if ($validator->fails()) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }

        $fileName = "";
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->uploadImage('copouns', $request->photo);
        }

        if ($request->type = 'App\Models\OfferCategory') {
            $id = $request->category_id;
            $category = OfferCategory::find($id);
            if (!$category) {
                Flashy::error('القسم المختار غير موجود لدينا');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

        } else {
            $id = $request->offer_id;
            $offer = Offer::find($id);
            if (!$offer) {
                Flashy::error('العرض المختار غير موجود لدينا');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
        }

        Banner::create([
            'photo' => $fileName,
            'bannerable_type' => $request->type,
            'bannerable_id' => $id
        ]);

        Flashy::success('تم إضافة البانر  بنجاح');
        return redirect()->route('admin.offers.banners');

    }

    public function edit($id)
    {
        try {
            $category = $this->getOfferCategoryById($id);
            if ($category == null)
                return view('errors.404');

            return view('offerCategories.edit', compact('category'));
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
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $category = $this->getOfferCategoryById($id);
            if ($category == null)
                return view('errors.404');

            $fileName = $category->photo;
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->uploadImage('copouns', $request->photo);
            }
            $category->update(['photo' => $fileName]);

            $this->updateOfferCategory($category, $request->except('photo'));

            Flashy::success('تم تعديل  القسم  بنجاح');
            return redirect()->route('admin.offerCategories');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id)
    {
        try {
            $category = $this->getOfferCategoryById($id);
            if ($category == null)
                return view('errors.404');
            $category->delete();
            Flashy::success('تم مسح  القسم وعروضه  بنجاح');

            return redirect()->route('admin.offerCategories');
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
            "category_id" => "required|exists:offers_categories,id"
        ]);

        if ($validator->fails()) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withErrors($validator)->withInput($request->all())->with(['OfferModalId' => $request->category_id]);
        }

        if ($request->hours == 0 && $request->minutes == 0 && $request->seconds == 0) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withInput($request->all())->withErrors(['hours' => 'لابد من ادخال اي من الحقل اولا'])->with(['OfferModalId' => $request->category_id]);
        }
        $category = OfferCategory::find($request->category_id);
        $category->update([
            'hastimer' => 1,
            'hours' => $request->hours,
            'minutes' => $request->minutes,
            'seconds' => $request->seconds,
        ]);

        Flashy::success('تمت الاضافة بنجاح');

        return redirect()->route('admin.offerCategories');
    }

    public function reorder()
    {
        $categories = OfferCategory::select('id', 'name_ar')->orderBy('lft')->get();
        return view('offerCategories.reorder', compact('categories'));
    }

    public function saveReorder(Request $request)
    {
        $count = 0;
        $all_entries = $request->input('tree');
        if (count($all_entries)) {
            foreach ($all_entries as $key => $entry) {
                if ($entry['item_id'] != "" && $entry['item_id'] != null) {
                    $item = OfferCategory::find($entry['item_id']);
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
