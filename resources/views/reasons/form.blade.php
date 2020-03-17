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

<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm' ]) }}
</div>
