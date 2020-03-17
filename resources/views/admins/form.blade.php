<div class="form-group has-float-label col-sm-6">
    {{ Form::text('name_ar', old('name_ar'), ['placeholder' => 'الإسم بالعربى',  'class' => 'form-control ' . ($errors->has('name_ar') ? 'redborder' : '') ]) }}
    <label for="name_ar">الإسم بالعربى <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('name_ar') ? $errors->first('name_ar') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::text('name_en', old('name_en'), ['placeholder' => 'الإسم بالانجليزية ',  'class' => 'form-control ' . ($errors->has('name_en') ? 'redborder' : '') ]) }}
    <label for="name_ar">الإسم بالانجليزية  <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('name_en') ? $errors->first('name_en') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::text('mobile', old('mobile'), ['placeholder' => 'رقم الجوال ',  'class' => 'form-control ' . ($errors->has('mobile') ? 'redborder' : '') ]) }}
    <label for="mobile"> رقم الجوال    <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('mobile') ? $errors->first('mobile') : '' }}</small>
</div>

<div class="form-group has-float-label col-sm-6">
    {{ Form::email('email', old('email'), ['placeholder' => ' البريد الالكتروني ', 'class' => 'form-control ' . ($errors->has('email') ? 'redborder' : '') ]) }}
    <label for="information_en">البريد الالكتروني   </label>
    <small class="text-danger">{{ $errors->has('email') ? $errors->first('email') : '' }}</small>
</div>


<div class="form-group has-float-label col-sm-6">
    {{ Form::password('password', ['id' => 'permission_password','class' => 'form-control ' . ($errors->has('password') ? 'redborder' : '') ]) }}
    <label for="name_en"> كلمه المرور <span class="astric">*</span></label>
    <small class="text-danger">{{ $errors->has('password') ? $errors->first('password') : '' }}</small>
</div>




<div class="form-group has-float-label col-sm-12" style="overflow-x:auto;">

    <table id='permissions' class="table table-striped table-bordered" >
        <thead>
        <tr>
            <th>القسم </th>
            <th>عرض</th>
            <th>تعديل</th>
            <th>حذف</th>
            <th>اضافة</th>
         </tr>
        </thead>
        <tbody>
                       @component('admins.permissions' ,['permissions' => @$admin -> permissions])
                           @endcomponent
        </tbody>
    </table>

</div>
<div class="form-group col-sm-12 submit">
    {{ Form::submit($btn, ['class' => 'btn btn-sm' ]) }}
</div>
