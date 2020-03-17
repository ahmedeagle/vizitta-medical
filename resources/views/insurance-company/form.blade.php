<div class="form-group has-float-label col-sm-12">
    {{ Form::text('name_ar', old('name_ar'), ['placeholder' => 'الإسم بالعربى', 'class' => 'form-control ' . ($errors->has('name_ar') ? 'redborder' : '') ]) }}
    <label for="name_ar">الإسم بالعربى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('name_ar') ? $errors->first('name_ar') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-12">
    {{ Form::text('name_en', old('name_en'), ['placeholder' => 'الإسم بالإنجليزى', 'class' => 'form-control ' . ($errors->has('name_en') ? 'redborder' : '') ]) }}
    <label for="name_en">الإسم بالإنجليزى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('name_en') ? $errors->first('name_en') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-12">
    {{ Form::select('status', [1 => 'مفعّل', 0 => 'غير مفعّل'], old('status'), ['placeholder' => 'التفعيل', 'class' => 'form-control ' . ($errors->has('status') ? 'redborder' : '') ]) }}
    <label for="status">التفعيل <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('status') ? $errors->first('status') : '' }}</small>
</div>

@if(isset($company))
    <div class="col-sm-2">
        <img src="@if(!empty($company->image)){{ asset($company->image) }}@else{{ asset('images/no_image.png') }}@endif" class="side_image">
    </div>
@endif

<div class="form-group has-float-label @if(isset($company)) col-sm-10 @else col-sm-12 @endif">
    {{ Form::file('image', ['placeholder' => 'الصورة', 'class' => 'form-control ' . ($errors->has('image') ? 'redborder' : '') ]) }}
    <label for="image">الصورة</label>
    <small class="text-danger">{{ $errors->has('image') ? $errors->first('image') : '' }}</small>
</div>

<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm' ]) }}
</div>
