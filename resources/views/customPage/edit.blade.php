@extends('layouts.master')

@section('title', 'تعديل الصفحة الفرعية')

@section('styles')
    {!! Html::style('css/form.css') !!}
@stop

@section('content')
@section('breadcrumbs')
    {!! Breadcrumbs::render('edit.customPage') !!}
@stop

<div class="page-content">
    <div class="col-md-12">
        <div class="page-header">
            <h1><i class="menu-icon fa fa-pencil"></i> تعديل الصفحة الفرعية </h1>
        </div>
    </div>

    <div class="col-md-12">
        {{ Form::model($customPage, ['route' => ['admin.customPage.update' , $customPage->id], 'class' => 'form', 'method' => 'PUT']) }}
            @include('customPage.form', ['btn' => 'حفظ', 'classes' => 'btn btn-primary'])
        {{ Form::close() }}
    </div>
</div>
@stop

@section('popup')
    <p>من فضلك إدخل جميع الحقول المطلوبة</p>
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $("textarea").each(function() {
                var editor = CKEDITOR.replace($(this).attr('id'), {
                    language: 'ar',
                }).on('required', function( evt ) {
                    $('.hover_popup').show();
                    evt.cancel();
                });
            });
        });
    </script>
@stop