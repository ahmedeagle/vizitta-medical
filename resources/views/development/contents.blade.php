@extends('layouts.master')

@section('title', '  الشركة المطورة    ')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('general') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> عن الشركة المطورة </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::model($settings, ['route' => 'admin.development.update', 'class' => 'form', 'method' => 'PUT', 'files' => true]) }}
        <br>

        <div class="form-group  col-sm-6">

            <label for="homeImage1"> شعار الشركة المطورة </label>
            {{ Form::file('dev_company_logo', ['placeholder' => 'الصورة', 'class' => 'form-control ' . ($errors->has('dev_company_logo') ? 'redborder' : '') ]) }}
            <small
                class="text-danger">{{ $errors->has('dev_company_logo') ? $errors->first('dev_company_logo') : '' }}</small>
        </div>
        <div class="form-group col-sm-12">
            <label for="features"> عن الشركة المطورة بالعربية </label>
            {{ Form::textarea('dev_company_ar', old('dev_company_ar'), ['placeholder' => 'عن  الشركة المطوره  بالعربية    ', 'id' => 'dev_company_ar',   'class' => 'form-control ' . ($errors->has('dev_company_ar') ? 'redborder' : '') ]) }}
            <small
                class="text-danger">{{ $errors->has('dev_company_ar') ? $errors->first('dev_company_ar') : '' }}</small>
        </div>

        <div class="form-group col-sm-12">
            <label for="features"> عن الشركة المطورة بالانجليزية </label>
            {{ Form::textarea('dev_company_en', old('dev_company_en'), ['placeholder' => 'عن  الشركة المطوره  بالانجليزية    ', 'id' => 'dev_company_en',   'class' => 'form-control ' . ($errors->has('dev_company_en') ? 'redborder' : '') ]) }}
            <small
                class="text-danger">{{ $errors->has('dev_company_en') ? $errors->first('dev_company_en') : '' }}</small>
        </div>

        <div class="form-group col-sm-12">
            <label for="features"> رابط الشركة المطورة </label>
            {{ Form::text('dev_company_link', old('dev_company_link'), ['placeholder' => 'رابط الشركة المطورة ',    'class' => 'form-control ' . ($errors->has('dev_company_link') ? 'redborder' : '') ]) }}
            <small
                class="text-danger">{{ $errors->has('dev_company_link') ? $errors->first('dev_company_link') : '' }}</small>
        </div>


        @if(  $settings -> dev_company_logo != "" )
            <div class="px-4 col-md-6 center">
                <div>
                    <div class="profile-picture">
                        <img id="avatar" style="max-height: 300px;" class="editable img-responsive" alt="Icon URL"
                             src="{{$settings -> dev_company_logo}}">
                    </div>
                </div>
                <div class="space-10"></div>
            </div>
        @endif

        <div class="form-group col-sm-12 submit">
            {{ Form::submit('حفظ', ['class' => 'btn btn-sm']) }}
        </div>
        {{ Form::close() }}
    </div>
</div>
@stop


@section('extra_scripts')
    <script>
        $(document).ready(function () {
            $("textarea").each(function () {
                var editor = CKEDITOR.replace($(this).attr('id'), {
                    language: 'ar',
                });
            });
        });
    </script>
@stop
