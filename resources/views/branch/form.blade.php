<div class="form-group has-float-label col-sm-6">
    {{ Form::select('provider_id', $providers, (isset($branch)) ? $branch->provider_id : old('provider_id'), ['placeholder' => 'اختر  مقدم الخدمة ', 'class' => 'form-control ' . ($errors->has('provider_id') ? 'redborder' : '') ]) }}
    <label for="provider_id"> مقدم الخدمة </label>
    <small class="text-danger">{{ $errors->has('provider_id') ? $errors->first('provider_id') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6" style="display: none;">
    {{ Form::text('address', old('address'), ['placeholder' => 'العنوان', 'class' => 'form-control ' . ($errors->has('address') ? 'redborder' : '') ]) }}
    <label for="address">العنوان</label>
    <small class="text-danger">{{ $errors->has('address') ? $errors->first('address') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::text('branch_no', old('branch_no'), ['placeholder' => 'رقم الفرع ',  'class' => 'form-control ' . ($errors->has('branch_no') ? 'redborder' : '') ]) }}
    <label for="branch_no">رقم الفرع <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('branch_no') ? $errors->first('branch_no') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('name_ar', old('name_ar'), ['placeholder' => 'الإسم بالعربى',  'class' => 'form-control ' . ($errors->has('name_ar') ? 'redborder' : '') ]) }}
    <label for="name_ar">الإسم بالعربى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('name_ar') ? $errors->first('name_ar') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('name_en', old('name_en'), ['placeholder' => 'الإسم بالإنجليزى',  'class' => 'form-control ' . ($errors->has('name_en') ? 'redborder' : '') ]) }}
    <label for="name_en">الإسم بالإنجليزى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('name_en') ? $errors->first('name_en') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('username', old('username'), ['placeholder' => 'أسم المستخدم ', 'class' => 'form-control ' . ($errors->has('username') ? 'redborder' : '') ]) }}
    <label for="sd"> أسم المستخدم <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('username') ? $errors->first('username') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::select('city_id', $cities, (isset($branch)) ? $branch->city_id : old('city_id'), ['placeholder' => 'اختر المدينة',  'class' => 'form-control ' . ($errors->has('city_id') ? 'redborder' : '') ]) }}
    <label for="city_id">المدينة <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('city_id') ? $errors->first('city_id') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('district_id', $districts, (isset($branch)) ? $branch->district_id : old('district_id'), ['placeholder' => 'اختر الحى', 'class' => 'form-control ' . ($errors->has('district_id') ? 'redborder' : '') ]) }}
    <label for="district_id">الحى</label>
    <small class="text-danger">{{ $errors->has('district_id') ? $errors->first('district_id') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('street', old('street'), ['placeholder' => 'الشارع', 'class' => 'form-control ' . ($errors->has('street') ? 'redborder' : '') ]) }}
    <label for="street">الشارع</label>
    <small class="text-danger">{{ $errors->has('street') ? $errors->first('street') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('status', [1 => 'مفعّل', 0 => 'غير مفعّل'], old('status'), ['placeholder' => 'التفعيل',  'class' => 'form-control ' . ($errors->has('status') ? 'redborder' : '') ]) }}
    <label for="status">التفعيل <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('status') ? $errors->first('status') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::select('has_home_visit', [1 => 'مفعّله', 0 => 'غير مفعّله'], old('has_home_visit'), ['placeholder' => 'خدمه الزياره المنزلية',  'class' => 'form-control ' . ($errors->has('has_home_visit') ? 'redborder' : '') ]) }}
    <label for="status">خدمات الزيارة المنزلية <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('has_home_visit') ? $errors->first('has_home_visit') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::text('mobile', old('mobile'), ['placeholder' => 'رقم هاتف المسئول ', 'class' => 'form-control ' . ($errors->has('mobile') ? 'redborder' : '') ]) }}
    <label for="mobile"> رقم الهاتف </label>
    <small class="text-danger">{{ $errors->has('mobile') ? $errors->first('mobile') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::password('password' , ['placeholder' => 'كلمة المرور ', 'class' => 'form-control ' . ($errors->has('password') ? 'redborder' : '') ]) }}
    <label for="street">كلمة المرور </label>
    <small class="text-danger">{{ $errors->has('password') ? $errors->first('password') : '' }}</small>
</div>

@if(!Request::is('mc33/branch/create'))
    <div class="form-group has-float-label col-sm-6">
        {{ Form::text('rate', old('rate'), ['placeholder' => 'تقييم الفرع', 'class' => 'form-control ' . ($errors->has('rate') ? 'redborder' : '') ]) }}
        <label for="street"> التقييم</label>
        <small class="text-danger">{{ $errors->has('rate') ? $errors->first('rate') : '' }}</small>
    </div>
@endif

<div class="form-group has-float-label col-sm-12">
    {{ Form::text('latLng', old('latLng'), ['placeholder' => 'أبحث هنا ','title' => 'أبحث هنا ','id'=>"pac-input", 'class' => 'form-control  controls' . ($errors->has('latLng') ? 'redborder' : '') ]) }}

    <label for="name_ar"> ابحث عن موقعك <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('latLng') ? $errors->first('latLng') : '' }}</small>
    <br>
    <input type="hidden" id="latitudef" name="latitude" value="{{@$branch -> latitude}}">
    <input type="hidden" id="longitudef" name="longitude" value="{{@$branch -> longitude}}">
    <div class="error-messagen">
        @if($errors->has('latitude'))
            {{$errors -> first('latitude')}}
        @endif
    </div>

    <div id="map" style="height: 500px;width: 1000px;"></div>
</div>

<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm' ]) }}
</div>
