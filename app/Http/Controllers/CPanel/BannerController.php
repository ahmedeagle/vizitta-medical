<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Banner;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\PromoCode;
use App\Models\PromoCodeCategory;
use App\Models\Provider;
use App\Models\Specification;
use App\Traits\CPanel\BannerTrait;
use App\Traits\CPanel\OfferTrait;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Validator;
use Flashy;

class BannerController extends Controller
{
    use  GlobalTrait, OfferTrait, BannerTrait;

    ################### manual upload ##############
    public function index()
    {
        try {
            $banners = $this->getBannersV2();
            if (count($banners->toArray()) > 0) {
                $banners->each(function ($banner) {
                    if ($banner->type == 'App\Models\OfferCategory') {
                        $type = 'category';
                        if ($banner->type_id == 0) {
                            $direct_type = 'أقسام';
                            $direct_to = 'كل الاقسام';
                        } else {
                            $category = OfferCategory::whereNull('parent_id')->where('id', $banner->type_id)->first();
                            $direct_type = 'أقسام';
                            $direct_to = @$category->{'name_' . app()->getLocale()};
                        }
                    } elseif ($banner->type == 'App\Models\Offer') {
                        $type = 'offer';
                        $direct_type = 'عروض';
                        $offer = Offer::find($banner->type_id);
                        $direct_to = @$offer->{'title_' . app()->getLocale()};
                    } elseif ($banner->type == 'App\Models\Provider') {
                        $type = 'provider';
                        $direct_type = "الأفرع";
                        if ($banner->subCategory_id == 1)// 1 -> doctors  2-> services
                        {
                            $direct = 'الاطباء';
                        } else {
                            $direct = 'الخدمات';
                        }
                        $direct_to = $direct;
                    } elseif ($banner->type == 'App\Models\MedicalCenter') {
                        $type = 'provider';
                        $direct_type = 'صفحة اضافه مركز طبي';
                        $direct_to = $direct_type;
                    } elseif ($banner->type == 'App\Models\Doctor') {
                        $type = 'consulting';
                        $direct_type = 'الاستشارات الطبيبة';
                        if ($banner->subCategory_id == null or $banner->subCategory_id == 0)
                            $direct_to = 'أقسام الاستشارات';
                        else {
                            $specification = Specification::where('id', $banner->subCategory_id)->first();
                            $direct_to = $specification->name_ar;
                        }
                    } elseif ($banner->type == 'external') {
                        $type = 'external';
                        $direct_type =  $banner -> external_link;
                        $direct_to = 'خارجي';
                    } else {
                        $type = 'none';
                        $direct_type = 'لا شي';
                        $direct_to = 'لا شي';
                    }
                    $banner->type = $type;
                    $banner->direct_type = $direct_type;
                    $banner->direct_to = $direct_to;

                    unset($banner->type_id);
                    unset($banner->subCategory_id);
                    return $banner;
                });

                $total_count = $banners->total();

                $banners = json_decode($banners->toJson());
                $bannersJson = new \stdClass();
                $bannersJson->current_page = $banners->current_page;
                $bannersJson->total_pages = $banners->last_page;
                $bannersJson->total_count = $total_count;
                $bannersJson->per_page = PAGINATION_COUNT;
                $bannersJson->data = $banners->data;
            }
            return $this->returnData('banners', $bannersJson);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function saveReorderBanners(Request $request)
    {
        try {
            $count = 0;
            $all_entries = $request->input('tree');
            if (count($all_entries)) {
                foreach ($all_entries as $key => $entry) {
                    if ($entry['item_id'] != "" && $entry['item_id'] != null) {
                        $item = Banner::find($entry['item_id']);
                        $item->depth = $entry['depth'];
                        $item->lft = $entry['left'];
                        $item->rgt = $entry['right'];
                        $item->save();
                        $count++;
                    }
                }
            } else {
                return $this->returnError('D000', __('main.oops_error'));

            }
            return $this->returnSuccessMessage(__('main.saved_successfully'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getReorders(Request $request){
        try {
            $banners = $this->getBannersList();
            if (count($banners->toArray()) > 0) {
                $banners->each(function ($banner) {
                    if ($banner->type == 'App\Models\OfferCategory') {
                        $type = 'category';
                        if ($banner->type_id == 0) {
                            $direct_type = 'أقسام';
                            $direct_to = 'كل الاقسام';
                        } else {
                            $category = OfferCategory::whereNull('parent_id')->where('id', $banner->type_id)->first();
                            $direct_type = 'أقسام';
                            $direct_to = @$category->{'name_' . app()->getLocale()};
                        }
                    } elseif ($banner->type == 'App\Models\Offer') {
                        $type = 'offer';
                        $direct_type = 'عروض';
                        $offer = Offer::find($banner->type_id);
                        $direct_to = @$offer->{'title_' . app()->getLocale()};
                    } elseif ($banner->type == 'App\Models\Provider') {
                        $type = 'provider';
                        $direct_type = "الأفرع";
                        if ($banner->subCategory_id == 1)// 1 -> doctors  2-> services
                        {
                            $direct = 'الاطباء';
                        } else {
                            $direct = 'الخدمات';
                        }
                        $direct_to = $direct;
                    } elseif ($banner->type == 'App\Models\MedicalCenter') {
                        $type = 'provider';
                        $direct_type = 'صفحة اضافه مركز طبي';
                        $direct_to = $direct_type;
                    } elseif ($banner->type == 'App\Models\Doctor') {
                        $type = 'consulting';
                        $direct_type = 'الاستشارات الطبيبة';
                        if ($banner->subCategory_id == null or $banner->subCategory_id == 0)
                            $direct_to = 'أقسام الاستشارات';
                        else {
                            $specification = Specification::where('id', $banner->subCategory_id)->first();
                            $direct_to = $specification->name_ar;
                        }
                    } elseif ($banner->type == 'external') {
                        $type = 'external';
                        $direct_type =  $banner -> external_link;
                        $direct_to = 'خارجي';
                    } else {
                        $type = 'none';
                        $direct_type = 'لا شي';
                        $direct_to = 'لا شي';
                    }
                    $banner->type = $type;
                    $banner->direct_type = $direct_type;
                    $banner->direct_to = $direct_to;

                    unset($banner->type_id);
                    unset($banner->subCategory_id);
                    return $banner;
                });
            }
            return $this->returnData('banners', $banners);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function edit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "banner_id" => "required|exists:banners,id",
            ]);


            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    ############### end manual upload ##############
    public function create()
    {
        try {
            $obj = new \stdClass();
            $categories = $this->getAllCategories();
            $obj->categories = $categories;
            $offers = $this->getAllOffers();
            $obj->offers = $offers;
            return $this->returnData('data', $obj);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "type" => "required|in:offer,category,center,branch,consulting,external,none",
            "photo" => "required"

        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        if ($request->type == 'external') {
            if (empty($request-> external_link )) {
                return $this->returnError('D000', __('messages.external link required'));
            }
        }
        if ($request->type == 'branch') {
            if ((empty($request->branch_id) or !is_numeric($request->branch_id)) && ($request->branch_id != 0)) {
                return $this->returnError('D000', __('messages.provider id required'));
            }
            //check if  branch is exists or not
            $branch = Provider::whereNotNull('provider_id')->where('id', $request->branch_id)->first();
            if (!$branch) {
                return $this->returnError('D000', __('messages.branch not found'));
            }
            // required   subcategory_id    1 -> doctors 2 -> services
            if ((empty($request->subcategory_id) or !is_numeric($request->subcategory_id)) && ($request->subcategory_id != 0)) {
                return $this->returnError('D000', __('messages.subcategory required'));
            }
            if ($request->subcategory_id != 1 && $request->subcategory_id != 2) {
                return $this->returnError('D000', __('messages.subcategory_id required and must be 1 for doctors 2 for services'));
            }
        }
        if ($request->type == 'center') {
            //nothing
        }
        if ($request->type == 'consulting') {
            // required only if category_id  not equal 0  //i.e not all categories then we need subcategory of this category
            if ((empty($request->subcategory_id) or !is_numeric($request->subcategory_id)) && ($request->subcategory_id != 0)) {
                return $this->returnError('D000', __('messages.subcategory required'));
            }
            //check if subcategory exists
            if ($request->has('subcategory_id')) {
                $specification = Specification::where('id', $request->subcategory_id)->first();

                if (!$specification && $request->subcategory_id != 0) {
                    return $this->returnError('D000', __('messages.subcategory not found'));
                }
            }


        }
        if ($request->type == 'category') {
            // 0 -> means all category of offers    otherwise mean offer category id
            if ((empty($request->category_id) or !is_numeric($request->category_id)) && ($request->category_id != 0)) {
                return $this->returnError('D000', __('messages.category required'));
            }

            //check if main category not equal 0 (i.e not all categroy)  we must check if this main category exists or not
            if ($request->category_id != 0) {
                $category = OfferCategory::whereNull('parent_id')->where('id', $request->category_id)->first();
                if (!$category) {
                    return $this->returnError('D000', __('messages.category not found'));
                }
                // required only if category_id  not equal 0  //i.e not all categories then we need subcategory of this category
                if ((empty($request->subcategory_id) or !is_numeric($request->subcategory_id)) && ($request->subcategory_id != 0)) {
                    return $this->returnError('D000', __('messages.subcategory required'));
                }
            }

            //check if subcategory exists
            if ($request->has('subcategory_id')) {
                if ($request->subcategory_id != 0) {
                    $subCategory = OfferCategory::whereNotNull('parent_id')->where('id', $request->subcategory_id)->first();
                    if (!$subCategory) {
                        return $this->returnError('D000', __('messages.subcategory not found'));
                    }
                }
            }

        }
        if ($request->type == 'offer') {
            if (empty($request->offer_id) or !is_numeric($request->offer_id)) {
                return $this->returnError('D000', __('messages.offer required'));
            }
            $offer = Offer::where('id', $request->offer_id)->first();   // offer subcategory
            if (!$offer)
                return $this->returnError('D000', __('messages.offer not found'));
        }

        $fileName = "";
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->saveImage('copouns', $request->photo);
        }

        if ($request->type == 'category') {
            $id = $request->category_id;
            $bannerable_type = 'App\Models\OfferCategory';
        } elseif ($request->type == 'offer') {
            $id = $request->offer_id;
            $bannerable_type = 'App\Models\Offer';
        } elseif ($request->type == 'branch') {
            $id = $request->branch_id;
            $bannerable_type = 'App\Models\Provider';
        } elseif ($request->type == 'center') {
            $id = 0;
            $bannerable_type = 'App\Models\MedicalCenter';
        } elseif ($request->type == 'consulting') {
            $id = 0;
            $bannerable_type = 'App\Models\Doctor';
        } elseif ($request->type == "external") {
            $id = null;
            $bannerable_type = 'external';
        } else {
            $id = null;
            $bannerable_type = 'none';
        }

        Banner::create([
            'photo' => $fileName,
            'bannerable_type' => $bannerable_type,
            'bannerable_id' => $id,
            'subCategory_id' => isset($request->subcategory_id) ? $request->subcategory_id : 0,
            'external_link' => isset($request-> external_link ) ? $request->external_link : null
        ]);

        return $this->returnSuccessMessage(trans('messages.Banner added successfully'));
    }

    public
    function destroy(Request $request)
    {
        try {
            $banner = Banner::find($request->banner_id);
            if ($banner == null)
                return $this->returnError('D000', __('messages.banner not found'));
            $banner->delete();

            return $this->returnSuccessMessage(trans('messages.Banner deleted successfully'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public
    function getOfferSubCategoriesByCatId(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "category_id" => "required|exists:offers_categories,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $subCategories = $this->getSubCategoriesByCatId($request->category_id);
            return $this->returnData('subCategories', $subCategories);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
}
