<div class="form-group has-float-label col-sm-12" style="padding-bottom: 8px;">
    <select name="category_ids[]" multiple="multiple" class='js-example-basic-multiple form-control ' . {{$errors->has('category_id') ? 'redborder' : ''}}>
        <optgroup label="أختر أقسام العرض">
            @if(isset($categories)&& $categories -> count() > 0 )
                @foreach($categories as $category)
                    <option value="{{$category -> id}}">{{$category -> name_ar}} {{$category -> hastimer ? '(عداد تنازلي)': ''}}</option>
                @endforeach
            @endif
        </optgroup>
    </select>
    <label for="status">القسم <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('category_ids')  or  $errors->has('category_ids.*') ? $errors->first('category_ids') : '' }}</small>
</div>

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


<div class="form-group has-float-label col-sm-6">
    {{ Form::select('coupons_type_id', [1 => 'خصم ', 2 => 'كوبون '], old('coupons_type_id'), ['placeholder' => ' النوع ', 'id' => 'cop_type', 'class' => 'form-control ' . ($errors->has('coupons_type_id') ? 'redborder' : '') ]) }}
    <label for="status">النوع <span class="astric">*</span></label>
    <small
        class="text-danger">{{ $errors->has('coupons_type_id') ? $errors->first('coupons_type_id') : '' }}</small>
</div>
<div id="cop_pricee" class="form-group has-float-label col-sm-6">
    {{ Form::number('price', old('price'), ['placeholder' => 'ادخل السعر في حاله الكوبون فقط ','class' => 'form-control ' . ($errors->has('price') ? 'redborder' : '') ]) }}
    <label for="price"> سعر الكوبون<span class="astric">*</span> </label>
    <small class="text-danger">{{ $errors->has('price') ? $errors->first('price') : '' }}</small>
</div>

<div id="cop_pricee" class="form-group has-float-label col-sm-6">
    {{ Form::number('price_after_discount', old('price_after_discount'), ['placeholder' => 'ادخل السعر  بعد الخصم','class' => 'form-control ' . ($errors->has('price_after_discount') ? 'redborder' : '') ]) }}
    <label for="price"> السعر بعد الخصم<span class="astric">*</span> </label>
    <small class="text-danger">{{ $errors->has('price_after_discount') ? $errors->first('price_after_discount') : '' }}</small>
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
    {{ Form::select('status', [1 => 'مفعّل', 0 => 'غير مفعّل'], old('status'), ['placeholder' => 'الحالة',  'class' => 'form-control ' . ($errors->has('status') ? 'redborder' : '') ]) }}
    <label for="status">الحالة <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('status') ? $errors->first('status') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::date('expired_at', old('expired_at'), ['placeholder' => 'تاريخ الإنتهاء',  'class' => 'form-control ' . ($errors->has('expired_at') ? 'redborder' : '') ]) }}
    <label for="expired_at">تاريخ الإنتهاء <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('expired_at') ? $errors->first('expired_at') : '' }}</small>
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
<div class="form-group has-float-label col-sm-12">
    <select name="doctorsIds[]" style='height: 100px !important;' id="doctors" multiple='multiple'
            class='appenddoctors js-example-basic-multiple form-control ' {{$errors->has('doctorsIds') ? 'redborder' : ''}}>
    </select>
    <label for="doctors"> الاطباء </label>
    <small class="text-danger">{{ $errors->has('doctorsIds') ? $errors->first('doctorsIds') : '' }}</small>
</div>

<div class="form-group col-sm-12">
    <label for="features">المحتوى بالعربي <span class="astric">*</span></label>
    {{ Form::textarea('features_ar', old('features_ar'), ['placeholder' => 'تفاصيل الكوبون ', 'id' => 'features_ar', 'required' => 'required', 'class' => 'artextarea form-control ' . ($errors->has('features_ar') ? 'redborder' : '') ]) }}
    <small class="text-danger">{{ $errors->has('features_ar') ? $errors->first('features_ar') : '' }}</small>
</div>

<div class="form-group col-sm-12">
    <label for="features">المحتوى بالانجليزي <span class="astric">*</span></label>
    {{ Form::textarea('features_en', old('features_en'), ['placeholder' => 'تفاصيل الكوبون ', 'id' => 'features_en', 'required' => 'required', 'class' => 'entextarea form-control ' . ($errors->has('features_en') ? 'redborder' : '') ]) }}
    <small class="text-danger">{{ $errors->has('features_en') ? $errors->first('features_en') : '' }}</small>
</div>
<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm' ]) }}
</div>

