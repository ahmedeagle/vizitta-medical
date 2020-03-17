<div class="form-group has-float-label col-sm-12">
    <select id="operation" name="operation" class='js-example-basic-multiple form-control '
        {{$errors->has('operation') ? 'redborder' : ''}}>
        <optgroup label="أختر عملية الفلتره ">
            <option value="0" @if(isset($filter) && $filter -> operation == 0) selected @endif> أقل من</option>
            <option value="1" @if(isset($filter) && $filter -> operation == 1) selected @endif> أكبر من</option>
            <option value="2" @if(isset($filter) && $filter -> operation == 2) selected @endif> يساوي</option>
            <option value="3" @if(isset($filter) && $filter -> operation == 3) selected @endif> الاكثر مبيعا</option>
            <option value="4" @if(isset($filter) && $filter -> operation == 4) selected @endif> الاكثر زياره</option>
            <option value="5" @if(isset($filter) && $filter -> operation == 5) selected @endif> الاحدث</option>
        </optgroup>
    </select>
    <label for="operation"> العملية </label>
    <small class="text-danger">{{ $errors->has('operation') ? $errors->first('operation') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::text('title_ar', old('title_ar'), ['placeholder' => ' العنوان بالعربي ', 'class' => 'form-control ' . ($errors->has('title_ar') ? 'redborder' : '') ]) }}
    <label for="title_ar"> عنوان الفلتر بالعربي </label>
    <small class="text-danger">{{ $errors->has('title_ar') ? $errors->first('title_ar') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('title_en', old('title_en'), ['placeholder' => ' العنوان بالانجليزي ', 'class' => 'form-control ' . ($errors->has('title_en') ? 'redborder' : '') ]) }}
    <label for="title_ar"> عنوان الفلتر بالانجليزي </label>
    <small class="text-danger">{{ $errors->has('title_en') ? $errors->first('title_en') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6" id= 'price'  style="display: none;">
    {{ Form::text('price', old('price'), ['placeholder' => 'السعر  ',  'class' => 'form-control ' . ($errors->has('price') ? 'redborder' : '') ]) }}
    <label for="price "> السعر <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('price') ? $errors->first('price') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::select('status', [1 => 'مفعّل', 0 => 'غير مفعّل'], old('status'), ['placeholder' => 'التفعيل',  'class' => 'form-control ' . ($errors->has('status') ? 'redborder' : '') ]) }}
    <label for="status">التفعيل <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('status') ? $errors->first('status') : '' }}</small>
</div>


<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm' ]) }}
</div>
