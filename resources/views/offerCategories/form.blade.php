@if(Request::is('mc33/offerCategories/edit/*'))
    @if($category && $category -> photo != "" )
        <div class="col-xs-12 col-sm-3 center">
            <div>
                <div class="profile-picture">
                    <img id="avatar" class="editable img-responsive" alt="Icon URL" src="{{$category -> photo}}">
                </div>
            </div>
            <div class="space-10"></div>
        </div>
    @endif
@endif

<br>
<div class="form-group has-float-label col-sm-6">
    {{ Form::file('photo', ['class' => 'form-control ' . ($errors->has('photo') ? 'redborder' : '') ]) }}
    <label for="title">صوره القسم  <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('photo') ? $errors->first('photo') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::select('parent_id', $parentCategories, (isset($category)) ? $category->parent_id : old('parent_id'), ['placeholder' => '--- اختر القسم الرئيسى ---',  'class' => 'form-control ' . ($errors->has('parent_id') ? 'redborder' : '') ]) }}
    <label for="parent_id">القسم الرئيسى</label>
    <small class="text-danger">{{ $errors->has('parent_id') ? $errors->first('parent_id') : '' }}</small>
</div>

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
