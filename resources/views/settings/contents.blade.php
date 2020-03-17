@extends('layouts.master')

@section('title', ' أعدادات عامة ')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
{!! Breadcrumbs::render('general') !!}
@stop
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1><i class="menu-icon fa fa-pencil"></i>  عام  </h1>
            </div>
        </div>
    </div>
    <div class="row">
    <div class="col-md-12">
        {{ Form::model($settings, ['route' => 'admin.settings.update', 'class' => 'form', 'method' => 'PUT', 'files' => true]) }}

        <div class="form-group has-float-label">
            <label for="approve_message_ar">  سعر النقطة بالريال     </label>
            {{ Form::number('point_price', old('point_price'), ['min' => 0 ,'placeholder' => '  ادخل سعر النقطه بالريال ',  'class' => 'form-control ' . ($errors->has('point_price') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('point_price') ? $errors->first('point_price') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            <label for="approve_message_ar"> نسبه اتعاب البنك لاودو    </label>
            {{ Form::number('bank_fees', old('bank_fees'), ['min' => 0 ,'placeholder' => '  أدخل قيمه الاتعاب البنكية  ',  'class' => 'form-control ' . ($errors->has('bank_fees') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('bank_fees') ? $errors->first('bank_fees') : '' }}</small>
        </div>


     {{--   <div class="form-group has-float-label">
            <label for="price_less"> اقل سعر فلتره الكوبون  </label>
            {{ Form::text('price_less', old('price_less'), [ 'placeholder' => '   سعر الفلتره الخاص بافلتره الكوبونات  ',  'class' => 'form-control ' . ($errors->has('price_less') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('price_less') ? $errors->first('price_less') : '' }}</small>
        </div>

        --}}

        <div class="form-group has-float-label">
            <label for="approve_message_ar"> رسالة مراجعة الحساب     </label>
            {{ Form::text('approve_message_ar', old('approve_message_ar'), ['placeholder' => '   نص رساله مراجعة حساب مزود الخدمة بالعربية ',  'class' => 'form-control ' . ($errors->has('approve_message_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('approve_message_ar') ? $errors->first('approve_message_ar') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            <label for="approve_message_en"> رسالة مراجعة الحساب بالانجليزية     </label>
            {{ Form::text('approve_message_en', old('approve_message_en'), ['placeholder' => '   نص رساله مراجعة حساب مزود الخدمة  بالانجليزية  ',  'class' => 'form-control ' . ($errors->has('approve_message_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('approve_message_en') ? $errors->first('approve_message_en') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            <label for="approve_message_en">  عنوان الموقع بالعربية     </label>
            {{ Form::text('title_ar', old('title_ar'), ['placeholder' => '   عنوان الموقع بالعربي  ',  'class' => 'form-control ' . ($errors->has('title_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('title_ar') ? $errors->first('title_ar') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            <label for="approve_message_en">  عنوان الموقع  بالانجليزي      </label>
            {{ Form::text('title_en', old('title_en'), ['placeholder' => '    عنوان الموقع بالانجليزي  ',   'class' => 'form-control ' . ($errors->has('title_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('title_en') ? $errors->first('title_en') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            <label for="approve_message_en">   رقم الجوال     </label>
            {{ Form::text('mobile', old('mobile'), ['placeholder' => '    رقم الجوال ',   'class' => 'form-control ' . ($errors->has('mobile') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('mobile') ? $errors->first('mobile') : '' }}</small>
        </div>


        <div class="form-group has-float-label">
            <label for="approve_message_en">  البريد الالكتروني    </label>
            {{ Form::text('email', old('email'), ['placeholder' => ' البريد الالكتروني ',   'class' => 'form-control ' . ($errors->has('email') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('email') ? $errors->first('email') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            <label for="approve_message_en">    مقر الشركة  بالعربية   </label>
            {{ Form::text('address_ar', old('address_ar'), ['placeholder' => ' مقر الشركة بالعربية  ',   'class' => 'form-control ' . ($errors->has('address_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('address_ar') ? $errors->first('address_ar') : '' }}</small>
        </div>


        <div class="form-group has-float-label">
            <label for="approve_message_en">    مقر الشركة  بالانجليزية   </label>
            {{ Form::text('address_en', old('address_en'), ['placeholder' => ' مقر الشركة بالعربية  ',   'class' => 'form-control ' . ($errors->has('address_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('address_en') ? $errors->first('address_en') : '' }}</small>
        </div>

        <div class="form-group">
            <label for="features">   الكلمات الدلالية بالعربية   </label>
            {{ Form::textarea('meta_keywords_ar', old('meta_keywords_ar'), ['placeholder' => ' الكلمات الدلالية    ', 'id' => 'features',   'class' => 'form-control ' . ($errors->has('meta_keywords_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('meta_keywords_ar') ? $errors->first('meta_keywords_ar') : '' }}</small>
        </div>


        <div class="form-group">
            <label for="features">   الكلمات الدلالية بالانجليزية    </label>
            {{ Form::textarea('meta_keywords_en', old('meta_keywords_en'), ['placeholder' => '  وصف الموقع لمحركات البحث    ', 'id' => 'meta_keywords_en',   'class' => 'form-control ' . ($errors->has('meta_keywords_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('meta_keywords_en') ? $errors->first('meta_keywords_en') : '' }}</small>
        </div>



        <div class="form-group">
            <label for="features">   وصف الموقع للبحث بالعربية   </label>
            {{ Form::textarea('meta_description_ar', old('meta_description_ar'), ['placeholder' => '  وصف الموقع لمحركات البحث     ', 'id' => 'meta_description_ar',   'class' => 'form-control ' . ($errors->has('meta_description_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('meta_description_ar') ? $errors->first('meta_description_ar') : '' }}</small>
        </div>


        <div class="form-group">
            <label for="features">   وصف الموقع للبحث بالانجليزية    </label>
            {{ Form::textarea('meta_description_en', old('meta_description_en'), ['placeholder' => ' وصف الموقع لمحركات البحث       ', 'id' => 'meta_description_en',   'class' => 'form-control ' . ($errors->has('meta_description_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('meta_description_en') ? $errors->first('meta_description_en') : '' }}</small>
        </div>

        <div class="form-group">
            <label for="features">  عن الطبيق بالعربية    </label>
            {{ Form::textarea('aboutApp_ar', old('aboutApp_ar'), ['placeholder' => 'عن الطبيق بالعربية    ', 'id' => 'aboutApp_ar',   'class' => 'form-control ' . ($errors->has('aboutApp_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('aboutApp_ar') ? $errors->first('aboutApp_ar') : '' }}</small>
        </div>

        <div class="form-group">
            <label for="features">  عن الطبيق بالانجليزية    </label>
            {{ Form::textarea('aboutApp_en', old('aboutApp_en'), ['placeholder' => 'عن الطبيق بالانجليزية    ', 'id' => 'aboutApp_en',   'class' => 'form-control ' . ($errors->has('aboutApp_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('aboutApp_en') ? $errors->first('aboutApp_en') : '' }}</small>
        </div>


        <div class="form-group">
            <label for="features">  نص  تحميل التطبيقات بالعربية   </label>
            {{ Form::textarea('app_text_ar', old('app_text_ar'), ['placeholder' => ' نص تحميل التطبيقات بالرئيسية    ', 'id' => 'app_text_ar',   'class' => 'form-control ' . ($errors->has('app_text_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('app_text_ar') ? $errors->first('app_text_ar') : '' }}</small>
        </div>

        <div class="form-group">
            <label for="features">  نص  تحميل التطبيقات بالانجليزية   </label>
            {{ Form::textarea('app_text_en', old('app_text_en'), ['placeholder' => 'نص تحميل التطبيقات بالرئيسية    ', 'id' => 'app_text_en',   'class' => 'form-control ' . ($errors->has('app_text_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('app_text_en') ? $errors->first('app_text_en') : '' }}</small>
        </div>

        <div class="form-group">
            <label for="features">  نص كيفية استخدام التطبيق 1 بالعربية   </label>
            {{ Form::textarea('use1_ar', old('use1_ar'), ['placeholder' => '  نص كيفية استخدام التطبيق 1 بالرئيسية    ', 'id' => 'use1_ar',   'class' => 'form-control ' . ($errors->has('use1_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('use1_ar') ? $errors->first('use1_ar') : '' }}</small>
        </div>

        <div class="form-group">
            <label for="features">   نص كيفية استخدام التطبيق 1 بالانجليزية   </label>
            {{ Form::textarea('use1_en', old('use1_en'), ['placeholder' => ' نص كيفية استخدام التطبيق 1 بالرئيسية    ', 'id' => 'use1_en',   'class' => 'form-control ' . ($errors->has('use1_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('use1_en') ? $errors->first('use1_en') : '' }}</small>
        </div>


        <div class="form-group">
            <label for="features">  نص كيفية استخدام التطبيق 2 بالعربية   </label>
            {{ Form::textarea('use2_ar', old('use2_ar'), ['placeholder' => '  نص كيفية استخدام التطبيق 2 بالرئيسية    ', 'id' => 'use2_ar',   'class' => 'form-control ' . ($errors->has('use2_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('use1_ar') ? $errors->first('use1_ar') : '' }}</small>
        </div>

        <div class="form-group">
            <label for="features">   نص كيفية استخدام التطبيق 2 بالانجليزية   </label>
            {{ Form::textarea('use2_en', old('use2_en'), ['placeholder' => ' نص كيفية استخدام التطبيق 2 بالرئيسية    ', 'id' => 'use2_en',   'class' => 'form-control ' . ($errors->has('use2_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('use2_en') ? $errors->first('use2_en') : '' }}</small>
        </div>




        <div class="form-group">
            <label for="features">  نص كيفية استخدام التطبيق 3 بالعربية   </label>
            {{ Form::textarea('use3_ar', old('use3_ar'), ['placeholder' => '  نص كيفية استخدام التطبيق 3 بالرئيسية    ', 'id' => 'use3_ar',   'class' => 'form-control ' . ($errors->has('use3_ar') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('use3_ar') ? $errors->first('use3_ar') : '' }}</small>
        </div>

        <div class="form-group">
            <label for="features">   نص كيفية استخدام التطبيق 3 بالانجليزية   </label>
            {{ Form::textarea('use3_en', old('use3_en'), ['placeholder' => ' نص كيفية استخدام التطبيق 3 بالرئيسية    ', 'id' => 'use3_en',   'class' => 'form-control ' . ($errors->has('use3_en') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('use3_en') ? $errors->first('use3_en') : '' }}</small>
        </div>


        <div class="form-group has-float-label">
            <label for="approve_message_en">  رابط الفيس بوك   </label>
            {{ Form::text('facebook', old('facebook'), ['placeholder' => ' ادخل رابط الفيس بوك  ',   'class' => 'form-control ' . ($errors->has('facebook') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('facebook') ? $errors->first('facebook') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            <label for="approve_message_en">  رابط  تويتر   </label>
            {{ Form::text('twitter', old('twitter'), ['placeholder' => ' ادخل رابط  تويتر ',   'class' => 'form-control ' . ($errors->has('twitter') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('twitter') ? $errors->first('twitter') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            <label for="approve_message_en">  رابط  الانستجرام   </label>
            {{ Form::text('instg', old('instg'), ['placeholder' => ' ادخل رابط الانستجرام  ',   'class' => 'form-control ' . ($errors->has('instg') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('instg') ? $errors->first('instg') : '' }}</small>
        </div>


        <div class="form-group has-float-label">
            <label for="approve_message_en">  رابط لينكد ان   </label>
            {{ Form::text('linkedIn', old('linkedIn'), ['placeholder' => ' ادخل رابط  لينكد ان  ',   'class' => 'form-control ' . ($errors->has('linkedIn') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('linkedIn') ? $errors->first('linkedIn') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            <label for="approve_message_en">     وتس اب    </label>
            {{ Form::text('whatsApp', old('whatsApp'), ['placeholder' => '  وتس اب   ',   'class' => 'form-control ' . ($errors->has('whatsApp') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('whatsApp') ? $errors->first('whatsApp') : '' }}</small>
        </div>


        <div class="form-group has-float-label">
            <label for="approve_message_en">    جوجل بلاي   </label>
            {{ Form::text('google_play', old('google_play'), ['placeholder' => '  جوجل بلاي  ',   'class' => 'form-control ' . ($errors->has('google_play') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('google_play') ? $errors->first('google_play') : '' }}</small>
        </div>


        <div class="form-group has-float-label">
            <label for="approve_message_en">    اب استور   </label>
            {{ Form::text('app_store', old('app_store'), ['placeholder' => '  اب استور   ',   'class' => 'form-control ' . ($errors->has('app_store') ? 'redborder' : '') ]) }}
            <small class="text-danger">{{ $errors->has('app_store') ? $errors->first('app_store') : '' }}</small>
        </div>



        <div class="form-group has-float-label">
            {{ Form::file('home_image1', ['placeholder' => 'الصورة', 'class' => 'form-control ' . ($errors->has('home_image1') ? 'redborder' : '') ]) }}
            <label for="homeImage1"> صوره الرئيسية 1</label>
            <small class="text-danger">{{ $errors->has('home_image1') ? $errors->first('home_image1') : '' }}</small>
        </div>

        <div class="form-group has-float-label">
            {{ Form::file('home_image2', ['placeholder' => 'الصورة', 'class' => 'form-control ' . ($errors->has('home_image2') ? 'redborder' : '') ]) }}
            <label for="image"> صورة الرئيسية 2</label>
            <small class="text-danger">{{ $errors->has('home_image2') ? $errors->first('home_image2') : '' }}</small>
        </div>
    </div>
    </div>
    <div class="row">
        @if(  $settings -> home_image1 != "" )
        <div class="col-md-6 center">
            <div>
                <div class="profile-picture">
                    <img id="avatar" style="max-height: 300px;" class="editable img-responsive" alt="Icon URL" src="{{$settings -> home_image1}}">
                </div>
            </div>
            <div class="space-10"></div>
        </div>
        @endif



        @if(  $settings -> home_image2 != "" )
        <div class="col-md-6 center">
            <div>
                <div class="profile-picture">
                    <img id="avatar"  style="max-height: 300px;" class="editable img-responsive" alt="Icon URL" src="{{$settings -> home_image2}}">
                </div>
            </div>
            <div class="space-10"></div>
        </div>
        @endif
    </div>

    <div class="form-group col-sm-12 submit">
        {{ Form::submit('حفظ', ['class' => 'btn btn-sm']) }}
    </div>
    {{ Form::close() }}
</div>
@stop
