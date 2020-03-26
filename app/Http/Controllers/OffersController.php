<?php

namespace App\Http\Controllers;


use App\Models\Doctor;
use App\Models\Filter;
use App\Models\Mix;
use App\Models\PromoCode;
use App\Models\PromoCodeCategory;
use App\Models\Specification;
use App\Models\User;
use App\Models\Payment;
use App\Traits\GlobalTrait;
use App\Traits\DoctorTrait;
use App\Traits\OdooTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;
use DB;
use Str;

use Illuminate\Validation\Rule;

class OffersController extends Controller
{
    use  GlobalTrait, SMSTrait, DoctorTrait, OdooTrait;

    public function __construct()
    {

    }

    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "category_id" => "required",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = $this->auth('user-api');
            if (!$user) {
                return $this->returnError('D000', trans('messages.User not found'));
            }

            $orderBy = 'id';
            if (isset($request->mostVisits) && $request->mostVisits == 1) {
                $orderBy = 'visits';
            }

            if (isset($request->mostpaid) && $request->mostpaid == 1) {
                $orderBy = 'uses';
            }

            /*  if (isset($request->lessThan) && $request->lessThan == 1) {
                  $orderBy = 'price';
              }*/

            // if 0 get all offer
            if ($request->category_id != 0) {
                $category = PromoCodeCategory::find($request->category_id);
                if (!$category) {
                    return $this->returnError('D000', trans('messages.category not found'));
                }
                $categoryId = $category->id;
                if (!$category)
                    return $this->returnError('E001', trans('messages.There is no category with this id'));
                else {

                    if (isset($request->featured) && $request->featured == 1) {
                        $offers = PromoCode::where(function ($qq) use ($user) {
                            $qq->where('general', 1)
                                ->orWhereHas('users', function ($qu) use ($user) {
                                    $qu->where('users.id', $user->id);
                                });
                        })->featured()
                            ->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            }])
                            ->whereHas('categories', function ($q) use ($categoryId) {
                                $q->where('promocodes_categories.id', $categoryId);
                            })
                            ->limit(10)
                            ->selection()
                            ->inRandomOrder()
                            ->get();
                    } else {
                        //return $this->returnError('E001', trans('messages.There featured  must be 1 or not present '));
                        $offers = PromoCode::where(function ($qq) use ($user) {
                            $qq->where('general', 1)
                                ->orWhereHas('users', function ($qu) use ($user) {
                                    $qu->where('users.id', $user->id);
                                });
                        })->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            }])
                            ->whereHas('categories', function ($q) use ($categoryId) {
                                $q->where('promocodes_categories.id', $categoryId);
                            })
                            ->selection()
                            ->inRandomOrder()
                            ->paginate(10);
                    }
                }

            } else {

                if (isset($request->featured) && $request->featured == 1) {
                    $offers = PromoCode::where(function ($qq) use ($user) {
                        $qq->where('general', 1)
                            ->orWhereHas('users', function ($qu) use ($user) {
                                $qu->where('users.id', $user->id);
                            });
                    })->featured()
                        ->active()
                        ->valid()
                        ->with(['provider' => function ($q) {
                            $q->select('id', 'rate', 'logo', 'type_id',
                                DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                            $q->with(['type' => function ($q) {
                                $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                        },])
                        ->selection()
                        ->limit(25)
                        ->inRandomOrder()
                        ->get();
                } else

                    // PromoCode::find(6) -> currentId();
                    $offers = PromoCode::where(function ($qq) use ($user) {
                        $qq->where('general', 1)
                            ->orWhereHas('users', function ($qu) use ($user) {
                                $qu->where('users.id', $user->id);
                            });
                    })->active()
                        ->valid()
                        ->with(['provider' => function ($q) {
                            $q->select('id', 'rate', 'logo', 'type_id',
                                DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                            $q->with(['type' => function ($q) {
                                $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                        }])
                        ->selection()
                        ->inRandomOrder()
                        ->paginate(10);
            }

            if (isset($request->featured) && $request->featured == 1) {
                //if coupon allowed only for some users
                /*        if (isset($offers) && $offers->count() > 0) {
                            foreach ($offers as $index => $offer) {
                                if (!empty($offer->users) && count($offer->users) > 0) {
                                    $authUserExistsForThisOffer = in_array($user->id, array_column($offer->users->toArray(), 'id'));
                                    if (!$authUserExistsForThisOffer) {
                                        unset($offers[$index]);
                                    }
                                }
                            }
                        }*/
                return $this->returnData('featured_offers', $offers);
            }

            $selectedValue = 0;
            if (count($offers->toArray()) > 0) {

                foreach ($offers as $index => $offer) {
                    unset($offer->provider_id);
                    unset($offer->available_count);
                    unset($offer->status);
                    unset($offer->created_at);
                    unset($offer->updated_at);
                    unset($offer->specification_id);
                    /* if ($offers->coupons_type_id == 1) {
                         $offers->price = "0";
                     }*/
                    if ($offer->coupons_type_id == 2) {
                        $offer->discount = "0";
                        $offer->code = "0";
                    }

                }

                $offers = json_decode($offers->toJson());
                $total_count = $offers->total;
                $offersJson = new \stdClass();
                $offersJson->current_page = $offers->current_page;
                $offersJson->total_pages = $offers->last_page;
                $offersJson->total_count = $total_count;
                $offersJson->data = $offers->data;
                return $this->returnData('offers', $offersJson);
            }

            return $this->returnError('E001', trans('messages.No offers founded'));

        } catch (\Exception $ex) {
            return $this->returnError('E1' . $ex->getCode(), $ex->getMessage());
            // return $ex;
        }
    }

    public function indexV2(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "category_id" => "required",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

             $user = $this->auth('user-api');
            if (!$user) {
                //return $this->returnError('D000', trans('messages.User not found'));
              return   $this->getOfferForVisitors($request);
            }
            $orderBy = 'id';
            $conditions = [];
            if (isset($request->filter_id) && !empty($request->filter_id)) {
                $filter = Filter::find($request->filter_id);
                if (!$filter)
                    return $this->returnError('D000', trans('messages.filter not found'));
                if (in_array($filter->operation, [0, 1, 2])) { //if filter operation is < or > or =
                    if ($filter->operation == 0) {   //less than
                        array_push($conditions, ['price_after_discount', '<=', (int)$filter->price]);
                    } elseif ($filter->operation == 1) {  //greater than
                        array_push($conditions, ['price_after_discount', '>=', (int)$filter->price]);
                    } else {
                        array_push($conditions, ['price_after_discount', '=', (int)$filter->price]);
                    }
                    $orderBy = 'price';
                } elseif (in_array($filter->operation, [3, 4, 5])) {  //3-> most paid 4-> most visited 5-> latest
                    if ($filter->operation == 3) {
                        $orderBy = 'uses';
                    } elseif ($filter->operation == 4) {
                        $orderBy = 'visits';
                    } else
                        $orderBy = 'id';
                } else {
                    $orderBy = 'id';
                }
            }

            // if 0 get all offer
            if ($request->category_id != 0) {
                $category = PromoCodeCategory::find($request->category_id);
                if (!$category)
                    return $this->returnError('E001', trans('messages.There is no category with this id'));
                else {

                    if (isset($request->featured) && $request->featured == 1) {

                        if (!empty($conditions) && count($conditions) > 0) {
                            $offers = PromoCode::where(function ($qq) use ($user) {
                                $qq->where('general', 1)
                                    ->orWhereHas('users', function ($qu) use ($user) {
                                        $qu->where('users.id', $user->id);
                                    });
                            })
                                ->where($conditions)
                                ->featured()
                                ->active()
                                ->valid()
                                ->with(['provider' => function ($q) {
                                    $q->select('id', 'rate', 'logo', 'type_id',
                                        DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                    $q->with(['type' => function ($q) {
                                        $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                    }]);
                                }])
                                ->where('category_id', $category->id)
                                ->orderBy($orderBy, 'DESC')
                                ->limit(10)
                                ->selection()
                                ->get();
                        } else {
                            $offers = PromoCode::where(function ($qq) use ($user) {
                                $qq->where('general', 1)
                                    ->orWhereHas('users', function ($qu) use ($user) {
                                        $qu->where('users.id', $user->id);
                                    });
                            })
                                ->featured()
                                ->active()
                                ->valid()
                                ->with(['provider' => function ($q) {
                                    $q->select('id', 'rate', 'logo', 'type_id',
                                        DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                    $q->with(['type' => function ($q) {
                                        $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                    }]);
                                }])
                                ->where('category_id', $category->id)
                                ->orderBy($orderBy, 'DESC')
                                ->limit(10)
                                ->selection()
                                ->get();
                        }

                    } else {
                        //return $this->returnError('E001', trans('messages.There featured  must be 1 or not present '));

                        if (!empty($conditions) && count($conditions) > 0) {

                            $offers = PromoCode::where(function ($qq) use ($user) {
                                $qq->where('general', 1)
                                    ->orWhereHas('users', function ($qu) use ($user) {
                                        $qu->where('users.id', $user->id);
                                    });
                            })->active()
                                ->valid()
                                ->with(['provider' => function ($q) {
                                    $q->select('id', 'rate', 'logo', 'type_id',
                                        DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                    $q->with(['type' => function ($q) {
                                        $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                    }]);
                                }])
                                ->where('category_id', $category->id)
                                ->where($conditions)
                                ->selection()
                                ->orderBy($orderBy, 'DESC')
                                ->paginate(10);
                        } else {
                            $offers = PromoCode::where(function ($qq) use ($user) {
                                $qq->where('general', 1)
                                    ->orWhereHas('users', function ($qu) use ($user) {
                                        $qu->where('users.id', $user->id);
                                    });
                            })->active()
                                ->valid()
                                ->with(['provider' => function ($q) {
                                    $q->select('id', 'rate', 'logo', 'type_id',
                                        DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                    $q->with(['type' => function ($q) {
                                        $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                    }]);
                                }])
                                ->where('category_id', $category->id)
                                ->selection()
                                ->orderBy($orderBy, 'DESC')
                                ->paginate(10);
                        }
                    }
                }

            } else {

                if (isset($request->featured) && $request->featured == 1) {

                    if (!empty($conditions) && count($conditions) > 0) {
                        $offers = PromoCode::where(function ($qq) use ($user) {
                            $qq->where('general', 1)
                                ->orWhereHas('users', function ($qu) use ($user) {
                                    $qu->where('users.id', $user->id);
                                });
                        })->featured()
                            ->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            },])
                            ->where($conditions)
                            ->orderBy($orderBy, 'DESC')
                            ->selection()
                            ->limit(25)
                            ->get();
                    } else {
                        $offers = PromoCode::where(function ($qq) use ($user) {
                            $qq->where('general', 1)
                                ->orWhereHas('users', function ($qu) use ($user) {
                                    $qu->where('users.id', $user->id);
                                });
                        })->featured()
                            ->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            },])
                            ->orderBy($orderBy, 'DESC')
                            ->selection()
                            ->limit(25)
                            ->get();
                    }
                } else

                    if (!empty($conditions) && count($conditions) > 0) {
                        // PromoCode::find(6) -> currentId();
                        $offers = PromoCode::where(function ($qq) use ($user) {
                            $qq->where('general', 1)
                                ->orWhereHas('users', function ($qu) use ($user) {
                                    $qu->where('users.id', $user->id);
                                });
                        })->where($conditions)
                            ->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            }])
                            ->selection()
                            ->orderBy($orderBy, 'DESC')
                            ->paginate(10);
                    } else {
                        $offers = PromoCode::where(function ($qq) use ($user) {
                            $qq->where('general', 1)
                                ->orWhereHas('users', function ($qu) use ($user) {
                                    $qu->where('users.id', $user->id);
                                });
                        })->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            }])
                            ->selection()
                            ->orderBy($orderBy, 'DESC')
                            ->paginate(10);
                    }
            }

            if (isset($request->featured) && $request->featured == 1) {
                //if coupon allowed only for some users
                /*        if (isset($offers) && $offers->count() > 0) {
                            foreach ($offers as $index => $offer) {
                                if (!empty($offer->users) && count($offer->users) > 0) {
                                    $authUserExistsForThisOffer = in_array($user->id, array_column($offer->users->toArray(), 'id'));
                                    if (!$authUserExistsForThisOffer) {
                                        unset($offers[$index]);
                                    }
                                }
                            }
                        }*/
                return $this->returnData('featured_offers', $offers);
            }

            $selectedValue = 0;
            if (count($offers->toArray()) > 0) {

                foreach ($offers as $index => $offer) {
                    unset($offer->provider_id);
                    unset($offer->available_count);
                    unset($offer->status);
                    unset($offer->created_at);
                    unset($offer->updated_at);
                    unset($offer->specification_id);
                    /* if ($offers->coupons_type_id == 1) {
                         $offers->price = "0";
                     }*/
                    if ($offer->coupons_type_id == 2) {
                        $offer->discount = "0";
                        $offer->code = "0";
                    }

                }

                $offers = json_decode($offers->toJson());
                $total_count = $offers->total;
                $offersJson = new \stdClass();
                $offersJson->current_page = $offers->current_page;
                $offersJson->total_pages = $offers->last_page;
                $offersJson->total_count = $total_count;
                $offersJson->data = $offers->data;
                return $this->returnData('offers', $offersJson);
            }

            return $this->returnError('E001', trans('messages.No offers founded'));

        } catch (\Exception $ex) {
            return $this->returnError('E1' . $ex->getCode(), $ex->getMessage());
        }
    }

    public function show(Request $request, $allow_code = false, $proCode = 0)
    {
        try {
            $user = $this->auth('user-api');
            if (!$user) {
                return $this->returnError('D000', trans('messages.User not found'));
            }
            $validator = Validator::make($request->all(), [
                "id" => "required|exists:promocodes,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $user = $this->auth('user-api');
            $_offer = PromoCode::active()->with(['provider' => function ($q) {
                $q->select('id', 'rate', 'logo', 'type_id',
                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                $q->with(['type' => function ($q) {
                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
            }, 'promocodebranches' => function ($qq) {
                $qq->select('*')->with(['branch' => function ($qqq) {
                    $qqq->select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                }]);
            }
            ])->selection();

            $offer = $_offer->find($request->id);
            if ($offer != null)
                unset($offer->provider_id);
            unset($offer->available_count);
            unset($offer->status);
            unset($offer->created_at);
            unset($offer->updated_at);
            /* if ($offer->coupons_type_id == 1) {
                 $offer->price = "0";
             }*/
            if ($offer->coupons_type_id == 2) {
                $offer->discount = "0";
                if (!$allow_code)
                    $offer->code = "0";
                else
                    $offer->code = $proCode;
            }

            event(new \App\Events\OfferWasVisited($offer));   // fire increase countrt  event
            return $this->returnData('offer', json_decode(json_encode($offer)));

            return $this->returnError('E001', trans('messages.No offer with this id'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function saveOfferPaymentDetails(Request $request)
    {
        try {
            $rules = [
                "id" => "required|exists:promocodes,id",
                "for_me" => "required|in:0,1",
                "total_amount" => "required",
                //"process_num" => "required|unique:payments,payment_no",
            ];

            if ($request->has('for_me')) {
                if ($request->for_me == 0) {
                    $rules['invited_user_mobile'] = "required";
                }
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $user = null;
            if ($request->api_token)
                $user = User::where('api_token', $request->api_token)->first();
            if (!$user)
                return $this->returnError('D000', trans('messages.User not found'));

            //check if amount paid equel offer amount
            $promoCode = PromoCode::find($request->id);
            if ($promoCode->price != $request->total_amount) {
                return $this->returnError('D000', trans('messages.paid amount not equal coupon price'));
            }
            if ($promoCode->coupons_type_id != 2) {
                return $this->returnError('D000', trans('messages.cannot pay this coupon'));
            }

            $invitedUser = null;
            $invitedMobile = null;
            if ($request->for_me == 0) {
                $invitedUser = User::where('mobile', $request->invited_user_mobile)->first();
                if (!$invitedUser)
                    return $this->returnError('D000', trans('messages.this mobile to belong to any user'));
                $invitedMobile = $invitedUser->mobile;
            }
            $payment = $this->savePayment($request, $user->id, $invitedMobile);
            $bank_fees = Mix::select('bank_fees')->first();
            $data = [];
            $data['bank_journal'] = 8;
            $data['bank_account'] = 26;
            $data['bank_fees_account'] = 480;
            $data['prepayments_account'] = 420;
            $data['offer_amount_WithoutVAT'] = $request->total_amount;
            $data['reference'] = $payment->payment_no;
            $data['comment'] = " سداد المستخدم عرض  " . $payment->offer->title;
            $data['bank_fees'] = $bank_fees->bank_fees ? $bank_fees->bank_fees : 0;
            $data['cost_center_id'] = 510;

            //save payment tp odoo system api
            if ($user->odoo_user_id) {
                $partner_id = $user->odoo_user_id;
                $data['partner_id'] = $partner_id;
            } else {
                // if provider not has an account on odoo , create new account
                // save user  to odoo erp system
                $odoo_user_id = $this->saveUserToOdoo($user->mobile, $user->name);
                $user->update(['odoo_user_id' => $odoo_user_id]);
                $partner_id = $odoo_user_id;
                $data['partner_id'] = $partner_id;
            }

            //save the app percentage of paid coupon and add the remain of coupon balance to   the branch
            $settings = $this->getAppInfo();
            // $promoCodeMainProvider = $promoCode->provider;
            $paid_coupon_percentage = $promoCode->paid_coupon_percentage;
            $appValueOfThisCoupon = (isset($promoCode->paid_coupon_percentage) && $promoCode->paid_coupon_percentage != null && $promoCode->paid_coupon_percentage > 0) ? ($promoCode->paid_coupon_percentage * $request->total_amount) / 100 : 0;
            $providerValueOfthisCoupon = $request->total_amount - $appValueOfThisCoupon;

            //saveApp Value
            Mix::first()->update(['admin_coupon_balance' => (int)($appValueOfThisCoupon + $settings->admin_coupon_balance)]);
            $odoo_offer_id = $this->BuyOffer($data);  // save to odoo
            $payment->update(['odoo_offer_id' => $odoo_offer_id, 'provider_value_of_coupon' => $providerValueOfthisCoupon]);

            if ($payment->id)
                return $this->show($request, true, $payment->code);
            else
                return $this->returnError('E001', trans('messages.failed to save data'));

        } catch (\Exception $ex) {
            return $this->returnError('E1' . $ex->getCode(), $ex->getMessage());
        }
    }

    public function sendCouponToMobile(Request $request)
    {

        $rules = [
            "id" => "required|exists:promocodes,id",
            "for_me" => "required|in:0,1",
            "code" => "exists:promocodes,code",
        ];
        if ($request->has('for_me')) {
            if ($request->for_me == 0) {
                $rules['invited_user_mobile'] = "required";
            }
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        $user = null;
        $mobile = null;
        if ($request->api_token)
            $user = User::where('api_token', $request->api_token)->first();
        if (!$user)
            return $this->returnError('D000', trans('messages.User not found'));
        $mobile = $user->mobile;
        //check if amount paid equel offer amount
        $promoCode = PromoCode::find($request->id);
        $invitedUser = null;
        if ($request->for_me == 0) {
            $invitedUser = User::where('mobile', $request->invited_user_mobile)->first();
            if (!$invitedUser)
                return $this->returnError('D000', trans('messages.this mobile to belong to any user'));
            $mobile = $invitedUser->mobile;
        }
        $code = $promoCode->code;
        $title = $promoCode->title;
        try {
            $message = __('messages.your Coupon Code') . " " . "{$title} " . __('messages.is') . "  - {$code} ";
            //send mobile sms
            $this->sendSMS($mobile, $message);
            return $this->returnSuccessMessage(trans('coupon code sent successfully'));

        } catch (\Exception $ex) {
        }
        return $this->returnError('E001', trans('messages.failed to send coupon code'));
    }

    protected function savePayment($request, $userId, $invitedMobile)
    {
        $code = $this->getRandomString(6);
        $inputs = [
            'user_id' => $userId,
            'for_me' => $request->for_me,
            'invited_user_mobile' => $invitedMobile,
            'amount' => $request->total_amount,
            'current_amount' => $request->total_amount,
            'payment_no' => Str::random(30),
            'offer_id' => $request->id,
            'code' => $code
        ];
        $payment = Payment::create($inputs);
        return $payment;
    }

    public function doctors(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            if (!$user) {
                return $this->returnError('D000', trans('messages.User not found'));
            }
            $validator = Validator::make($request->all(), [
                "offer_id" => "required|exists:promocodes,id",
                "branch_id" => "required|exists:providers,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $doctorIds = PromoCode::find($request->offer_id)->promocodedoctors->pluck('doctor_id');
            return $doctors = $this->getOfferDoctors($doctorIds, $user->id, $request->branch_id);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    protected function getOfferDoctors($doctorIds, $userId = null, $branch_id)
    {
        $doctor = Doctor::query();
        $doctor = $doctor->whereHas('provider', function ($q) use ($branch_id) {
            $q->where('id', $branch_id);
        })->whereIn('id', $doctorIds)
            ->with(['specification' => function ($q1) {
                $q1->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
            },// 'times' => function($q){
                // $q->orderBy('order');
                //},
                'nationality' => function ($q2) {
                    $q2->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'insuranceCompanies' => function ($q2) {
                    $q2->select('insurance_companies.id', 'image', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'nickname' => function ($q3) {
                    $q3->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
                }, 'provider' => function ($provider) use ($userId) {

                    $provider->with(['type' => function ($q) {
                        $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));

                    }, 'favourites' => function ($qu) use ($userId) {
                        $qu->where('user_id', $userId)->select('provider_id');
                    }, 'city' => function ($q) {
                        $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }, 'district' => function ($q) {
                        $q->select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                    }])->select('id', 'type_id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);

        $doctor = $doctor->select('id', 'provider_id', 'specification_id', 'nationality_id', 'nickname_id', 'photo', 'gender', 'rate', 'price', 'status',
            DB::raw('name_' . $this->getCurrentLang() . ' as name'),
            DB::raw('information_' . $this->getCurrentLang() . ' as information')
        );

        $doctors = $doctor->/*where('doctors.status', 1)->*/ paginate(10);

        if (count($doctors) > 0) {
            foreach ($doctors as $key => $doctor) {
                $doctor->time = "";
                $days = $doctor->times;
                $match = $this->getMatchedDateToDays($days);

                if (!$match || $match['date'] == null) {
                    $doctor->time = new \stdClass();;
                    continue;
                }
                $doctorTimesCount = $this->getDoctorTimePeriodsInDay($match['day'], $match['day']['day_code'], true);
                $availableTime = $this->getFirstAvailableTime($doctor->id, $doctorTimesCount, $days, $match['date'], $match['index']);
                $doctor->time = $availableTime;
                $doctor->branch_name = Doctor::find($doctor->id)->provider->{'name_' . app()->getLocale()};
            }
            $total_count = $doctors->total();
            $doctors->getCollection()->each(function ($doctor) {
                $doctor->makeVisible(['name_en', 'name_ar', 'information_en', 'information_ar']);
                return $doctor;
            });


            $doctors = json_decode($doctors->toJson());
            $doctorsJson = new \stdClass();
            $doctorsJson->current_page = $doctors->current_page;
            $doctorsJson->total_pages = $doctors->last_page;
            $doctorsJson->total_count = $total_count;
            $doctorsJson->data = $doctors->data;
            return $this->returnData('doctors', $doctorsJson);
        }

        return $this->returnError('E001', trans('messages.No doctors founded'));
    }

    private function getOfferForVisitors(Request $request)
    {
        $orderBy = 'id';
        $conditions = [];
        if (isset($request->filter_id) && !empty($request->filter_id)) {
            $filter = Filter::find($request->filter_id);
            if (!$filter)
                return $this->returnError('D000', trans('messages.filter not found'));
            if (in_array($filter->operation, [0, 1, 2])) { //if filter operation is < or > or =
                if ($filter->operation == 0) {   //less than
                    array_push($conditions, ['price_after_discount', '<=', (int)$filter->price]);
                } elseif ($filter->operation == 1) {  //greater than
                    array_push($conditions, ['price_after_discount', '>=', (int)$filter->price]);
                } else {
                    array_push($conditions, ['price_after_discount', '=', (int)$filter->price]);
                }
                $orderBy = 'price';
            } elseif (in_array($filter->operation, [3, 4, 5])) {  //3-> most paid 4-> most visited 5-> latest
                if ($filter->operation == 3) {
                    $orderBy = 'uses';
                } elseif ($filter->operation == 4) {
                    $orderBy = 'visits';
                } else
                    $orderBy = 'id';
            } else {
                $orderBy = 'id';
            }
        }

        // if 0 get all offer
        if ($request->category_id != 0) {
            $category = PromoCodeCategory::find($request->category_id);
            if (!$category)
                return $this->returnError('E001', trans('messages.There is no category with this id'));
            else {

                if (isset($request->featured) && $request->featured == 1) {

                    if (!empty($conditions) && count($conditions) > 0) {
                        $offers = PromoCode::where(function ($qq){
                            $qq->where('general', 1);
                        })
                            ->where($conditions)
                            ->featured()
                            ->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            }])
                            ->where('category_id', $category->id)
                            ->orderBy($orderBy, 'DESC')
                            ->limit(10)
                            ->selection()
                            ->get();
                    } else {
                        $offers = PromoCode::where(function ($qq)  {
                            $qq->where('general', 1);
                        })
                            ->featured()
                            ->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            }])
                            ->where('category_id', $category->id)
                            ->orderBy($orderBy, 'DESC')
                            ->limit(10)
                            ->selection()
                            ->get();
                    }

                } else {
                    //return $this->returnError('E001', trans('messages.There featured  must be 1 or not present '));

                    if (!empty($conditions) && count($conditions) > 0) {

                        $offers = PromoCode::where(function ($qq) {
                            $qq->where('general', 1);
                        })->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            }])
                            ->where('category_id', $category->id)
                            ->where($conditions)
                            ->selection()
                            ->orderBy($orderBy, 'DESC')
                            ->paginate(10);
                    } else {
                        $offers = PromoCode::where(function ($qq) {
                            $qq->where('general', 1);
                        })->active()
                            ->valid()
                            ->with(['provider' => function ($q) {
                                $q->select('id', 'rate', 'logo', 'type_id',
                                    DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                                $q->with(['type' => function ($q) {
                                    $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                }]);
                            }])
                            ->where('category_id', $category->id)
                            ->selection()
                            ->orderBy($orderBy, 'DESC')
                            ->paginate(10);
                    }
                }
            }

        } else {

            if (isset($request->featured) && $request->featured == 1) {

                if (!empty($conditions) && count($conditions) > 0) {
                    $offers = PromoCode::where(function ($qq) {
                        $qq->where('general', 1);
                    })->featured()
                        ->active()
                        ->valid()
                        ->with(['provider' => function ($q) {
                            $q->select('id', 'rate', 'logo', 'type_id',
                                DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                            $q->with(['type' => function ($q) {
                                $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                        },])
                        ->where($conditions)
                        ->orderBy($orderBy, 'DESC')
                        ->selection()
                        ->limit(25)
                        ->get();
                } else {
                    $offers = PromoCode::where(function ($qq)  {
                        $qq->where('general', 1);
                    })->featured()
                        ->active()
                        ->valid()
                        ->with(['provider' => function ($q) {
                            $q->select('id', 'rate', 'logo', 'type_id',
                                DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                            $q->with(['type' => function ($q) {
                                $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                        },])
                        ->orderBy($orderBy, 'DESC')
                        ->selection()
                        ->limit(25)
                        ->get();
                }
            } else

                if (!empty($conditions) && count($conditions) > 0) {
                    // PromoCode::find(6) -> currentId();
                    $offers = PromoCode::where(function ($qq)  {
                        $qq->where('general', 1);
                    })->where($conditions)
                        ->active()
                        ->valid()
                        ->with(['provider' => function ($q) {
                            $q->select('id', 'rate', 'logo', 'type_id',
                                DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                            $q->with(['type' => function ($q) {
                                $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                        }])
                        ->selection()
                        ->orderBy($orderBy, 'DESC')
                        ->paginate(10);
                } else {
                    $offers = PromoCode::where(function ($qq) {
                        $qq->where('general', 1);
                    })->active()
                        ->valid()
                        ->with(['provider' => function ($q) {
                            $q->select('id', 'rate', 'logo', 'type_id',
                                DB::raw('name_' . $this->getCurrentLang() . ' as name'));
                            $q->with(['type' => function ($q) {
                                $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                        }])
                        ->selection()
                        ->orderBy($orderBy, 'DESC')
                        ->paginate(10);
                }
        }

        if (isset($request->featured) && $request->featured == 1) {
            //if coupon allowed only for some users
            /*        if (isset($offers) && $offers->count() > 0) {
                        foreach ($offers as $index => $offer) {
                            if (!empty($offer->users) && count($offer->users) > 0) {
                                $authUserExistsForThisOffer = in_array($user->id, array_column($offer->users->toArray(), 'id'));
                                if (!$authUserExistsForThisOffer) {
                                    unset($offers[$index]);
                                }
                            }
                        }
                    }*/
            return $this->returnData('featured_offers', $offers);
        }

        $selectedValue = 0;
        if (count($offers->toArray()) > 0) {

            foreach ($offers as $index => $offer) {
                unset($offer->provider_id);
                unset($offer->available_count);
                unset($offer->status);
                unset($offer->created_at);
                unset($offer->updated_at);
                unset($offer->specification_id);
                /* if ($offers->coupons_type_id == 1) {
                     $offers->price = "0";
                 }*/
                if ($offer->coupons_type_id == 2) {
                    $offer->discount = "0";
                    $offer->code = "0";
                }

            }

            $offers = json_decode($offers->toJson());
            $total_count = $offers->total;
            $offersJson = new \stdClass();
            $offersJson->current_page = $offers->current_page;
            $offersJson->total_pages = $offers->last_page;
            $offersJson->total_count = $total_count;
            $offersJson->data = $offers->data;
            return $this->returnData('offers', $offersJson);
        }

        return $this->returnError('E001', trans('messages.No offers founded'));
    }

}
