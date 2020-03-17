@if(Request::is('mc33/provider/edit/*'))
 @if($provider && $provider -> logo != "" )
  <div class="col-xs-12 col-sm-3 center">
    <div>
        <div class="profile-picture">
            <img id="avatar" class="editable img-responsive" alt="Icon URL" src="{{$provider -> logo}}">
        </div>
    </div>
         <div class="space-10"></div>
  </div>
 @endif
@endif

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('name_ar', old('name_ar'), ['placeholder' => 'الإسم بالعربى', 'class' => 'form-control ' . ($errors->has('name_ar') ? 'redborder' : '') ]) }}
    <label for="name_ar">الإسم بالعربى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('name_ar') ? $errors->first('name_ar') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::text('name_en', old('name_en'), ['placeholder' => 'الإسم بالإنجليزى', 'class' => 'form-control ' . ($errors->has('name_en') ? 'redborder' : '') ]) }}
    <label for="name_en">الإسم بالإنجليزى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('name_en') ? $errors->first('name_en') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('commercial_ar', old('commercial_ar'), ['placeholder' => 'الأسم التجاري  يالعربي   ', 'class' => 'form-control ' . ($errors->has('commercial_ar') ? 'redborder' : '') ]) }}
    <label for="commercial_ar"> الأسم  التجاري  بالعربى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('commercial_ar') ? $errors->first('commercial_ar') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::text('commercial_en', old('commercial_en'), ['placeholder' => ' الأسم التجاري بالانجليزي   ', 'class' => 'form-control ' . ($errors->has('commercial_en') ? 'redborder' : '') ]) }}
    <label for="commercial_en">الاسم التجاري بالانجليزي   <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('commercial_en') ? $errors->first('commercial_en') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('username', old('username'), ['placeholder' => 'أسم المستخدم ', 'class' => 'form-control ' . ($errors->has('username') ? 'redborder' : '') ]) }}
    <label for="sd"> أسم المستخدم <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('username') ? $errors->first('username') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::text('mobile', old('mobile'), ['placeholder' => 'رقم  الجوال  ', 'class' => 'form-control ' . ($errors->has('mobile') ? 'redborder' : '') ]) }}
    <label for="name_en">رقم الجوال <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('mobile') ? $errors->first('mobile') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('commercial_no', old('commercial_no'), ['placeholder' => 'رقم السجل التجاري ', 'class' => 'form-control ' . ($errors->has('commercial_no') ? 'redborder' : '') ]) }}
    <label for="name_en"> رقم السجل التجاري <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('commercial_no') ? $errors->first('commercial_no') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6" style="display: none;">
    {{ Form::email('email', old('email'), ['placeholder' => 'البريد الإلكترونى', 'class' => 'form-control ' . ($errors->has('email') ? 'redborder' : '') ]) }}
    <label for="email">البريد الإلكترونى</label>
    <small class="text-danger">{{ $errors->has('email') ? $errors->first('email') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6" style="display: none;">
    {{ Form::text('address', old('address'), ['placeholder' => 'العنوان', 'class' => 'form-control ' . ($errors->has('address') ? 'redborder' : '') ]) }}
    <label for="address">العنوان</label>
    <small class="text-danger">{{ $errors->has('address') ? $errors->first('address') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('type_id', $types, (isset($provider)) ? $provider->type_id : old('type_id'), ['placeholder' => 'اختر النوع',  'class' => 'form-control ' . ($errors->has('type_id') ? 'redborder' : '') ]) }}
    <label for="type_id">النوع <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('type_id') ? $errors->first('type_id') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('city_id', $cities, (isset($provider)) ? $provider->city_id : old('city_id'), ['placeholder' => 'اختر المدينة', 'class' => 'form-control ' . ($errors->has('city_id') ? 'redborder' : '') ]) }}
    <label for="city_id">المدينة <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('city_id') ? $errors->first('city_id') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('district_id', $districts, (isset($provider)) ? $provider->district_id : old('district_id'), ['placeholder' => 'اختر الحى', 'class' => 'form-control ' . ($errors->has('district_id') ? 'redborder' : '') ]) }}
    <label for="district_id">الحى</label>
    <small class="text-danger">{{ $errors->has('district_id') ? $errors->first('district_id') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6" style="display: none;">
    {{ Form::text('street', old('street'), ['placeholder' => 'الشارع', 'class' => 'form-control ' . ($errors->has('street') ? 'redborder' : '') ]) }}
    <label for="street">الشارع</label>
    <small class="text-danger">{{ $errors->has('street') ? $errors->first('street') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('status', [1 => 'مفعّل', 0 => 'غير مفعّل'], old('status'), ['placeholder' => 'التفعيل', 'class' => 'form-control ' . ($errors->has('status') ? 'redborder' : '') ]) }}
    <label for="status">التفعيل <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('status') ? $errors->first('status') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::password('password', ['placeholder' => 'كلمة المرور ', 'class' => 'form-control ' . ($errors->has('password') ? 'redborder' : '') ]) }}
    <label for="name_en"> كلمه المرور <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('password') ? $errors->first('password') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6"  >
    {{ Form::number('application_percentage', old('application_percentage'), ['placeholder' => ' نسبة التطبيق', 'class' => 'form-control ' . ($errors->has('application_percentage') ? 'redborder' : '') ]) }}
    <label for="street"> نسبة التطبيق علي الكشف</label>
    <small class="text-danger">{{ $errors->has('application_percentage') ? $errors->first('application_percentage') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6"  >
    {{ Form::number('application_percentage_bill', old('application_percentage_bill'), ['placeholder' => '  نسبة التطبيق من الفاتوره ', 'class' => 'form-control ' . ($errors->has('application_percentage_bill') ? 'redborder' : '') ]) }}
    <label for="street"> نسبة التطبيق علي الفاتورة</label>
    <small class="text-danger">{{ $errors->has('application_percentage_bill') ? $errors->first('application_percentage_bill') : '' }}</small>
</div>



<div class="form-group has-float-label col-sm-6"  >
    {{ Form::number('application_percentage_bill_insurance', old('application_percentage_bill_insurance'), ['placeholder' => '  نسبة التطبيق من الفاتوره بالتامين ', 'class' => 'form-control ' . ($errors->has('application_percentage_bill_insurance') ? 'redborder' : '') ]) }}
    <label for="street"> نسبة التطبيق علي الفاتورة بوجود تأمين </label>
 </div>



<div class="form-group has-float-label col-sm-6">
    {{ Form::file('logo', ['placeholder' => 'الصورة', 'class' => 'form-control ' . ($errors->has('logo') ? 'redborder' : '') ]) }}
    <label for="logo">الصورة</label>
    <small class="text-danger">{{ $errors->has('logo') ? $errors->first('logo') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-12">
   {{ Form::text('latLng', old('latLng'), ['placeholder' => 'أبحث هنا ','title' => 'أبحث هنا ','id'=>"pac-input", 'class' => 'form-control  controls' . ($errors->has('latLng') ? 'redborder' : '') ]) }}

    <label for="name_ar"> ابحث عن موقعك <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('latLng') ? $errors->first('latLng') : '' }}</small>
   <br>
   <input type="hidden" id="latitudef"  name="latitude" value="{{@$provider -> latitude}}">
   <input type="hidden" id="longitudef" name="longitude" value="{{@$provider -> longitude}}">
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
