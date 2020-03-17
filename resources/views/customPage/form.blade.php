<div class="form-group has-float-label col-sm-6">
    {{ Form::text('title_ar', old('title_ar'), ['placeholder' => 'العنوان بالعربى', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('title_ar') ? 'redborder' : '') ]) }}
    <label for="title_ar">العنوان بالعربى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('title_ar') ? $errors->first('title_ar') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('title_en', old('title_en'), ['placeholder' => 'العنوان بالإنجليزى', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('title_en') ? 'redborder' : '') ]) }}
    <label for="title_en">العنوان بالإنجليزى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('title_en') ? $errors->first('title_en') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('user', [1 => 'نعم', 0 => 'لا'], old('user'), ['placeholder' => 'خاص بالمستخدم', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('user') ? 'redborder' : '') ]) }}
    <label for="user">خاص بالمستخدم <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('user') ? $errors->first('user') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('provider', [1 => 'نعم', 0 => 'لا'], old('provider'), ['placeholder' => 'خاص بمقدم الخدمة', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('provider') ? 'redborder' : '') ]) }}
    <label for="provider">خاص بمقدم الخدمة <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('provider') ? $errors->first('provider') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-12">
    {{ Form::select('status', [1 => 'مفعّل', 0 => 'غير مفعّل'], old('status'), ['placeholder' => 'الحالة', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('status') ? 'redborder' : '') ]) }}
    <label for="status">الحالة <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('status') ? $errors->first('status') : '' }}</small>
</div>

<div class="form-group col-sm-12">
    <label for="content_ar">المحتوى بالعربية <span class="astric">*</span></label>
    {{ Form::textarea('content_ar', old('content_ar'), ['placeholder' => 'المحتوى بالعربية', 'id' => 'content_ar', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('content_ar') ? 'redborder' : '') ]) }}
    <small class="text-danger">{{ $errors->has('content_ar') ? $errors->first('content_ar') : '' }}</small>
</div>

<div class="form-group col-sm-12">
    <label for="content_en">المحتوى بالإنجليزية <span class="astric">*</span></label>
    {{ Form::textarea('content_en', old('content_en'), ['placeholder' => 'المحتوى بالإنجليزية', 'id' => 'content_en', 'required' => 'required', 'class' => 'form-control ' . ($errors->has('content_en') ? 'redborder' : '') ]) }}
    <small class="text-danger">{{ $errors->has('content_en') ? $errors->first('content_en') : '' }}</small>
</div>

<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm' ]) }}
</div>