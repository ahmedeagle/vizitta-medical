<div class="form-group has-float-label col-sm-12" style="padding-bottom: 8px;">
    <select id="parent_categories" name="category_ids[]" multiple="multiple"
            class="js-example-basic-multiple form-control {{$errors->has('category_id') ? 'redborder' : ''}}">
        <optgroup label="أختر أقسام العرض">
            @if(isset($categories)&& $categories -> count() > 0 )
                @foreach($categories as $category)
                    <option
                        value="{{$category -> id}}">{{$category -> name_ar}} {{$category -> hastimer ? '(عداد تنازلي)': ''}}</option>
                @endforeach
            @endif
        </optgroup>
    </select>
    <label for="status">القسم الرئيسى<span class="astric">*</span></label>
    <small
        class="text-danger">{{ $errors->has('category_ids')  or  $errors->has('category_ids.*') ? $errors->first('category_ids') : '' }}</small>
</div>

<div id="childCategories"></div>
<div class="clearfix"></div>
<br>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('title_ar', old('title_ar'), ['placeholder' => 'خصم كبير علي الحجوزات ',  'class' => 'form-control ' . ($errors->has('title_ar') ? 'redborder' : '') ]) }}
    <label for="title"> عنوان الكوبون بالعربي <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('title_ar') ? $errors->first('title_ar') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::text('title_en', old('title_en'), ['placeholder' => 'خصم كبير علي الحجوزات ',  'class' => 'form-control ' . ($errors->has('title_en') ? 'redborder' : '') ]) }}
    <label for="title"> عنوان الكوبون بالانجليزي <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('title_en') ? $errors->first('title_en') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::file('photo', ['class' => 'form-control ' . ($errors->has('photo') ? 'redborder' : '') ]) }}
    <label for="title">صوره الكوبون <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('photo') ? $errors->first('photo') : '' }}</small>
</div>

<div id="cop_pricee" class="form-group has-float-label col-sm-6">
    {{ Form::number('price', old('price'), ['placeholder' => 'ادخل السعر في حاله الكوبون فقط ','class' => 'form-control ' . ($errors->has('price') ? 'redborder' : '') ]) }}
    <label for="price"> سعر الكوبون<span class="astric">*</span> </label>
    <small class="text-danger">{{ $errors->has('price') ? $errors->first('price') : '' }}</small>
</div>

<div id="cop_pricee" class="form-group has-float-label col-sm-6">
    {{ Form::number('price_after_discount', old('price_after_discount'), ['placeholder' => 'ادخل السعر  بعد الخصم','class' => 'form-control ' . ($errors->has('price_after_discount') ? 'redborder' : '') ]) }}
    <label for="price"> السعر بعد الخصم<span class="astric">*</span> </label>
    <small
        class="text-danger">{{ $errors->has('price_after_discount') ? $errors->first('price_after_discount') : '' }}</small>
</div>

<div id="cop_percg" class="form-group has-float-label col-sm-6">
    {{ Form::number('paid_coupon_percentage', old('paid_coupon_percentage'), ['placeholder' => 'ادخل  نسبة الاداره من الكوبون ','class' => 'form-control ' . ($errors->has('paid_coupon_percentage') ? 'redborder' : '') ]) }}
    <label for="price"> نسبة الاداره من الكوبون </label>
    <small
        class="text-danger">{{ $errors->has('paid_coupon_percentage') ? $errors->first('paid_coupon_percentage') : '' }}</small>
</div>

<div id="copounCode" class="form-group has-float-label col-sm-6">
    {{ Form::text('code', old('code'), ['placeholder' => 'الرمز',  'class' => 'form-control ' . ($errors->has('code') ? 'redborder' : '') ]) }}
    <label for="code">الرمز <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('code') ? $errors->first('code') : '' }}</small>
</div>

<div id="cop_discount" class="form-group has-float-label col-sm-6">
    {{ Form::text('discount', old('discount'), ['placeholder' => 'الخصم',  'class' => 'form-control ' . ($errors->has('discount') ? 'redborder' : '') ]) }}
    <label for="discount">الخصم <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('discount') ? $errors->first('discount') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::number('available_count', old('available_count'), ['placeholder' => 'العدد المتاح',  'class' => 'form-control ' . ($errors->has('available_count') ? 'redborder' : '') ]) }}
    <label for="available_count">العدد المتاح <span class="astric">*</span></label>
    <small
        class="text-danger">{{ $errors->has('available_count') ? $errors->first('available_count') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('available_count_type', ['once' => 'مرة واحدة', 'more_than_once' => 'اكثر من مرة'], old('available_count_type'), ['placeholder' => 'نوع العدد المتاح',  'class' => 'form-control ' . ($errors->has('available_count_type') ? 'redborder' : '') ]) }}
    <label for="available_count_type">نوع العدد المتاح <span class="astric">*</span></label>
    <small
        class="text-danger">{{ $errors->has('available_count_type') ? $errors->first('available_count_type') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::date('started_at', old('started_at'), ['placeholder' => 'تاريخ البداية',  'class' => 'form-control ' . ($errors->has('started_at') ? 'redborder' : '') ]) }}
    <label for="started_at">تاريخ البداية <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('started_at') ? $errors->first('started_at') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::date('expired_at', old('expired_at'), ['placeholder' => 'تاريخ الإنتهاء',  'class' => 'form-control ' . ($errors->has('expired_at') ? 'redborder' : '') ]) }}
    <label for="expired_at">تاريخ الإنتهاء <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('expired_at') ? $errors->first('expired_at') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('gender', ['all' => 'رجال ونساء', 'males' => 'رجال فقط', 'females' => 'نساء فقط'], old('gender'), ['class' => 'form-control ' . ($errors->has('gender') ? 'redborder' : '') ]) }}
    <label for="gender">الجنس <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('gender') ? $errors->first('gender') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('status', [1 => 'مفعّل', 0 => 'غير مفعّل'], old('status'), ['placeholder' => 'الحالة',  'class' => 'form-control ' . ($errors->has('status') ? 'redborder' : '') ]) }}
    <label for="status">الحالة <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('status') ? $errors->first('status') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6" id="app_perc" style="display: none;">
    {{ Form::number('application_percentage', old('application_percentage'), ['placeholder' => ' نسبة التطبيق', 'class' => 'form-control ' . ($errors->has('application_percentage') ? 'redborder' : '') ]) }}
    <label for="street"> نسبة التطبق من العرض لهذا التاجر</label>
    <small
        class="text-danger">{{ $errors->has('application_percentage') ? $errors->first('application_percentage') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('featured', $featured, old('featured'), ['name'=>'featured' ,'class' => 'form-control ' . ($errors->has('featured') ? 'redborder' : '') ]) }}
    <label for="provider_id"> تمييز العرض </label>
    <small class="text-danger">{{ $errors->has('featured') ? $errors->first('featured') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('device_type', old('device_type'), ['placeholder' => 'نوع الجهاز ',  'class' => 'form-control ' . ($errors->has('device_type') ? 'redborder' : '') ]) }}
    <label for="title"> نوع الجهاز </label>
    <small class="text-danger">{{ $errors->has('device_type') ? $errors->first('device_type') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-12">
    {{ Form::select('users', $users, old('users'), ['multiple'=>'multiple','name'=>'users[]' ,'data-live-search'=> 'true', 'id'=>"users", 'class' => 'js-example-basic-multiple form-control ' . ($errors->has('users') ? 'redborder' : '') ]) }}
    <label for="provider_id"> تخصيص مستخدمي العرض </label>
    <small class="text-danger">{{ $errors->has('users') ? $errors->first('users') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-12">
    {{ Form::select('provider_id', $providers, old('provider_id'), [ 'data-live-search'=> 'true','placeholder' => 'مقدم الخدمة','id' => 'providers', 'class' => 'form-control js-example-basic-single' . ($errors->has('provider_id') ? 'redborder' : '') ]) }}
    <label for="provider_id">مقدم الخدمة</label>
    <small class="text-danger">{{ $errors->has('provider_id') ? $errors->first('provider_id') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-12 ">
    <select name="branchIds[]" style='height: 100px !important;' id="branches" multiple='multiple'
            class='appendbrnaches js-example-basic-multiple form-control ' {{$errors->has('branch_id') ? 'redborder' : ''}}>
    </select>
    <label for="branches"> الأفرع </label>
    <small class="text-danger">{{ $errors->has('branchIds') ? $errors->first('branchIds') : '' }}</small>
</div>

<div id="branchTimesDiv" style="margin-bottom: 50px"></div>
<div class="clearfix"></div>
<hr>
<br>
{{--<label> وسائل الدفع </label>--}}
<div class="row form-group has-float-label">
    @if(isset($paymentMethods) && $paymentMethods -> count() > 0)
        @foreach($paymentMethods as $index => $paymentMethod)
            <div class="col-md-4">
                <label class="checkbox-inline" style="user-select: none">
                    <input style="margin: -8px -19px 0 0 " type="checkbox" id="payment_method" name="payment_method[]"
                           {{ (is_array(old('payment_method')) && in_array($paymentMethod->id, old('payment_method'))) ? ' checked' : '' }} @if($paymentMethod -> selected == 1) checked
                           @endif style=" display: inline-block"
                           value="{{$paymentMethod -> id}}"> {{$paymentMethod -> name_ar}}
                </label>
            </div>
        @endforeach
    @endif
    <small class="text-danger">{{ $errors->has('payment_method') ? $errors->first('payment_method') : '' }}</small>
</div>
<br>
<div class="form-group has-float-label col-sm-6" style="display: none;" id="amountTypeDiv">
    {{ Form::select('payment_amount_type', ['all' => 'المبلغ كامل', 'custom' => 'مبلغ معين'], old('payment_amount_type'), ['id'=>'payment_amount_type', 'name'=>'payment_amount_type' ,'class' => 'form-control', '']) }}
    <label for="payment_amount_type"> نوع المبلغ </label>
</div>

<div class="form-group has-float-label col-sm-6" style="display: none;" id="customAmountDiv">
    {{ Form::number('payment_amount', old('payment_amount'), ['placeholder' => 'المبلغ',  'class' => 'form-control ' . ($errors->has('payment_amount') ? 'redborder' : '') ]) }}
    <label for="title"> المبلغ </label>
    <small class="text-danger">{{ $errors->has('payment_amount') ? $errors->first('payment_amount') : '' }}</small>
</div>

<hr>
<br>

<div class="form-group has-float-label offer-content" style="padding-top: 30px">
    <div class="col-sm-6">
        <label for="title"> المحتوى بالعربية </label>
        <input type="text" name="offer_content[ar][]" placeholder="المحتوى بالعربية" style="width: 100%;" value="">
    </div>
    <div class="col-sm-6">
        <label for="title"> المحتوى بالإنجليزية </label>
        <input type="text" name="offer_content[en][]" placeholder="المحتوى بالإنجليزية" style="width: 73%;" value="">
        <button type="button" id="" class="btnAddMoreContent btn btn-success sm"><i
                class="menu-icon fa fa-plus-circle fa-fw"></i></button>

    </div>
</div>

<div class="form-group has-float-label offer-content" id="allContentDivs"></div>


{{--<div class="form-group has-float-label col-sm-12">
    <select name="doctorsIds[]" style='height: 100px !important;' id="doctors" multiple='multiple'
            class='appenddoctors js-example-basic-multiple form-control ' {{$errors->has('doctorsIds') ? 'redborder' : ''}}>
    </select>
    <label for="doctors"> الاطباء </label>
    <small class="text-danger">{{ $errors->has('doctorsIds') ? $errors->first('doctorsIds') : '' }}</small>
</div>--}}

{{--<div class="form-group col-sm-12">
    <label for="features">المحتوى بالعربي <span class="astric">*</span></label>
    {{ Form::textarea('features_ar', old('features_ar'), ['placeholder' => 'تفاصيل الكوبون ', 'id' => 'features_ar', 'required' => 'required', 'class' => 'artextarea form-control ' . ($errors->has('features_ar') ? 'redborder' : '') ]) }}
    <small class="text-danger">{{ $errors->has('features_ar') ? $errors->first('features_ar') : '' }}</small>
</div>

<div class="form-group col-sm-12">
    <label for="features">المحتوى بالانجليزي <span class="astric">*</span></label>
    {{ Form::textarea('features_en', old('features_en'), ['placeholder' => 'تفاصيل الكوبون ', 'id' => 'features_en', 'required' => 'required', 'class' => 'entextarea form-control ' . ($errors->has('features_en') ? 'redborder' : '') ]) }}
    <small class="text-danger">{{ $errors->has('features_en') ? $errors->first('features_en') : '' }}</small>
</div>--}}

<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm' ]) }}
</div>


