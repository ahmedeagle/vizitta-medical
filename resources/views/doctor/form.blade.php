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
    {{ Form::text('abbreviation_ar', old('abbreviation_ar'), ['placeholder' => 'النبذة بالعربى  ',  'class' => 'form-control ' . ($errors->has('abbreviation_ar') ? 'redborder' : '') ]) }}
    <label for="abbreviation_ar"> النبذة بالعربى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('abbreviation_ar') ? $errors->first('abbreviation_ar') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('abbreviation_en', old('abbreviation_en'), ['placeholder' => ' النبذة بالعربى ',  'class' => 'form-control ' . ($errors->has('abbreviation_en') ? 'redborder' : '') ]) }}
    <label for="abbreviation_en">النبذة بالإنجليزى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('abbreviation_en') ? $errors->first('abbreviation_en') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::textarea('information_ar', old('information_ar'), ['placeholder' => 'المعلومات بالعربية', 'class' => 'form-control ' . ($errors->has('information_ar') ? 'redborder' : '') ]) }}
    <label for="information_ar">المعلومات بالعربية</label>
    <small class="text-danger">{{ $errors->has('information_ar') ? $errors->first('information_ar') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::textarea('information_en', old('information_en'), ['placeholder' => 'المعلومات بالإنجليزية', 'class' => 'form-control ' . ($errors->has('information_en') ? 'redborder' : '') ]) }}
    <label for="information_en">المعلومات بالإنجليزية</label>
    <small class="text-danger">{{ $errors->has('information_en') ? $errors->first('information_en') : '' }}</small>
</div>



@if(isset($doctor))
    <div class="col-sm-2">
        <img src="@if(!empty($doctor->photo)){{ asset($doctor->photo) }}@else{{ asset('images/no_image.png') }}@endif"
             class="side_image">
    </div>
@endif

<div class="form-group has-float-label @if(isset($doctor)) col-sm-10 @else col-sm-12 @endif">
    {{ Form::file('photo', ['placeholder' => 'الصورة', 'class' => 'form-control ' . ($errors->has('photo') ? 'redborder' : '') ]) }}
    <label for="photo">الصورة</label>
    <small class="text-danger">{{ $errors->has('photo') ? $errors->first('photo') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('gender', [1 => "رجل", 2 => "سيدة"], old('gender'), ['placeholder' => 'النوع',  'class' => 'form-control ' . ($errors->has('gender') ? 'redborder' : '') ]) }}
    <label for="gender">النوع <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('gender') ? $errors->first('gender') : '' }}</small>
</div>

@if(isset($branchId) && $branchId != null)
    <input type="hidden" name="provider_id" value="{{$branchId}}">
@else
    <div class="form-group has-float-label col-sm-12 selectpickerdiv">
        {{ Form::select('provider_id', $providers, (isset($doctor)) ? $doctor->provider_id : old('provider_id'), ['placeholder' => 'اختر  الفرع ', 'data-live-search' => "true", 'class' => 'form-control js-example-basic-single' . ($errors->has('provider_id') ? 'redborder' : '') ]) }}
        <label for="provider_id"> الفرع <span class="astric">*</span></label>
        <small class="text-danger">{{ $errors->has('provider_id') ? $errors->first('provider_id') : '' }}</small>
    </div>
@endif

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('nickname_id', $nicknames, (isset($doctor)) ? $doctor->nickname_id : old('nickname_id'), ['placeholder' => 'اختر اللقب',  'class' => 'form-control ' . ($errors->has('nickname_id') ? 'redborder' : '') ]) }}
    <label for="nickname_id">اللقب <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('nickname_id') ? $errors->first('nickname_id') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('specification_id', $specifications, (isset($doctor)) ? $doctor->specification_id : old('specification_id'), ['placeholder' => 'اختر التخصص',  'class' => 'form-control ' . ($errors->has('specification_id') ? 'redborder' : '') ]) }}
    <label for="specification_id">التخصص <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('specification_id') ? $errors->first('specification_id') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('nationality_id', $nationalities, (isset($doctor)) ? $doctor->nationality_id : old('nationality_id'), ['placeholder' => 'اختر الجنسية',  'class' => 'form-control ' . ($errors->has('nationality_id') ? 'redborder' : '') ]) }}
    <label for="nationality_id">الجنسية <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('nationality_id') ? $errors->first('nationality_id') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::number('price', old('price'), ['placeholder' => 'سعر الكشف',  'class' => 'form-control ' . ($errors->has('price') ? 'redborder' : '') ]) }}
    <label for="price">سعر الكشف <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('price') ? $errors->first('price') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('status', [1 => 'مفعّل', 0 => 'غير مفعّل'], old('status'), ['placeholder' => 'التفعيل',  'class' => 'form-control ' . ($errors->has('status') ? 'redborder' : '') ]) }}
    <label for="status">التفعيل <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('status') ? $errors->first('status') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::number('reservation_period', old('reservation_period'), ['placeholder' => '15 مثال ','onkeyup'=>'keyupFunction(this)','id' =>'reservation_period','min' => '15' ,'max' => '120','class' => 'form-control ' . ($errors->has('reservation_period') ? 'redborder' : '') ]) }}
    <label for="reservation_period"> مدة الكشف <span class="astric">*</span></label>
    <small
        class="text-danger">{{ $errors->has('reservation_period') ? $errors->first('reservation_period') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('waiting_period', old('waiting_period'), ['placeholder' => '15 مثال ','onkeyup'=>'keyupFunction(this)','id' =>'waiting_period' ,'class' => 'form-control ' . ($errors->has('waiting_period') ? 'redborder' : '') ]) }}
    <label for="waiting_period"> مدة الانتظار  </label>
    <small
        class="text-danger">{{ $errors->has('waiting_period') ? $errors->first('waiting_period') : '' }}</small>
</div>

{{-- <div class="form-group has-float-label col-sm-6">
    {{ Form::select('insurance_companies[]', $companies, isset($doctor->insuranceCompanies) ? $doctor->insuranceCompanies->pluck('id')->toArray() : old('insurance_companies'), [ 'multiple' => 'multiple',  'class' => 'js-example-basic-multiple form-control ' . ($errors->has('insurance_companies.*') ? 'redborder' : '') ]) }}
    <label for="insurance_companies">شركات التأمين <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('insurance_companies') ? 'لابد من اختيار شركه تامين ': '' }}</small>
</div>  --}}

<div class="col-sm-12">

    <div class="row">
        @if(isset($companies) && $companies -> count() > 0)
            @foreach($companies as $index =>  $company)
                <div class="col-md-4">
                    <label class="checkbox-inline" style="user-select: none">
                        <input style="margin: -8px -19px 0 0 " type="checkbox" name="insurance_companies[]"
                               {{ (is_array(old('insurance_companies')) && in_array($company -> id, old('insurance_companies'))) ? ' checked' : '' }} @if($company -> selected == 1) checked
                               @endif style=" display: inline-block"
                               value="{{$company -> id}}"> {{$company -> name_ar}}
                    </label>
                </div>
            @endforeach
        @endif
    </div>
    <small class="text-danger">{{ $errors->has('insurance_companies') ? 'لابد من اختيار شركه تامين ': '' }}</small>
</div>

<div class="form-group has-float-label col-sm-12">


    <table class="table">
        <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">اليوم</th>
            <th scope="col">من</th>
            <th scope="col">الي</th>
            @if(Request::is('mc33/doctor/create'))
                <th scope="col"></th>
            @endif
        </tr>
        </thead>
        <tbody>

        @if(Request::is('mc33/doctor/edit/*'))

            <div class="page-content">
                <div class="col-md-12">
                    <div class="page-header">
                        <h1><i class="menu-icon fa fa-clock-o"></i> المواعيد الحالية لدي الطبيب </h1>
                    </div>
                </div>
            @component('doctor.days',['days' => $days,'times' => @$times])
            @endcomponent
        @else
            @component('doctor.daysCreate',['days' => $days])
            @endcomponent
        @endif

        </tbody>
    </table>
</div>
<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm saveDoctor' ,'id' => 'postWorkingHourse']) }}
</div>
